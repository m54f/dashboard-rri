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

if (empty($input['page_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID Petugas tidak ditemukan.']);
    exit;
}

 $ch = curl_init("https://api.notion.com/v1/pages/{$input['page_id']}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['archived' => true]));

 $response = curl_exec($ch);
 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $error = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $error['message'] ?? "Gagal menghapus"]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Data petugas berhasil dihapus!']);
?>