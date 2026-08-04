<?php
require_once __DIR__.'/../config/notion.php';

function clearJurnalCache() {
    $cacheFile = __DIR__ . '/../cache/jurnal_cache.json';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

function getJurnal($forceRefresh = false) {
    $cacheDir = __DIR__ . '/../cache';
    $cacheFile = $cacheDir . '/jurnal_cache.json';
    $ttl = 180; // 3 menit TTL cache

    // Kembalikan data dari cache jika masih segar dan tidak dipaksa refresh
    if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $cached = @json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached) && !isset($cached['error'])) {
            return $cached;
        }
    }

    // Gunakan DATABASE_ID_KEGIATAN sesuai config Anda
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_KEGIATAN . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $entries = [];
    $has_more = true;
    $start_cursor = null;
    $pageCount = 0;
    $maxPages = 5; // Batasi maksimal 5 halaman (500 data terbaru) agar sinkronisasi sangat cepat

    while ($has_more && $pageCount < $maxPages) {
        $pageCount++;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $payloadArray = [
            "page_size" => 100,
            "sorts" => [["property" => "Date", "direction" => "descending"]]
        ];
        if ($start_cursor) {
            $payloadArray['start_cursor'] = $start_cursor;
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadArray));
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            if (file_exists($cacheFile)) {
                $fallback = @json_decode(file_get_contents($cacheFile), true);
                if (is_array($fallback)) return $fallback;
            }
            return ['error' => "cURL Error: " . $error];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['message'] ?? 'Unknown Notion API Error';
            if (file_exists($cacheFile)) {
                $fallback = @json_decode(file_get_contents($cacheFile), true);
                if (is_array($fallback)) return $fallback;
            }
            return ['error' => "Notion Error (HTTP {$httpCode}): {$errMsg}"]; 
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['results'])) {
            if (file_exists($cacheFile)) {
                $fallback = @json_decode(file_get_contents($cacheFile), true);
                if (is_array($fallback)) return $fallback;
            }
            return ['error' => 'Invalid API response'];
        }

        foreach ($data['results'] as $item) {
            $props = $item['properties'];
            
            $full_id = $item['id'];
            $id = strtoupper(substr(str_replace('-', '', $full_id), 0, 6));
            $url_page = $item['url'] ?? '#';
            
            $title_arr = $props['Nama Kegiatan']['title'] ?? [];
            $title = !empty($title_arr) ? $title_arr[0]['plain_text'] : 'Tanpa Judul';
            
            $date_info = $props['Date']['date'] ?? null;
            $date_str = $date_info ? ($date_info['start'] ?? '') : '';
            $date = $date_str ? date('d M Y', strtotime($date_str)) : '-';
            $date_raw = $date_str ? date('Y-m-d\TH:i', strtotime($date_str)) : ''; 
            
            $tags_arr = [];
            if (!empty($props['Jenis Kegiatan']['multi_select'])) {
                foreach ($props['Jenis Kegiatan']['multi_select'] as $sel) { 
                    $tags_arr[] = $sel['name']; 
                }
            }
            $tag = !empty($tags_arr) ? strtoupper($tags_arr[0]) : 'KEGIATAN';

            $ops_arr = [];
            if (!empty($props['Petugas']['multi_select'])) {
                foreach ($props['Petugas']['multi_select'] as $op_sel) { 
                    $ops_arr[] = $op_sel['name']; 
                }
            }
            $op = !empty($ops_arr) ? implode(', ', $ops_arr) : '-';
            
            $status = 'Selesai';
            if (isset($props['Status']['select']['name'])) {
                $status = $props['Status']['select']['name'];
            } elseif (isset($props['Status']['status']['name'])) {
                $status = $props['Status']['status']['name'];
            }
            
            $eviden_3 = $props['Eviden 3 (Link)']['url'] ?? '';

            $docs = [];
            if (!empty($props['Eviden']['files'])) {
                foreach($props['Eviden']['files'] as $f) {
                    $fileUrl = $f['external']['url'] ?? ($f['file']['url'] ?? '');
                    if ($fileUrl) {
                        $docs[] = ['name' => $f['name'] ?? 'file', 'url' => $fileUrl];
                    }
                }
            }

            $entries[] = [ 
                'id' => $id, 
                'full_id' => $full_id, 
                'title' => $title, 
                'tag' => $tag, 
                'tags_arr' => $tags_arr, 
                'op' => $op, 
                'ops_arr' => $ops_arr, 
                'date' => $date, 
                'date_raw' => $date_raw, 
                'url' => $url_page, 
                'docs' => $docs, 
                'status' => $status, 
                'eviden_3' => $eviden_3 
            ];
        }

        $has_more = $data['has_more'];
        $start_cursor = $data['next_cursor'];
    }

    // Simpan ke file cache jika data berhasil ditarik
    if (!empty($entries)) {
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, json_encode($entries, JSON_PRETTY_PRINT));
    }

    return $entries;
}
?>