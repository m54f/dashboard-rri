<?php
header('Content-Type: application/json');

// Matikan error output agar tidak merusak format JSON
error_reporting(0);

require_once __DIR__.'/../config/notion.php';

// Load semua file API dengan aman
$apiFiles = [
    __DIR__.'/pemadaman.php',
    __DIR__.'/troubleshoot.php',
    __DIR__.'/checklist.php',
    __DIR__.'/jurnal.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// 1. BAGIAN INI DIPERBARUI: Blokir error jika nama kosong dihapus
$namaInput = $_GET['nama'] ?? '';

// 2. BAGIAN INI DIPERBARUI: Fungsi pencocokan nama
// Jika targetName kosong, berarti mode "Laporan Keseluruhan", jadi anggap selalu true (cocok untuk semua data).
function isPetugasIni($targetName, $dataArray) {
    $targetClean = strtolower(trim($targetName));
    
    // Jika tidak ada nama yang dikirim dari frontend, tampilkan semua laporan
    if (empty($targetClean)) {
        return true;
    }

    if (empty($dataArray)) return false;
    
    $namesToCheck = [];
    
    // Jika data adalah array (multi-select)
    if (is_array($dataArray)) {
        $namesToCheck = $dataArray;
    } 
    // Jika data adalah string (dipisah koma)
    else {
        $namesToCheck = explode(',', $dataArray);
    }
    
    foreach ($namesToCheck as $n) {
        $nClean = strtolower(trim($n));
        if (empty($nClean)) continue;
        
        // Cek jika salah satu mengandung yang lain (misal: "saiful" cocok dengan "saiful anam")
        if (strpos($targetClean, $nClean) !== false || strpos($nClean, $targetClean) !== false) {
            return true;
        }
    }
    return false;
}

// Fungsi bantu untuk ekstrak URL foto dari array Notion files
function extractFotoUrl($dataArray, $keysToCheck) {
    foreach ($keysToCheck as $key) {
        if (isset($dataArray[$key]) && is_array($dataArray[$key]) && !empty($dataArray[$key])) {
            $file = $dataArray[$key][0];
            if (isset($file['url'])) return $file['url']; // Jika formatnya langsung array berisi url
            if (isset($file['file']['url'])) return $file['file']['url']; // Jika upload ke Notion
            if (isset($file['external']['url'])) return $file['external']['url']; // Jika link eksternal
        }
    }
    return ''; // Kosong jika tidak ada foto
}

$riwayat = [];
$totalDurasiMenit = 0;
$statistik = ['Pemadaman Listrik' => 0, 'Troubleshoot' => 0, 'Checklist' => 0, 'Jurnal Kegiatan' => 0];
$debugLog = [];

// ==========================================
// 1. DARI LOGBOOK PEMADAMAN
// ==========================================
if (function_exists('getPemadaman')) {
    try {
        $pemadaman = getPemadaman();
        if (is_array($pemadaman) && !isset($pemadaman['error'])) {
            foreach ($pemadaman as $p) {
                if (isPetugasIni($namaInput, $p['petugas_arr'] ?? []) || isPetugasIni($namaInput, $p['petugas'] ?? '')) {
                    $statistik['Pemadaman Listrik']++;
                    $durasiText = '-';
                    if (!empty($p['tanggal_raw']) && !empty($p['end_time']) && $p['end_time'] !== '-') {
                        try {
                            $start = new DateTime($p['tanggal_raw']);
                            $endRaw = explode('T', $p['tanggal_raw'])[0] . 'T' . $p['end_time'] . ':00+07:00';
                            $end = new DateTime($endRaw);
                            $diff = $start->diff($end);
                            $totalDurasiMenit += ($diff->h * 60) + $diff->i;
                            $durasiText = $diff->h . 'j ' . $diff->i . 'm';
                        } catch (Exception $e) {}
                    }
                    
                    // Ambil URL foto (sesuaikan 'foto', 'docs', atau 'eviden' dengan nama kolom di Notion Anda)
                    $fotoUrl = extractFotoUrl($p, ['foto', 'docs', 'eviden', 'dokumentasi']);
                    
                    // 3. TAMBAHAN OPSIONAL: Selipkan nama petugas ke dalam deskripsi agar jelas laporan ini milik siapa
                    $namaLabel = empty($namaInput) ? " (Oleh: " . ($p['petugas'] ?? 'Anonim') . ")" : "";
                    
                    $riwayat[] = [
                        'tanggal' => $p['tanggal'] ?? '-',
                        'kategori' => 'Pemadaman Listrik',
                        'deskripsi' => ($p['pemancar'] ?? '-') . $namaLabel,
                        'status' => 'Selesai',
                        'durasi' => $durasiText,
                        'url' => $p['url'] ?? '#',
                        'foto' => $fotoUrl
                    ];
                }
            }
        } else {
            $debugLog[] = 'Pemadaman API Error: ' . ($pemadaman['error'] ?? 'Unknown');
        }
    } catch (Exception $e) {
        $debugLog[] = 'Pemadaman Exception: ' . $e->getMessage();
    }
}

// ==========================================
// 2. DARI TROUBLESHOOT
// ==========================================
if (function_exists('getTroubleshoot')) {
    try {
        $troubleshoot = getTroubleshoot();
        if (is_array($troubleshoot) && !isset($troubleshoot['error'])) {
            foreach ($troubleshoot as $t) {
                if (isPetugasIni($namaInput, $t['petugas_arr'] ?? []) || isPetugasIni($namaInput, $t['petugas'] ?? '') || isPetugasIni($namaInput, $t['pic'] ?? '')) {
                    $statistik['Troubleshoot']++;
                    
                    // Ambil URL foto dari array dokumentasi troubleshoot
                    $fotoUrl = '';
                    if (!empty($t['dokumentasi'][0]['url'])) {
                        $fotoUrl = $t['dokumentasi'][0]['url'];
                    } else {
                        $fotoUrl = extractFotoUrl($t, ['foto', 'docs', 'eviden']);
                    }
                    
                    $namaLabel = empty($namaInput) ? " (Oleh: " . ($t['petugas'] ?? $t['pic'] ?? 'Anonim') . ")" : "";
                    
                    $riwayat[] = [
                        'tanggal' => $t['date'] ?? '-',
                        'kategori' => 'Troubleshoot',
                        'deskripsi' => ($t['title'] ?? '-') . $namaLabel,
                        'status' => $t['status'] ?? '-',
                        'durasi' => '-',
                        'url' => $t['url'] ?? '#',
                        'foto' => $fotoUrl
                    ];
                }
            }
        } else {
            $debugLog[] = 'Troubleshoot API Error: ' . ($troubleshoot['error'] ?? 'Unknown');
        }
    } catch (Exception $e) {
        $debugLog[] = 'Troubleshoot Exception: ' . $e->getMessage();
    }
}

// ==========================================
// 3. DARI CHECKLIST
// ==========================================
if (function_exists('getChecklist')) {
    try {
        $checklist = getChecklist();
        if (is_array($checklist) && !isset($checklist['error'])) {
            foreach ($checklist as $c) {
                if (isPetugasIni($namaInput, $c['petugas_arr'] ?? []) || isPetugasIni($namaInput, $c['petugas'] ?? '')) {
                    $statistik['Checklist']++;
                    
                    $fotoUrl = extractFotoUrl($c, ['foto', 'docs', 'eviden', 'dokumentasi']);
                    
                    $namaLabel = empty($namaInput) ? " (Oleh: " . ($c['petugas'] ?? 'Anonim') . ")" : "";
                    
                    $riwayat[] = [
                        'tanggal' => $c['tanggal'] ?? '-',
                        'kategori' => 'Checklist',
                        'deskripsi' => 'Cek ' . ($c['done_items'] ?? 0) . '/5 Perangkat' . $namaLabel,
                        'status' => ($c['is_complete'] ?? false) ? 'Lengkap' : 'Belum Lengkap',
                        'durasi' => '-',
                        'url' => $c['url'] ?? '#',
                        'foto' => $fotoUrl
                    ];
                }
            }
        } else {
            $debugLog[] = 'Checklist API Error: ' . ($checklist['error'] ?? 'Unknown');
        }
    } catch (Exception $e) {
        $debugLog[] = 'Checklist Exception: ' . $e->getMessage();
    }
}

// ==========================================
// 4. DARI JURNAL KEGIATAN
// ==========================================
if (function_exists('getJurnal')) {
    try {
        $jurnal = getJurnal();
        if (is_array($jurnal) && !isset($jurnal['error'])) {
            foreach ($jurnal as $j) {
                if (isPetugasIni($namaInput, $j['ops_arr'] ?? []) || isPetugasIni($namaInput, $j['op'] ?? '')) {
                    $statistik['Jurnal Kegiatan']++;
                    
                    // Ambil URL foto dari array docs jurnal
                    $fotoUrl = '';
                    if (!empty($j['docs'][0]['url'])) {
                        $fotoUrl = $j['docs'][0]['url'];
                    } else {
                        $fotoUrl = extractFotoUrl($j, ['foto', 'eviden', 'dokumentasi']);
                    }
                    
                    $namaLabel = empty($namaInput) ? " (Oleh: " . ($j['op'] ?? 'Anonim') . ")" : "";
                    
                    $riwayat[] = [
                        'tanggal' => $j['date'] ?? '-',
                        'kategori' => 'Jurnal Kegiatan',
                        'deskripsi' => ($j['title'] ?? '-') . $namaLabel,
                        'status' => $j['status'] ?? '-',
                        'durasi' => '-',
                        'url' => $j['url'] ?? '#',
                        'foto' => $fotoUrl
                    ];
                }
            }
        } else {
            $debugLog[] = 'Jurnal API Error: ' . ($jurnal['error'] ?? 'Unknown');
        }
    } catch (Exception $e) {
        $debugLog[] = 'Jurnal Exception: ' . $e->getMessage();
    }
}

// Urutkan riwayat berdasarkan tanggal terbaru
usort($riwayat, function($a, $b) {
    $timeA = strtotime($a['tanggal']);
    $timeB = strtotime($b['tanggal']);
    return $timeB - $timeA;
});

$totalJam = floor($totalDurasiMenit / 60);
$sisaMenit = $totalDurasiMenit % 60;

echo json_encode([
    'nama' => empty($namaInput) ? 'Semua Pegawai' : $namaInput,
    'total_tugas' => count($riwayat),
    'total_durasi' => $totalJam . ' Jam ' . $sisaMenit . ' Menit',
    'statistik' => $statistik,
    'riwayat' => $riwayat,
    'debug' => $debugLog // Sengaja ditampilkan untuk pengecekan
]);
?>