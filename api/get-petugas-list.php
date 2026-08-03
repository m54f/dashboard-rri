<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/petugas.php';

$petugas = getPetugas();

if (isset($petugas['error'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $petugas['error']]);
    exit;
}

$names = array_map(fn($p) => $p['name'], $petugas);
$names = array_values(array_filter($names)); // buang yang kosong

echo json_encode(['success' => true, 'data' => $names]);
