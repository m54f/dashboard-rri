<?php
$base = '..'; $pageTitle = 'Dashboard'; $activeMenu = 'dashboard';

$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$hour  = (int) $today->format('H');
$greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$stats = [
    ['label' => 'Total Kegiatan Hari Ini',   'value' => '6',     'sub' => 'Agenda terjadwal',        'icon' => 'ri-calendar-line',       'tone' => 'blue'],
    ['label' => 'Checklist Selesai',         'value' => '18/22', 'sub' => '82% perangkat siap',       'icon' => 'ri-checkbox-circle-line','tone' => 'green'],
    ['label' => 'Troubleshoot Bulan Ini',    'value' => '12',    'sub' => '4 kasus belum selesai',    'icon' => 'ri-tools-line',          'tone' => 'orange'],
    ['label' => 'Live Streaming Bulan Ini',  'value' => '15',    'sub' => '+3 dari bulan lalu',       'icon' => 'ri-play-circle-line',    'tone' => 'purple'],
    ['label' => 'Jurnal Kegiatan',           'value' => '28',    'sub' => 'Total dokumentasi',        'icon' => 'ri-file-text-line',      'tone' => 'blue'],
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

$gallery = [
    ['title' => 'Deteksi Dini Kekerasan Pada Anak',            'show' => 'Obrolan Psikologi', 'time' => '20:10', 'date' => '9 Jul 2026', 'live' => true],
    ['title' => 'Sore Ceria - Sinergi Radio Dalam Kampanye CBP Rupiah', 'show' => 'Sore Ceria', 'time' => '16:00', 'date' => '9 Jul 2026', 'live' => true],
    ['title' => 'Berita Siang RRI Malang',                     'show' => 'Berita Siang',      'time' => '15:30', 'date' => '8 Jul 2026', 'live' => true],
    ['title' => 'Catur Laras Jagongan UMKM',                   'show' => 'Catur Laras',       'time' => '18:45', 'date' => '7 Jul 2026', 'live' => false],
];

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
    <div class="card-title"><i class="ri-youtube-line"></i>Gallery Youtube Live On This Week</div>
    <a class="link-btn" href="<?= $base ?>/pages/gallery.php">Lihat Semua</a>
  </div>
  <div class="gallery-grid">
    <?php foreach ($gallery as $g): ?>
    <div class="feed-card">
      <div class="feed-thumb">
        <?php if ($g['live']): ?><span class="live-badge">LIVE</span><?php endif; ?>
        <span class="feed-badge-time"><?= htmlspecialchars($g['time']) ?></span>
        <span class="overlay-label"><?= htmlspecialchars($g['show']) ?></span>
      </div>
      <div class="feed-info">
        <div class="title"><?= htmlspecialchars($g['title']) ?></div>
        <div class="meta"><span><?= htmlspecialchars($g['date']) ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
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
