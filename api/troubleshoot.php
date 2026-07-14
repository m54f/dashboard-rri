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
        
        // 1. ID - gunakan 6 karakter pertama dari page ID Notion
        $id = substr($item['id'], 0, 6);
        
        // 2. Judul Kendala (dari property "Kendala" - tipe Title)
        $title = $props['Kendala']['title'][0]['plain_text'] ?? 'Tanpa Judul';
        
        // 3. Kategori/Bidang (dari property "Bidang" - tipe Select)
        $cat = $props['Bidang']['select']['name'] ?? 'Umum';
        
        // 4. Tanggal Submission (dari property "Submission time" - tipe Date)
        $dateRaw = $props['Submission time']['date']['start'] ?? date('Y-m-d');
        $date = date('d M Y', strtotime($dateRaw));
        
        // 5. PIC/Nama Petugas (dari property "Nama Petugas" - tipe People)
        $pic = 'Belum Ditentukan';
        if (isset($props['Nama Petugas']['people']) && count($props['Nama Petugas']['people']) > 0) {
            $pic = $props['Nama Petugas']['people'][0]['name'];
        }
        
        // 6. Status - karena di data Excel tidak ada kolom status eksplisit,
        //    kita bisa menggunakan kesimpulan penyelesaian sebagai acuan
        //    Jika ada "Kesimpulan Penyelesaian", anggap "Selesai", jika tidak "Proses"
        $kesimpulan = $props['Kesimpulan Penyelesaian']['rich_text'][0]['plain_text'] ?? '';
        $status = !empty($kesimpulan) ? 'Selesai' : 'Proses';
        
        // 7. Kelas Warna untuk status
        $cls = 'orange'; // Default untuk 'Proses'
        if ($status === 'Selesai') {
            $cls = 'green';
        }

        $tickets[] = [
            'id'     => strtoupper($id),
            'title'  => $title,
            'cat'    => $cat,
            'pic'    => $pic,
            'status' => $status,
            'date'   => $date,
            'cls'    => $cls,
            // Data tambahan yang bisa digunakan nanti
            'deskripsi' => $props['Deskripsi Aktivitas Identifikasi']['rich_text'][0]['plain_text'] ?? '',
            'kesimpulan' => $kesimpulan,
        ];
    }

    return $tickets;
}