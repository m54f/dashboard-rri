<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/api-auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body.']);
    exit;
}

$pageId        = trim($input['page_id'] ?? '');
$checklistType = trim($input['checklist'] ?? '');
$tanggal       = trim($input['tanggal'] ?? '');
$pro1          = !empty($input['pro1']);
$pro2          = !empty($input['pro2']);
$pro4          = !empty($input['pro4']);
$tetrack       = !empty($input['tetrack']);
$mcr           = !empty($input['mcr']);
$petugas       = $input['petugas'] ?? [];
$keterangan    = trim($input['keterangan'] ?? '');

if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "page_id" wajib diisi.']);
    exit;
}
if (empty($checklistType) || !in_array($checklistType, ['Pagi', 'Malam'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "checklist" wajib "Pagi" atau "Malam".']);
    exit;
}
if (empty($tanggal)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "tanggal" wajib diisi.']);
    exit;
}
if (!is_array($petugas) || count($petugas) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Pilih minimal satu "Petugas DP".']);
    exit;
}

$validPetugas = ['Alfian','Alif','Saiful','Winsa','Yayang','Yhanuar','Yuda'];
foreach ($petugas as $nama) {
    if (!in_array($nama, $validPetugas)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nama petugas tidak valid: ' . $nama]);
        exit;
    }
}

$url = "https://api.notion.com/v1/pages/" . $pageId;
$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
];

$petugasPayload = array_map(fn($n) => ["name" => $n], $petugas);

$properties = [
   // Di update API, Title tidak wajib dikirim, tapi tidak apa-apa dikirim
   "Question 1" => [
       "title" => [
           ["text" => ["content" => "Checklist {$checklistType} - {$tanggal}"]]
       ]
   ],
   "Checklist"   => ["multi_select" => [["name" => $checklistType]]],
   "Tanggal Cek" => ["date" => ["start" => $tanggal]],
   "Pro 1"       => ["checkbox" => $pro1],
   "Pro 2"       => ["checkbox" => $pro2],
   "Pro 4"       => ["checkbox" => $pro4],
   "Checkbox"    => ["checkbox" => $tetrack],
   "MCR Depan"   => ["checkbox" => $mcr],
   "Petugas DP"  => ["multi_select" => $petugasPayload],
   "Keterangan Checklist" => [
       "rich_text" => !empty($keterangan) ? [["text" => ["content" => $keterangan]]] : []
   ],
];

// JANGAN masukkan "Submission time" dan "Respondent" ke $properties

$payload = ["properties" => $properties];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . $error]);
    exit;
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$responseData = json_decode($response, true);

if ($httpCode === 200) {
    echo json_encode([
        'success' => true,
        'message' => 'Checklist berhasil diperbarui!',
        'notion_page_id' => $responseData['id'] ?? null,
        'notion_url' => $responseData['url'] ?? null
    ]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}