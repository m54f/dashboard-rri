<?php
require_once __DIR__.'/config/notion.php';

$dbId = DATABASE_ID_CHECKLIST;
$url = "https://api.notion.com/v1/databases/{$dbId}";
$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (isset($data['properties'])) {
    foreach ($data['properties'] as $name => $prop) {
        echo "{$name} => {$prop['type']}\n";
    }
} else {
    echo "Error: " . ($data['message'] ?? $response);
}
?>