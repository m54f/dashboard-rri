<?php

require_once '../config/notion.php';

$url = "https://api.notion.com/v1/databases/" . DATABASE_ID . "/query";

$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Content-Type: application/json",
    "Notion-Version: " . NOTION_VERSION
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));

$response = curl_exec($ch);
curl_close($ch);

echo $response;