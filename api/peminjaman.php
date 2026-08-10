<?php
require_once __DIR__.'/../config/notion.php';

function getPeminjaman()
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_PEMINJAMAN . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $items = [];
    $today = date('Y-m-d');
    
    // Variabel untuk pagination
    $has_more = true;
    $start_cursor = null;

    while ($has_more) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $payload = [
            "sorts" => [
                ["property" => "Tanggal Pinjam", "direction" => "descending"]
            ]
        ];

        // Tambahkan start_cursor jika ada (untuk halaman ke-2 dan seterusnya)
        if ($start_cursor !== null) {
            $payload["start_cursor"] = $start_cursor;
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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

        // Proses data dari halaman saat ini
        foreach ($data['results'] as $item) {
            $props = $item['properties'];

            $kegiatan = $props['Nama Kegiatan']['title'][0]['plain_text'] ?? 'Tanpa Nama';
            $picMulti  = $props['Nama PIC PT']['multi_select'] ?? [];
            if (!empty($picMulti)) {
                $pic = implode(', ', array_map(fn($p) => $p['name'], $picMulti));
            } else {
                $pic = $props['Nama PIC PT']['select']['name'] ?? '-';
            }
            $lokasi   = $props['Lokasi']['rich_text'][0]['plain_text'] ?? '-';

            $tglKegiatan = $props['Tanggal Kegiatan']['date']['start'] ?? null;
            $tglPinjam   = $props['Tanggal Pinjam']['date']['start'] ?? null;
            $tglKembali  = $props['Tanggal Kembali']['date']['start'] ?? null;

            $alatOptions = $props['Alat Dipinjam']['multi_select'] ?? [];
            $rawAlatArr  = array_map(fn($a) => $a['name'], $alatOptions);

            $detailAlat = $props['Detail Alat']['rich_text'][0]['plain_text'] ?? '';
            $durasi     = $props['Durasi (jam)']['number'] ?? null;
            $keterangan = $props['Keterangan']['rich_text'][0]['plain_text'] ?? '';

            $alatDisplay = !empty($detailAlat) ? $detailAlat : (!empty($rawAlatArr) ? implode(', ', $rawAlatArr) : '-');

            // Parse detail_alat ke alat_arr untuk kemudahan edit mode di peminjaman_form.php
            $parsedAlatArr = [];
            if (!empty($detailAlat)) {
                $parts = explode(',', $detailAlat);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (empty($p)) continue;
                    
                    // Regex mencocokkan pattern: [Kategori] Nama (Tipe) (Qtyx) atau Nama (Tipe) (Qty)
                    if (preg_match('/^(?:\[(.*?)\]\s*)?([^(]+)(?:\((.*?)\))?(?:\s*\((.*?)\))?$/', $p, $m)) {
                        $kat  = trim($m[1] ?? '');
                        $nama = trim($m[2] ?? '');
                        $tipe = trim($m[3] ?? '');
                        $qty  = preg_replace('/[^0-9]/', '', $m[4] ?? '1');
                        
                        if (!empty($nama)) {
                            $parsedAlatArr[] = [
                                'kategori' => $kat,
                                'alat' => $nama,
                                'jenis' => $tipe,
                                'tipe' => $tipe,
                                'qty' => (int)($qty ?: 1)
                            ];
                        }
                    }
                }
            }
            if (empty($parsedAlatArr) && !empty($rawAlatArr)) {
                foreach ($rawAlatArr as $ra) {
                    $parsedAlatArr[] = ['kategori' => '', 'alat' => $ra, 'jenis' => '', 'tipe' => '', 'qty' => 1];
                }
            }

            $statusRaw = $props['Status']['select']['name'] ?? 'Dipinjam';

            if ($statusRaw === 'Dikembalikan') {
                $status = 'Dikembalikan';
                $cls = 'dikembalikan';
            } elseif ($tglPinjam && $tglPinjam < $today) {
                $status = 'Terlambat';
                $cls = 'terlambat';
            } else {
                $status = 'Dipinjam';
                $cls = 'dipinjam';
            }

            $items[] = [
                'page_id'      => $item['id'],
                'url'          => $item['url'] ?? '#',
                'kegiatan'     => $kegiatan,
                'pic'          => $pic,
                'lokasi'       => $lokasi,
                'tgl_kegiatan' => $tglKegiatan,
                'tgl_pinjam'   => $tglPinjam,
                'tgl_kembali'  => $tglKembali,
                'alat_arr'     => $parsedAlatArr,
                'alat_display' => $alatDisplay,
                'detail_alat'  => $detailAlat,
                'durasi'       => $durasi,
                'keterangan'   => $keterangan,
                'status'       => $status,
                'cls'          => $cls,
            ];
        }

        // Cek apakah masih ada halaman berikutnya
        $has_more = $data['has_more'] ?? false;
        $start_cursor = $data['next_cursor'] ?? null;
    }

    return $items;
}