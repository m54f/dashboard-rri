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
$databaseId  = DATABASE_ID_PADAM_PLN;

if (empty($input['pemancar']) || empty($input['tanggal']) || empty($input['start_time']) || empty($input['end_time']) || empty($input['petugas'])) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap.']);
    exit;
}

$startDate = $input['tanggal'] . 'T' . $input['start_time'] . ':00+07:00';
$endDate   = $input['tanggal'] . 'T' . $input['end_time'] . ':00+07:00';

// Format sebagai multi_select (array of objects)
$pemancarSelect = [['name' => $input['pemancar']]];
$petugasSelect  = array_map(fn($nama) => ['name' => trim($nama)], $input['petugas']);

$body = [
    'parent' => ['database_id' => $databaseId],
    'properties' => [
        'Pemancar' => ['multi_select' => $pemancarSelect], // <-- PERUBAHAN DI SINI
        'Date' => [
            'date' => [
                'start' => $startDate,
                'end'   => $endDate
            ]
        ],
        'Petugas' => ['multi_select' => $petugasSelect]
    ]
];

$url = "https://api.notion.com/v1/pages";
$headers = [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $error = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $error['message'] ?? "Gagal menyimpan (HTTP {$httpCode})"]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Data pemadaman berhasil dicatat!']);
?>