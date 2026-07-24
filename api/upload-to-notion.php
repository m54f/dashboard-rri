<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/notion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['eviden'])) {
    echo json_encode(['success' => false, 'error' => 'Tidak ada file diterima server.']);
    exit;
}

$uploadedFiles = [];
$errors = [];

foreach ($_FILES['eviden']['name'] as $idx => $name) {
    if ($_FILES['eviden']['error'][$idx] !== UPLOAD_ERR_OK) {
        $errors[] = "Server gagal menerima $name";
        continue;
    }

    $tmpName = $_FILES['eviden']['tmp_name'][$idx];
    $mimeType = $_FILES['eviden']['type'][$idx];
    $fileData = file_get_contents($tmpName);

    $safeName = str_replace(' ', '_', $name);
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $safeName);

    // ===== LANGKAH 1: Create File Upload object (JSON, bukan multipart) =====
    $ch1 = curl_init('https://api.notion.com/v1/file_uploads');
    curl_setopt($ch1, CURLOPT_POST, true);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch1, CURLOPT_POSTFIELDS, json_encode([
        'filename' => $safeName,
        'content_type' => $mimeType
    ]));

    $createResponse = curl_exec($ch1);
    $createHttpCode = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
    curl_close($ch1);

    if ($createHttpCode !== 200) {
        $err = json_decode($createResponse, true);
        $errors[] = "Gagal membuat file upload untuk $name (HTTP $createHttpCode): " . ($err['message'] ?? 'Unknown error');
        continue;
    }

    $createData = json_decode($createResponse, true);
    $fileUploadId = $createData['id'] ?? null;
    if (!$fileUploadId) {
        $errors[] = "Tidak dapat ID file upload untuk $name";
        continue;
    }

    // ===== LANGKAH 2: Send isi file (multipart, HANYA field 'file') =====
    $boundary = uniqid();
    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . $safeName . "\"\r\n";
    $body .= "Content-Type: " . $mimeType . "\r\n\r\n";
    $body .= $fileData . "\r\n";
    $body .= "--" . $boundary . "--\r\n";

    $ch2 = curl_init("https://api.notion.com/v1/file_uploads/{$fileUploadId}/send");
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: multipart/form-data; boundary=" . $boundary
    ]);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $body);

    $sendResponse = curl_exec($ch2);
    $sendHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($sendHttpCode === 200) {
        $uploadedFiles[] = [
            'id' => $fileUploadId,
            'name' => $name
        ];
    } else {
        $err = json_decode($sendResponse, true);
        $errors[] = "Notion menolak isi file $name (HTTP $sendHttpCode): " . ($err['message'] ?? 'Unknown error');
    }
}

$finalError = '';
if (!empty($errors)) {
    $finalError = implode(' | ', $errors);
}

echo json_encode([
    'success' => count($uploadedFiles) > 0,
    'files'   => $uploadedFiles,
    'error'   => $finalError
]);