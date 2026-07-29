<?php
/**
 * Check "Created By" Property in Notion Databases
 * 
 * Script untuk mengecek apakah property "Created By" sudah ada
 * di semua database Notion yang perlu tracking.
 */

require_once __DIR__ . '/config/notion.php';

$databases = [
    'Checklist' => DATABASE_ID_CHECKLIST,
    'Troubleshoot' => DATABASE_ID_TROUBLESHOOT,
    'Pemadaman' => DATABASE_ID_PADAM_PLN,
    'Peminjaman' => DATABASE_ID_PEMINJAMAN,
    'Petugas' => DATABASE_ID_PETUGAS,
    'Jurnal/Kegiatan' => DATABASE_ID_KEGIATAN,
    'YouTube/Video' => DATABASE_ID_YOUTUBE,
];

echo "\n";
echo "================================================\n";
echo "CHECK 'CREATED BY' PROPERTY STATUS\n";
echo "================================================\n";
echo "\n";

$allOk = true;
$needsProperty = [];

foreach ($databases as $name => $dbId) {
    echo "Checking: $name\n";
    echo "  Database ID: $dbId\n";
    
    // Query database schema
    $ch = curl_init("https://api.notion.com/v1/databases/$dbId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "  ✗ Error: Cannot access database (HTTP $httpCode)\n";
        $allOk = false;
        continue;
    }
    
    $data = json_decode($response, true);
    $properties = $data['properties'] ?? [];
    
    if (isset($properties['Created By'])) {
        $type = $properties['Created By']['type'] ?? 'unknown';
        echo "  ✓ Property 'Created By' EXISTS (type: $type)\n";
    } else {
        echo "  ✗ Property 'Created By' NOT FOUND\n";
        $allOk = false;
        $needsProperty[] = [
            'name' => $name,
            'id' => $dbId,
            'url' => "https://www.notion.so/" . str_replace('-', '', $dbId)
        ];
    }
    
    echo "\n";
}

echo "================================================\n";

if ($allOk) {
    echo "✓ ALL DATABASES READY!\n";
    echo "\nSemua database sudah memiliki property 'Created By'.\n";
    echo "Anda bisa mengaktifkan tracking di semua create-*.php files.\n";
} else {
    echo "✗ SOME DATABASES NEED SETUP\n";
    echo "\nDatabase yang perlu ditambahkan property 'Created By':\n\n";
    
    foreach ($needsProperty as $db) {
        echo "  • {$db['name']}\n";
        echo "    URL: {$db['url']}\n\n";
    }
    
    echo "------------------------------------------------\n";
    echo "CARA MENAMBAHKAN PROPERTY:\n";
    echo "------------------------------------------------\n";
    echo "1. Buka database di Notion (URL di atas)\n";
    echo "2. Klik '+' di kolom paling kanan header tabel\n";
    echo "3. Pilih property type: 'Text' atau 'Rich text'\n";
    echo "4. Beri nama: 'Created By' (case-sensitive!)\n";
    echo "5. Save\n";
    echo "\n";
    echo "Atau bisa copy-paste property dari database lain\n";
    echo "yang sudah punya 'Created By'.\n";
    echo "------------------------------------------------\n";
    echo "\n";
    echo "Setelah semua property ditambahkan, uncomment\n";
    echo "baris 'Created By' di file create-*.php yang\n";
    echo "sesuai dengan database tersebut.\n";
}

echo "================================================\n";
echo "\n";
?>
