<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/../includes/api-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

 $input = json_decode(file_get_contents('php://input'), true);
 $notionToken = NOTION_TOKEN;
 $databaseId  = DATABASE_ID_PADAM_PLN;

// Validasi data utama
if (empty($input['pemancar']) || empty($input['tanggal']) || empty($input['start_time']) || empty($input['end_time'])) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap (Tanggal/Waktu/Pemancar wajib diisi).']);
    exit;
}

$startTime = !empty($input['start_time']) ? trim($input['start_time']) : '00:00';
$endTime   = !empty($input['end_time'])   ? trim($input['end_time'])   : $startTime;

if (strlen($startTime) === 5) $startTime .= ':00';
if (strlen($endTime) === 5) $endTime .= ':00';

$startDate = $input['tanggal'] . 'T' . $startTime . '+07:00';
$endDate   = $input['tanggal'] . 'T' . $endTime . '+07:00';

$pemancarSelect = [['name' => $input['pemancar']]];

$petugasInput = $input['petugas'] ?? [];
if (!is_array($petugasInput)) $petugasInput = [];
$petugasSelect = array_map(fn($nama) => ['name' => trim($nama)], $petugasInput);

$createdBy = getApiAdminName() ?? 'Open Mode';

$datePayload = ['start' => $startDate];
if ($endDate > $startDate) {
    $datePayload['end'] = $endDate;
}

$body = [
    'parent' => ['database_id' => $databaseId],
    'properties' => [
        'Pemancar' => ['multi_select' => $pemancarSelect],
        'Date' => [
            'date' => $datePayload
        ],
        'Petugas' => ['multi_select' => $petugasSelect]
    ]
];

// Add Created By tracking (optional - uncomment after adding property to Notion)
// $body['properties']['Created By'] = ['rich_text' => [['text' => ['content' => $createdBy]]]];

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

echo json_encode(['success' => true, 'message' => 'Data aktivitas berhasil dicatat!']);
?>