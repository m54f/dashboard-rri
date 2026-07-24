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
    $mimeType = $_FILES['eviden']['type'][$idx] ?: 'application/octet-stream';
    $fileData = file_get_contents($tmpName);

    // Sanitasi nama file
    $safeName = str_replace(' ', '_', $name);
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $safeName);

    // Buat boundary unik yang panjang
    $boundary = '----WebKitFormBoundary' . md5(time() . uniqid());
    
    // === BANGUN MULTIPART FORM SECARA MANUAL ===
    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Disposition: form-data; name=\"mode\"\r\n\r\n";
    $body .= "single_page\r\n";
    
    $body .= "--" . $boundary . "\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . $safeName . "\"\r\n";
    $body .= "Content-Type: " . $mimeType . "\r\n\r\n";
    $body .= $fileData . "\r\n";
    $body .= "--" . $boundary . "--\r\n";
    // ============================================

    $ch = curl_init('https://api.notion.com/v1/file_uploads');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: 2022-06-28", // Hardcode versi agar pasti didukung
        "Content-Type: multipart/form-data; boundary=" . $boundary,
        "Expect: " // Wajib dikosongkan agar cURL tidak kirim header Expect: 100-continue
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        if (isset($data['id'])) {
            $uploadedFiles[] = [
                'id' => $data['id'],
                'name' => $name 
            ];
        } else {
            $errors[] = "Gagal parse ID dari Notion untuk $name";
        }
    } else {
        $err_data = json_decode($response, true);
        $err_msg = $err_data['message'] ?? 'Unknown Notion Error';
        $errors[] = "Notion menolak $name (HTTP $http_code): $err_msg";
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