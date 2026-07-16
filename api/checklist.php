<?php
require_once __DIR__.'/../config/notion.php';

function getChecklist()
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_CHECKLIST . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $payload = json_encode([
        "sorts" => [
            ["property" => "Tanggal Cek", "direction" => "descending"]
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

    $items = [];

    foreach ($data['results'] as $item) {
        $props = $item['properties'];

        $id = strtoupper(substr($item['id'], 0, 6));

        // 1. Baca Checklist (multi_select)
        $checklistArr = $props['Checklist']['multi_select'] ?? [];
        $checklistType = !empty($checklistArr) ? $checklistArr[0]['name'] : '-';

        $tanggalRaw = $props['Tanggal Cek']['date']['start'] ?? null;
        $tanggal = $tanggalRaw ? date('d M Y', strtotime($tanggalRaw)) : '-';

        $pro1    = $props['Pro 1']['checkbox'] ?? false;
        $pro2    = $props['Pro 2']['checkbox'] ?? false;
        $pro4    = $props['Pro 4']['checkbox'] ?? false;
        $tetrack = $props['Checkbox']['checkbox'] ?? false;   // property "Checkbox" = Te-track "Sudah Submit"
        $mcr     = $props['MCR Depan']['checkbox'] ?? false;

        $petugasOptions = $props['Petugas DP']['multi_select'] ?? [];
        $petugasArr = array_map(fn($p) => $p['name'], $petugasOptions);
        $petugas = !empty($petugasArr) ? implode(', ', $petugasArr) : 'Belum Ditentukan';

        $keterangan = '';
        foreach (($props['Keterangan Checklist']['rich_text'] ?? []) as $rt) {
            $keterangan .= $rt['plain_text'] ?? '';
        }

        // 2. Baca Submission time (created_time)
        $submissionRaw = $props['Submission time']['created_time'] ?? $item['created_time'] ?? null;
        $submission = $submissionRaw ? date('d M Y, H:i', strtotime($submissionRaw)) . ' WIB' : '-';

        // 3. Baca Respondent (created_by) - Jika nanti ingin ditampilkan
        $respondentName = $props['Respondent']['created_by']['name'] ?? 'Unknown';

        // Hitung kelengkapan checklist (5 item: Pro1, Pro2, Pro4, Te-track, MCR Depan)
        $totalItems = 5;
        $doneItems = ($pro1?1:0) + ($pro2?1:0) + ($pro4?1:0) + ($tetrack?1:0) + ($mcr?1:0);
        $isComplete = $doneItems === $totalItems;

        $items[] = [
            'id'             => $id,
            'page_id'        => $item['id'],
            'url'            => $item['url'] ?? '#',
            'checklist_type' => $checklistType,
            'tanggal_raw'    => $tanggalRaw,
            'tanggal'        => $tanggal,
            'pro1'           => $pro1,
            'pro2'           => $pro2,
            'pro4'           => $pro4,
            'tetrack'        => $tetrack,
            'mcr'            => $mcr,
            'petugas'        => $petugas,
            'petugas_arr'    => $petugasArr,
            'keterangan'     => $keterangan,
            'submission'     => $submission,
            'done_items'     => $doneItems,
            'total_items'    => $totalItems,
            'is_complete'    => $isComplete,
            // Uncomment baris di bawah jika ingin mengirim data respondent ke frontend
            // 'respondent'     => $respondentName, 
        ];
    }

    return $items;
}