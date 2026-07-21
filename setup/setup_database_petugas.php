 <?php
 $notionToken = 'ntn_j76903444178v9FXe0iemukaBFghFWRt6gMYAwqvN9zc8g';
 $databaseId  = '3a31ca6157e18025856cf0d403573ec5'; 

 $headers = [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
];

echo "<h3>Memulai Setup Tabel Notion...</h3>";
// ==========================================
// TAHAP 1: TAMBAHKAN KOLOM (PROPERTIES) YANG BELUM ADA
// ==========================================
echo "Mengecek dan menambahkan kolom...<br>";

// Kita hanya mengirim kolom baru, kolom 'Name' tidak perlu dikirim karena sudah ada
 $updatePayload = [
    'properties' => [
        'NO' => ['number' => ['format' => 'number']],
        'NIP/NIPPK/NRP' => ['rich_text' => (object)[]], // (object)[] menghasilkan {} di JSON
        'JABATAN' => ['select' => ['options' => []]],
        'Posisi' => ['select' => ['options' => []]],
        'PANGKAT/GOL.RUANG' => ['select' => ['options' => []]]
    ]
];

 $ch = curl_init("https://api.notion.com/v1/databases/{$databaseId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

 $response = curl_exec($ch);
 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Gagal menambahkan kolom. Error: <pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}
echo "<span style='color:green'>✅ Kolom berhasil ditambahkan/diperbarui!</span><br><br>";
// ==========================================
// TAHAP 2: ISI DATA SEEDER
// ==========================================
echo "<h3>Mengisi Data Seeder...</h3>";

 $dataKaryawan = [
    ["NO" => 1, "NAMA" => "M. Ludfi Mashadi, S.AP", "NIP" => "198405122008011005", "JABATAN" => "Asisten Teknisi Siaran Pemula", "POSISI" => "Koordinator", "PANGKAT" => "V / F"],
    ["NO" => 2, "NAMA" => "Muhammad Qomarudin", "NIP" => "197002151995031002", "JABATAN" => "Penata Muda", "POSISI" => "Studio", "PANGKAT" => "III / a"],
    ["NO" => 3, "NAMA" => "Saiful Anam", "NIP" => "1990010100000000", "JABATAN" => "Asisten Teknisi Siaran", "POSISI" => "Transmitter", "PANGKAT" => "IX"],
    ["NO" => 4, "NAMA" => "Dedy Yulianto", "NIP" => "1988010100000000", "JABATAN" => "Penonatur", "POSISI" => "Auditorium", "PANGKAT" => "II / c"],
    ["NO" => 5, "NAMA" => "Anggi Pratama", "NIP" => "1995010100000000", "JABATAN" => "Pranata Komputer", "POSISI" => "IT Support", "PANGKAT" => "III / b"],
];

 $successCount = 0;
 $failCount = 0;

foreach ($dataKaryawan as $karyawan) {
    // Perhatikan bahwa kita menggunakan 'Name' (sesuai dengan kolom di Notion Anda)
    $pagePayload = [
        'parent' => ['database_id' => $databaseId],
        'properties' => [
            'Name' => [
                'title' => [['text' => ['content' => $karyawan['NAMA']]]]
            ],
            'NO' => [
                'number' => $karyawan['NO']
            ],
            'NIP/NIPPK/NRP' => [
                'rich_text' => [['text' => ['content' => (string)$karyawan['NIP']]]]
            ],
            'JABATAN' => [
                'select' => ['name' => $karyawan['JABATAN']]
            ],
            'Posisi' => [
                'select' => ['name' => $karyawan['POSISI']]
            ],
            'PANGKAT/GOL.RUANG' => [
                'select' => ['name' => $karyawan['PANGKAT']]
            ]
        ]
    ];

    $ch = curl_init("https://api.notion.com/v1/pages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pagePayload));
    
    $pageResponse = curl_exec($ch);
    $pageHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($pageHttpCode === 200) {
        echo "✅ Berhasil ditambahkan: <strong>" . $karyawan['NAMA'] . "</strong><br>";
        $successCount++;
    } else {
        echo "❌ Gagal menambah data " . $karyawan['NAMA'] . ". Error: " . htmlspecialchars($pageResponse) . "<br>";
        $failCount++;
    }
    
    // Delay 0.4 detik agar tidak kena rate limit Notion
    usleep(400000); 
}

echo "<br><strong>Seeder Selesai!</strong><br>";
echo "Total Berhasil: " . $successCount . " | Total Gagal: " . $failCount . "<br>";
echo "Silakan cek tabel Notion Anda.";
?>