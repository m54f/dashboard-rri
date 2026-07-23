 <?php
 $notionToken = 'ntn_j76903444178v9FXe0iemukaBFghFWRt6gMYAwqvN9zc8g';
 $databaseId  = '3a31ca6157e18025856cf0d403573ec5'; 

 $headers = [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
];

// ==========================================
// 1. DATA DARI EXCEL (17 KARYAWAN)
// ==========================================
 $dataKaryawan = [
    ["NO" => 1, "NAMA" => "M. LUDFI MASHADI, S.AP", "JABATAN" => "ASISTEN TEKNISI SIARAN PEMULA", "GOLONGAN" => "V", "NIP" => "199511282022211005", "POSISI" => "KATIM TMB"],
    ["NO" => 2, "NAMA" => "DICKY MAHADI", "JABATAN" => "ASISTEN TEKNISI SIARAN PEMULA", "GOLONGAN" => "V", "NIP" => "198302142022211013", "POSISI" => "KOORDINATOR PEMANCAR NGIJO"],
    ["NO" => 3, "NAMA" => "PRIYO SULAKSONO, S.T.", "JABATAN" => "PRANATA SIARAN AHLI PERTAMA", "GOLONGAN" => "IX", "NIP" => "198108062022211008", "POSISI" => "KOORDINATOR PEMANCAR"],
    ["NO" => 4, "NAMA" => "ANGGI SETIAWAN, S.E.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "IX", "NIP" => "199003162022211007", "POSISI" => "KOORDINATOR SAPRAS"],
    ["NO" => 5, "NAMA" => "TAUKHID NUR HIDAYAT", "JABATAN" => "ASISTEN TEKNISI SIARAN PEMULA", "GOLONGAN" => "V", "NIP" => "199010102024211001", "POSISI" => "KOORDINATOR STUDIO"],
    ["NO" => 6, "NAMA" => "ABDUL SYUKUR, S.M", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "IX", "NIP" => "197606172022211009", "POSISI" => "PEMANCAR NGADIREJO"],
    ["NO" => 7, "NAMA" => "DEDY HARIYANTO, S.M.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "IX", "NIP" => "197504142022211005", "POSISI" => "PEMANCAR NGADIREJO"],
    ["NO" => 8, "NAMA" => "DENY SETYADI, S.T.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "IX", "NIP" => "197803192024211001", "POSISI" => "TEKNISI ELEKTRONIKA"],
    ["NO" => 9, "NAMA" => "YHANUAR EFFENDI, A.Md.", "JABATAN" => "ASISTEN TEKNISI SIARAN TERAMPIL", "GOLONGAN" => "VII", "NIP" => "198601132024211001", "POSISI" => "PT"],
    ["NO" => 10, "NAMA" => "AHMAD SHOFIRIN, A.Md.T.", "JABATAN" => "ASISTEN TEKNISI SIARAN TERAMPIL", "GOLONGAN" => "VII", "NIP" => "199212292022211011", "POSISI" => "BKO"],
    ["NO" => 11, "NAMA" => "YUDA PAKSI, S.Tr.I.Kom.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "IX", "NIP" => "199207312023211012", "POSISI" => "PT"],
    ["NO" => 12, "NAMA" => "M. ALFIAN NUR, S.T.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "Penata Muda-III/a", "NIP" => "199311252025041003", "POSISI" => "PT"],
    ["NO" => 13, "NAMA" => "WINSA BAGUS A., A.Md.T", "JABATAN" => "ASISTEN TEKNISI SIARAN TERAMPIL", "GOLONGAN" => "Pengatur - II/c", "NIP" => "199507092025041002", "POSISI" => "PT"],
    ["NO" => 14, "NAMA" => "SELVY ANDY WIJAYA, S.T.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "Penata Muda-III/a", "NIP" => "199704102025042002", "POSISI" => "ADMINISTRASI"],
    ["NO" => 15, "NAMA" => "ALIF ABDILLAH, A.Md.", "JABATAN" => "ASISTEN TEKNISI SIARAN TERAMPIL", "GOLONGAN" => "Pengatur - II/c", "NIP" => "199711132025041002", "POSISI" => "PT"],
    ["NO" => 16, "NAMA" => "M. SAIFUL ANWAR, A.Md.T.", "JABATAN" => "ASISTEN TEKNISI SIARAN TERAMPIL", "GOLONGAN" => "Pengatur - II/c", "NIP" => "199804202025041002", "POSISI" => "PT"],
    ["NO" => 17, "NAMA" => "M. YAYANG SUBEKTI, S.T.", "JABATAN" => "TEKNISI SIARAN AHLI PERTAMA", "GOLONGAN" => "Penata Muda-III/a", "NIP" => "200107022025041004", "POSISI" => "PT"]
];

echo "<h3>Memulai Proses Reset & Seeder Data Petugas...</h3>";

// ==========================================
// TAHAP 1: HAPUS SEMUA DATA LAMA DI NOTION
// ==========================================
echo "<h4>Tahap 1: Menghapus data lama...</h4>";

 $queryUrl = "https://api.notion.com/v1/databases/{$databaseId}/query";
 $hasMore = true;
 $nextCursor = null;
 $deletedCount = 0;

while ($hasMore) {
    $queryBody = $nextCursor ? ['start_cursor' => $nextCursor] : new stdClass();
    
    $ch = curl_init($queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryBody));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['results'])) {
        foreach ($data['results'] as $page) {
            $pageId = $page['id'];
            
            // Archive (hapus) halaman
            $delCh = curl_init("https://api.notion.com/v1/pages/{$pageId}");
            curl_setopt($delCh, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($delCh, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($delCh, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($delCh, CURLOPT_POSTFIELDS, json_encode(['archived' => true]));
            
            curl_exec($delCh);
            curl_close($delCh);
            
            $deletedCount++;
            usleep(300000); // Delay 0.3 detik
        }
        
        $hasMore = $data['has_more'];
        $nextCursor = $data['next_cursor'] ?? null;
    } else {
        echo "❌ Gagal mengambil data lama. Error: " . htmlspecialchars($response) . "<br>";
        $hasMore = false;
    }
}

echo "<span style='color:orange'>✅ Berhasil menghapus $deletedCount data lama dari Notion.</span><br><br>";

// ==========================================
// TAHAP 2: INSERT DATA BARU KE NOTION
// ==========================================
echo "<h4>Tahap 2: Mengisi data 17 karyawan baru...</h4>";

 $successCount = 0;
 $failCount = 0;

foreach ($dataKaryawan as $karyawan) {
    $pagePayload = [
        'parent' => ['database_id' => $databaseId],
        'properties' => [
            'Name' => [
                'title' => [['text' => ['content' => $karyawan['NAMA']]]]
            ],
            'JABATAN' => [
                'select' => ['name' => $karyawan['JABATAN']]
            ],
            'Posisi' => [
                'select' => ['name' => $karyawan['POSISI']]
            ],
            'PANGKAT/GOL.RUANG' => [
                'select' => ['name' => $karyawan['GOLONGAN']]
            ],
            'NIP/NIPPK/NRP' => [
                'rich_text' => [['text' => ['content' => (string)$karyawan['NIP']]]]
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
        echo "✅ Berhasil ditambahkan: <strong>" . $karyawan['NAMA'] . "</strong> (Posisi: " . $karyawan['POSISI'] . ")<br>";
        $successCount++;
    } else {
        echo "❌ Gagal menambah data " . $karyawan['NAMA'] . ". Error: " . htmlspecialchars($pageResponse) . "<br>";
        $failCount++;
    }
    
    usleep(400000); 
}

echo "<br><strong>Proses Selesai!</strong><br>";
echo "Total Dihapus: $deletedCount | Total Berhasil Baru: $successCount | Total Gagal: $failCount<br>";
echo "Silakan cek tabel Notion Anda.";
?>