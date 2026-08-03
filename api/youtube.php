<?php
require_once __DIR__.'/../config/notion.php';

function getYoutubeVideos($filters = [])
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_YOUTUBE . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $payload = [];
    $andConditions = [];

    if (!empty($filters['programa'])) {
        $prog = trim($filters['programa']);
        $andConditions[] = [
            'property' => 'Programa',
            'select' => ['equals' => $prog]
        ];
    }

    if (!empty($filters['pt_setting'])) {
        $pt = trim($filters['pt_setting']);
        $andConditions[] = [
            'property' => 'Petugas PT Setting',
            'multi_select' => ['contains' => $pt]
        ];
    }

    if (!empty($filters['pt_produksi'])) {
        $pa = trim($filters['pt_produksi']);
        $andConditions[] = [
            'property' => 'Petugas PT Produksi',
            'multi_select' => ['contains' => $pa]
        ];
    }

    if (!empty($filters['penyiar'])) {
        $pen = trim($filters['penyiar']);
        $andConditions[] = [
            'or' => [
                ['property' => 'Penyiar Pro 1', 'multi_select' => ['contains' => $pen]],
                ['property' => 'Penyiar Pro 2', 'multi_select' => ['contains' => $pen]],
                ['property' => 'Penyiar Pro 4', 'multi_select' => ['contains' => $pen]]
            ]
        ];
    }

    if (!empty($andConditions)) {
        if (count($andConditions) === 1) {
            $payload['filter'] = $andConditions[0];
        } else {
            $payload['filter'] = ['and' => $andConditions];
        }
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(!empty($payload) ? $payload : (object)[]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

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

        // 2.6 Tema Acara - properti TERSENDIRI (bukan hasil parsing dari judul).
        // Dicek sebagai title & rich_text karena tipe kolomnya bisa berbeda
        // tergantung database Notion masing-masing.
        $tema = '';
        foreach (['Tema Acara', 'Tema'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $tema = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
            if (isset($props[$key]['title'][0]['plain_text'])) {
                $tema = $props[$key]['title'][0]['plain_text'];
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

        // 7. Petugas Teknik
        // 7. Petugas Teknik (Menggunakan nama kolom dari Notion: "Petugas PT Setting")
        $teknik = '';
        foreach (['Petugas PT Setting', 'Petugas Teknik', 'Teknik', 'Pengarah Teknik'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $teknik = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
            if (isset($props[$key]['multi_select'])) {
                $tempTeknik = [];
                foreach ($props[$key]['multi_select'] as $msItem) {
                    if (isset($msItem['name'])) {
                        $tempTeknik[] = $msItem['name'];
                    }
                }
                if (!empty($tempTeknik)) {
                    $teknik = implode(', ', $tempTeknik);
                    break;
                }
            }
        }

        // 8. Petugas PA / Acara (Diasumsikan menggunakan kolom: "Petugas PT Produksi")
        $pa = '';
        foreach (['Petugas PT Produksi', 'Petugas PA', 'PA', 'Pengarah Acara'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $pa = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
            if (isset($props[$key]['multi_select'])) {
                $tempPa = [];
                foreach ($props[$key]['multi_select'] as $msItem) {
                    if (isset($msItem['name'])) {
                        $tempPa[] = $msItem['name'];
                    }
                }
                if (!empty($tempPa)) {
                    $pa = implode(', ', $tempPa);
                    break;
                }
            }
        }


        $videos[] = [
            'id'             => $item['id'],
            'namaAcara'      => $namaAcara,
            'title'          => $title,
            'tema'           => $tema,
            'programa'       => $programa,
            'programaColor'  => $programaColor,
            'flyer'          => $flyer,
            'date'           => $date,
            'time'           => $time,
            'url'            => $url,
            'penyiar'        => $penyiarStr,
            'narasumber'     => $narasumber,
            'teknik'         => $teknik,
            'pa'             => $pa,
        ];
    }

    return $videos;
}