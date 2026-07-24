<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/notion-file-helpers.php'; // Panggil helper ini!

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['page_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid payload atau page_id hilang']);
    exit;
}

$pageId = $input['page_id'];
$nama = $input['nama_kegiatan'] ?? '';
$tanggal = $input['tanggal'] ?? date('Y-m-d\TH:i:s');
$jenis = $input['jenis_kegiatan'] ?? [];
$petugas = $input['petugas'] ?? [];
$eviden_3 = $input['eviden_3_link'] ?? '';
$fileIdsBaru = $input['dokumentasi_file_ids'] ?? [];
$keepDocs = $input['dokumentasi_keep'] ?? []; // Array berisi ['name' => '...', 'url' => '...']

$properties = [
    'Nama Kegiatan' => ['title' => [['text' => ['content' => $nama]]]],
    'Date' => ['date' => ['start' => date('c', strtotime($tanggal))]]
];

if (!empty($jenis)) $properties['Jenis Kegiatan'] = ['multi_select' => array_map(fn($i) => ['name' => $i], $jenis)];
if (!empty($petugas)) $properties['Petugas'] = ['multi_select' => array_map(fn($i) => ['name' => $i], $petugas)];
if (!empty($eviden_3)) $properties['Eviden 3 (Link)'] = ['url' => $eviden_3];

// ===== PROSES DOKUMENTASI (BARU + LAMA) =====
$filesPayload = [];

// 1. Tambahkan file yang BARU diupload
if (!empty($fileIdsBaru)) {
    foreach ($fileIdsBaru as $f) {
        $filesPayload[] = ["type" => "file_upload", "file_upload" => ["id" => $f['id']]];
    }
}

// 2. Tambahkan file LAMA yang dipertahankan (Re-upload agar tidak expired)
if (!empty($keepDocs)) {
    foreach ($keepDocs as $doc) {
        try {
            // Panggil fungsi dari notion-file-helpers.php
            $newFileId = notionReuploadFromUrl($doc['url'], $doc['name']);
            $filesPayload[] = ["type" => "file_upload", "file_upload" => ["id" => $newFileId]];
        } catch (Exception $e) {
            // Jika URL lama sudah expired dan gagal di-download, kita bisa skip atau return error
            error_log("Gagal re-upload file lama: " . $e->getMessage());
            // Opsional: echo json_encode(['success' => false, 'error' => 'Gagal memproses foto lama: ' . $e->getMessage()]); exit;
        }
    }
}

// Masukkan ke properti jika ada file
if (!empty($filesPayload)) {
    $properties['Eviden'] = ['files' => $filesPayload];
} else {
    // Jika user menghapus SEMUA foto, kosongkan properti files
    $properties['Eviden'] = ['files' => []];
}

// ===== UPDATE KE NOTION =====
$payload = ['properties' => $properties];
$url = "https://api.notion.com/v1/pages/" . $pageId;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); // Gunakan PATCH untuk update
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN, 
    "Content-Type: application/json", 
    "Notion-Version: " . NOTION_VERSION
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo json_encode(['success' => true, 'message' => 'Jurnal berhasil diperbarui!']);
} else {
    $err = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $err['message'] ?? 'Gagal memperbarui data di Notion']);
}
?>