<?php
/**
 * Debug endpoint: cek status pdo_pgsql dan koneksi Supabase
 * Akses: http://localhost:8000/debug-supabase.php
 *
 * HAPUS file ini setelah selesai debugging!
 */
header('Content-Type: application/json; charset=utf-8');

$result = [
    'php_version'       => PHP_VERSION,
    'pdo_loaded'        => extension_loaded('pdo'),
    'pdo_pgsql_loaded'  => extension_loaded('pdo_pgsql'),
    'php_ini_loaded'    => php_ini_loaded_file(),
    'extensions'        => array_values(array_filter(get_loaded_extensions(), fn($e) => str_contains(strtolower($e), 'pdo') || str_contains(strtolower($e), 'pgsql'))),
    'connection'        => null,
    'error'             => null,
];

if (!extension_loaded('pdo_pgsql')) {
    $result['error'] = 'Extension pdo_pgsql TIDAK aktif. Restart server port 8000 setelah mengubah php.ini.';
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Coba koneksi ke Supabase
try {
    require_once __DIR__ . '/config/supabase.php';
    $t = microtime(true);
    $pdo = new PDO(
        SUPABASE_DSN,
        SUPABASE_USER,
        SUPABASE_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
    );
    $ms = round((microtime(true) - $t) * 1000, 1);
    $row = $pdo->query("SELECT NOW() AS t, version() AS v")->fetch(PDO::FETCH_ASSOC);

    $result['connection'] = [
        'ok'          => true,
        'latency_ms'  => $ms,
        'server_time' => $row['t'] ?? null,
        'pg_version'  => substr($row['v'] ?? '', 0, 60),
        'dsn_used'    => SUPABASE_DSN,
    ];
} catch (Throwable $e) {
    $result['connection'] = ['ok' => false];
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
