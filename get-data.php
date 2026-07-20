<?php

header('Content-Type: application/json; charset=utf-8');

$rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id=UCa45qWSDXbdgLBjrGfQ7tmA";

$xml = simplexml_load_file($rssUrl);

if (!$xml) {
    echo json_encode([
        "status" => false,
        "message" => "Gagal mengambil RSS"
    ]);
    exit;
}

$mediaNS = "http://search.yahoo.com/mrss/";
$ytNS    = "http://www.youtube.com/xml/schemas/2015";

function toWIB($utcTime)
{
    $date = new DateTime($utcTime);
    $date->setTimezone(new DateTimeZone('Asia/Jakarta'));

    return [
        "datetime" => $date->format('Y-m-d H:i:s'),
        "date"     => $date->format('Y-m-d'),
        "time"     => $date->format('H:i:s'),
        "unix"     => $date->getTimestamp()
    ];
}

$result = [];

foreach ($xml->entry as $entry) {

    $media = $entry->children($mediaNS);
    $yt    = $entry->children($ytNS);

    $thumbnail = "";

    if (isset($media->group->thumbnail)) {
        $thumbnail = (string)$media->group->thumbnail->attributes()->url;
    }

    $published = toWIB((string)$entry->published);
    $updated   = toWIB((string)$entry->updated);

    $result[] = [
        "video_id"    => (string)$yt->videoId,
        "title"       => (string)$entry->title,
        "description" => trim((string)$media->group->description),
        "published"   => $published,
        "updated"     => $updated,
        "thumbnail"   => $thumbnail,
        "link"        => "https://www.youtube.com/watch?v=" . (string)$yt->videoId,
    ];
}

$feedUpdated = toWIB((string)$xml->updated);

echo json_encode([
    "status"  => true,
    "channel" => (string)$xml->title,
    "updated" => $feedUpdated,
    "total"   => count($result),
    "videos"  => $result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);