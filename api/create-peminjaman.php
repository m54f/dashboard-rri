<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/../includes/api-auth.php';

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

// Get admin name for tracking
 $createdBy = getApiAdminName() ?? 'Open Mode';

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
            
            // Fallback query tanpa filter kategori jika filter kategori terlalu kaku
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
                
                // Update status Inventaris menjadi Dipinjam
                notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $invPageId, [
                    "properties" => ["Status" => ["select" => ["name" => "Dipinjam"]]]
                ]);
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