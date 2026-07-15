<?php
require_once __DIR__.'/../config/notion.php';

function getTroubleshoot()
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_TROUBLESHOOT . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Sorting by Submission time (descending - terbaru dulu)
    $payload = json_encode([
        "sorts" => [
            [
                "property" => "Submission time",
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

    $tickets = [];

    foreach ($data['results'] as $item) {
        $props = $item['properties'];
        
        // ID pendek untuk ditampilkan di tabel
        $id = substr($item['id'], 0, 6);
        
        // 2. Judul Kendala (dari property "Kendala" - tipe Title)
        $title = $props['Kendala']['title'][0]['plain_text'] ?? 'Tanpa Judul';
        
        // 3. Kategori/Bidang (dari property "Bidang" - tipe Multi Select)
        $catOptions = $props['Bidang']['multi_select'] ?? [];
        $cat = !empty($catOptions) ? $catOptions[0]['name'] : 'Umum';
        
        // 4. Tanggal Submission (dari property "Submission time" - tipe Created Time)
        $dateRaw = $props['Submission time']['created_time'] 
            ?? $props['Date']['date']['start'] 
            ?? date('Y-m-d');
        $date = date('d M Y', strtotime($dateRaw));
        
        // 5. PIC/Nama Petugas (dari property "Nama Petugas" - tipe Multi Select)
        $petugasOptions = $props['Nama Petugas']['multi_select'] ?? [];
        $petugasArr = array_map(fn($p) => $p['name'], $petugasOptions);
        $pic = !empty($petugasArr) ? implode(', ', $petugasArr) : 'Belum Ditentukan';
        
        // 6. Status berdasarkan ada/tidaknya Kesimpulan Penyelesaian
        $kesimpulan = $props['Kesimpulan Penyelesaian']['rich_text'][0]['plain_text'] ?? '';
        $status = !empty($kesimpulan) ? 'Selesai' : 'Proses';
        
        // 7. Kelas Warna untuk status
        $cls = $status === 'Selesai' ? 'green' : 'orange';

        $deskripsi = $props['Deskripsi Aktivitas Identifikasi']['rich_text'][0]['plain_text'] ?? '';

        $tickets[] = [
            'id'          => strtoupper($id),
            'page_id'     => $item['id'],                 // ID ASLI (UUID) — dipakai untuk edit & delete
            'url'         => $item['url'] ?? '#',
            'title'       => $title,
            'cat'         => $cat,
            'bidang_raw'  => $cat,                         // value asli buat pre-fill form edit
            'pic'         => $pic,                         // string gabungan buat tampilan tabel
            'petugas_arr' => $petugasArr,                  // array asli buat pre-check checkbox saat edit
            'status'      => $status,
            'date'        => $date,
            'cls'         => $cls,
            'deskripsi'   => $deskripsi,
            'kesimpulan'  => $kesimpulan,
        ];
    }

    return $tickets;
}