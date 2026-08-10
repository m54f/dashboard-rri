<?php
/**
 * API Endpoint: Update Jurnal Kegiatan di Notion
 * 
 * Menerima POST request JSON dengan field:
 *   - page_id               (string, required) : ID page Notion yang mau diedit
 *   - nama_kegiatan         (string, required) : Judul kegiatan
 *   - tanggal               (string, required) : Waktu kegiatan
 *   - jenis_kegiatan        (array, optional)  : Jenis/Kategori kegiatan
 *   - petugas               (array, optional)  : Nama petugas
 *   - eviden_3_link         (string, optional) : Link Eviden Tambahan
 *   - dokumentasi_file_ids  (array, optional)  : Foto baru yang diupload [{id, name}]
 *   - dokumentasi_keep      (array, optional)  : Foto lama yang dipertahankan [{name, url}]
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/api-auth.php';
requireAdmin();

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/notion-file-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$pageId             = trim($input['page_id'] ?? '');
$nama               = trim($input['nama_kegiatan'] ?? '');
$tanggal            = $input['tanggal'] ?? date('Y-m-d\TH:i:s');
$jenis              = $input['jenis_kegiatan'] ?? [];
$petugas            = $input['petugas'] ?? [];
$eviden_3           = trim($input['eviden_3_link'] ?? '');
$dokumentasiFileIds = $input['dokumentasi_file_ids'] ?? []; // Foto BARU: [{id, name}]
$dokumentasiKeep    = $input['dokumentasi_keep'] ?? [];     // Foto LAMA yang dipertahankan: [{name, url}]

if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "page_id" wajib diisi.']);
    exit;
}

if (empty($nama)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "nama_kegiatan" wajib diisi.']);
    exit;
}

// ===== Bangun Payload Properties Notion API =====
$properties = [
    'Nama Kegiatan' => ['title' => [['text' => ['content' => $nama]]]],
    'Date'          => ['date' => ['start' => date('c', strtotime($tanggal))]],
];

if (!empty($jenis)) {
    $properties['Jenis Kegiatan'] = ['multi_select' => array_map(fn($i) => ['name' => $i], $jenis)];
} else {
    $properties['Jenis Kegiatan'] = ['multi_select' => []];
}

if (!empty($petugas)) {
    $properties['Petugas'] = ['multi_select' => array_map(fn($i) => ['name' => $i], $petugas)];
} else {
    $properties['Petugas'] = ['multi_select' => []];
}

$properties['Eviden 3 (Link)'] = ['url' => !empty($eviden_3) ? $eviden_3 : null];

// ===== Olah Foto Dokumentasi (Files) =====
// Gabungkan foto lama yang dipertahankan + foto baru yang di-upload
if (isset($input['dokumentasi_file_ids']) || isset($input['dokumentasi_keep'])) {
    $filesPayload = [];

    // 1) Foto lama yang dipertahankan -> re-upload sebagai file_upload permanen di Notion
    foreach ($dokumentasiKeep as $doc) {
        if (empty($doc['url'])) continue;
        try {
            $id = notionReuploadFromUrl($doc['url'], $doc['name'] ?? 'dokumentasi.jpg');
            $filesPayload[] = [
                "type"        => "file_upload",
                "file_upload" => ["id" => $id],
                "name"        => $doc['name'] ?? 'dokumentasi.jpg',
            ];
        } catch (Throwable $e) {
            error_log('Gagal reupload foto jurnal lama: ' . $e->getMessage());
        }
    }

    // 2) Foto baru yang di-upload pengguna
    foreach ($dokumentasiFileIds as $doc) {
        if (empty($doc['id'])) continue;
        $filesPayload[] = [
            "type"        => "file_upload",
            "file_upload" => ["id" => $doc['id']],
            "name"        => $doc['name'] ?? 'dokumentasi.jpg',
        ];
    }

    $properties['Eviden'] = ['files' => $filesPayload];
}

// ===== Kirim PATCH ke Notion API =====
$url = "https://api.notion.com/v1/pages/" . $pageId;
$payload = ['properties' => $properties];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN, 
    "Content-Type: application/json", 
    "Notion-Version: " . NOTION_VERSION
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

require_once __DIR__ . '/jurnal.php';

if ($http_code === 200) {
    clearJurnalCache();
    echo json_encode(['success' => true, 'message' => 'Jurnal berhasil diperbarui!']);
} else {
    $err = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $err['message'] ?? "Gagal memperbarui ke Notion (HTTP {$http_code})"]);
}