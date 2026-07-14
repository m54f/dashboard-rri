<?php

require_once 'config/notion.php';

$url = "https://api.notion.com/v1/databases/" . DATABASE_ID . "/query";

$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: 2022-06-28",
    "Content-Type: application/json"
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die(curl_error($ch));
}

curl_close($ch);

echo "<pre>";
echo $response;
echo "</pre>";
