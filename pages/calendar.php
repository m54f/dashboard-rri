<?php
$base = '..'; $pageTitle = 'Calendar Recap'; $activeMenu = 'calendar';

$year = 2026; $month = 7; // Juli 2026
$monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$firstDay = new DateTime("$year-$month-01");
$daysInMonth = (int) $firstDay->format('t');
$startOffset = ((int) $firstDay->format('N')) - 1; // 0 = Senin
$todayStr = '2026-07-08';

// key: tanggal (d) => list event ['label','type']
$events = [
    3 => [['label'=>'Simphoni Keroncong','type'=>'live']],
    5 => [['label'=>'Ruatan Malam','type'=>'default']],
    6 => [['label'=>'Perbaikan Capture Card','type'=>'event']],
    7 => [['label'=>'Sore Ceria Live','type'=>'live'],['label'=>'Catur Laras','type'=>'default']],
    8 => [['label'=>'Podcast Luar Studio','type'=>'default'],['label'=>'Senam Sore & Pagi','type'=>'event'],['label'=>'2x Live Youtube','type'=>'live']],
    9 => [['label'=>'Obrolan Psikologi','type'=>'live'],['label'=>'CBP Rupiah','type'=>'live']],
    12 => [['label'=>'Dialog Publik','type'=>'default']],
    15 => [['label'=>'Rapat Evaluasi Bulanan','type'=>'event']],
    20 => [['label'=>'Live Youtube Spesial','type'=>'live']],
];

$dows = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Calendar Recap</h2>
    <p>Rekap kegiatan dan siaran dalam tampilan kalender bulanan.</p>
  </div>
  <div class="date-chip"><i class="ri-calendar-2-line"></i> <?= $monthNames[$month] ?> <?= $year ?></div>
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Kegiatan</h3><div class="stat-value">34</div><div class="stat-sub">Bulan ini</div></div><div class="stat-icon blue"><i class="ri-calendar-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Live Streaming</h3><div class="stat-value">15</div><div class="stat-sub">Sesi tayang</div></div><div class="stat-icon red"><i class="ri-live-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Event Khusus</h3><div class="stat-value">6</div><div class="stat-sub">Di luar jadwal rutin</div></div><div class="stat-icon orange"><i class="ri-star-line"></i></div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-calendar-2-line"></i><?= $monthNames[$month] ?> <?= $year ?></div>
    <div style="display:flex;gap:14px;font-size:11.5px;color:var(--text-secondary);">
      <span style="display:flex;align-items:center;gap:6px;"><i style="width:8px;height:8px;border-radius:3px;background:var(--primary);display:inline-block;"></i>Kegiatan</span>
      <span style="display:flex;align-items:center;gap:6px;"><i style="width:8px;height:8px;border-radius:3px;background:var(--danger);display:inline-block;"></i>Live</span>
      <span style="display:flex;align-items:center;gap:6px;"><i style="width:8px;height:8px;border-radius:3px;background:var(--warning);display:inline-block;"></i>Event</span>
    </div>
  </div>
  <div class="cal-grid">
    <?php foreach ($dows as $d): ?><div class="cal-dow"><?= $d ?></div><?php endforeach; ?>

    <?php for ($i = 0; $i < $startOffset; $i++): ?>
      <div class="cal-cell other-month"></div>
    <?php endfor; ?>

    <?php for ($d = 1; $d <= $daysInMonth; $d++):
      $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
      $isToday = $dateStr === $todayStr;
    ?>
    <div class="cal-cell <?= $isToday ? 'today' : '' ?>">
      <div class="cal-daynum"><?= $d ?></div>
      <?php if (isset($events[$d])): foreach ($events[$d] as $ev): ?>
        <div class="cal-event <?= $ev['type'] === 'live' ? 'live' : ($ev['type'] === 'event' ? 'event' : '') ?>"><?= htmlspecialchars($ev['label']) ?></div>
      <?php endforeach; endif; ?>
    </div>
    <?php endfor; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
