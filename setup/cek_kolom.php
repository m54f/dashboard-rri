<?php
 $notionToken = 'ntn_j76903444178v9FXe0iemukaBFghFWRt6gMYAwqvN9zc8g';
 $databaseId  = '3a01ca6157e1808cabbccd89c39a32f5'; 

 $url = "https://api.notion.com/v1/databases/{$databaseId}";
 $headers = [
    "Authorization: Bearer {$notionToken}",
    "Notion-Version: 2022-06-28"
];

 $ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 $response = curl_exec($ch);
curl_close($ch);

 $data = json_decode($response, true);

echo "<h3>Daftar Nama Kolom di Tabel Notion Anda:</h3><ul>";
if (isset($data['properties'])) {
    foreach ($data['properties'] as $namaKolom => $detail) {
        echo "<li><strong>" . $namaKolom . "</strong> (Tipe: " . $detail['type'] . ")</li>";
    }
} else {
    echo "Gagal membaca database. Cek ID dan Token Anda.";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
echo "</ul>";
?>