<?php
require_once __DIR__.'/../config/notion.php';

function getPetugas() {
    $notionToken = NOTION_TOKEN; 
    $databaseId  = DATABASE_ID_PETUGAS; // Ganti dengan nama variabel ID Database Petugas di config Anda
    
    $url = "https://api.notion.com/v1/databases/{$databaseId}/query";
    $headers = [
        "Authorization: Bearer {$notionToken}",
        "Notion-Version: 2022-06-28",
        "Content-Type: application/json"
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        return ['error' => $error['message'] ?? "Notion API Error (HTTP {$httpCode})"];
    }
    
    $data = json_decode($response, true);
    $results = [];
    
    if (!isset($data['results'])) return $results;
    
    foreach ($data['results'] as $page) {
        $props = $page['properties'];
        
        // Ambil Nama (Title)
        $name = '';
        if (isset($props['Name']['title']) && !empty($props['Name']['title'])) {
            $name = $props['Name']['title'][0]['plain_text'];
        }
        
        // Ambil JABATAN, Posisi, PANGKAT (Select)
        $jabatan = $props['JABATAN']['select']['name'] ?? '-';
        $posisi  = $props['Posisi']['select']['name'] ?? '-';
        $pangkat = $props['PANGKAT/GOL.RUANG']['select']['name'] ?? '-';
        
        // Ambil NIP (Rich Text)
        $nip = '';
        if (isset($props['NIP/NIPPK/NRP']['rich_text']) && !empty($props['NIP/NIPPK/NRP']['rich_text'])) {
            $nip = $props['NIP/NIPPK/NRP']['rich_text'][0]['plain_text'];
        }
        
        // Buat inisial otomatis dari Nama
        $words = explode(' ', $name);
        $init = '';
        foreach ($words as $w) {
            if (!empty($w)) $init .= strtoupper($w[0]);
            if (strlen($init) >= 2) break;
        }
        
        $results[] = [
            'page_id' => $page['id'],
            'name'    => $name,
            'role'    => $jabatan,
            'posisi'  => $posisi,
            'pangkat' => $pangkat,
            'nip'     => $nip,
            'init'    => $init ?: '?'
        ];
    }
    
    return $results;
}
?>