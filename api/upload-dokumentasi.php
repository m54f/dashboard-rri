<?php
/**
 * api/upload-dokumentasi.php
 * Upload foto dokumentasi troubleshoot.
 * Menerima multipart/form-data POST dengan file(s) di field "dokumentasi[]".
 * Menyimpan ke /uploads/dokumentasi/ dan mengembalikan URL yang bisa diakses.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/dokumentasi/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Detect base URL for generating accessible file URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
$baseUrl = $protocol . '://' . $host . rtrim($basePath, '/');

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 10 * 1024 * 1024; // 10MB per file

$uploaded = [];
$errors = [];

// Handle single or multiple files
if (!isset($_FILES['dokumentasi'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tidak ada file dokumentasi yang dikirim']);
    exit;
}

$files = $_FILES['dokumentasi'];

// Normalize to array of files (works for both single and multiple uploads)
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    $name    = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
    $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $error   = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
    $size    = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "File '{$name}': Upload error code {$error}";
        continue;
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "File '{$name}': Tipe file tidak diizinkan ({$mimeType})";
        continue;
    }

    // Validate size
    if ($size > $maxSize) {
        $errors[] = "File '{$name}': Ukuran melebihi 10MB";
        continue;
    }

    // Generate unique filename
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($tmpName, $filepath)) {
        $fileUrl = $baseUrl . '/uploads/dokumentasi/' . $filename;
        $uploaded[] = [
            'filename' => $filename,
            'original' => $name,
            'url'      => $fileUrl,
            'size'     => $size,
        ];
    } else {
        $errors[] = "File '{$name}': Gagal menyimpan file";
    }
}

if (empty($uploaded) && !empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode('; ', $errors), 'files' => []]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => count($uploaded) . ' file berhasil diupload',
    'files'   => $uploaded,
    'errors'  => $errors,
]);
