<?php
require_once __DIR__.'/../config/notion.php';

function getJurnal()
{
    // Pastikan konstan DATABASE_ID_JURNAL sudah kamu buat di config/notion.php
    // Atau jika kamu menggunakan variabel, ubah menjadi global $database_id_jurnal;
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_KEGIATAN . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Sorting by Date (descending - terbaru dulu)
    $payload = json_encode([
        "page_size" => 50,
        "sorts" => [
            [
                "property" => "Date",
                "direction" => "descending"
            ]
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

    $entries = [];

    foreach ($data['results'] as $item) {
        $props = $item['properties'];
        
        // 1. ID Unik
        $full_id = $item['id'];
        $id = strtoupper(substr(str_replace('-', '', $full_id), 0, 6));
        $url = $item['url'] ?? '#';
        
        // 2. Judul Kegiatan
        $title = $props['Nama Kegiatan']['title'][0]['plain_text'] ?? 'Tanpa Judul';
        
        // 3. Tanggal
        $date_str = $props['Date']['date']['start'] ?? '';
        $date = $date_str ? date('d M Y', strtotime($date_str)) : '-';
        $date_raw = $date_str ? date('Y-m-d\TH:i', strtotime($date_str)) : ''; 
        
        // 4. Kategori / Jenis Kegiatan
        $tags_arr = [];
        if (!empty($props['Jenis Kegiatan']['multi_select'])) {
            foreach ($props['Jenis Kegiatan']['multi_select'] as $sel) { $tags_arr[] = $sel['name']; }
        }
        $tag = !empty($tags_arr) ? strtoupper($tags_arr[0]) : 'KEGIATAN';

        // 5. PIC / Petugas
        $ops_arr = [];
        if (!empty($props['Petugas']['multi_select'])) {
            foreach ($props['Petugas']['multi_select'] as $op_sel) { $ops_arr[] = $op_sel['name']; }
        }
        $op = !empty($ops_arr) ? implode(', ', $ops_arr) : '-';
        
        // 6. Status
        $status = 'Selesai';
        if (isset($props['Status']['select']['name'])) $status = $props['Status']['select']['name'];
        elseif (isset($props['Status']['status']['name'])) $status = $props['Status']['status']['name'];
        
        // 7. Link Eviden 3
        $eviden_3 = $props['Eviden 3 (Link)']['url'] ?? '';

        // 8. Dokumentasi (Gambar)
        $docs = [];
        if (isset($props['Eviden']['files'])) {
            foreach($props['Eviden']['files'] as $f) {
                if(isset($f['file']['url'])) $docs[] = $f['file']['url'];
                elseif(isset($f['external']['url'])) $docs[] = $f['external']['url'];
            }
        }

        $entries[] = [ 
            'id' => $id, 
            'full_id' => $full_id, 
            'title' => $title, 
            'tag' => $tag, 
            'tags_arr' => $tags_arr, 
            'op' => $op, 
            'ops_arr' => $ops_arr, 
            'date' => $date, 
            'date_raw' => $date_raw, 
            'url' => $url, 
            'docs' => $docs, 
            'status' => $status, 
            'eviden_3' => $eviden_3 
        ];
    }

    return $entries;
}
?>