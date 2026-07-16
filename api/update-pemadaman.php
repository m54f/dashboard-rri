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

if (empty($input['page_id']) || empty($input['pemancar']) || empty($input['tanggal'])) {
    echo json_encode(['success' => false, 'error' => 'Data tidak valid.']);
    exit;
}

$startDate = $input['tanggal'] . 'T' . $input['start_time'] . ':00+07:00';
$endDate   = $input['tanggal'] . 'T' . $input['end_time'] . ':00+07:00';

$pemancarSelect = [['name' => $input['pemancar']]]; // <-- PERUBAHAN DI SINI
$petugasSelect  = array_map(fn($nama) => ['name' => trim($nama)], $input['petugas']);

$body = [
    'properties' => [
        'Pemancar' => ['multi_select' => $pemancarSelect],
        'Date' => [
            'date' => [
                'start' => $startDate,
                'end'   => $endDate
            ]
        ],
        'Petugas' => ['multi_select' => $petugasSelect]
    ]
];

$pageId = $input['page_id'];
$url = "https://api.notion.com/v1/pages/{$pageId}";
$headers = [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $error = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $error['message'] ?? "Gagal memperbarui (HTTP {$httpCode})"]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui!']);
?>