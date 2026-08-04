<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/api-auth.php';
requireAdmin();

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/notion-file-helpers.php'; // Pastikan file ini ada!

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$nama = $input['nama_kegiatan'] ?? '';
$tanggal = $input['tanggal'] ?? date('Y-m-d\TH:i:s');
$jenis = $input['jenis_kegiatan'] ?? [];
$petugas = $input['petugas'] ?? [];
$eviden_3 = $input['eviden_3_link'] ?? '';
$fileIds = $input['dokumentasi_file_ids'] ?? [];

$properties = [
    'Nama Kegiatan' => ['title' => [['text' => ['content' => $nama]]]],
    'Date' => ['date' => ['start' => date('c', strtotime($tanggal))]]
];

if (!empty($jenis)) {
    $properties['Jenis Kegiatan'] = ['multi_select' => array_map(fn($i) => ['name' => $i], $jenis)];
}
if (!empty($petugas)) {
    $properties['Petugas'] = ['multi_select' => array_map(fn($i) => ['name' => $i], $petugas)];
}
if (!empty($eviden_3)) {
    $properties['Eviden 3 (Link)'] = ['url' => $eviden_3];
}

// Format file dokumentasi untuk Notion
// PENTING: type harus "file_upload" (bukan "file") untuk melampirkan file
// yang sudah diupload lewat Notion File Upload API.
$filesPayload = [];
if (!empty($fileIds)) {
    foreach ($fileIds as $f) {
        $filesPayload[] = ["type" => "file_upload", "file_upload" => ["id" => $f['id']]];
    }
    $properties['Eviden'] = ['files' => $filesPayload];
}

$payload = [
    'parent' => ['database_id' => DATABASE_ID_KEGIATAN],
    'properties' => $properties
];

$ch = curl_init('https://api.notion.com/v1/pages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN, 
    "Content-Type: application/json", 
    "Notion-Version: " . NOTION_VERSION
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

require_once __DIR__ . '/jurnal.php';

if ($http_code === 200) {
    clearJurnalCache();
    echo json_encode(['success' => true, 'message' => 'Jurnal berhasil diperbarui!']);
} else {
    $err = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $err['message'] ?? 'Gagal menyimpan ke Notion']);
}