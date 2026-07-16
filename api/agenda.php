<?php
require_once __DIR__ . '/../config/notion.php';

function getAgenda()
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_AGENDA . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Sort by time ascending
    $payload = json_encode([
        "sorts" => [
            [
                "property" => "Waktu",
                "direction" => "ascending"
            ]
        ]
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['results'])) {
        return ['error' => 'Invalid API response', 'raw' => $response];
    }

    $agendaList = [];

    foreach ($data['results'] as $page) {
        $props = $page['properties'];

        $title = 'Tanpa Judul';
        if (isset($props['Kegiatan']['title'][0])) {
            // title can be nested differently depending on Notion response
            if (isset($props['Kegiatan']['title'][0]['plain_text'])) {
                $title = $props['Kegiatan']['title'][0]['plain_text'];
            } elseif (isset($props['Kegiatan']['title'][0]['text']['content'])) {
                $title = $props['Kegiatan']['title'][0]['text']['content'];
            }
        }

        $waktuFull = $props['Waktu']['date']['start'] ?? null;
        $time = $waktuFull ? date('H.i', strtotime($waktuFull)) : '-';

        $kategori = $props['Kategori']['select']['name'] ?? 'EVENT';

        $pic = 'NN';
        if (isset($props['PIC']['rich_text'][0])) {
            if (isset($props['PIC']['rich_text'][0]['plain_text'])) {
                $pic = $props['PIC']['rich_text'][0]['plain_text'];
            } elseif (isset($props['PIC']['rich_text'][0]['text']['content'])) {
                $pic = $props['PIC']['rich_text'][0]['text']['content'];
            }
        }

        $status = $props['Status']['select']['name'] ?? 'Menunggu';

        $notes = '';
        if (isset($props['Catatan']['rich_text'][0])) {
            if (isset($props['Catatan']['rich_text'][0]['plain_text'])) {
                $notes = $props['Catatan']['rich_text'][0]['plain_text'];
            } elseif (isset($props['Catatan']['rich_text'][0]['text']['content'])) {
                $notes = $props['Catatan']['rich_text'][0]['text']['content'];
            }
        }

        $tone = 'blue';
        if ($kategori === 'PODCAST') $tone = 'purple';
        if ($kategori === 'LIVE') $tone = 'red';
        if ($kategori === 'EVENT') $tone = 'orange';

        $cls = 'belum';
        if ($status === 'Selesai') $cls = 'selesai';
        if ($status === 'Proses') $cls = 'proses';

        $agendaList[] = [
            'id' => $page['id'],
            'time' => $time,
            'title' => $title,
            'tag' => $kategori,
            'tone' => $tone,
            'pic' => $pic,
            'status' => $status,
            'cls' => $cls,
            'notes' => $notes
        ];
    }

    return $agendaList;
}

?>