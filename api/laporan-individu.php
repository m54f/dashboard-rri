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

 $namaInput = $_GET['nama'] ?? '';
if (empty($namaInput)) {
    echo json_encode(['error' => 'Nama petugas wajib diisi']);
    exit;
}

// Fungsi pencocokan nama yang pintar (mencocokkan sebagian nama)
function isPetugasIni($targetName, $dataArray) {
    if (empty($dataArray)) return false;
    
    $targetClean = strtolower(trim($targetName));
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

 $riwayat = [];
 $totalDurasiMenit = 0;
 $statistik = ['Pemadaman Listrik' => 0, 'Troubleshoot' => 0, 'Checklist' => 0, 'Jurnal Kegiatan' => 0];

// ==========================================
// 1. DARI LOGBOOK PEMADAMAN
// ==========================================
if (function_exists('getPemadaman')) {
    $pemadaman = getPemadaman();
    // Pastikan bukan error array
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
                $riwayat[] = [
                    'tanggal' => $p['tanggal'] ?? '-',
                    'kategori' => 'Pemadaman Listrik',
                    'deskripsi' => $p['pemancar'] ?? '-',
                    'status' => 'Selesai',
                    'durasi' => $durasiText,
                    'url' => $p['url'] ?? '#'
                ];
            }
        }
    }
}

// ==========================================
// 2. DARI TROUBLESHOOT
// ==========================================
if (function_exists('getTroubleshoot')) {
    $troubleshoot = getTroubleshoot();
    if (is_array($troubleshoot) && !isset($troubleshoot['error'])) {
        foreach ($troubleshoot as $t) {
            if (isPetugasIni($namaInput, $t['petugas_arr'] ?? []) || isPetugasIni($namaInput, $t['petugas'] ?? '') || isPetugasIni($namaInput, $t['pic'] ?? '')) {
                $statistik['Troubleshoot']++;
                $riwayat[] = [
                    'tanggal' => $t['date'] ?? '-',
                    'kategori' => 'Troubleshoot',
                    'deskripsi' => $t['title'] ?? '-',
                    'status' => $t['status'] ?? '-',
                    'durasi' => '-',
                    'url' => $t['url'] ?? '#'
                ];
            }
        }
    }
}

// ==========================================
// 3. DARI CHECKLIST
// ==========================================
if (function_exists('getChecklist')) {
    $checklist = getChecklist();
    if (is_array($checklist) && !isset($checklist['error'])) {
        foreach ($checklist as $c) {
            if (isPetugasIni($namaInput, $c['petugas_arr'] ?? []) || isPetugasIni($namaInput, $c['petugas'] ?? '')) {
                $statistik['Checklist']++;
                $riwayat[] = [
                    'tanggal' => $c['tanggal'] ?? '-',
                    'kategori' => 'Checklist ' . ($c['checklist_type'] ?? ''),
                    'deskripsi' => 'Cek ' . ($c['done_items'] ?? 0) . '/5 Perangkat',
                    'status' => ($c['is_complete'] ?? false) ? 'Lengkap' : 'Belum Lengkap',
                    'durasi' => '-',
                    'url' => $c['url'] ?? '#'
                ];
            }
        }
    }
}

// ==========================================
// 4. DARI JURNAL KEGIATAN
// ==========================================
if (function_exists('getJurnal')) {
    $jurnal = getJurnal();
    if (is_array($jurnal) && !isset($jurnal['error'])) {
        foreach ($jurnal as $j) {
            if (isPetugasIni($namaInput, $j['ops_arr'] ?? []) || isPetugasIni($namaInput, $j['op'] ?? '')) {
                $statistik['Jurnal Kegiatan']++;
                $riwayat[] = [
                    'tanggal' => $j['date'] ?? '-',
                    'kategori' => $j['tag'] ?? 'Jurnal',
                    'deskripsi' => $j['title'] ?? '-',
                    'status' => $j['status'] ?? '-',
                    'durasi' => '-',
                    'url' => $j['url'] ?? '#'
                ];
            }
        }
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
    'nama' => $namaInput,
    'total_tugas' => count($riwayat),
    'total_durasi' => $totalJam . ' Jam ' . $sisaMenit . ' Menit',
    'statistik' => $statistik,
    'riwayat' => $riwayat
]);
?>