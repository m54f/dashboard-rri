<?php
/**
 * API Error Logger
 * Menulis log detail ke file logs/api-error.log
 * 
 * Cara baca log: buka file logs/api-error.log di text editor
 * atau akses via: http://localhost/logbook-rri/logs/api-error.log
 */

define('LOG_FILE', __DIR__ . '/../logs/api-error.log');
define('LOG_MAX_SIZE', 2 * 1024 * 1024); // 2MB auto-rotate

function apiLog(string $level, string $context, $data = null): void {
    // Auto-rotate jika file terlalu besar
    if (file_exists(LOG_FILE) && filesize(LOG_FILE) > LOG_MAX_SIZE) {
        rename(LOG_FILE, LOG_FILE . '.old');
    }

    $timestamp = date('Y-m-d H:i:s');
    $session   = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'guest';

    $line = "[{$timestamp}] [{$level}] [{$context}] user={$session}";

    if ($data !== null) {
        if (is_string($data)) {
            $line .= " | " . $data;
        } else {
            $line .= " | " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    $line .= PHP_EOL;

    // Buat direktori logs jika belum ada
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

/** Shortcut helpers */
function logInfo(string $ctx, $data = null)  { apiLog('INFO',  $ctx, $data); }
function logWarn(string $ctx, $data = null)  { apiLog('WARN',  $ctx, $data); }
function logError(string $ctx, $data = null) { apiLog('ERROR', $ctx, $data); }

/**
 * Log respons Notion API — jika bukan 200, catat sebagai error.
 */
function logNotionResponse(string $ctx, array $res, string $label = ''): void {
    $code = $res['code'] ?? 0;
    $data = $res['data'] ?? null;

    if ($code === 200) {
        logInfo($ctx, "OK {$label} code={$code}");
    } else {
        logError($ctx, [
            'label'   => $label,
            'code'    => $code,
            'message' => $data['message']  ?? '(no message)',
            'object'  => $data['object']   ?? null,
            'status'  => $data['status']   ?? null,
        ]);
    }
}
