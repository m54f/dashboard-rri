<?php
/**
 * API Endpoint: Tandai Peminjaman sebagai "Dikembalikan"
 * Body JSON: { page_id }
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';

$input = json_decode(file_get_contents('php://input'), true);
$pageId = trim($input['page_id'] ?? '');

if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "page_id" wajib diisi.']);
    exit;
}

$payload = [
    "properties" => [
        "Status" => ["select" => ["name" => "Dikembalikan"]],
        "Tanggal Kembali" => ["date" => ["start" => date('Y-m-d')]],
    ]
];

$ch = curl_init("https://api.notion.com/v1/pages/" . $pageId);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
]);
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
    echo json_encode(['success' => true, 'message' => 'Alat ditandai sudah dikembalikan.']);
} else {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}