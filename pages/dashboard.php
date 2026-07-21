<?php
 $base = '..'; 
 $pageTitle = 'Dashboard'; 
 $activeMenu = 'dashboard';

require_once __DIR__ . '/../api/agenda.php';

 $today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
 $hour  = (int) $today->format('H');
 $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));

// --- AMBIL DATA AGENDA DARI NOTION (Dengan Fallback Dummy) ---
 $todayDateStr = $today->format('Y-m-d');
 $fetchedAgenda = getAgenda($todayDateStr, 'asc');

 $agenda = [];
if (is_array($fetchedAgenda) && !isset($fetchedAgenda['error'])) {
    foreach ($fetchedAgenda as $a) {
        $agenda[] = [
            'time' => substr($a['time'], 0, 5),
            'title' => $a['title'],
            // Pakai tag & tone yang sama persis dengan yang dihasilkan
            // getAgenda()/categoryTone() di api/agenda.php — biar konsisten
            // dengan halaman Daily Agenda (termasuk kapitalisasi aslinya,
            // tidak dipaksa uppercase lagi).
            'tag' => $a['tag'] ?? 'Umum',
            'tone' => $a['tone'] ?? 'gray',
            'pic' => $a['pic'] ?? '-'
        ];
    }
} else {
    // Fallback Dummy jika Notion error
    $agenda = [
        ['time' => '09.00', 'title' => 'Podcast Luar Studio',        'tag' => 'PODCAST',  'tone'=>'purple', 'pic' => 'Alfan'],
        ['time' => '11.00', 'title' => 'Persiapan Senam Sore RRI',   'tag' => 'EVENT',    'tone'=>'orange', 'pic' => 'Yanuar'],
        ['time' => '15.30', 'title' => 'Live Youtube: Sore Ceria',   'tag' => 'LIVE',     'tone'=>'red',    'pic' => 'Alif'],
        ['time' => '19.00', 'title' => 'Produksi Ruatan Malam',      'tag' => 'PRODUKSI', 'tone'=>'blue',   'pic' => 'Yayang'],
    ];
}

// --- DATA DUMMY METRICS (Sesuaikan dengan fetch database Anda nanti) ---
 $checklistReady = 18; $checklistTotal = 22;
 $checklistPct = $checklistTotal > 0 ? round($checklistReady / $checklistTotal * 100) : 0;

 $checklist = [
    ['name' => 'Mixer Audio',    'status' => 'Baik', 'ok' => true],
    ['name' => 'Microphone',     'status' => 'Baik', 'ok' => true],
    ['name' => 'PC Streaming',   'status' => 'Baik', 'ok' => true],
    ['name' => 'Encoder',        'status' => 'Perlu Dicek', 'ok' => false],
];

// Filter hanya yang belum selesai untuk dashboard
 $troubleshootActive = [
    ['title' => 'Internet Telkom (IP 60) tidak tersambung', 'status' => 'Belum Selesai', 'cls'=>'belum', 'date' => '8 Jul, 08.45'],
    ['title' => 'Output Radio Kepsta Putus Nyambung',       'status' => 'Proses',        'cls'=>'proses','date' => '7 Jul, 14.20'],
];
 $activeTroubleCount = count($troubleshootActive);

// --- DATA YOUTUBE GALLERY ---
require_once __DIR__ . '/../api/youtube.php';
 $fetchedVideos = getYoutubeVideos();

 $gallery = [];
