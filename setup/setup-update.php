<?php
/**
 * 1. SETUP SKEMA TABEL PEMINJAMAN (AUTO-RENAME TITLE)
 */
set_time_limit(60);
header('Content-Type: text/plain; charset=utf-8');

    $NOTION_TOKEN           = 'ntn_j76903444178v9FXe0iemukaBFghFWRt6gMYAwqvN9zc8g'; // Token Anda
    $DATABASE_ID_PEMINJAMAN = '3a01ca6157e180f5825ac169bd7f0f32'; // ID Table Peminjaman

if (strpos($NOTION_TOKEN, 'xxx') !== false) exit("ERROR: Isi Konfigurasi dulu!\n");

function notionCurl($method, $url, $body = []) {
    global $NOTION_TOKEN;
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $NOTION_TOKEN,
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

 $colors = ['gray','brown','orange','yellow','green','blue','purple','pink','red'];
function buildOptions(array $names, array $colors): array {
    $out = [];
    foreach ($names as $i => $name) $out[] = ['name' => $name, 'color' => $colors[$i % count($colors)]];
    return $out;
}

echo "=== [1/4] SETUP TABEL PEMINJAMAN ===\n";

// 1. Auto-rename Title bawaan Notion
 $skemaPeminjaman = [];
 $dbData = notionCurl('GET', 'https://api.notion.com/v1/databases/' . $DATABASE_ID_PEMINJAMAN);
if (isset($dbData['data']['properties'])) {
    foreach ($dbData['data']['properties'] as $name => $prop) {
        if ($prop['type'] === 'title' && $name !== 'Nama Kegiatan') {
            $skemaPeminjaman[$name] = ['name' => 'Nama Kegiatan', 'type' => 'title', 'title' => new stdClass()];
            echo "ℹ️ Rename kolom Title '$name' -> 'Nama Kegiatan'\n";
        }
    }
}

// 2. Skema Kolom Lainnya
 $picOptions = ['Alfian','Alif','Saiful','Yhanuar','Yayang','Yudha','Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Dayat','Winsa','Selvy'];
 $alatOptions = ['Mixer', 'Mic', 'Laptop', 'Tripod', 'Kamera', 'Kabel', 'Soundcard', 'Speaker', 'HT', 'Headphone', 'Modem', 'Video Switcher', 'Video Sender', 'Converter', 'Baterai', 'Lampu', 'Overlength', 'Earpik', 'PC Desktop', 'Monitor', 'Keyboard', 'Mouse', 'Audio Distribution', 'Stand Mic', 'Lainnya'];

 $skemaPeminjaman['Nama PIC PT']         = ['multi_select' => ['options' => buildOptions($picOptions, $colors)]];
 $skemaPeminjaman['Nama PIC Pengembali'] = ['rich_text' => new stdClass()]; // <-- KOLOM BARU (PIC Pengembali)
 $skemaPeminjaman['Lokasi']              = ['rich_text' => new stdClass()];
 $skemaPeminjaman['Tanggal Kegiatan']    = ['date' => new stdClass()];
 $skemaPeminjaman['Tanggal Pinjam']      = ['date' => new stdClass()];
 $skemaPeminjaman['Tanggal Kembali']     = ['date' => new stdClass()];
 $skemaPeminjaman['Alat Dipinjam']       = ['multi_select' => ['options' => buildOptions($alatOptions, $colors)]];
 $skemaPeminjaman['Detail Alat']         = ['rich_text' => new stdClass()];
 $skemaPeminjaman['Durasi (jam)']        = ['number' => ['format' => 'number']];
 $skemaPeminjaman['Keterangan']          = ['rich_text' => new stdClass()];
 $skemaPeminjaman['Status']              = ['select' => ['options' => [['name' => 'Dipinjam', 'color' => 'yellow'], ['name' => 'Dikembalikan', 'color' => 'green']]]];

// 3. Kirim Update
 $res = notionCurl('PATCH', 'https://api.notion.com/v1/databases/' . $DATABASE_ID_PEMINJAMAN, ['properties' => $skemaPeminjaman]);
if ($res['code'] >= 400) { echo "GAGAL: " . json_encode($res['data']) . "\n"; exit; }
echo "✅ Skema Peminjaman sukses diupdate.\n";