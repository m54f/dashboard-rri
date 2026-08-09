<?php
/**
 * SEED DATABASE INVENTARIS STUDIO LENGKAP KE NOTION
 * ==================================================
 * Script ini meng-clear database inventaris yang ada di Notion,
 * kemudian memasukkan seluruh data inventaris studio (Gudang Musik 1,
 * Detail Rak & Kabel, Lemari Besi/Kayu, Studio PRO 1, PRO 2, PRO 4, dan Status Khusus)
 * sesuai dengan skema kolom Notion:
 * - Nama Alat (title)
 * - Kode (rich_text)
 * - Type (rich_text)
 * - Kategori (select)
 * - Qty (number)
 * - Lokasi (select)
 * - Status (select)
 * - Keterangan (rich_text)
 */

set_time_limit(0); // Tanpa batas waktu execution
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/notion.php';

function notionCurl($method, $url, $body = []) {
    $token = defined('NOTION_TOKEN') ? NOTION_TOKEN : '';
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json',
        ],
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if (!empty($body)) $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    } elseif ($method === 'PATCH') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        if (!empty($body)) $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

$dbId = defined('DATABASE_ID_INVENTARIS') ? DATABASE_ID_INVENTARIS : '';

echo "=== [1/2] MENGHAPUS DATA INVENTARIS LAMA ===\n";
$hasMore = true;
$nextCursor = null;
$totalDeleted = 0;

while ($hasMore) {
    $queryBody = ["page_size" => 100];
    if ($nextCursor) $queryBody["start_cursor"] = $nextCursor;

    $res = notionCurl('POST', 'https://api.notion.com/v1/databases/' . $dbId . '/query', $queryBody);
    if ($res['code'] >= 400) {
        echo "Gagal mengambil data lama: " . json_encode($res['data']) . "\n";
        break;
    }

    $pages = $res['data']['results'] ?? [];
    $hasMore = $res['data']['has_more'] ?? false;
    $nextCursor = $res['data']['next_cursor'] ?? null;

    if (count($pages) === 0) break;

    foreach ($pages as $p) {
        notionCurl('PATCH', 'https://api.notion.com/v1/pages/' . $p['id'], ['archived' => true]);
        $totalDeleted++;
        usleep(150000);
    }
}
echo "Total data lama di-archive: {$totalDeleted}\n\n";

echo "=== [2/2] MEMASUKKAN SEED DATA INVENTARIS STUDIO ===\n";

