<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/notion.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

 $input = json_decode(file_get_contents('php://input'), true);

if (empty($input['page_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID Halaman tidak ditemukan.']);
    exit;
}

 $pageId = $input['page_id'];
 $notionToken = NOTION_TOKEN;
 $url = "https://api.notion.com/v1/pages/{$pageId}";

 $headers = [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
];

// Notion melakukan soft-delete dengan mengubah status arsip
 $body = ['archived' => true];

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
    echo json_encode(['success' => false, 'error' => $error['message'] ?? "Gagal menghapus data (HTTP {$httpCode})"]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus (diarsipkan)!']);
?>