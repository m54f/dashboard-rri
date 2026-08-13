<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * api/upload-to-notion.php
 * Endpoint untuk menerima file upload dari browser (multipart/form-data)
 * lalu meneruskannya ke Notion File Upload API via helper function.
 *
 * Mendukung key 'dokumentasi' (troubleshoot) dan 'eviden' (pemadaman/jurnal).
 */
@set_time_limit(120);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/notion-file-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// === DETEKSI OTOMATIS NAMA KEY DARI $_FILES ===
$filesKey = '';
if (!empty($_FILES['eviden'])) {
    $filesKey = 'eviden';
} elseif (!empty($_FILES['dokumentasi'])) {
    $filesKey = 'dokumentasi';
} elseif (!empty($_FILES['files'])) {
    $filesKey = 'files';
} elseif (!empty($_FILES['file'])) {
    $filesKey = 'file';
} else {
    $keys = array_keys($_FILES);
    if (!empty($keys)) {
        $filesKey = $keys[0];
    }
}

if (empty($filesKey) || empty($_FILES[$filesKey]['name'])) {
    // === FALLBACK: Fetch URL eksternal (Thumbnail YouTube) & Upload ke Notion S3 ===
    $externalUrl = trim($_POST['flyer_url'] ?? '');
    if (!empty($externalUrl) && filter_var($externalUrl, FILTER_VALIDATE_URL)) {
        try {
            $ch = curl_init($externalUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $binary = curl_exec($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/jpeg';
            $curlErr = curl_error($ch);
            curl_close($ch);

            if (!empty($binary) && empty($curlErr)) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'rss_flyer_');
                file_put_contents($tmpPath, $binary);

                $mimeType = trim(explode(';', $contentType)[0]) ?: 'image/jpeg';
                $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                $ext = $extMap[$mimeType] ?? 'jpg';
                $safeName = 'thumbnail_youtube.' . $ext;

                $fileUploadId = notionUploadSingleFile($tmpPath, $safeName, $mimeType);
                @unlink($tmpPath);

                echo json_encode([
                    'success' => true,
                    'files'   => [
                        [
                            'id'   => $fileUploadId,
                            'name' => $safeName
                        ]
                    ],
                    'error'   => ''
                ]);
                exit;
            }
        } catch (Exception $e) {
            error_log("Upload thumbnail RSS ke Notion S3 gagal: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'files'   => [
                [
                    'url'  => $externalUrl,
                    'name' => 'Thumbnail YouTube',
                    'type' => 'external'
                ]
            ]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Tidak ada file diterima server.']);
    exit;
}

// Normalize $_FILES item structure into consistent list
$fileList = [];
$rawObj = $_FILES[$filesKey];

if (is_array($rawObj['name'])) {
    foreach ($rawObj['name'] as $i => $name) {
        $fileList[] = [
            'name'     => $name,
            'tmp_name' => $rawObj['tmp_name'][$i] ?? '',
            'type'     => $rawObj['type'][$i] ?? '',
            'error'    => $rawObj['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $rawObj['size'][$i] ?? 0
        ];
    }
} else {
    $fileList[] = [
        'name'     => $rawObj['name'],
        'tmp_name' => $rawObj['tmp_name'],
        'type'     => $rawObj['type'],
        'error'    => $rawObj['error'],
        'size'     => $rawObj['size']
    ];
}

$uploadedFiles = [];
$errors = [];

foreach ($fileList as $item) {
    if ($item['error'] !== UPLOAD_ERR_OK || empty($item['tmp_name']) || !is_uploaded_file($item['tmp_name'])) {
        $errors[] = "Gagal menerima file " . htmlspecialchars($item['name']);
        continue;
    }

    // Limit size 10MB
    if ($item['size'] > 10 * 1024 * 1024) {
        $errors[] = "Ukuran file " . htmlspecialchars($item['name']) . " melebihi batas 10MB.";
        continue;
    }

    $tmpName  = $item['tmp_name'];
    $mimeType = $item['type'] ?: mime_content_type($tmpName) ?: 'application/octet-stream';
    $name     = $item['name'];

    $safeName = str_replace(' ', '_', $name);
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $safeName);

    try {
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