if (is_array($fetchedVideos) && !isset($fetchedVideos['error'])) {
    $sekarang = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
    $semingguLalu = clone $sekarang;
    $semingguLalu->modify('-7 days');
    $sekarang->modify('+1 day'); // Besok
    
    foreach ($fetchedVideos as $v) {
        $vDate = DateTime::createFromFormat('j M Y', $v['date'], new DateTimeZone('Asia/Jakarta'));
        if ($vDate) {
            $vDate->setTime(0, 0, 0); // Set ke jam 00:00 agar perbandingan akurat
            if ($vDate >= $semingguLalu && $vDate <= $sekarang) {
                $gallery[] = $v;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
  :root {
    --bg: #f8fafc;
    --card-bg: #ffffff;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border: #e2e8f0;
    --primary: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --purple: #8b5cf6;
  }

  body { background: var(--bg); color: var(--text-primary); }
  
  /* Header */
  .dash-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
  .dash-header h2 { font-size: 1.5rem; font-weight: 700; margin: 0; }
  .dash-header p { color: var(--text-secondary); margin: 0.25rem 0 0; font-size: 0.95rem; }
  .date-badge { background: var(--card-bg); border: 1px solid var(--border); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; }

  /* Key Metrics (3 Cards) */
  .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
  .metric-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; display: flex; align-items: flex-start; gap: 1rem; transition: transform 0.2s; }
  .metric-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  .metric-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
  .metric-icon.blue { background: #eff6ff; color: var(--primary); }
  .metric-icon.green { background: #ecfdf5; color: var(--success); }
  .metric-icon.orange { background: #fff7ed; color: var(--warning); }
  .metric-info h3 { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin: 0 0 0.25rem; font-weight: 600; }
  .metric-info .value { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: 0.25rem; }
  .metric-info .sub { font-size: 0.8rem; color: var(--text-secondary); }

  /* Main Layout */
  .main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
  @media (max-width: 900px) { .main-grid { grid-template-columns: 1fr; } }

  /* Cards */
  .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
  .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
  .card-title { font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem; }
  .card-body { padding: 1.25rem; }
  .link-btn { font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600; }
  .link-btn:hover { text-decoration: underline; }

  /* Agenda Timeline */
  .timeline-item { display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border); }
  .timeline-item:last-child { border-bottom: none; }
  .timeline-time { font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); width: 50px; flex-shrink: 0; padding-top: 2px; }
  .timeline-content { flex: 1; }
  .timeline-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem; }
  .timeline-meta { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
  .tag { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
  .tag.red { background: #fef2f2; color: var(--danger); }
  .tag.blue { background: #eff6ff; color: var(--primary); }
  .tag.orange { background: #fff7ed; color: var(--warning); }
  .tag.purple { background: #f5f3ff; color: var(--purple); }
  .tag.gray { background: #f3f4f6; color: var(--text-secondary); }
  .tag.green { background: #ecfdf5; color: var(--success); }
  .pic-badge { font-size: 0.75rem; color: var(--text-secondary); display: flex; align-items: center; gap: 4px; }

  /* Troubleshoot List */
  .trouble-item { display: flex; justify-content: space-between; align-items: flex-start; padding: 0.75rem; background: #fff; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 0.75rem; }
  .trouble-item:last-child { margin-bottom: 0; }
  .trouble-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 0.25rem; }
  .trouble-date { font-size: 0.75rem; color: var(--text-secondary); }
  .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
  .status-dot.belum { background: var(--danger); }
  .status-dot.proses { background: var(--warning); }
  .status-tag { font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 99px; white-space: nowrap; }
  .status-tag.belum { background: #fef2f2; color: var(--danger); }
  .status-tag.proses { background: #fff7ed; color: var(--warning); }

  /* Checklist Progress */
  .progress-wrap { margin-bottom: 1.25rem; }
  .progress-header { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
  .progress-bar { height: 8px; background: var(--border); border-radius: 99px; overflow: hidden; }
  .progress-fill { height: 100%; background: var(--success); border-radius: 99px; transition: width 0.5s ease; }
  .checklist-mini { display: flex; flex-direction: column; gap: 0.5rem; }
  .check-mini-item { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.5rem; background: var(--bg); border-radius: 6px; }
  .check-mini-item.ok { color: var(--success); }
  .check-mini-item.not-ok { color: var(--danger); font-weight: 600; }

  /* Quick Actions */
  .quick-actions { display: grid; gap: 0.75rem; }
  .btn-action { display: flex; align-items: center; gap: 0.75rem; width: 100%; padding: 0.85rem; border: 1px solid var(--border); background: var(--card-bg); border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; text-align: left; text-decoration: none; color: var(--text-primary); }
  .btn-action:hover { background: var(--bg); border-color: var(--primary); color: var(--primary); }
  .btn-action i { font-size: 1.25rem; }

  /* YouTube Compact Slider */
  .yt-section { margin-top: 1.5rem; }
  .yt-slider { display: flex; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 1rem; scrollbar-width: thin; }
  .yt-slider::-webkit-scrollbar { height: 6px; }
  .yt-slider::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }
  .yt-card { flex: 0 0 280px; scroll-snap-align: start; background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
  .yt-thumb { height: 140px; background: #e2e8f0; position: relative; }
  .yt-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .yt-badge { position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; }
  .yt-info { padding: 0.75rem; }
  .yt-title { 
    font-size: 0.85rem; 
    font-weight: 600; 
    line-height: 1.3; 
    margin-bottom: 0.5rem; 
    display: -webkit-box; 
    -webkit-line-clamp: 2; 
    line-clamp: 2; 
    -webkit-box-orient: vertical; 
    overflow: hidden; 
  }
  .yt-meta { font-size: 0.75rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; }
</style>

<div class="dash-header">
  <div>
    <h2><?= htmlspecialchars($greeting) ?>, TMB Team! 👋</h2>
    <p>Pantau ringkasan operasional dan pastikan semua perangkat siap.</p>
  </div>
  <div class="date-badge">
    <i class="ri-calendar-line"></i> <?= $today->format('l, j F Y') ?>
  </div>
</div>

<!-- 1. KEY METRICS -->
<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-icon blue"><i class="ri-calendar-event-line"></i></div>
    <div class="metric-info">
      <h3>Agenda Hari Ini</h3>
      <div class="value"><?= count($agenda) ?></div>
      <div class="sub">Kegiatan terjadwal</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon green"><i class="ri-checkbox-circle-line"></i></div>
    <div class="metric-info">
      <h3>Kesiapan Perangkat</h3>
      <div class="value"><?= $checklistPct ?>%</div>
      <div class="sub"><?= $checklistReady ?> dari <?= $checklistTotal ?> perangkat siap</div>
    </div>
  </div>
  <div class="metric-card">
    <div class="metric-icon orange"><i class="ri-tools-line"></i></div>
    <div class="metric-info">
      <h3>Troubleshoot Aktif</h3>
      <div class="value"><?= $activeTroubleCount ?></div>
      <div class="sub">Kasus belum selesai</div>
    </div>
  </div>
</div>

<!-- 2. MAIN CONTENT: Agenda & Troubleshoot -->
<div class="main-grid">
  <!-- Left Column: Agenda -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-time-line"></i> Jadwal Hari Ini</div>
      <a class="link-btn" href="<?= $base ?>/pages/agenda.php">Lihat Semua</a>
    </div>
    <div class="card-body" style="padding-top: 0.5rem;">
      <?php if (empty($agenda)): ?>
        <div style="text-align:center; padding: 2rem; color: var(--text-secondary);">Tidak ada agenda hari ini.</div>
      <?php else: ?>
        <?php foreach ($agenda as $a): ?>
        <div class="timeline-item">
          <div class="timeline-time"><?= htmlspecialchars($a['time']) ?></div>
          <div class="timeline-content">
            <div class="timeline-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="timeline-meta">
              <span class="tag <?= htmlspecialchars($a['tone']) ?>"><?= htmlspecialchars($a['tag']) ?></span>
              <span class="pic-badge"><i class="ri-user-line"></i> <?= htmlspecialchars($a['pic']) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Active Issues & Quick Actions -->
  <div style="display: flex; flex-direction: column; gap: 1.5rem;">
    
    <!-- Active Troubleshoot -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="ri-alert-line" style="color: var(--danger);"></i> Perlu Perhatian</div>
      </div>
      <div class="card-body">
        <?php if (empty($troubleshootActive)): ?>
          <div style="text-align:center; padding: 1.5rem; color: var(--success); font-weight: 600;">
            <i class="ri-checkbox-circle-line" style="font-size: 1.5rem; display:block; margin-bottom:0.5rem;"></i>
            Semua sistem aman
          </div>
        <?php else: ?>
          <?php foreach ($troubleshootActive as $tr): ?>
          <div class="trouble-item">
            <div>
              <div class="trouble-title"><?= htmlspecialchars($tr['title']) ?></div>
              <div class="trouble-date"><span class="status-dot <?= htmlspecialchars($tr['cls']) ?>"></span><?= htmlspecialchars($tr['date']) ?></div>
            </div>
            <span class="status-tag <?= htmlspecialchars($tr['cls']) ?>"><?= htmlspecialchars($tr['status']) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-body">
        <div class="quick-actions">
          <a href="<?= $base ?>/pages/troubleshoot.php?action=create" class="btn-action">
            <i class="ri-add-circle-line" style="color: var(--danger);"></i> Laporkan Gangguan
          </a>
          <a href="<?= $base ?>/pages/checklist.php" class="btn-action">
            <i class="ri-clipboard-line" style="color: var(--primary);"></i> Isi Checklist Harian
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- 3. BOTTOM SECTION: Checklist Detail & YouTube Recap -->
<div class="main-grid">
  
  <!-- Checklist Detail -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-equipment-line"></i> Status Perangkat Kritis</div>
      <a class="link-btn" href="<?= $base ?>/pages/checklist.php">Detail</a>
    </div>
    <div class="card-body">
      <div class="progress-wrap">
        <div class="progress-header">
          <span>Tingkat Kesiapan</span>
          <span style="color: var(--success);"><?= $checklistPct ?>%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?= $checklistPct ?>%;"></div>
        </div>
      </div>
      <div class="checklist-mini">
        <?php foreach ($checklist as $c): ?>
        <div class="check-mini-item <?= $c['ok'] ? 'ok' : 'not-ok' ?>">
          <span><?= htmlspecialchars($c['name']) ?></span>
          <span><?= htmlspecialchars($c['status']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- YouTube Recap -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-youtube-line" style="color: #ef4444;"></i> Live 7 Hari Terakhir</div>
      <a class="link-btn" href="<?= $base ?>/pages/gallery.php">Arsip</a>
    </div>
    <div class="card-body" style="padding: 1rem;">
      <?php if (empty($gallery)): ?>
        <div style="text-align:center; color: var(--text-secondary); font-size: 0.85rem; padding: 1rem;">
          Belum ada data live streaming minggu ini.
        </div>
      <?php else: ?>
        <div class="yt-slider">
          <?php foreach (array_slice($gallery, 0, 5) as $g): ?>
            <?php
              $title = $g['title'] ?? 'Tanpa Judul';
              $programa = $g['programa'] ?? 'Umum';
              $date = $g['date'] ?? 'TBA';
              $flyer = $g['flyer'] ?? '';
            ?>
            <div class="yt-card">
              <div class="yt-thumb">
                <?php if ($flyer): ?>
                  <img src="<?= htmlspecialchars($flyer) ?>" alt="Thumbnail">
                <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
                    <i class="ri-image-line" style="font-size:2rem;"></i>
                  </div>
                <?php endif; ?>
                <span class="yt-badge"><?= htmlspecialchars($programa) ?></span>
              </div>
              <div class="yt-info">
                <div class="yt-title"><?= htmlspecialchars($title) ?></div>
                <div class="yt-meta">
                  <i class="ri-calendar-line"></i> <?= htmlspecialchars($date) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>