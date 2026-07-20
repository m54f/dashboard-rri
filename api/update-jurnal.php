<?php
require_once __DIR__ . '/../config/notion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_id = $_POST['page_id'];
    $nama_kegiatan = $_POST['nama_kegiatan'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d\TH:i:s');
    $iso_date = date('c', strtotime($tanggal)); 
    $jenis_kegiatan = $_POST['jenis_kegiatan'] ?? [];
    $petugas = $_POST['petugas'] ?? [];
    $eviden_3 = $_POST['eviden_3_link'] ?? '';

    $properties = [
        'Nama Kegiatan' => ['title' => [['text' => ['content' => $nama_kegiatan]]]],
        'Date' => ['date' => ['start' => $iso_date]]
    ];

    if (!empty($jenis_kegiatan)) {
        $properties['Jenis Kegiatan'] = ['multi_select' => array_map(function($item) { return ['name' => $item]; }, $jenis_kegiatan)];
    } else {
        $properties['Jenis Kegiatan'] = ['multi_select' => []]; // Kosongkan jika tidak ada yang dicentang
    }

    if (!empty($petugas)) {
        $properties['Petugas'] = ['multi_select' => array_map(function($item) { return ['name' => $item]; }, $petugas)];
    } else {
        $properties['Petugas'] = ['multi_select' => []]; // Kosongkan jika tidak ada yang dicentang
    }
    
    $properties['Eviden 3 (Link)'] = empty($eviden_3) ? null : ['url' => $eviden_3];

    $payload = ['properties' => $properties];

    $ch = curl_init("https://api.notion.com/v1/pages/$page_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOTION_TOKEN, 
        "Content-Type: application/json", 
        "Notion-Version: 2022-06-28"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        header('Location: ../pages/jurnal.php?status=updated');
    } else {
        header('Location: ../pages/jurnal.php?status=error');
    }
    exit;
}
?>