<?php
/**
 * api/upload-to-notion.php
 * Endpoint untuk menerima file upload dari browser (multipart/form-data)
 * lalu meneruskannya ke Notion File Upload API via helper function.
 *
 * Mendukung key 'dokumentasi' (troubleshoot) dan 'eviden' (pemadaman/jurnal).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/notion-file-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// === DETEKSI OTOMATIS NAMA KEY ===
$filesKey = '';
if (!empty($_FILES['eviden'])) {
    $filesKey = 'eviden';
} elseif (!empty($_FILES['dokumentasi'])) {
    $filesKey = 'dokumentasi';
}

if (empty($filesKey)) {
    echo json_encode(['success' => false, 'error' => 'Tidak ada file diterima server.']);
    exit;
}

$uploadedFiles = [];
$errors = [];

foreach ($_FILES[$filesKey]['name'] as $idx => $name) {
    if ($_FILES[$filesKey]['error'][$idx] !== UPLOAD_ERR_OK) {
        $errors[] = "Server gagal menerima $name";
        continue;
    }

    $tmpName  = $_FILES[$filesKey]['tmp_name'][$idx];
    $mimeType = $_FILES[$filesKey]['type'][$idx] ?: 'application/octet-stream';

    // Sanitasi nama file (hapus spasi & karakter aneh)
    $safeName = str_replace(' ', '_', $name);
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $safeName);

    try {
        // Gunakan helper 2-tahap yang sudah teruji:
        // Tahap 1: POST JSON ke /v1/file_uploads → dapat file_upload ID
        // Tahap 2: POST multipart ke /v1/file_uploads/{id}/send → status "uploaded"
        $fileUploadId = notionUploadSingleFile($tmpName, $safeName, $mimeType);

        $uploadedFiles[] = [
            'id'   => $fileUploadId,
            'name' => $name
        ];
    } catch (Exception $e) {
        $errors[] = "Gagal upload $name: " . $e->getMessage();
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