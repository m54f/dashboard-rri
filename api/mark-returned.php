<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/notion.php';

if (!defined('NOTION_TOKEN') || !defined('DATABASE_ID_PEMINJAMAN')) {
    echo json_encode(['error' => 'Konfigurasi Notion belum lengkap.']);
    exit;
}

function notionCurl($method, $url, $body = []) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . NOTION_TOKEN,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json',
        ],
    ];
    if ($method === 'GET') $opts[CURLOPT_HTTPGET] = true;
    elseif ($method === 'PATCH') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

$payload = json_decode(file_get_contents('php://input'), true);

$pageId              = trim($payload['page_id'] ?? '');
$namaPicPengembali   = trim($payload['nama_pic_pengembali'] ?? '');
$kondisiAlat         = trim($payload['kondisi_alat'] ?? '');
$catatanPengembalian = trim($payload['catatan_pengembalian'] ?? '');

// Validasi wajib
if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Page ID hilang.']);
    exit;
}

if (empty($namaPicPengembali)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nama PIC Pengembali wajib diisi.']);
    exit;
}

// 1. Ambil data Peminjaman untuk mengetahui alat apa saja yang dipinjam (melalui relasi)
$pageData  = notionCurl('GET', 'https://api.notion.com/v1/pages/' . $pageId);
$relations = $pageData['data']['properties']['Inventaris Terkait']['relation'] ?? [];

// 2. Kembalikan status semua alat terkait menjadi "Tersedia"
foreach ($relations as $rel) {
    notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $rel['id'], [
        "properties" => ["Status" => ["select" => ["name" => "Tersedia"]]]
    ]);
}

// 3. Bangun body update Notion
$properties = [
    "Status"          => ["select" => ["name" => "Dikembalikan"]],
    "Tanggal Kembali" => ["date"   => ["start" => date('Y-m-d')]],
    "Inventaris Terkait" => ["relation" => []], // Kosongkan relasi

    // Field pengembalian baru
    "Nama PIC Pengembali" => [
        "rich_text" => [[
            "type" => "text",
            "text" => ["content" => $namaPicPengembali]
        ]]
    ],
];

// Kondisi alat (opsional)
if (!empty($kondisiAlat)) {
    $properties["Kondisi Alat Saat Kembali"] = ["select" => ["name" => $kondisiAlat]];
}

// Catatan pengembalian (opsional)
if (!empty($catatanPengembalian)) {
    $properties["Catatan Pengembalian"] = [
        "rich_text" => [[
            "type" => "text",
            "text" => ["content" => $catatanPengembalian]
        ]]
    ];
}

// 4. Update halaman Peminjaman di Notion
$res = notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pageId, [
    "properties" => $properties
]);

if ($res['code'] === 200) {
    echo json_encode(['success' => true, 'message' => 'Alat dikembalikan & stok inventaris diperbarui!']);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal menandai dikembalikan', 'detail' => $res['data']]);
}