<?php
require_once __DIR__.'/../config/notion.php';

function getYoutubeVideos()
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_YOUTUBE . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode((object)[]));

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

    $videos = [];

    foreach ($data['results'] as $item) {
        $props = $item['properties'];
        
        // 1. Title - cari dari berbagai nama field yang mungkin
        $title = 'Tanpa Judul';
        foreach (['Judul', 'Title', 'Nama Video', 'Video', 'Judul Video', 'Tema Acara', 'Tema'] as $key) {
            if (isset($props[$key]['title'][0]['plain_text'])) {
                $title = $props[$key]['title'][0]['plain_text'];
                break;
            }
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $title = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
        }
        
        // 2. Programa
        $programa = 'Umum';
        $programaColor = 'gray';
        if (isset($props['Programa']['select']['name'])) {
            $programa = $props['Programa']['select']['name'];
            if (isset($props['Programa']['select']['color'])) {
                $programaColor = $props['Programa']['select']['color'];
            }
        } elseif (isset($props['Programa']['title'][0]['plain_text'])) {
            $programa = $props['Programa']['title'][0]['plain_text'];
        }
        
        // 2.5 Nama Acara
        $namaAcara = '';
        foreach (['Acara Pro 1', 'Acara Pro 2', 'Acara Pro 4'] as $key) {
            if (isset($props[$key]['select']['name'])) {
                $namaAcara = $props[$key]['select']['name'];
                break;
            }
        }
        
        // 3. Flyer
        $flyer = '';
        if (isset($props['Flyer']['files'][0]['file']['url'])) {
            $flyer = $props['Flyer']['files'][0]['file']['url'];
        } elseif (isset($props['Flyer']['files'][0]['external']['url'])) {
            $flyer = $props['Flyer']['files'][0]['external']['url'];
        } elseif (isset($props['Flyer']['url'])) {
            $flyer = $props['Flyer']['url'];
        }
        
        // 4. Tanggal - dari property "Tanggal" atau nama lain
        $dateRaw = '';
        foreach (['Tanggal', 'Date', 'Tanggal Tayang', 'Tanggal Upload', 'Tanggal Siaran'] as $key) {
            if (isset($props[$key]['date']['start'])) {
                $dateRaw = $props[$key]['date']['start'];
                break;
            }
        }
        $date = 'TBA';
        if ($dateRaw !== '') {
            try {
                $dateObj = new DateTime($dateRaw);
                $date = $dateObj->format('j M Y');
            } catch (Exception $e) {
                $date = $dateRaw;
            }
        }
        
        // 5. Jam / Time
        $time = 'TBA';
        foreach (['Jam', 'Time', 'Waktu', 'Duration', 'Durasi'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $time = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
            if (isset($props[$key]['title'][0]['plain_text'])) {
                $time = $props[$key]['title'][0]['plain_text'];
                break;
            }
        }
        
        // 6. URL Link
        $url = '#';
        foreach (['Link', 'URL', 'Video URL', 'Youtube', 'Link Youtube'] as $key) {
            if (isset($props[$key]['url'])) {
                $url = $props[$key]['url'];
                break;
            }
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $url = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
        }

        # 
        $penyiar = [];
        foreach (['Penyiar Pro 1', 'Penyiar Pro 2', 'Penyiar Pro 4'] as $key) {
            if (isset($props[$key]['multi_select'])) {
                foreach ($props[$key]['multi_select'] as $items) {
                    if (isset($items['name'])) {
                        $penyiar[] = $items['name'];
                    }
                }
                if (!empty($penyiar)) {
                    break;
                }    
            }
        }
        $penyiarStr = implode(', ', $penyiar);

        $narasumber = [];
        foreach (['Narasumber 1', 'Narasumber 2', 'Narasumber 3'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text']) && is_array($props[$key]['rich_text'])) {
                $narasumber[] = $props[$key]['rich_text'][0]['plain_text'];
            }
        }
        // $narasumberStr = implode(', ', $narasumber);
        // $narasumber tetap array untuk dikirim sebagai list ke frontend


        $videos[] = [
            'id'             => $item['id'],
            'namaAcara'      => $namaAcara,
            'title'          => $title,
            'programa'       => $programa,
            'programaColor'  => $programaColor,
            'flyer'          => $flyer,
            'date'           => $date,
            'time'           => $time,
            'url'            => $url,
            'penyiar'        => $penyiarStr,
            'narasumber'     => $narasumber,
            // 'narasumberStr'  => $narasumberStr,
        ];
    }

    return $videos;
}