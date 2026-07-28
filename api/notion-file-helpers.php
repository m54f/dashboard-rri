<?php
/**
 * api/notion-file-helpers.php
 * Fungsi bersama untuk upload file ke Notion via File Upload API.
 * Dipakai oleh: upload-to-notion.php (file baru) dan update-troubleshoot.php (foto lama yang dipertahankan).
 *
 * PENTING: NOTION_VERSION di config/notion.php WAJIB >= "2025-09-03"
 * karena File Upload API baru tersedia mulai versi tersebut.
 */

/**
 * Upload satu file (dari path lokal/tmp) ke Notion, mode single_part.
 * Return: file_upload id (string) berstatus "uploaded", siap dilampirkan ke property "files".
 *
 * @throws Exception jika gagal di tahap manapun
 */
function notionUploadSingleFile(string $tmpPath, string $originalName, string $mimeType): string
{
    // Gunakan versi khusus File Upload API (berbeda dari versi umum)
    $fileUploadVersion = defined('NOTION_VERSION_FILE_UPLOAD') ? NOTION_VERSION_FILE_UPLOAD : '2026-03-11';

    // ===== Tahap 1: Buat File Upload object =====
    $createHeaders = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . $fileUploadVersion,
        "Content-Type: application/json",
    ];

    $createPayload = json_encode([
        "mode"         => "single_part",
        "filename"     => $originalName,
        "content_type" => $mimeType,
    ]);

    $ch = curl_init("https://api.notion.com/v1/file_uploads");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $createHeaders);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $createPayload);
    $createResp = curl_exec($ch);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("Gagal membuat file upload di Notion (cURL: {$err})");
    }
    $createCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $createData = json_decode($createResp, true);
    if ($createCode !== 200 || empty($createData['id'])) {
        $msg = $createData['message'] ?? $createResp;
        throw new Exception("Notion menolak membuat file upload (HTTP {$createCode}): {$msg}. Cek NOTION_VERSION_FILE_UPLOAD di config/notion.php, minimal '2026-03-11'.");
    }

    $fileUploadId = $createData['id'];
    $sendUrl = "https://api.notion.com/v1/file_uploads/{$fileUploadId}/send";

    // ===== Tahap 2: Kirim isi file (multipart/form-data) =====
    $sendHeaders = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . $fileUploadVersion,
        // Jangan set Content-Type manual — biarkan cURL generate boundary multipart otomatis
    ];

    $cfile = new CURLFile($tmpPath, $mimeType, $originalName);

    $ch = curl_init($sendUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ["file" => $cfile]);
    $sendResp = curl_exec($ch);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("Gagal mengirim isi file ke Notion (cURL: {$err})");
    }
    $sendCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $sendData = json_decode($sendResp, true);
    if ($sendCode !== 200 || ($sendData['status'] ?? '') !== 'uploaded') {
        $msg = $sendData['message'] ?? $sendResp;
        throw new Exception("Notion gagal memproses file (HTTP {$sendCode}): {$msg}");
    }

    return $fileUploadId;
}

/**
 * Download file dari URL (misal: signed URL foto lama dari Notion) lalu upload ulang
 * sebagai file_upload baru. Dipakai supaya foto lama yang "dipertahankan" saat edit
 * tetap jadi attachment permanen, bukan link sementara yang bisa kedaluwarsa.
 *
 * @throws Exception jika gagal download atau upload
 */
function notionReuploadFromUrl(string $sourceUrl, string $name): string
{
    $ch = curl_init($sourceUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $binary = curl_exec($ch);

    if (curl_errno($ch) || empty($binary)) {
        $err = curl_error($ch) ?: 'Respon kosong';
        curl_close($ch);
        throw new Exception("Gagal mengambil file lama '{$name}': {$err} (kemungkinan link sudah kedaluwarsa)");
    }
    curl_close($ch);

    $tmpPath = tempnam(sys_get_temp_dir(), 'notion_doc_');
    file_put_contents($tmpPath, $binary);

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpPath) ?: 'image/jpeg';
    finfo_close($finfo);

    try {
        $id = notionUploadSingleFile($tmpPath, $name, $mimeType);
    } finally {
        @unlink($tmpPath);
    }

    return $id;
}