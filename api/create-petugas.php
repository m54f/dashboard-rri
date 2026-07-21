<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/notion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

 $input = json_decode(file_get_contents('php://input'), true);
 $notionToken = NOTION_TOKEN;
 $databaseId  = DATABASE_ID_PETUGAS; // Sesuaikan dengan variabel config Anda

if (empty($input['name']) || empty($input['role']) || empty($input['posisi'])) {
    echo json_encode(['success' => false, 'error' => 'Nama, Jabatan, dan Posisi wajib diisi.']);
    exit;
}

 $body = [
    'parent' => ['database_id' => $databaseId],
    'properties' => [
        'Name' => ['title' => [['text' => ['content' => $input['name']]]]],
        'JABATAN' => ['select' => ['name' => $input['role']]],
        'Posisi' => ['select' => ['name' => $input['posisi']]],
        'PANGKAT/GOL.RUANG' => ['select' => ['name' => $input['pangkat'] ?? '-']],
        'NIP/NIPPK/NRP' => ['rich_text' => [['text' => ['content' => $input['nip'] ?? '']]]]
    ]
];

 $ch = curl_init("https://api.notion.com/v1/pages");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

 $response = curl_exec($ch);
 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $error = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $error['message'] ?? "Gagal menyimpan"]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Data petugas berhasil ditambahkan!']);
?>