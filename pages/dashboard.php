<?php
$base = '..'; $pageTitle = 'Dashboard'; $activeMenu = 'dashboard';

$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$hour  = (int) $today->format('H');
$greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$stats = [
    ['label' => 'Total Kegiatan Hari Ini',   'value' => '6',     'sub' => 'Agenda terjadwal',       'icon' => 'ri-calendar-line',       'tone' => 'blue'],
    ['label' => 'Checklist Selesai',         'value' => '18/22', 'sub' => '82% perangkat siap',       'icon' => 'ri-checkbox-circle-line','tone' => 'green'],
    ['label' => 'Troubleshoot Bulan Ini',    'value' => '12',    'sub' => '4 kasus belum selesai',    'icon' => 'ri-tools-line',          'tone' => 'orange'],
    ['label' => 'Live Streaming Bulan Ini',  'value' => '15',    'sub' => '+3 dari bulan lalu',       'icon' => 'ri-play-circle-line',    'tone' => 'purple'],
    ['label' => 'Jurnal Kegiatan',           'value' => '28',    'sub' => 'Total dokumentasi',        'icon' => 'ri-file-text-line',      'tone' => 'blue'],
    ['label' => 'Pemadaman PLN Bulan Ini',   'value' => '4',     'sub' => 'Total kejadian',           'icon' => 'ri-flashlight-line',     'tone' => 'orange'],
];

$agenda = [
    ['time' => '09.00', 'title' => 'Podcast Luar Studio',        'tag' => 'PODCAST',  'tone'=>'purple', 'pic' => 'Alfan'],
    ['time' => '11.00', 'title' => 'Persiapan Senam Sore RRI',   'tag' => 'EVENT',    'tone'=>'orange', 'pic' => 'Yanuar'],
    ['time' => '13.00', 'title' => 'Persiapan Senam Pagi RRI',   'tag' => 'EVENT',    'tone'=>'orange', 'pic' => 'Saiful'],
    ['time' => '15.30', 'title' => 'Live Youtube: Sore Ceria',   'tag' => 'LIVE',     'tone'=>'red',    'pic' => 'Alif'],
    ['time' => '19.00', 'title' => 'Produksi Ruatan Malam',      'tag' => 'PRODUKSI', 'tone'=>'blue',   'pic' => 'Yayang'],
    ['time' => '21.00', 'title' => 'Live Youtube: Simphoni Keroncong', 'tag' => 'LIVE', 'tone'=>'red',  'pic' => 'Yanuar'],
];

$checklist = [
    ['name' => 'Mixer Audio',    'status' => 'Baik',       'ok' => true],
    ['name' => 'Microphone',     'status' => 'Baik',       'ok' => true],
    ['name' => 'PC Streaming',   'status' => 'Baik',       'ok' => true],
    ['name' => 'Encoder',        'status' => 'Perlu Dicek','ok' => false],
    ['name' => 'Internet',       'status' => 'Baik',       'ok' => true],
];
$checklistReady = 18; $checklistTotal = 22;
$checklistPct = round($checklistReady / $checklistTotal * 100);

$troubleshootDist = [
    ['label' => 'Internet / Jaringan', 'pct' => 42, 'color' => '#2563eb'],
    ['label' => 'Perangkat Audio',     'pct' => 25, 'color' => '#10b981'],
    ['label' => 'PC / Komputer',       'pct' => 17, 'color' => '#f59e0b'],
    ['label' => 'Output Siaran',       'pct' => 11, 'color' => '#8b5cf6'],
    ['label' => 'Lainnya',             'pct' => 5,  'color' => '#94a3b8'],
];
$troubleshootTotal = 12;

$troubleshootRecent = [
    ['title' => 'Internet Telkom (IP 60) tidak tersambung', 'status' => 'Belum Selesai', 'cls'=>'belum', 'date' => '8 Jul 2026, 08.45'],
    ['title' => 'Output Radio Kepsta Putus Nyambung',       'status' => 'Proses',        'cls'=>'proses','date' => '7 Jul 2026, 14.20'],
    ['title' => 'Modulasi dari Music 1',                    'status' => 'Selesai',       'cls'=>'selesai','date' => '6 Jul 2026, 09.15'],
    ['title' => 'Internet PC dias tidak ada internet',      'status' => 'Selesai',       'cls'=>'selesai','date' => '5 Jul 2026, 11.30'],
];

