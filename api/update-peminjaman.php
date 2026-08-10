<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/api-auth.php';
requireAdmin();

require_once __DIR__ . '/../config/notion.php';

if (!defined('NOTION_TOKEN') || !defined('DATABASE_ID_PEMINJAMAN') || !defined('DATABASE_ID_INVENTARIS')) {
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
    elseif ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    } elseif ($method === 'PATCH') {
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
    echo json_encode(['error' => 'Page ID wajib diisi untuk update.']);
    exit;
}

// 1. Ambil data lama untuk melepas stok sebelumnya
 $oldPageData = notionCurl('GET', 'https://api.notion.com/v1/pages/' . $pageId);
 $oldRelations = $oldPageData['data']['properties']['Inventaris Terkait']['relation'] ?? [];

// Kembalikan status Inventaris lama menjadi Tersedia
foreach ($oldRelations as $rel) {
    notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $rel['id'], [
        "properties" => ["Status" => ["select" => ["name" => "Tersedia"]]]
    ]);
}

// 2. Susun properti Peminjaman baru
 $picRaw = $payload['pic'] ?? '';
 if (is_array($picRaw)) {
     $picArr = array_filter(array_map('trim', $picRaw));
 } else {
     $picArr = array_filter(array_map('trim', explode(',', (string)$picRaw)));
 }
 $picMultiSelect = array_map(fn($p) => ['name' => $p], array_values($picArr));

 $alatPayload = $payload['alat'] ?? [];
 $detailAlatParts = [];
 $alatTags = [];

foreach ($alatPayload as $a) {
    $name = trim($a['alat'] ?? '');
    $tipe = trim($a['tipe'] ?? $a['jenis'] ?? '');
    $kat  = trim($a['kategori'] ?? '');
    $qty  = (int)($a['qty'] ?? 1);
    
    if (empty($name)) continue;
    
    $alatTags[] = $name;
    $part = $kat ? "[{$kat}] {$name}" : $name;
    if (!empty($tipe)) $part .= " ({$tipe})";
    $part .= " ({$qty}x)";
    $detailAlatParts[] = $part;
}

 $detailAlat = implode(', ', $detailAlatParts);
 $alatTags = array_unique($alatTags);

 $properties = [
    'Nama Kegiatan' => ['title' => [['text' => ['content' => $payload['kegiatan'] ?? '']]]],
    'Nama PIC PT' => ['multi_select' => $picMultiSelect],
    'Lokasi' => ['rich_text' => [['text' => ['content' => $payload['lokasi'] ?? '']]]],
    'Tanggal Kegiatan' => ['date' => ['start' => $payload['tgl_kegiatan'] ?? null]],
    'Tanggal Pinjam' => ['date' => ['start' => $payload['tgl_pinjam'] ?? null]],
    'Alat Dipinjam' => ['multi_select' => array_map(fn($t) => ['name' => $t], $alatTags)],
    'Detail Alat' => ['rich_text' => [['text' => ['content' => $detailAlat]]]],
    'Durasi (jam)' => ['number' => (int)($payload['durasi'] ?? 0)],
    'Keterangan' => ['rich_text' => [['text' => ['content' => $payload['keterangan'] ?? '']]]],
    'Status' => ['select' => ['name' => $payload['status'] ?? 'Dipinjam']]
];
 $properties = array_filter($properties, fn($v) => $v !== null);

// Kosongkan relasi lama di Peminjaman (akan diisi ulang jika ada)
 $properties['Inventaris Terkait'] = ['relation' => []];

 $res = notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pageId, ['properties' => $properties]);

if ($res['code'] === 200) {
    // 3. Cari & Hubungkan stok baru jika status masih Dipinjam
    if (($payload['status'] ?? 'Dipinjam') === 'Dipinjam') {
        $relationIds = [];
        foreach ($alatPayload as $alatItem) {
            $name = trim($alatItem['alat'] ?? '');
            $tipe = trim($alatItem['tipe'] ?? $alatItem['jenis'] ?? '');
            $kat  = trim($alatItem['kategori'] ?? '');
            $qty  = (int)($alatItem['qty'] ?? 1);
            
            if (empty($name)) continue;
            
            $filterAnd = [
                ["property" => "Status", "select" => ["equals" => "Tersedia"]],
                ["property" => "Nama Alat", "title" => ["contains" => $name]]
            ];
            if (!empty($kat)) {
                $filterAnd[] = ["property" => "Kategori", "select" => ["equals" => $kat]];
            }
            
            $invQuery = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', [
                "filter" => ["and" => $filterAnd],
                "page_size" => $qty
            ]);
            
            $matchedResults = $invQuery['data']['results'] ?? [];
            
            if (empty($matchedResults) && !empty($kat)) {
                $fallbackFilterAnd = [
                    ["property" => "Status", "select" => ["equals" => "Tersedia"]],
                    ["property" => "Nama Alat", "title" => ["contains" => $name]]
                ];
                $invQueryFallback = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', [
                    "filter" => ["and" => $fallbackFilterAnd],
                    "page_size" => $qty
                ]);
                $matchedResults = $invQueryFallback['data']['results'] ?? [];
            }
            
            foreach ($matchedResults as $invPage) {
                $invPageId = $invPage['id'];
                $relationIds[] = $invPageId;
                notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $invPageId, [
                    "properties" => ["Status" => ["select" => ["name" => "Dipinjam"]]]
                ]);
            }
        }
        
        if (!empty($relationIds)) {
            notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pageId, [
                "properties" => [
                    "Inventaris Terkait" => [
                        "relation" => array_map(fn($id) => ["id" => $id], $relationIds)
                    ]
                ]
            ]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Peminjaman diupdate & stok disinkronisasi ulang!']);
} else {
    echo json_encode(['error' => 'Gagal update peminjaman', 'detail' => $res['data']]);
}