<?php
require_once __DIR__.'/../config/notion.php';

function getPeminjaman()
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_PEMINJAMAN . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $payload = json_encode([
        "sorts" => [
            ["property" => "Tanggal Pinjam", "direction" => "descending"]
        ]
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['results'])) {
        return ['error' => 'Invalid API response', 'raw' => $response];
    }

    $items = [];
    $today = date('Y-m-d');

    foreach ($data['results'] as $item) {
        $props = $item['properties'];

        $kegiatan = $props['Nama Kegiatan']['title'][0]['plain_text'] ?? 'Tanpa Nama';
        $pic      = $props['Nama PIC PT']['select']['name'] ?? '-';
        $lokasi   = $props['Lokasi']['rich_text'][0]['plain_text'] ?? '-';

        $tglKegiatan = $props['Tanggal Kegiatan']['date']['start'] ?? null;
        $tglPinjam   = $props['Tanggal Pinjam']['date']['start'] ?? null;
        $tglKembali  = $props['Tanggal Kembali']['date']['start'] ?? null;

        $alatOptions = $props['Alat Dipinjam']['multi_select'] ?? [];
        $alatArr     = array_map(fn($a) => $a['name'], $alatOptions);
        $alatDisplay = !empty($alatArr) ? implode(', ', $alatArr) : '-';

        $detailAlat = $props['Detail Alat']['rich_text'][0]['plain_text'] ?? '';
        $durasi     = $props['Durasi (jam)']['number'] ?? null;
        $keterangan = $props['Keterangan']['rich_text'][0]['plain_text'] ?? '';

        $statusRaw = $props['Status']['select']['name'] ?? 'Dipinjam';

        // Status "Terlambat" dihitung otomatis: masih Dipinjam tapi tanggal pinjam sudah lewat
        if ($statusRaw === 'Dikembalikan') {
            $status = 'Dikembalikan';
            $cls = 'dikembalikan';
        } elseif ($tglPinjam && $tglPinjam < $today) {
            $status = 'Terlambat';
            $cls = 'terlambat';
        } else {
            $status = 'Dipinjam';
            $cls = 'dipinjam';
        }

        $items[] = [
            'page_id'      => $item['id'],
            'url'          => $item['url'] ?? '#',
            'kegiatan'     => $kegiatan,
            'pic'          => $pic,
            'lokasi'       => $lokasi,
            'tgl_kegiatan' => $tglKegiatan,
            'tgl_pinjam'   => $tglPinjam,
            'tgl_kembali'  => $tglKembali,
            'alat_arr'     => $alatArr,
            'alat_display' => $alatDisplay,
            'detail_alat'  => $detailAlat,
            'durasi'       => $durasi,
            'keterangan'   => $keterangan,
            'status'       => $status,
            'cls'          => $cls,
        ];
    }

    return $items;
}