<?php
/**
 * api/get-master-data.php
 * Ambil daftar Nama Acara, Nama Pemandu, Petugas Teknik, dan Petugas PA dari
 * FILE LOKAL data/master-data.json, dikelompokkan per Programa (Pro 1/2/4),
 * untuk mengisi dropdown dinamis di form Tambah/Edit Video (gallery.php).
 *
 * Data ini TIDAK diambil dari Notion -- disimpan lokal di server sendiri.
 * Untuk menambah/mengubah nama, edit langsung file:
 *   data/master-data.json
 *
 * Format file:
 * {
 *   "acara":   { "Pro 1": ["Nama A", ...], "Pro 2": [...], "Pro 4": [...] },
 *   "pemandu": { "Pro 1": ["Nama B", ...], "Pro 2": [...], "Pro 4": [...] },
 *   "teknik":  { "Pro 1": ["Nama C", ...], "Pro 2": [...], "Pro 4": [...] },
 *   "pa":      { "Pro 1": ["Nama D", ...], "Pro 2": [...], "Pro 4": [...] }
 * }
 */

header('Content-Type: application/json; charset=utf-8');

$dataFile = __DIR__ . '/../data/master-data.json';
$categoriesGrouped = ['acara', 'pemandu'];
$programaLabels = ['Pro 1', 'Pro 2', 'Pro 4'];

$defaultStructure = [
    'petugas' => [] // Inisialisasi array tunggal untuk petugas
];

// Buat struktur grup Pro 1, Pro 2, Pro 4 HANYA untuk acara dan pemandu
foreach ($categoriesGrouped as $cat) {
    $defaultStructure[$cat] = array_fill_keys($programaLabels, []);
}

if (!file_exists($dataFile)) {
    // Belum ada file sama sekali -> buat baru dengan struktur kosong,
    // supaya endpoint tetap jalan dan halaman tidak error.
    @mkdir(dirname($dataFile), 0775, true);
    @file_put_contents($dataFile, json_encode($defaultStructure, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true] + $defaultStructure);
    exit;
}

$raw = file_get_contents($dataFile);
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'data/master-data.json rusak atau bukan JSON valid.']);
    exit;
}

$result = [];

// 1. Ambil dan tata data untuk acara & pemandu (berdasarkan programa)
foreach ($categoriesGrouped as $cat) {
    $fromFile = isset($data[$cat]) && is_array($data[$cat]) ? $data[$cat] : [];
    $merged = array_merge($defaultStructure[$cat], $fromFile);

    foreach ($programaLabels as $label) {
        $merged[$label] = isset($merged[$label]) && is_array($merged[$label]) ? array_values($merged[$label]) : [];
    }

    $result[$cat] = $merged;
}

// 2. Ambil data petugas (langsung ambil sebagai array tunggal)
$result['petugas'] = isset($data['petugas']) && is_array($data['petugas']) ? array_values($data['petugas']) : [];

echo json_encode(['success' => true] + $result);