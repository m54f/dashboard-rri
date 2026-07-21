<?php
require_once __DIR__.'/../config/notion.php';

function getPemadaman() {
    $notionToken = NOTION_TOKEN; 
    $databaseId  = DATABASE_ID_PADAM_PLN;
    
    $url = "https://api.notion.com/v1/databases/{$databaseId}/query";
    $headers = [
        "Authorization: Bearer {$notionToken}",
        "Notion-Version: 2022-06-28",
        "Content-Type: application/json"
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}'); // Objek kosong
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        return ['error' => $error['message'] ?? "Notion API Error (HTTP {$httpCode})"];
    }
    
    $data = json_decode($response, true);
    $results = [];
    
    if (!isset($data['results'])) return $results;
    
    foreach ($data['results'] as $page) {
        $props = $page['properties'] ?? [];
        
        $dateStart = $props['Date']['date']['start'] ?? null;
        $dateEnd   = $props['Date']['date']['end'] ?? null;
        
        // Baca Pemancar sebagai multi_select
        $pemancarArr = [];
        if (isset($props['Pemancar']['multi_select'])) {
            foreach ($props['Pemancar']['multi_select'] as $p) {
                $pemancarArr[] = $p['name'];
            }
        }
        
        // Baca Petugas sebagai multi_select
        $petugasArr = [];
        if (isset($props['Petugas']['multi_select'])) {
            foreach ($props['Petugas']['multi_select'] as $p) {
                $petugasArr[] = $p['name'];
            }
        }

        $filesArr = [];
        if (isset($props['Files & media']['files'])) {
            foreach ($props['Files & media']['files'] as $f) {
                $filesArr[] = $f['name'];
            }
        }

        $results[] = [
            'page_id'      => $page['id'],
            'id'           => strtoupper(substr($page['id'], 0, 8)),
            'url'          => $page['url'] ?? '#',
            'pemancar'     => implode(', ', $pemancarArr) ?: 'Tidak Diketahui',
            'tanggal_raw'  => $dateStart,
            'tanggal'      => $dateStart ? formatDateIndo($dateStart) : '-',
            'start_time'   => $dateStart ? date('H:i', strtotime($dateStart)) : '-',
            'end_time'     => $dateEnd ? date('H:i', strtotime($dateEnd)) : '-',
            'petugas_arr'  => $petugasArr,
            'petugas'      => implode(', ', $petugasArr) ?: '-',
            'files'        => $filesArr,
            'submission'   => formatDateIndo($page['created_time'] ?? 'now'),
        ];
    }
    
    usort($results, fn($a, $b) => strtotime($b['tanggal_raw'] ?? 'now') - strtotime($a['tanggal_raw'] ?? 'now'));
    return $results;
}

function formatDateIndo($dateStr) {
    if (empty($dateStr)) return '';
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    try {
        $date = new DateTime($dateStr);
        return $date->format('j ') . $months[(int)$date->format('m') - 1] . ' ' . $date->format('Y');
    } catch (Exception $e) {
        return '';
    }
}
?>