require_once __DIR__ . '/../api/youtube.php';
$fetchedVideos = getYoutubeVideos();

$gallery = [];
if (is_array($fetchedVideos) && !isset($fetchedVideos['error'])) {
    $sekarang = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    
    // Pisahkan proses clone dan modify agar tidak terjadi syntax error
    $semingguLalu = clone $sekarang;
    $semingguLalu->modify('-7 days');
    
    $sekarang->modify('+1 day'); // Memastikan kegiatan hari ini ikut masuk batas akhir
    
    foreach ($fetchedVideos as $v) {
        // Karena di youtube.php tanggal diformat 'j M Y', kita ubah lagi jadi objek waktu
        $vDate = DateTime::createFromFormat('j M Y', $v['date']);
        
        if ($vDate) {
            $vDate->setTime(0, 0, 0); // Reset jam agar akurat
            
            // Cek apakah tanggal berada di rentang 7 hari terakhir
            if ($vDate >= $semingguLalu && $vDate <= $sekarang) {
                $gallery[] = $v;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2><?= htmlspecialchars($greeting) ?>, TMB Team! 👋</h2>
    <p>Pantau kegiatan operasional dan pastikan semua berjalan dengan baik.</p>
  </div>
  <div class="date-chip"><i class="ri-calendar-line"></i> <?= $today->format('l, j F Y') ?></div>
</div>

<div class="stats-row">
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-info">
      <h3><?= htmlspecialchars($s['label']) ?></h3>
      <div class="stat-value"><?= htmlspecialchars($s['value']) ?></div>
      <div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div>
    </div>
    <div class="stat-icon <?= $s['tone'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid-3">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-calendar-line"></i>Daily Agenda Hari Ini</div>
      <a class="link-btn" href="<?= $base ?>/pages/agenda.php">Lihat Semua</a>
    </div>
    <?php foreach ($agenda as $a): ?>
    <div style="display:flex;gap:14px;padding:12px 0;border-bottom:1px solid var(--border-color);">
      <div style="width:48px;flex-shrink:0;font-size:12.5px;font-weight:700;color:var(--text-secondary);"><?= htmlspecialchars($a['time']) ?></div>
      <div style="flex:1;min-width:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-size:13.5px;font-weight:600;"><?= htmlspecialchars($a['title']) ?></span>
        <span class="tag <?= $a['tone'] ?>"><?= $a['tag'] ?></span>
      </div>
      <div style="font-size:11px;color:var(--text-secondary);text-align:right;flex-shrink:0;">
        <span style="display:block;font-size:9.5px;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;">PIC</span><?= htmlspecialchars($a['pic']) ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-line-chart-line"></i>Ringkasan Aktivitas</div>
      <a class="link-btn" href="#">7 Hari Terakhir</a>
    </div>
    <div style="display:flex;gap:16px;font-size:11.5px;color:var(--text-secondary);margin-bottom:6px;">
      <span style="display:flex;align-items:center;gap:6px;"><i style="width:8px;height:8px;border-radius:50%;background:var(--primary);display:inline-block;"></i>Kegiatan</span>
      <span style="display:flex;align-items:center;gap:6px;"><i style="width:8px;height:8px;border-radius:50%;background:var(--success);display:inline-block;"></i>Live Streaming</span>
      <span style="display:flex;align-items:center;gap:6px;"><i style="width:8px;height:8px;border-radius:50%;background:var(--warning);display:inline-block;"></i>Troubleshoot</span>
    </div>
    <div style="position:relative;height:230px;"><canvas id="activityChart"></canvas></div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-checkbox-circle-line"></i>Checklist Perangkat</div>
      <a class="link-btn" href="<?= $base ?>/pages/checklist.php">Lihat Semua</a>
    </div>
    <div class="ring-wrap">
      <div class="ring">
        <svg width="74" height="74" viewBox="0 0 74 74">
          <circle cx="37" cy="37" r="31" fill="none" stroke="#e2e8f0" stroke-width="8"/>
          <circle cx="37" cy="37" r="31" fill="none" stroke="var(--primary)" stroke-width="8"
            stroke-dasharray="<?= round(2 * pi() * 31) ?>"
            stroke-dashoffset="<?= round(2 * pi() * 31 * (1 - $checklistPct / 100)) ?>"
            stroke-linecap="round"/>
        </svg>
        <div class="ring-pct"><?= $checklistPct ?>%</div>
      </div>
      <div class="ring-label"><h4>Perangkat Siap</h4><p><?= $checklistReady ?> dari <?= $checklistTotal ?> perangkat</p></div>
    </div>
    <?php foreach ($checklist as $c): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border-color);font-size:12.5px;">
      <span style="display:flex;align-items:center;gap:9px;font-weight:500;"><i class="ri-checkbox-blank-circle-fill" style="font-size:6px;color:var(--text-secondary);"></i><?= htmlspecialchars($c['name']) ?></span>
      <span style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:5px;color:<?= $c['ok'] ? 'var(--success)' : 'var(--warning)' ?>">
        <?= htmlspecialchars($c['status']) ?><i class="<?= $c['ok'] ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill' ?>"></i>
      </span>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card" style="grid-column: span 1;">
    <div class="card-header">
      <div class="card-title"><i class="ri-flashlight-line"></i>Log Pemadaman PLN</div>
      <a class="link-btn" href="<?= $base ?>/pages/pemadaman.php">Kelola</a>
    </div>
    <div style="padding: 1rem 0;">
      <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
        <div style="flex:1;">
          <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Pemadaman Terbaru</div>
          <div style="font-weight:600; font-size:13px;">Pemancar Studio</div>
          <div style="font-size:11px; color:var(--text-secondary);">10 Mei 2026, 07:12 - 07:17</div>
        </div>
        <span class="tag orange">Aktif</span>
      </div>
      <button class="btn btn-primary btn-sm" onclick="location.href='<?= $base ?>/pages/pemadaman.php?action=create'" style="width:100%;">
        <i class="ri-add-line"></i> Catat Pemadaman
      </button>
    </div>
  </div>
</div>

<div class="grid-2" style="margin-bottom:18px;">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="ri-pie-chart-line"></i>Distribusi Troubleshoot</div></div>
    <div style="display:flex;align-items:center;gap:20px;">
      <div style="position:relative;width:150px;height:150px;flex-shrink:0;">
        <canvas id="troubleDonut"></canvas>
        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
          <span style="font-size:22px;font-weight:800;"><?= $troubleshootTotal ?></span>
          <span style="font-size:10px;color:var(--text-secondary);">Total Kasus</span>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:9px;flex:1;">
        <?php foreach ($troubleshootDist as $t): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;">
          <span style="display:flex;align-items:center;gap:8px;font-weight:500;"><i style="width:8px;height:8px;border-radius:50%;display:inline-block;background:<?= $t['color'] ?>"></i><?= htmlspecialchars($t['label']) ?></span>
          <span style="font-weight:700;color:var(--text-secondary);"><?= $t['pct'] ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-history-line"></i>Troubleshoot Terbaru</div>
      <a class="link-btn" href="<?= $base ?>/pages/troubleshoot.php">Lihat Semua</a>
    </div>
    <?php foreach ($troubleshootRecent as $tr): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-color);gap:10px;">
      <div>
        <div style="font-size:13px;font-weight:600;margin-bottom:3px;"><?= htmlspecialchars($tr['title']) ?></div>
        <div style="font-size:11px;color:var(--text-secondary);"><?= htmlspecialchars($tr['date']) ?></div>
      </div>
      <span class="status-tag <?= $tr['cls'] ?>"><?= htmlspecialchars($tr['status']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-youtube-line"></i>Recap Youtube Live On This Week</div>
    <a class="link-btn" href="<?= $base ?>/pages/gallery.php">Lihat Semua</a>
  </div>
  <div class="slider-wrapper" style="position: relative;">
    <!-- Tombol Navigasi Kiri -->
    <button id="slideLeftBtn" onclick="scrollGallery(-1)" style="display:flex; opacity:0; visibility:hidden; transition:all 0.3s ease; position:absolute; left:-15px; top:50%; transform:translateY(-50%); z-index:10; background:var(--primary, #2563eb); color:#fff; border:none; width:34px; height:34px; border-radius:50%; cursor:pointer; box-shadow:0 3px 6px rgba(0,0,0,0.3); align-items:center; justify-content:center;">
      <i class="ri-arrow-left-s-line" style="font-size:22px;"></i>
    </button>

    <!-- Kontainer Slider (overflow-x: hidden agar mouse/touchpad tidak bisa scroll) -->
    <div id="youtubeSlider" style="display:flex; gap:16px; overflow-x:hidden; padding-bottom: 5px; padding-top: 5px; scroll-behavior: smooth;">
      
      <?php if (empty($gallery)): ?>
        <div style="width:100%; text-align:center; color: var(--text-secondary); padding: 2rem;">
          Data video untuk seminggu kebelakang belum tersedia.
        </div>
      <?php else: ?>
        <?php foreach ($gallery as $g): ?>
          <?php
            $title = isset($g['title']) && $g['title'] !== '' ? $g['title'] : 'Tanpa Judul';
            $namaAcara = isset($g['namaAcara']) && $g['namaAcara'] !== '' ? $g['namaAcara'] : '';
            $programa = isset($g['programa']) && $g['programa'] !== '' ? $g['programa'] : 'Umum';
            $programaColor = isset($g['programaColor']) ? $g['programaColor'] : 'gray';
            $date = isset($g['date']) && $g['date'] !== '' ? $g['date'] : 'TBA';
            $time = isset($g['time']) && $g['time'] !== '' ? $g['time'] : 'TBA';
            $flyer = isset($g['flyer']) && $g['flyer'] !== '' ? $g['flyer'] : '';
            
            $colorMap = [
                'gray'    => '#64748b', 'brown'   => '#92400e', 'orange'  => '#f59e0b',
                'yellow'  => '#fbbf24', 'green'   => '#10b981', 'blue'    => '#2563eb',
                'purple'  => '#8b5cf6', 'pink'    => '#ec4899', 'red'     => '#ef4444',
                'default' => '#64748b'
            ];
            $bgColor = isset($colorMap[$programaColor]) ? $colorMap[$programaColor] : $colorMap['default'];
          ?>
          
          <!-- ITEM SLIDER: Lebar dinamis (25% minus porsi gap) agar pas 4 kotak tanpa terpotong -->
          <div class="feed-card" style="display:flex; flex-direction:column; flex: 0 0 calc(25% - 12px); width: calc(25% - 12px);">
            <div class="feed-thumb" style="display:block; position:relative; overflow:hidden; background:#f0f0f0; min-height:180px;">
              <?php if ($flyer !== ''): ?>
                <img src="<?= htmlspecialchars($flyer, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; height:100%; object-fit:cover; position:absolute;">
              <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#e0e0e0; color:#999; position:absolute;">
                  <i class="ri-image-line" style="font-size:3rem;"></i>
                </div>
              <?php endif; ?>
              <span class="overlay-label" style="position:absolute; top:10px; left:10px; background:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; color:#fff; padding:4px 12px; border-radius:4px; font-size:12px; font-weight:bold; z-index:2;">
                <?= htmlspecialchars($programa, ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
            <div class="feed-info" style="flex:1; display:flex; flex-direction:column;">
              <div style="flex:1;">
                <?php if ($namaAcara !== ''): ?>
                  <div style="font-size:12px; color:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; font-weight:bold; margin-bottom:4px;">
                    <?= htmlspecialchars($namaAcara, ENT_QUOTES, 'UTF-8') ?>
                  </div>
                <?php endif; ?>
                <div class="title" style="white-space:normal;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="meta" style="font-size:13px; color:var(--text-secondary); border-top:1px solid var(--border-color); padding-top:12px; margin-top:12px;">
                <span><i class="ri-calendar-line"></i> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
                <span><i class="ri-time-line"></i> <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Tombol Navigasi Kanan -->
    <button id="slideRightBtn" onclick="scrollGallery(1)" style="display:flex; opacity:1; visibility:visible; transition:all 0.3s ease; position:absolute; right:-15px; top:50%; transform:translateY(-50%); z-index:10; background:var(--primary, #2563eb); color:#fff; border:none; width:34px; height:34px; border-radius:50%; cursor:pointer; box-shadow:0 3px 6px rgba(0,0,0,0.3); align-items:center; justify-content:center;">
      <i class="ri-arrow-right-s-line" style="font-size:22px;"></i>
    </button>
  </div>

  <!-- Script untuk mekanisme scroll horizontal dan hide/show tombol panah dengan animasi -->
  <script>
  function scrollGallery(direction) {
      const slider = document.getElementById('youtubeSlider');
      const card = slider.querySelector('.feed-card');
      
      if (!card) return;

      // Ambil lebar kotak secara dinamis ditambah gap (16px), lalu dikali 4
      const scrollAmount = (card.offsetWidth + 16) * 4; 
      
      // Lakukan scroll smooth
      slider.scrollBy({ left: direction * scrollAmount });
  }

  function updateSliderButtons() {
      const slider = document.getElementById('youtubeSlider');
      const leftBtn = document.getElementById('slideLeftBtn');
      const rightBtn = document.getElementById('slideRightBtn');

      if (!slider || !leftBtn || !rightBtn) return;

      // Animasi Tombol Kiri
      if (slider.scrollLeft <= 5) { // Diberi toleransi 5px untuk presisi browser
          leftBtn.style.opacity = '0';
          leftBtn.style.visibility = 'hidden';
      } else {
          leftBtn.style.opacity = '1';
          leftBtn.style.visibility = 'visible';
      }

      // Animasi Tombol Kanan
      if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 5) { // Diberi toleransi 5px
          rightBtn.style.opacity = '0';
          rightBtn.style.visibility = 'hidden';
      } else {
          rightBtn.style.opacity = '1';
          rightBtn.style.visibility = 'visible';
      }
  }

  document.addEventListener('DOMContentLoaded', function() {
      const slider = document.getElementById('youtubeSlider');
      if (slider) {
          // Menggunakan 'scrollend' agar tombol dicek TEPAT setelah animasi geser selesai berhenti
          slider.addEventListener('scrollend', updateSliderButtons);
          
          window.addEventListener('resize', updateSliderButtons);
          
          // Jalankan pengecekan pertama saat halaman dimuat
          setTimeout(updateSliderButtons, 100); 
      }
  });
  </script>
</div>


<script>
new Chart(document.getElementById('activityChart'), {
  type: 'line',
  data: {
    labels: ['2 Jul','3 Jul','4 Jul','5 Jul','6 Jul','7 Jul','8 Jul'],
    datasets: [
      { label:'Kegiatan', data:[10,17,13,20,18,24,22], borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,.06)', tension:.4, fill:true, pointRadius:3 },
      { label:'Live Streaming', data:[6,9,8,12,10,15,13], borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.06)', tension:.4, fill:true, pointRadius:3 },
      { label:'Troubleshoot', data:[2,4,3,6,4,7,5], borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.06)', tension:.4, fill:true, pointRadius:3 }
    ]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true, grid:{color:'#f1f5f9'}}, x:{grid:{display:false}} } }
});
new Chart(document.getElementById('troubleDonut'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($troubleshootDist, 'label')) ?>,
    datasets: [{ data: <?= json_encode(array_column($troubleshootDist, 'pct')) ?>, backgroundColor: <?= json_encode(array_column($troubleshootDist, 'color')) ?>, borderWidth: 0 }]
  },
  options: { cutout: '72%', plugins: { legend: { display: false } } }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>