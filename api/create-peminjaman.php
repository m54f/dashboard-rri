<?php
header('Content-Type: application/json; charset=utf-8');

// ── Extend timeout agar tidak timeout saat banyak alat (banyak API call) ──
set_time_limit(300);
ignore_user_abort(true);

require_once __DIR__ . '/../config/notion.php';
require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/../includes/api-logger.php';

if (!defined('NOTION_TOKEN') || !defined('DATABASE_ID_PEMINJAMAN') || !defined('DATABASE_ID_INVENTARIS')) {
    logError('create-peminjaman', 'Konfigurasi Notion belum lengkap (TOKEN/DB_ID tidak terdefinisi)');
    echo json_encode(['error' => 'Konfigurasi Notion belum lengkap.']);
    exit;
}

/**
 * Notion cURL single request dengan retry otomatis saat rate-limited (HTTP 429).
 */
function notionCurl($method, $url, $body = [], $retries = 3) {
    for ($attempt = 0; $attempt < $retries; $attempt++) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
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
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            logError('create-peminjaman/notionCurl', "cURL error: {$curlErr} | url={$url}");
        }

        if ($code === 429) {
            logWarn('create-peminjaman/notionCurl', "Rate limited (429), retry attempt=" . ($attempt + 1) . " url={$url}");
            sleep(1 + $attempt);
            continue;
        }

        return ['code' => $code, 'data' => json_decode($res, true)];
    }
    logError('create-peminjaman/notionCurl', "Rate limited after {$retries} retries, url={$url}");
    return ['code' => 429, 'data' => ['message' => 'Rate limited after retries']];
}

/**
 * Batch PATCH banyak inventory page secara paralel menggunakan cURL multi-handle.
 */