$seedData = [
    // 1. GUDANG MUSIK 1 (PERALATAN UMUM)
    ['kode' => 'GM-001', 'nama' => 'Tripod Besar', 'type' => '2 ada tasnya', 'kategori' => 'Tripod', 'lokasi' => 'Gudang Musik 1', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-002', 'nama' => 'Tripod Kecil', 'type' => 'Bertas', 'kategori' => 'Tripod', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-003', 'nama' => 'Stand Lampu', 'type' => 'Stand', 'kategori' => 'Lighting', 'lokasi' => 'Gudang Musik 1', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-004', 'nama' => 'Over Length', 'type' => 'Isi 5', 'kategori' => 'Power', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-005', 'nama' => 'Over Length', 'type' => 'Isi 6', 'kategori' => 'Power', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-006', 'nama' => 'Over Length', 'type' => 'Isi 4', 'kategori' => 'Power', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-007', 'nama' => 'Over Length', 'type' => 'Isi 3', 'kategori' => 'Power', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-008', 'nama' => 'Over Length', 'type' => 'MCB Kayu', 'kategori' => 'Power', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-009', 'nama' => 'Speaker Kecil', 'type' => 'Standar', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],

    // Lemari Besi (Gudang Musik 1)
    ['kode' => 'GM-LB01', 'nama' => 'Kabel Roll Audio', 'type' => 'Kecil 20m', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 7, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB02', 'nama' => 'Kamera', 'type' => 'PXW', 'kategori' => 'Kamera', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB03', 'nama' => 'Kamera', 'type' => 'MD', 'kategori' => 'Kamera', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB04', 'nama' => 'Kamera', 'type' => 'Pak Pirin', 'kategori' => 'Kamera', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB05', 'nama' => 'Baterai', 'type' => 'Kamera MD', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB06', 'nama' => 'Baterai', 'type' => 'Kecil Sony NP-F570 (MC2500)', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB07', 'nama' => 'Baterai', 'type' => 'Besar Sony 1997 (MC2500)', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB08', 'nama' => 'Video Sender', 'type' => 'Lama (Keroncong)', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB09', 'nama' => 'Laptop', 'type' => 'MSI', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB10', 'nama' => 'Laptop', 'type' => 'TUF', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB11', 'nama' => 'Mouse', 'type' => 'Wireless Logitech', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB12', 'nama' => 'Flashdisk', 'type' => 'Sandisk 64 GB', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB13', 'nama' => 'Modem', 'type' => 'Orbit', 'kategori' => 'Jaringan', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB14', 'nama' => 'Adaptor', 'type' => '5V', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB15', 'nama' => 'Converter', 'type' => 'VGA to HDMI', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB16', 'nama' => 'Converter', 'type' => 'HDMI to USB', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB17', 'nama' => 'Capture Card', 'type' => 'Ezcape', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB18', 'nama' => 'Video Switcher', 'type' => 'Avmatrix', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB19', 'nama' => 'Soundcard', 'type' => 'Behringer', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB20', 'nama' => 'Box Baterai', 'type' => 'Dummy Kingma', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB21', 'nama' => 'Box Video Sender', 'type' => 'Hollyland', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB22', 'nama' => 'Box Mic Clip On', 'type' => 'Saramonic', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB23', 'nama' => 'Box Splitter', 'type' => 'HDMI Splitter', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB24', 'nama' => 'Box Converter', 'type' => 'Avmatrix Converter', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB25', 'nama' => 'Lampu', 'type' => 'LED Kotak', 'kategori' => 'Lighting', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB26', 'nama' => 'Lampu', 'type' => 'Godox', 'kategori' => 'Lighting', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB27', 'nama' => 'Mouse', 'type' => 'Kabel', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB28', 'nama' => 'Mouse', 'type' => 'Wireless Tanpa Dongle', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LB29', 'nama' => 'Tas', 'type' => 'Laptop', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],

    // Lemari Kayu Kiri (Gudang Musik 1)
    ['kode' => 'GM-LK01', 'nama' => 'HT', 'type' => 'WLN Kecil', 'kategori' => 'Komunikasi', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 6, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK02', 'nama' => 'HT', 'type' => 'Antena Alinco', 'kategori' => 'Komunikasi', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK03', 'nama' => 'Tas Charge HT', 'type' => '+ Earpic (6)', 'kategori' => 'Komunikasi', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK04', 'nama' => 'Mixer', 'type' => '8 Channel Ashley', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK05', 'nama' => 'Kamera', 'type' => 'Besar Sony MC2500', 'kategori' => 'Kamera', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK06', 'nama' => 'Box Mic Wireless', 'type' => 'Primatech', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK07', 'nama' => 'Box Mic Wireless', 'type' => 'Shure Baru', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK08', 'nama' => 'Box Mic Clip On', 'type' => 'Shure (Isi 1)', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK09', 'nama' => 'Box Mic Clip On', 'type' => 'Ashley (Isi 2)', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK10', 'nama' => 'Box Mixer', 'type' => 'Digital', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK11', 'nama' => 'Koper Mic Wireless', 'type' => 'Shure Lama', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK12', 'nama' => 'Kabel Roll', 'type' => 'Biru', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK13', 'nama' => 'Kabel Roll', 'type' => 'Audio Orange', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK14', 'nama' => 'Koper Mic Kabel', 'type' => 'Isi 13', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-LK15', 'nama' => 'Koper Mic Kabel', 'type' => 'Lama Isi 6', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Kayu Kiri', 'qty' => 1, 'status' => 'Rusak', 'catatan' => 'Tidak dipakai'],

    // Headphone & Misc (Gudang Musik 1)
    ['kode' => 'GM-HP01', 'nama' => 'Headphone', 'type' => 'AKG K77', 'kategori' => 'Audio', 'lokasi' => 'Pemancar Ngadirejo', 'qty' => 1, 'status' => 'Dipinjam', 'catatan' => 'Dibawa ke pemancar Ngadirejo beserta conektornya'],
    ['kode' => 'GM-HP02', 'nama' => 'Headphone', 'type' => 'AKG K77', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-HP03', 'nama' => 'Headphone', 'type' => 'Sennheiser', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-HP04', 'nama' => 'Headphone', 'type' => 'ATH AVC200', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-MISC01', 'nama' => 'Power Soundtrack', 'type' => 'Standar', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1', 'qty' => 6, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-MISC02', 'nama' => 'Kabel SDI', 'type' => 'Pendek', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-MISC03', 'nama' => 'Kabel HDMI', 'type' => 'Mini Kamera', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1', 'qty' => 5, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'GM-MISC04', 'nama' => 'Kabel HDMI', 'type' => 'Pendek', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1', 'qty' => 6, 'status' => 'Tersedia', 'catatan' => ''],

    // 2. MUSIK 1 (DETAIL RAK & KABEL)
    ['kode' => 'KB-RK1-01', 'nama' => 'Kabel Male Female', 'type' => 'Sangat Panjang (Hitam)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'KB-RK1-02', 'nama' => 'Kabel Male Female', 'type' => 'Panjang (Hitam)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'KB-RK2-01', 'nama' => 'Kabel Power', 'type' => 'Hitam', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 17, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'KB-RK2-02', 'nama' => 'Kabel Power', 'type' => 'Putih', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'KB-RK2-03', 'nama' => 'Kabel Power', 'type' => 'Abu-abu', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'KB-RK3-01', 'nama' => 'Kabel Male Female', 'type' => 'Keroncong (Hitam 9, Merah 1, Coklat 2, Ungu 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 13, 'status' => 'Tersedia', 'catatan' => 'Rak 3 Keroncong'],
    ['kode' => 'KB-RK3-02', 'nama' => 'Kabel TS-Male', 'type' => 'Keroncong (Merah 3, Biru 1, Hijau 1, Orange 1, Hitam 3)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 9, 'status' => 'Tersedia', 'catatan' => 'Rak 3 Keroncong'],
    ['kode' => 'KB-RK4-01', 'nama' => 'Kabel TS-Male', 'type' => '(Biru 3, Hitam 6, Merah 1, Coklat 1, Putih 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 12, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'KB-RK4-02', 'nama' => 'Kabel TS-Female', 'type' => '(Coklat 1, Hitam 4)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 5, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'KB-RK4-03', 'nama' => 'Kabel TS-TS', 'type' => 'Sedang (Biru 1, Hitam 2, Orange 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'KB-RK4-04', 'nama' => 'Kabel TS-TS', 'type' => 'Panjang (Hitam 1, Biru 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'KB-RK5-01', 'nama' => 'Kabel LAN', 'type' => 'Panjang (Biru 2, Abu 2)', 'kategori' => 'Jaringan', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'KB-RK5-02', 'nama' => 'Kabel LAN', 'type' => '(Putih 1, Abu Muda 2, Abu Tua 1 CCTV, Kuning 3)', 'kategori' => 'Jaringan', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 7, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'KB-RK5-03', 'nama' => 'Kabel VGA', 'type' => '(Panjang 3, Pendek 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'KB-RK6-01', 'nama' => 'Kabel SDI', 'type' => 'Panjang (Hitam 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 6'],
    ['kode' => 'KB-RK6-02', 'nama' => 'Kabel SDI', 'type' => 'Sedang (Abu 2, Hijau 1, Hitam 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 6'],
    ['kode' => 'KB-RK6-03', 'nama' => 'Kabel HDMI', 'type' => '(Hitam 1, Abu 1, Merah 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Kabel', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => 'Rak 6'],

    // Kabel Dinding
    ['kode' => 'KB-DD-01', 'nama' => 'Kabel Male Female', 'type' => 'Dinding (Hitam 5, Merah 3, Orange 1, Coklat 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 10, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'KB-DD-02', 'nama' => 'Kabel TS-TS', 'type' => 'Dinding (Hitam 15, Coklat 2, Merah 2, Marron 2)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 21, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'KB-DD-03', 'nama' => 'Kabel Male to Male', 'type' => 'Dinding (Biru 1, Hijau 1, Kuning 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'KB-DD-04', 'nama' => 'Kabel Female to Female', 'type' => 'Dinding (Kuning 1, Hitam 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'KB-DD-05', 'nama' => 'Kabel Male to TS', 'type' => 'Dinding (Orange 1, Hitam 1)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'KB-DD-06', 'nama' => 'Kabel Female to TS', 'type' => 'Dinding (Merah 1, Hitam 1, Biru 2)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'KB-DD-07', 'nama' => 'Kabel & Adaptor Audio', 'type' => 'Dinding (RCA, 3.5mm, VGA, Lemo)', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Dinding', 'qty' => 17, 'status' => 'Tersedia', 'catatan' => 'RCA-3.5 (2), 3.5-3.5 (3), 3.5-female (1), VGA-female (1), RCA-male (3), RCA-RCA (2), 3.5-TS (2), Lemo-Lemo (1), Lemo-TS (2), Lemo-Male (1), 3.5-double TS (1)'],

    // Lemari Besi Podcast
    ['kode' => 'LB-POD-00', 'nama' => 'Kabel Roll Audio', 'type' => 'Male Female', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 14, 'status' => 'Tersedia', 'catatan' => 'Diatas Rak'],
    ['kode' => 'LB-POD-11', 'nama' => 'Kamera', 'type' => 'PXW', 'kategori' => 'Kamera', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-12', 'nama' => 'Kamera', 'type' => 'Mirrorless', 'kategori' => 'Kamera', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-13', 'nama' => 'Baterai', 'type' => 'Mirrorless', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-14', 'nama' => 'Baterai', 'type' => 'NP-F570', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-15', 'nama' => 'Baterai', 'type' => 'Sony 1997', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-16', 'nama' => 'Kabel HDMI', 'type' => 'Pendek', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 5, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-17', 'nama' => 'Power Behringer', 'type' => 'Soundtrack', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 6, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-18', 'nama' => 'Kabel SDI', 'type' => 'Standar', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 1'],
    ['kode' => 'LB-POD-21', 'nama' => 'Laptop', 'type' => 'MSI (+ Charger)', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-22', 'nama' => 'Laptop', 'type' => 'TUF (+ Charger)', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-23', 'nama' => 'Mouse', 'type' => 'Wireless Logitech M170', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-24', 'nama' => 'Mouse', 'type' => 'Wireless Mikuso', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-25', 'nama' => 'Modem', 'type' => 'Orbit', 'kategori' => 'Jaringan', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-26', 'nama' => 'Flashdisk', 'type' => 'Sandisk 64 GB', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-27', 'nama' => 'Converter', 'type' => 'HDMI to USB (Ezcape/Capture)', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-28', 'nama' => 'Converter', 'type' => 'VGA to HDMI', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-29', 'nama' => 'Converter', 'type' => 'SDI to HDMI', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 2'],
    ['kode' => 'LB-POD-31', 'nama' => 'Video Sender', 'type' => 'Hollyland (+ Transmitter)', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-32', 'nama' => 'Baterai', 'type' => 'Dummy Kingma', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-33', 'nama' => 'Mic Clip On', 'type' => 'Saramonic', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-34', 'nama' => 'Converter', 'type' => 'Video Mini', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-35', 'nama' => 'Splitter', 'type' => 'HDMI', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-36', 'nama' => 'Kabel VGA', 'type' => 'Standar', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-37', 'nama' => 'Video Switcher', 'type' => 'Avmatrix', 'kategori' => 'Video', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-38', 'nama' => 'Soundcard', 'type' => 'Behringer', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-39', 'nama' => 'Kabel HDMI', 'type' => 'Mini Mirrorless', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-40', 'nama' => 'Kabel Avmatrix', 'type' => 'Standar', 'kategori' => 'Kabel', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 3'],
    ['kode' => 'LB-POD-41', 'nama' => 'Proyektor', 'type' => 'Epson', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'LB-POD-42', 'nama' => 'Lampu', 'type' => 'LED Kotak (+ Kabel)', 'kategori' => 'Lighting', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'LB-POD-43', 'nama' => 'Lampu', 'type' => 'Godox (+ Power Godog)', 'kategori' => 'Lighting', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'LB-POD-44', 'nama' => 'Tripod', 'type' => 'Gimbal', 'kategori' => 'Tripod', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 4'],
    ['kode' => 'LB-POD-51', 'nama' => 'Keyboard', 'type' => 'Wireless', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'LB-POD-52', 'nama' => 'Keyboard', 'type' => 'Kabel', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'LB-POD-53', 'nama' => 'Mouse', 'type' => 'Kabel', 'kategori' => 'Komputer', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'LB-POD-54', 'nama' => 'Tas', 'type' => 'Laptop', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'LB-POD-55', 'nama' => 'Baterai', 'type' => 'Rechargeable Energizer AA (+ Charger)', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 12, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],
    ['kode' => 'LB-POD-56', 'nama' => 'Baterai', 'type' => 'Krisbow DR20', 'kategori' => 'Aksesoris', 'lokasi' => 'Gudang Musik 1 - Lemari Besi Podcast', 'qty' => 12, 'status' => 'Tersedia', 'catatan' => 'Rak 5'],

    // 3. PERALATAN STUDIO PRO 1
    ['kode' => 'P1-PC01', 'nama' => 'PC Desktop', 'type' => 'AIRCAST i5-10400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'DESKTOP-4MPRE8V (IP: 192.168.50.16)'],
    ['kode' => 'P1-PC02', 'nama' => 'PC Desktop', 'type' => 'OBS i5-3470', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'DESKTOP-DRN7AON (IP: 192.168.60.15)'],
    ['kode' => 'P1-PC03', 'nama' => 'PC Desktop', 'type' => 'PA i5-12400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'DESKTOP-4APVHOL'],
    ['kode' => 'P1-MON01', 'nama' => 'Monitor PC', 'type' => 'Lenovo L22e-40 / ASUS VS207HR / AOC E970S / BenQ G61SHDPL', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 1', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => '4 unit monitor'],
    ['kode' => 'P1-KB01', 'nama' => 'Keyboard', 'type' => 'Logitech K120 / Fantech KM103 / A4Tech KM720', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 1', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => '3 unit'],
    ['kode' => 'P1-MS01', 'nama' => 'Mouse', 'type' => 'Logitech M100R / Fantech KM103', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 1', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => '3 unit'],
    ['kode' => 'P1-MIX01', 'nama' => 'Mixer', 'type' => 'AURON AMU-16-2 (Digital)', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'NUP 12'],
    ['kode' => 'P1-HS01', 'nama' => 'Headset', 'type' => 'Sennheiser HD569 / Mackie MC-100 / AKG K77', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 5, 'status' => 'Tersedia', 'catatan' => 'Penyiar HD569 (NUP 84), Narasumber (NUP 85, 86, Mackie NUP 4), Gatekeeper AKG K77'],
    ['kode' => 'P1-MIC01', 'nama' => 'Microphone', 'type' => 'Shure SM7B (Penyiar)', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Shure SM7B'],
    ['kode' => 'P1-MIC02', 'nama' => 'Microphone', 'type' => 'AKG D321 / Sennheiser E835 / Shure SM58', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => '3 Narasumber, 1 Gatekeeper'],
    ['kode' => 'P1-DOD01', 'nama' => 'Audio Distribution', 'type' => 'McLELLAND MD-50', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P1-SPK01', 'nama' => 'Speaker Monitor', 'type' => 'Peer Tronic Sicherung 0.2 A/T', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P1-CAM01', 'nama' => 'Camera', 'type' => 'Logitech Brio 500', 'kategori' => 'Kamera', 'lokasi' => 'Studio PRO 1', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'NUP 1, 2'],
    ['kode' => 'P1-TRI01', 'nama' => 'Tripod & Stand', 'type' => 'Ulazi MT44 & Stand Besi', 'kategori' => 'Tripod', 'lokasi' => 'Studio PRO 1', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Ulazi NUP 5'],

    // STUDIO PRO 2
    ['kode' => 'P2-PC01', 'nama' => 'PC Desktop', 'type' => 'OBS DELL i5-8400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 2', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'IP: 192.168.50.34:8080/app/'],
    ['kode' => 'P2-PC02', 'nama' => 'PC Desktop', 'type' => 'AIRCAST i5-10400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 2', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'IP: 192.168.60.35'],
    ['kode' => 'P2-PC03', 'nama' => 'PC Desktop', 'type' => 'Cadangan DELL i5-8400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 2', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P2-MON01', 'nama' => 'Monitor PC', 'type' => 'DELL E2016HV (2x) & ASUS VS207 (1x)', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 2', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P2-MS01', 'nama' => 'Mouse', 'type' => 'DELL MS116T1 / KB216T / Logitech M100R', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 2', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P2-KB01', 'nama' => 'Keyboard', 'type' => 'ASUS PK1100U / DELL MS116T1 / KB216T', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 2', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P2-MIX01', 'nama' => 'Mixer', 'type' => 'Axel Technology Oxygen 3000 (Analog)', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 2', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'NUP 6'],
    ['kode' => 'P2-HS01', 'nama' => 'Headset', 'type' => 'ATH-M20X / Mackie MC-100 / AKG K77 / HD569', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 2', 'qty' => 5, 'status' => 'Tersedia', 'catatan' => 'Penyiar ATK-M20X, Narasumber Mackie (NUP 7), AKG K77 (2), HD569 (NUP 87)'],
    ['kode' => 'P2-MIC01', 'nama' => 'Microphone', 'type' => 'Shure SM58', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 2', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Narasumber'],
    ['kode' => 'P2-DOD01', 'nama' => 'Audio Distribution', 'type' => 'Behringer Power Play Pro-8', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 2', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P2-SPK01', 'nama' => 'Speaker Monitor', 'type' => 'Peer Tronic Sicherung 0.2 A/T', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 2', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P2-CAM01', 'nama' => 'Camera', 'type' => 'Logitech Brio 500', 'kategori' => 'Kamera', 'lokasi' => 'Studio PRO 2', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'NUP 3, 4'],
    ['kode' => 'P2-TRI01', 'nama' => 'Tripod', 'type' => 'Ulazi MT44', 'kategori' => 'Tripod', 'lokasi' => 'Studio PRO 2', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'NUP 7'],

    // STUDIO PRO 4
    ['kode' => 'P4-PC01', 'nama' => 'PC Desktop', 'type' => 'AIRCAST i5-10400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 4', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'IP: 192.168.50.45:8080/app/'],
    ['kode' => 'P4-PC02', 'nama' => 'PC Desktop', 'type' => 'Cadangan ASUS i3-4170', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 4', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'IP: 192.168.60.46'],
    ['kode' => 'P4-PC03', 'nama' => 'PC Desktop', 'type' => 'OBS DELL i5-8400', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 4', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P4-MON01', 'nama' => 'Monitor PC', 'type' => 'LG 19EN33SA (1x) & ASUS VS197 (2x)', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 4', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P4-KB01', 'nama' => 'Keyboard', 'type' => 'ASUS PK1100U / Logitech K120 / DELL KB216T', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 4', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P4-MS01', 'nama' => 'Mouse', 'type' => 'A4TECH OP-6200 / ASUS MOBTUOA / DELL MS116T1', 'kategori' => 'Komputer', 'lokasi' => 'Studio PRO 4', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P4-MIX01', 'nama' => 'Mixer', 'type' => 'AURON AMU-16-2 (Digital)', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 4', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'NUP 13'],
    ['kode' => 'P4-HS01', 'nama' => 'Headset', 'type' => 'ATH-AVC200 / Mackie MC-100 / AKG K77', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 4', 'qty' => 4, 'status' => 'Tersedia', 'catatan' => 'Penyiar ATH-AVC200, Narasumber Mackie NUP 3, AKG K77 (2)'],
    ['kode' => 'P4-MIC01', 'nama' => 'Microphone', 'type' => 'Shure SM7B (Penyiar)', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 4', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => 'Penyiar'],
    ['kode' => 'P4-MIC02', 'nama' => 'Microphone', 'type' => 'AKG D5 (2x) & Sennheiser MD421', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 4', 'qty' => 3, 'status' => 'Tersedia', 'catatan' => 'Narasumber AKG D5 (NUP 108, 109), Sennheiser MD421'],
    ['kode' => 'P4-DOD01', 'nama' => 'Audio Distribution', 'type' => 'Behringer Power Play Pro-8', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 4', 'qty' => 1, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P4-SPK01', 'nama' => 'Speaker Monitor', 'type' => 'Road Master Gong 107 Series', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 4', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'P4-CAM01', 'nama' => 'Camera', 'type' => 'Logitech Brio 500', 'kategori' => 'Kamera', 'lokasi' => 'Studio PRO 4', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'NUP 5, 6'],

    // 4. STATUS & CATATAN KHUSUS
    ['kode' => 'ST-001', 'nama' => 'Mixer', 'type' => '4 Channel', 'kategori' => 'Audio', 'lokasi' => 'Pemancar Ngijo', 'qty' => 1, 'status' => 'Dipinjam', 'catatan' => 'Ditinggal di Pemancar Ngijo'],
    ['kode' => 'ST-002', 'nama' => 'Microphone', 'type' => 'Kabel', 'kategori' => 'Audio', 'lokasi' => 'Gudang Musik 1', 'qty' => 1, 'status' => 'Dalam Perbaikan', 'catatan' => 'Masuk bengkel karena rusak'],
    ['kode' => 'ST-003', 'nama' => 'Stand Mic', 'type' => 'Kecil', 'kategori' => 'Tripod', 'lokasi' => 'Gudang Musik 1', 'qty' => 5, 'status' => 'Tersedia', 'catatan' => ''],
    ['kode' => 'ST-004', 'nama' => 'Speaker', 'type' => 'Huper', 'kategori' => 'Audio', 'lokasi' => 'Studio PRO 1', 'qty' => 2, 'status' => 'Tersedia', 'catatan' => 'Berada di Studio 1'],
    ['kode' => 'ST-005', 'nama' => 'Stand Mic', 'type' => 'Besar', 'kategori' => 'Tripod', 'lokasi' => 'Studio PRO 1', 'qty' => 6, 'status' => 'Tersedia', 'catatan' => 'Berada di Studio 1'],
];

$total = count($seedData);
$success = 0;
$fail = 0;

foreach ($seedData as $i => $item) {
    $props = [
        'Nama Alat'  => ['title' => [['text' => ['content' => $item['nama']]]]],
        'Kode'       => ['rich_text' => [['text' => ['content' => $item['kode']]]]],
        'Type'       => ['rich_text' => [['text' => ['content' => $item['type']]]]],
        'Kategori'   => ['select' => ['name' => $item['kategori']]],
        'Qty'        => ['number' => (int)$item['qty']],
        'Lokasi'     => ['select' => ['name' => $item['lokasi']]],
        'Status'     => ['select' => ['name' => $item['status']]],
    ];

    if (!empty($item['catatan'])) {
        $props['Keterangan'] = ['rich_text' => [['text' => ['content' => $item['catatan']]]]];
    }

    $pageBody = [
        'parent' => ['database_id' => $dbId],
        'properties' => $props
    ];

    $res = notionCurl('POST', 'https://api.notion.com/v1/pages', $pageBody);

    if ($res['code'] === 200) {
        $success++;
        echo "  (" . ($i+1) . "/{$total}) OK: {$item['nama']} [{$item['type']}]\n";
    } else {
        $fail++;
        echo "  (" . ($i+1) . "/{$total}) GAGAL: {$item['nama']} | " . json_encode($res['data']) . "\n";
    }
    usleep(100000); // 100ms delay
}

echo "\n=== SELESAI SEEDING INVENTARIS STUDIO ===\n";
echo "Total Diproses : {$total}\n";
echo "Berhasil       : {$success}\n";
echo "Gagal          : {$fail}\n";
