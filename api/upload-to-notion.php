<?php
/**
 * api/upload-to-notion.php
 * Upload foto dokumentasi LANGSUNG ke Notion via Notion File Upload API.
 * Tidak menyimpan ke folder lokal sama sekali.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/notion-file-helpers.php';

if (!isset($_FILES['dokumentasi'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tidak ada file dokumentasi yang dikirim']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 10 * 1024 * 1024; // 10MB per file

$files = $_FILES['dokumentasi'];
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

$uploaded = [];
$errors = [];

for ($i = 0; $i < $fileCount; $i++) {
    $name    = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
    $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $error   = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
    $size    = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "File '{$name}': Upload error code {$error}";
        continue;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "File '{$name}': Tipe file tidak diizinkan ({$mimeType})";
        continue;
    }
    if ($size > $maxSize) {
        $errors[] = "File '{$name}': Ukuran melebihi 10MB";
        continue;
    }

    try {
        $fileUploadId = notionUploadSingleFile($tmpName, $name, $mimeType);
        $uploaded[] = [
            'id'       => $fileUploadId, // dipakai untuk attach ke property "files" saat create/update
            'original' => $name,
            'size'     => $size,
        ];
    } catch (Exception $e) {
        $errors[] = "File '{$name}': " . $e->getMessage();
    }
}

if (empty($uploaded) && !empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode('; ', $errors), 'files' => []]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => count($uploaded) . ' file berhasil diupload ke Notion',
    'files'   => $uploaded,
    'errors'  => $errors,
]);