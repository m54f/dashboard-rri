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
            'tag' => $a['tag'] ?? 'Umum',
            'tone' => $a['tone'] ?? 'gray',
            'pic' => $a['pic'] ?? '-'
        ];
    }
} else {
    $agenda = [
        ['time' => '09.00', 'title' => 'Podcast Luar Studio',        'tag' => 'PODCAST',  'tone'=>'purple', 'pic' => 'Alfan'],
        ['time' => '11.00', 'title' => 'Persiapan Senam Sore RRI',   'tag' => 'EVENT',    'tone'=>'orange', 'pic' => 'Yanuar'],
        ['time' => '15.30', 'title' => 'Live Youtube: Sore Ceria',   'tag' => 'LIVE',     'tone'=>'red',    'pic' => 'Alif'],
        ['time' => '19.00', 'title' => 'Produksi Ruatan Malam',      'tag' => 'PRODUKSI', 'tone'=>'blue',   'pic' => 'Yayang'],
    ];
}

// --- DATA DUMMY METRICS ---
$checklistReady = 18; $checklistTotal = 22;
$checklistPct = $checklistTotal > 0 ? round($checklistReady / $checklistTotal * 100) : 0;

$checklist = [
    ['name' => 'Mixer Audio',    'status' => 'Baik', 'ok' => true],
    ['name' => 'Microphone',     'status' => 'Baik', 'ok' => true],
    ['name' => 'PC Streaming',   'status' => 'Baik', 'ok' => true],
    ['name' => 'Encoder',        'status' => 'Perlu Dicek', 'ok' => false],
];

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
    $sekarang->modify('+1 day');
    
    foreach ($fetchedVideos as $v) {
        $vDate = DateTime::createFromFormat('j M Y', $v['date'], new DateTimeZone('Asia/Jakarta'));
        if ($vDate) {
            $vDate->setTime(0, 0, 0);
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
    --primary-light: #eff6ff;
    --success: #10b981;
    --success-light: #ecfdf5;
    --warning: #f59e0b;
    --warning-light: #fff7ed;
    --danger: #ef4444;
    --danger-light: #fef2f2;
    --purple: #8b5cf6;
    --purple-light: #f5f3ff;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
    --radius: 16px;
  }

  body { background: var(--bg); color: var(--text-primary); font-family: 'Inter', system-ui, -apple-system, sans-serif; }
  
  /* Header */
  .dash-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
  .dash-header h2 { font-size: 1.75rem; font-weight: 800; margin: 0; color: var(--text-primary); letter-spacing: -0.025em; }
  .dash-header p { color: var(--text-secondary); margin: 0.35rem 0 0; font-size: 1rem; }
  .date-badge { 
    background: var(--card-bg); border: 1px solid var(--border); padding: 0.6rem 1.2rem; border-radius: 99px; 
    font-size: 0.9rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 0.6rem;
    box-shadow: var(--shadow-sm); transition: all 0.2s ease;
  }
  .date-badge:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
  .date-badge i { color: var(--primary); font-size: 1.1rem; }

  /* Key Metrics */
  .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
  .metric-card { 
    background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; 
    display: flex; align-items: flex-start; gap: 1.25rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-sm); position: relative; overflow: hidden;
  }
  .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; opacity: 0; transition: opacity 0.3s ease; }
  .metric-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: transparent; }
  .metric-card:hover::before { opacity: 1; }
  .metric-card.blue::before { background: var(--primary); }
  .metric-card.green::before { background: var(--success); }
  .metric-card.orange::before { background: var(--warning); }

  .metric-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; flex-shrink: 0; transition: transform 0.3s ease; }
  .metric-card:hover .metric-icon { transform: scale(1.05); }
  .metric-icon.blue { background: var(--primary-light); color: var(--primary); }
  .metric-icon.green { background: var(--success-light); color: var(--success); }
  .metric-icon.orange { background: var(--warning-light); color: var(--warning); }
  
  .metric-info h3 { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin: 0 0 0.4rem; font-weight: 700; }
  .metric-info .value { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 0.35rem; color: var(--text-primary); letter-spacing: -0.025em; }
  .metric-info .sub { font-size: 0.85rem; color: var(--text-secondary); font-weight: 500; }

  /* Main Layout (Atas: Agenda & Troubleshoot) */
  .main-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
  
  /* Layout Khusus Bagian Bawah */
  .bottom-grid { display: grid; grid-template-columns: 1.6fr 0.8fr; gap: 1.5rem; align-items: start; margin-bottom: 1.5rem; }

  @media (max-width: 1024px) { 
    .main-grid { grid-template-columns: 1fr; } 
    .bottom-grid { grid-template-columns: 1fr; } /* <-- MEMBUATNYA KE BAWAH DI HP/TABLET */
  }

  /* Cards General */
  .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); transition: box-shadow 0.3s ease; }
  .card:hover { box-shadow: var(--shadow-md); }
  .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(248, 250, 252, 0.5); }
  .card-title { font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 0.6rem; color: var(--text-primary); }
  .card-title i { font-size: 1.2rem; color: var(--primary); }
  .card-body { padding: 1.5rem; }
  .link-btn { font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 4px; transition: gap 0.2s ease; }
  .link-btn:hover { gap: 8px; }

  /* Agenda Timeline */
  .timeline-list { position: relative; padding-left: 1.5rem; }
  .timeline-list::before { content: ''; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background: var(--border); border-radius: 2px; }
  .timeline-item { position: relative; padding: 0 0 1.5rem 1.5rem; }
  .timeline-item:last-child { padding-bottom: 0; }
  .timeline-item::before {
    content: ''; position: absolute; left: -1.5rem; top: 6px; width: 16px; height: 16px;
    border-radius: 50%; background: var(--card-bg); border: 3px solid var(--primary); z-index: 1; transition: all 0.2s ease;
  }
  .timeline-item:hover::before { background: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }
  .timeline-time { font-size: 0.85rem; font-weight: 700; color: var(--primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem; }
  .timeline-title { font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary); line-height: 1.4; }
  .timeline-meta { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
  
  .tag { font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.025em; }
  .tag.red { background: var(--danger-light); color: var(--danger); }
  .tag.blue { background: var(--primary-light); color: var(--primary); }
  .tag.orange { background: var(--warning-light); color: var(--warning); }
  .tag.purple { background: var(--purple-light); color: var(--purple); }
  .tag.gray { background: #f1f5f9; color: var(--text-secondary); }
  .tag.green { background: var(--success-light); color: var(--success); }
  
  .pic-badge { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 5px; background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border); }
  .pic-badge i { color: var(--text-secondary); }

  /* Troubleshoot List */
  .trouble-item { 
    display: flex; justify-content: space-between; align-items: flex-start; padding: 1rem; 
    background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 0.75rem;
    transition: all 0.2s ease; border-left: 4px solid transparent;
  }
  .trouble-item:hover { transform: translateX(4px); box-shadow: var(--shadow-sm); }
  .trouble-item.belum { border-left-color: var(--danger); }
  .trouble-item.proses { border-left-color: var(--warning); }
  
  .trouble-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.4rem; color: var(--text-primary); }
  .trouble-date { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; }
  .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
  .status-dot.belum { background: var(--danger); box-shadow: 0 0 0 3px var(--danger-light); }
  .status-dot.proses { background: var(--warning); box-shadow: 0 0 0 3px var(--warning-light); }
  
  .status-tag { font-size: 0.75rem; font-weight: 700; padding: 5px 12px; border-radius: 99px; white-space: nowrap; }
  .status-tag.belum { background: var(--danger-light); color: var(--danger); }
  .status-tag.proses { background: var(--warning-light); color: var(--warning); }

  /* ===== CHECKLIST COMPACT (DIPERKECIL) ===== */
  .checklist-compact .card-body { padding: 1rem 1.25rem; }
  .checklist-compact .progress-wrap { margin-bottom: 0.75rem; }
  .checklist-compact .progress-header { font-size: 0.8rem; margin-bottom: 0.3rem; }
  .checklist-compact .progress-bar { height: 6px; }
  .checklist-compact .checklist-mini { gap: 0.4rem; }
  .checklist-compact .check-mini-item { 
    padding: 0.5rem 0.75rem; font-size: 0.8rem; border-radius: 8px; 
  }
  .checklist-compact .check-mini-item i { font-size: 1rem; margin-right: 4px; }

  /* Progress Bar Animation */
  .progress-fill { 
    height: 100%; background: linear-gradient(90deg, var(--success), #34d399); border-radius: 99px; 
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
  }
  .progress-fill::after {
    content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
  }
  @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

  .check-mini-item { 
    display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding: 0.75rem 1rem; 
    background: #f8fafc; border-radius: 10px; border: 1px solid transparent; transition: all 0.2s ease;
  }
  .check-mini-item:hover { background: #fff; border-color: var(--border); }
  .check-mini-item.ok { color: var(--success); font-weight: 600; }
  .check-mini-item.not-ok { color: var(--danger); font-weight: 600; background: var(--danger-light); border-color: rgba(239, 68, 68, 0.2); }
  .check-mini-item i { margin-right: 6px; font-size: 1.1rem; }

  /* Quick Actions */
  .quick-actions { display: grid; gap: 0.75rem; }
  .btn-action { 
    display: flex; align-items: center; gap: 0.85rem; width: 100%; padding: 1rem 1.25rem; 
    border: 1px solid var(--border); background: var(--card-bg); border-radius: 12px; 
    font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.2s ease; 
    text-align: left; text-decoration: none; color: var(--text-primary);
  }
  .btn-action:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
  .btn-action i { font-size: 1.35rem; transition: transform 0.2s ease; }
  .btn-action:hover i { transform: scale(1.1); }

  /* ===== YOUTUBE SECTION ===== */
  .yt-slider { 
    display: flex; 
    gap: 1.25rem; 
    overflow-x: auto; 
    scroll-snap-type: x mandatory; 
    padding-bottom: 1rem; 
    scrollbar-width: thin; 
    margin: 0 -1.5rem; 
    padding: 0 1.5rem 1rem 1.5rem; 
  }
  .yt-slider::-webkit-scrollbar { height: 6px; }
  .yt-slider::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
  
  .yt-card { 
    flex: 0 0 300px; 
    scroll-snap-align: start; 
    background: var(--card-bg); 
    border: 1px solid var(--border); 
    border-radius: 14px; 
    overflow: hidden; 
    transition: all 0.3s ease;
  }
  .yt-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: transparent; }
  
  .yt-thumb { height: 160px; background: #e2e8f0; position: relative; overflow: hidden; }
  .yt-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
  .yt-card:hover .yt-thumb img { transform: scale(1.05); }
  .yt-thumb::after {
    content: '\f16a'; font-family: 'remixicon'; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: rgba(255,255,255,0.9); background: rgba(0,0,0,0.2); opacity: 0; transition: opacity 0.3s ease;
  }
  .yt-card:hover .yt-thumb::after { opacity: 1; }
  .yt-badge { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 4px 8px; border-radius: 6px; z-index: 2; }
  
  .yt-info { padding: 1rem 1.25rem; }
  .yt-title { 
    font-size: 0.95rem; font-weight: 600; line-height: 1.4; margin-bottom: 0.5rem; color: var(--text-primary); 
    display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; 
  }
  .yt-meta { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; }

  /* Media Query khusus HP (Maksimal Lebar 768px): Vertikal & Hanya 5 Video */
  @media (max-width: 768px) {
    .yt-slider {
      display: flex;
      flex-direction: column;
      overflow-x: visible;
      margin: 0;
      padding: 0;
      scroll-snap-type: none;
      gap: 1rem;
    }
    .yt-card {
      flex: unset;
      width: 100%;
      max-height: 240px;
      scroll-snap-align: unset;
    }
    /* Menyembunyikan video ke-6 dan seterusnya khusus di tampilan HP */
    .yt-card:nth-child(n+6) {
      display: none;
    }
    .yt-thumb {
      height: 130px; 
    }
  }

  /* Empty State */
  .empty-state { text-align: center; padding: 3rem 1.5rem; color: var(--text-secondary); }
  .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; color: #cbd5e1; }
  .empty-state p { font-size: 0.95rem; font-weight: 500; }

  @media (max-width: 640px) {
    .dash-header { flex-direction: column; align-items: flex-start; }
    .metrics-grid { grid-template-columns: 1fr; }
    .yt-card { flex: 0 0 85vw; }
  }
</style>

<div class="dash-header">
  <div>
    <h2><?= htmlspecialchars($greeting) ?>, TMB Team! 👋</h2>
    <p>Pantau ringkasan operasional dan pastikan semua perangkat siap siaran.</p>
  </div>
  <div class="date-badge">
    <i class="ri-calendar-event-line"></i> <?= $today->format('l, j F Y') ?>
  </div>
</div>

<!-- 1. KEY METRICS -->
<div class="metrics-grid">
  <div class="metric-card blue">
    <div class="metric-icon blue"><i class="ri-calendar-check-line"></i></div>
    <div class="metric-info">
      <h3>Agenda Hari Ini</h3>
      <div class="value"><?= count($agenda) ?></div>
      <div class="sub">Kegiatan terjadwal</div>
    </div>
  </div>
  <div class="metric-card green">
    <div class="metric-icon green"><i class="ri-server-line"></i></div>
    <div class="metric-info">
      <h3>Kesiapan Perangkat</h3>
      <div class="value"><?= $checklistPct ?>%</div>
      <div class="sub"><?= $checklistReady ?> dari <?= $checklistTotal ?> perangkat siap</div>
    </div>
  </div>
  <div class="metric-card orange">
    <div class="metric-icon orange"><i class="ri-tools-line"></i></div>
    <div class="metric-info">
      <h3>Troubleshoot Aktif</h3>
      <div class="value"><?= $activeTroubleCount ?></div>
      <div class="sub">Kasus belum terselesaikan</div>
    </div>
  </div>
</div>

<!-- 2. MAIN CONTENT: Agenda & Troubleshoot -->
<div class="main-grid">
  <!-- Left Column: Agenda -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-time-line"></i> Jadwal Hari Ini</div>
      <a class="link-btn" href="<?= $base ?>/pages/agenda.php">Lihat Semua <i class="ri-arrow-right-line"></i></a>
    </div>
    <div class="card-body">
      <?php if (empty($agenda)): ?>
        <div class="empty-state">
          <i class="ri-calendar-schedule-line"></i>
          <p>Tidak ada agenda terjadwal untuk hari ini.</p>
        </div>
      <?php else: ?>
        <div class="timeline-list">
          <?php foreach ($agenda as $a): ?>
          <div class="timeline-item">
            <div class="timeline-time"><?= htmlspecialchars($a['time']) ?></div>
            <div class="timeline-content">
              <div class="timeline-title"><?= htmlspecialchars($a['title']) ?></div>
              <div class="timeline-meta">
                <span class="tag <?= htmlspecialchars($a['tone']) ?>"><?= htmlspecialchars($a['tag']) ?></span>
                <span class="pic-badge"><i class="ri-user-smile-line"></i> <?= htmlspecialchars($a['pic']) ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
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
          <div class="empty-state" style="padding: 2rem 1rem;">
            <i class="ri-checkbox-circle-line" style="color: var(--success);"></i>
            <p style="color: var(--success); font-weight: 600;">Semua sistem beroperasi normal</p>
          </div>
        <?php else: ?>
          <?php foreach ($troubleshootActive as $tr): ?>
          <div class="trouble-item <?= htmlspecialchars($tr['cls']) ?>">
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
            <i class="ri-add-circle-line" style="color: var(--danger);"></i> 
            <span>Laporkan Gangguan Baru</span>
          </a>
          <a href="<?= $base ?>/pages/checklist.php" class="btn-action">
            <i class="ri-clipboard-line" style="color: var(--primary);"></i> 
            <span>Isi Checklist Harian</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 3. BOTTOM SECTION: Checklist Detail & YouTube Recap -->
<div class="bottom-grid">
  
  <!-- YouTube Recap - DI KIRI (Desktop) / ATAS (Mobile) -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-youtube-line" style="color: #ef4444;"></i> Live 7 Hari Terakhir</div>
      <a class="link-btn" href="<?= $base ?>/pages/gallery.php">Lihat Arsip <i class="ri-arrow-right-line"></i></a>
    </div>
    <div class="card-body">
      <?php if (empty($gallery)): ?>
        <div class="empty-state">
          <i class="ri-video-off-line"></i>
          <p>Belum ada data live streaming minggu ini.</p>
        </div>
      <?php else: ?>
        <div class="yt-slider">
          <?php 
            // Menampilkan seluruh video galeri yang ada tanpa batasan angka di laptop,
            // sementara di HP dibatasi lewat CSS (.yt-card:nth-child(n+6) { display: none; })
            foreach ($gallery as $g): 
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
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8; background: #f1f5f9;">
                    <i class="ri-image-2-line" style="font-size:2.5rem;"></i>
                  </div>
                <?php endif; ?>
                <span class="yt-badge"><?= htmlspecialchars($programa) ?></span>
              </div>
              <div class="yt-info">
                <div class="yt-title"><?= htmlspecialchars($title) ?></div>
                <div class="yt-meta">
                  <i class="ri-calendar-2-line"></i> <?= htmlspecialchars($date) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Checklist Detail - DI KANAN (Desktop) / BAWAH (Mobile) -->
  <div class="card checklist-compact">
    <div class="card-header">
      <div class="card-title"><i class="ri-equipment-line"></i> Status Perangkat</div>
      <a class="link-btn" href="<?= $base ?>/pages/checklist.php">Detail <i class="ri-arrow-right-line"></i></a>
    </div>
    <div class="card-body">
      <div class="progress-wrap">
        <div class="progress-header">
          <span>Tingkat Kesiapan</span>
          <span style="color: var(--success); font-weight: 700;"><?= $checklistPct ?>%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?= $checklistPct ?>%;"></div>
        </div>
      </div>
      <div class="checklist-mini">
        <?php foreach ($checklist as $c): ?>
        <div class="check-mini-item <?= $c['ok'] ? 'ok' : 'not-ok' ?>">
          <span><i class="<?= $c['ok'] ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill' ?>"></i> <?= htmlspecialchars($c['name']) ?></span>
          <span><?= htmlspecialchars($c['status']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>