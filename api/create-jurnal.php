<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/notion.php';

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

 $nama      = $input['nama_kegiatan'] ?? '';
 $tanggal   = $input['tanggal'] ?? date('c');
 $jenis     = $input['jenis_kegiatan'] ?? [];
 $petugas   = $input['petugas'] ?? [];
 $eviden_3  = $input['eviden_3_link'] ?? '';
 $dokumentasiFileIds = $input['dokumentasi_file_ids'] ?? []; // Berisi array: [{id: "xxx", name: "file.jpg"}]

 $properties = [
    'Nama Kegiatan' => ['title' => [['text' => ['content' => $nama]]]],
    'Date'          => ['date' => ['start' => date('c', strtotime($tanggal))]]
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

// === GUNAKAN FILE UPLOAD ID SESUAI KODE ANDA ===
if (!empty($dokumentasiFileIds) && is_array($dokumentasiFileIds)) {
    $filesPayload = [];
    foreach ($dokumentasiFileIds as $doc) {
        if (!empty($doc['id'])) {
            $filesPayload[] = [
                "type" => "file_upload",
                "file_upload" => ["id" => $doc['id']],
                "name" => $doc['name'] ?? 'dokumentasi.jpg',
            ];
        }
    }
    if (!empty($filesPayload)) {
        // Pastikan nama property "Eviden" sesuai dengan database Notion Anda
        $properties['Eviden'] = ['files' => $filesPayload];
    }
}

 $payload = [
    'parent'     => ['database_id' => DATABASE_ID_KEGIATAN],
    'properties' => $properties
];

// ===== Kirim ke Notion API =====
 $ch = curl_init('https://api.notion.com/v1/pages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Content-Type: application/json",
    "Notion-Version: 2022-06-28"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

 $response = curl_exec($ch);
 $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo json_encode(['success' => true, 'message' => 'Jurnal berhasil ditambahkan!']);
} else {
    $err = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $err['message'] ?? 'Gagal menyimpan ke Notion']);
}