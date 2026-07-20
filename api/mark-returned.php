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
    curl_close($ch);
    return ['code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'data' => json_decode($res, true)];
}

 $payload = json_decode(file_get_contents('php://input'), true);
 $pageId = $payload['page_id'] ?? '';

if (empty($pageId)) {
    echo json_encode(['error' => 'Page ID hilang.']);
    exit;
}

// 1. Ambil data Peminjaman untuk mengetahui alat apa saja yang dipinjam (melalui relasi)
 $pageData = notionCurl('GET', 'https://api.notion.com/v1/pages/' . $pageId);
 $relations = $pageData['data']['properties']['Inventaris Terkait']['relation'] ?? [];

// 2. Kembalikan status semua alat terkait menjadi "Tersedia"
foreach ($relations as $rel) {
    notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $rel['id'], [
        "properties" => ["Status" => ["select" => ["name" => "Tersedia"]]]
    ]);
}

// 3. Update Peminjaman menjadi Dikembalikan & hapus relasi agar inventaris bersih
 $updateBody = [
    "properties" => [
        "Status" => ["select" => ["name" => "Dikembalikan"]],
        "Tanggal Kembali" => ["date" => ["start" => date('Y-m-d')]],
        "Inventaris Terkait" => ["relation" => []] // Kosongkan relasi
    ]
];

 $res = notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pageId, $updateBody);

if ($res['code'] === 200) {
    echo json_encode(['success' => true, 'message' => 'Alat dikembalikan & stok inventaris diperbarui!']);
} else {
    echo json_encode(['error' => 'Gagal menandai dikembalikan', 'detail' => $res['data']]);
}