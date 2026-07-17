<?php
require_once __DIR__ . '/../config/notion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_id = $_POST['page_id'];
    
    // Notion menghapus baris dengan cara mengarsipkannya
    $payload = ['archived' => true];

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
        header('Location: ../pages/jurnal.php?status=deleted');
    } else {
        header('Location: ../pages/jurnal.php?status=error');
    }
    exit;
}
?>