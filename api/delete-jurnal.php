<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/notion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// jurnal.php mengirim body JSON (fetch dengan Content-Type: application/json),
// jadi page_id harus dibaca dari php://input, bukan $_POST.
$input = json_decode(file_get_contents('php://input'), true);
$page_id = $input['page_id'] ?? '';

if (empty($page_id)) {
    echo json_encode(['success' => false, 'error' => 'page_id hilang']);
    exit;
}

// Notion menghapus baris dengan cara mengarsipkannya
$payload = ['archived' => true];

$ch = curl_init("https://api.notion.com/v1/pages/$page_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN, 
    "Content-Type: application/json", 
    "Notion-Version: " . NOTION_VERSION
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo json_encode(['success' => true, 'message' => 'Jurnal berhasil dihapus (diarsipkan)!']);
} else {
    $err = json_decode($response, true);
    echo json_encode(['success' => false, 'error' => $err['message'] ?? 'Gagal menghapus data di Notion']);
}