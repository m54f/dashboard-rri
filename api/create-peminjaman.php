<?php
header('Content-Type: application/json; charset=utf-8');
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
    if ($method === 'POST') {
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

 $pic = $payload['pic'] ?? '';
 $kegiatan = $payload['kegiatan'] ?? '';
 $tglPinjam = $payload['tgl_pinjam'] ?? date('Y-m-d');
 $status = $payload['status'] ?? 'Dipinjam';

 $alatTags = array_unique(array_map(fn($a) => $a['alat'], $payload['alat'] ?? []));
 $detailAlat = implode(', ', array_map(fn($a) => "{$a['alat']} ({$a['jenis']}) ({$a['qty']})", $payload['alat'] ?? []));

 $properties = [
    'Nama Kegiatan' => ['title' => [['text' => ['content' => $kegiatan]]]],
    'Nama PIC PT' => ['select' => ['name' => $pic]],
    'Lokasi' => ['rich_text' => [['text' => ['content' => $payload['lokasi'] ?? '']]]],
    'Tanggal Kegiatan' => ['date' => ['start' => $payload['tgl_kegiatan'] ?? null]],
    'Tanggal Pinjam' => ['date' => ['start' => $tglPinjam]],
    'Alat Dipinjam' => ['multi_select' => array_map(fn($t) => ['name' => $t], $alatTags)],
    'Detail Alat' => ['rich_text' => [['text' => ['content' => $detailAlat]]]],
    'Durasi (jam)' => ['number' => (int)($payload['durasi'] ?? 0)],
    'Keterangan' => ['rich_text' => [['text' => ['content' => $payload['keterangan'] ?? '']]]],
    'Status' => ['select' => ['name' => $status]]
];
 $properties = array_filter($properties, fn($v) => $v !== null);

 $body = ['parent' => ['database_id' => DATABASE_ID_PEMINJAMAN], 'properties' => $properties];
 $res = notionCurl('POST', 'https://api.notion.com/v1/pages', $body);

if ($res['code'] === 200) {
    $pinjamId = $res['data']['id'];
    
    // === LOGIKA RELASI & UPDATE STOK INVENTARIS ===
    if ($status === 'Dipinjam') {
        $relationIds = [];
        foreach ($payload['alat'] as $alatItem) {
            // Cari alat di Inventaris yang namanya dan keterangannya cocok, serta statusnya Tersedia
            $filter = [
                "and" => [
                    ["property" => "Nama Alat", "title" => ["contains" => $alatItem['alat']]],
                    ["property" => "Status", "select" => ["equals" => "Tersedia"]]
                ]
            ];
            if (!empty($alatItem['jenis'])) {
                $filter['and'][] = ["property" => "Keterangan", "rich_text" => ["contains" => $alatItem['jenis']]];
            }
            
            $invQuery = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', [
                "filter" => $filter,
                "page_size" => (int)$alatItem['qty']
            ]);
            
            if (isset($invQuery['data']['results'])) {
                foreach ($invQuery['data']['results'] as $invPage) {
                    $invPageId = $invPage['id'];
                    $relationIds[] = $invPageId;
                    
                    // Update status Inventaris menjadi Dipinjam
                    notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $invPageId, [
                        "properties" => ["Status" => ["select" => ["name" => "Dipinjam"]]]
                    ]);
                }
            }
        }
        
        // Hubungkan Inventaris ke Peminjaman (Relasi)
        if (!empty($relationIds)) {
            notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pinjamId, [
                "properties" => [
                    "Inventaris Terkait" => [
                        "relation" => array_map(fn($id) => ["id" => $id], $relationIds)
                    ]
                ]
            ]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Peminjaman dicatat & stok inventaris otomatis diperbarui!']);
} else {
    echo json_encode(['error' => 'Gagal mencatat peminjaman', 'detail' => $res['data']]);
}