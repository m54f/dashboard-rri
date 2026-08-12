<?php
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

// === DETEKSI OTOMATIS NAMA KEY ===
$filesKey = '';
if (!empty($_FILES['eviden'])) {
    $filesKey = 'eviden';
} elseif (!empty($_FILES['dokumentasi'])) {
    $filesKey = 'dokumentasi';
}

if (empty($filesKey)) {
    // === FALLBACK: Fetch URL eksternal (Thumbnail YouTube) & Upload ke Notion S3 ===
    $externalUrl = trim($_POST['flyer_url'] ?? '');
    if (!empty($externalUrl) && filter_var($externalUrl, FILTER_VALIDATE_URL)) {
        try {
            // Download thumbnail gambar dari URL (timeout 6 detik)
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

                // Upload file secara native ke server Notion S3
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
            error_log("Upload thumbnail RSS ke Notion S3 gagal, fallback ke external URL: " . $e->getMessage());
        }

        // Safety fallback jika upload S3 gagal/timeout: kirim URL eksternal
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