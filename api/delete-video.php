<?php
/**
 * API Endpoint: Delete Video Entry dari Notion (Archive Page)
 * 
 * Menerima POST request JSON dengan field:
 *   - page_id (string, required) : ID halaman Notion yang akan dihapus/diarsipkan
 * 
 * Mengirim data perubahan status archived ke Notion API via (PATCH Page).
 */

header('Content-Type: application/json; charset=utf-8');

// Hanya terima POST sesuai standard model troubleshoot
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';

// Baca JSON body dari request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body.']);
    exit;
}

// ===== Validasi & Sanitasi Input =====
$pageId = trim($input['page_id'] ?? '');

// Validasi field wajib
if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "page_id" wajib diisi.']);
    exit;
}

// ===== Bangun Payload Notion API =====
// Di Notion, proses 'delete' dilakukan dengan mengarsipkan halaman (archived: true) melalui metode PATCH
$url = "https://api.notion.com/v1/pages/" . $pageId;

$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
];

$payload = [
    "archived" => true
];

// ===== Kirim ke Notion API =====
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); // Menggunakan PATCH untuk proses archive/update status
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
        'message' => 'Video berhasil dihapus dari rekap Notion.',
        'notion_page_id' => $responseData['id'] ?? null,
        'archived' => $responseData['archived'] ?? true
    ]);
} else {
    // Gagal — kirim detail error dari Notion API sesuai standard troubleshoot
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}