function batchPatchInventoryStatus(array $pageIds, string $statusName) {
    if (empty($pageIds)) return;

    logInfo('create-peminjaman/batchPatch', "Memulai batch PATCH " . count($pageIds) . " inventory → status={$statusName}");

    $mh = curl_multi_init();
    $handles = [];

    foreach ($pageIds as $pid) {
        $ch = curl_init('https://api.notion.com/v1/pages/' . $pid);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_CUSTOMREQUEST   => 'PATCH',
            CURLOPT_HTTPHEADER      => [
                'Authorization: Bearer ' . NOTION_TOKEN,
                'Notion-Version: 2022-06-28',
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS      => json_encode([
                'properties' => ['Status' => ['select' => ['name' => $statusName]]]
            ], JSON_UNESCAPED_UNICODE),
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    $failCount = 0;
    foreach ($handles as $ch) {
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code !== 200) {
            $failCount++;
            logWarn('create-peminjaman/batchPatch', "Satu inventory PATCH gagal code={$code}");
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    if ($failCount > 0) {
        logError('create-peminjaman/batchPatch', "{$failCount} dari " . count($pageIds) . " PATCH inventory gagal");
    } else {
        logInfo('create-peminjaman/batchPatch', "Semua " . count($pageIds) . " inventory berhasil di-PATCH");
    }
}

// ── Parse payload ──────────────────────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$payload  = json_decode($rawInput, true);

if ($payload === null) {
    logError('create-peminjaman', 'JSON payload tidak valid: ' . $rawInput);
    echo json_encode(['error' => 'Payload tidak valid (bukan JSON).']);
    exit;
}

logInfo('create-peminjaman', [
    'kegiatan'    => $payload['kegiatan'] ?? '',
    'tgl_pinjam'  => $payload['tgl_pinjam'] ?? '',
    'jumlah_alat' => count($payload['alat'] ?? []),
    'pic'         => $payload['pic'] ?? '',
]);

$picRaw = $payload['pic'] ?? '';
if (is_array($picRaw)) {
    $picArr = array_filter(array_map('trim', $picRaw));
} else {
    $picArr = array_filter(array_map('trim', explode(',', (string)$picRaw)));
}
$picMultiSelect = array_map(fn($p) => ['name' => $p], array_values($picArr));

$kegiatan  = $payload['kegiatan'] ?? '';
$tglPinjam = $payload['tgl_pinjam'] ?? date('Y-m-d');
$status    = $payload['status'] ?? 'Dipinjam';

$alatPayload     = $payload['alat'] ?? [];
$detailAlatParts = [];
$alatTags        = [];

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
if (mb_strlen($detailAlat) > 1990) {
    logWarn('create-peminjaman', 'detailAlat terpotong: ' . mb_strlen($detailAlat) . ' karakter → 1990');
    $detailAlat = mb_substr($detailAlat, 0, 1990) . '…';
}
// array_values() wajib setelah array_unique() agar key berurutan dari 0
// Tanpa ini, JSON encode menghasilkan object {"0":{},"1":{}} bukan array [{},{}]
// Notion API menolak object untuk field multi_select → HTTP 400
$alatTags = array_values(array_unique($alatTags));

$createdBy = getApiAdminName() ?? 'Open Mode';

// ── Susun properties Notion ──────────────────────────────────────────────
$properties = [
    'Nama Kegiatan'    => ['title'        => [['text' => ['content' => $kegiatan]]]],
    'Nama PIC PT'      => ['multi_select' => $picMultiSelect],
    'Lokasi'           => ['rich_text'    => [['text' => ['content' => $payload['lokasi'] ?? '']]]],
    'Tanggal Kegiatan' => !empty($payload['tgl_kegiatan'])
        ? ['date' => ['start' => $payload['tgl_kegiatan']]]
        : ['date' => null],
    'Tanggal Pinjam'   => ['date'         => ['start' => $tglPinjam]],
    'Alat Dipinjam'    => ['multi_select' => array_values(array_map(fn($t) => ['name' => $t], $alatTags))],
    'Detail Alat'      => ['rich_text'    => [['text' => ['content' => $detailAlat]]]],
    'Durasi (jam)'     => ['number'       => floatval($payload['durasi'] ?? 0)],
    'Keterangan'       => ['rich_text'    => [['text' => ['content' => $payload['keterangan'] ?? '']]]],
    'Status'           => ['select'       => ['name' => $status]],
];
$properties = array_filter($properties, fn($v) => $v !== null);

logInfo('create-peminjaman', 'Mengirim POST ke Notion /pages (peminjaman baru)');
$body = ['parent' => ['database_id' => DATABASE_ID_PEMINJAMAN], 'properties' => $properties];
$res  = notionCurl('POST', 'https://api.notion.com/v1/pages', $body);
logNotionResponse('create-peminjaman', $res, 'POST /pages peminjaman');

if ($res['code'] === 200) {
    $pinjamId = $res['data']['id'];
    logInfo('create-peminjaman', "Peminjaman berhasil dibuat: page_id={$pinjamId}");

    if ($status === 'Dipinjam') {
        $relationIds = [];

        foreach ($alatPayload as $idx => $alatItem) {
            $name = trim($alatItem['alat'] ?? '');
            $tipe = trim($alatItem['tipe'] ?? $alatItem['jenis'] ?? '');
            $kat  = trim($alatItem['kategori'] ?? '');
            $qty  = max(1, (int)($alatItem['qty'] ?? 1));

            if (empty($name)) continue;

            logInfo('create-peminjaman', "Query inventory #{$idx}: name={$name} tipe={$tipe} kat={$kat} qty={$qty}");

            $filterAnd = [
                ["property" => "Status",    "select" => ["equals" => "Tersedia"]],
                ["property" => "Nama Alat", "title"  => ["contains" => $name]],
            ];
            if (!empty($kat)) {
                $filterAnd[] = ["property" => "Kategori", "select" => ["equals" => $kat]];
            }

            $invQuery       = notionCurl('POST',
                'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query',
                ["filter" => ["and" => $filterAnd], "page_size" => min($qty, 100)]
            );
            $matchedResults = $invQuery['data']['results'] ?? [];
            logInfo('create-peminjaman', "Inventory query #{$idx} → matched=" . count($matchedResults) . " code=" . $invQuery['code']);

            if (empty($matchedResults) && !empty($kat)) {
                logWarn('create-peminjaman', "Fallback query tanpa kategori untuk: {$name}");
                $invQueryFallback = notionCurl('POST',
                    'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query',
                    ["filter" => ["and" => [
                        ["property" => "Status",    "select" => ["equals" => "Tersedia"]],
                        ["property" => "Nama Alat", "title"  => ["contains" => $name]],
                    ]], "page_size" => min($qty, 100)]
                );
                $matchedResults = $invQueryFallback['data']['results'] ?? [];
                logInfo('create-peminjaman', "Fallback #{$idx} → matched=" . count($matchedResults));
            }

            foreach ($matchedResults as $invPage) {
                $relationIds[] = $invPage['id'];
            }
        }

        logInfo('create-peminjaman', "Total inventory ditemukan untuk direlasikan: " . count($relationIds));

        if (!empty($relationIds)) {
            batchPatchInventoryStatus($relationIds, 'Dipinjam');

            $relRes = notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $pinjamId, [
                'properties' => [
                    'Inventaris Terkait' => [
                        'relation' => array_map(fn($id) => ['id' => $id], $relationIds)
                    ]
                ]
            ]);
            logNotionResponse('create-peminjaman', $relRes, 'PATCH relasi inventaris');
        }
    }

    logInfo('create-peminjaman', '=== SELESAI (sukses) ===');
    echo json_encode(['success' => true, 'message' => 'Peminjaman dicatat & stok inventaris otomatis diperbarui!']);
} else {
    logError('create-peminjaman', [
        'msg'    => 'POST /pages peminjaman gagal',
        'code'   => $res['code'],
        'detail' => $res['data'],
        'payload_summary' => [
            'kegiatan'    => $kegiatan,
            'tgl_pinjam'  => $tglPinjam,
            'jumlah_alat' => count($alatPayload),
        ],
    ]);
    echo json_encode([
        'error'  => 'Gagal mencatat peminjaman',
        'detail' => $res['data'],
        'code'   => $res['code'],
    ]);
}