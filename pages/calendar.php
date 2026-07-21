<?php
$base = '..'; $pageTitle = 'Calendar Recap'; $activeMenu = 'calendar';

require_once __DIR__ . '/../api/agenda.php';

$jakartaNow = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$year  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) $jakartaNow->format('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) $jakartaNow->format('n');
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$firstDay = new DateTime("$year-$month-01");
$daysInMonth = (int) $firstDay->format('t');
$startOffset = ((int) $firstDay->format('N')) - 1; // 0 = Senin
$todayStr = $jakartaNow->format('Y-m-d');

$prevMonth = $month - 1; $prevYear = $year; if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year; if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Ambil SEMUA agenda (bukan cuma bulan ini), lalu saring di PHP — karena
// getAgenda() cuma bisa filter per satu tanggal spesifik, bukan rentang bulan.
$allAgenda = getAgenda(null, 'asc');
$errorMessage = null;
$errorRaw = null;
if (!is_array($allAgenda) || isset($allAgenda['error'])) {
    $errorMessage = $allAgenda['error'] ?? 'Gagal memuat data agenda dari Notion';
    $errorRaw = $allAgenda['raw'] ?? null;
    $allAgenda = [];
}

// key: tanggal (d) => list event ['label','type']
$events = [];
$totalCount = 0; $liveCount = 0; $eventCount = 0;
foreach ($allAgenda as $a) {
    if ($a['date'] === '') continue;
    $parts = explode('-', $a['date']);
    if (count($parts) !== 3) continue;
    [$ay, $am, $ad] = array_map('intval', $parts);
    if ($ay !== $year || $am !== $month) continue;

    $totalCount++;
    $tagLower = strtolower($a['tag']);
    if (strpos($tagLower, 'live') !== false) {
        $type = 'live';
        $liveCount++;
    } elseif (strpos($tagLower, 'event') !== false || strpos($tagLower, 'kunjungan') !== false) {
        $type = 'event';
        $eventCount++;
    } else {
        $type = 'default';
    }
    $events[$ad][] = ['label' => $a['title'], 'type' => $type];
}

$dows = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];

include __DIR__ . '/../includes/header.php';
?>


<div class="page-head">
  <div>
    <h2>Calendar Recap</h2>
    <p>Rekap kegiatan dan siaran dalam tampilan kalender bulanan.</p>
  </div>
  <form method="get" style="display:inline-flex; align-items:center; gap:0.5rem;">
    <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-secondary" style="padding:0.5rem 0.65rem; display:flex; align-items:center;" title="Bulan sebelumnya"><i class="ri-arrow-left-s-line"></i></a>
    <select name="month" onchange="this.form.submit()" style="padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #d1d5db; font-weight:600;">
      <?php for ($m = 1; $m <= 12; $m++): ?>
      <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" onchange="this.form.submit()" style="padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #d1d5db; font-weight:600;">
      <?php
        $yearNow = (int) $jakartaNow->format('Y');
        $yearStart = min($yearNow - 2, $year);
        $yearEnd = max($yearNow + 2, $year);
        for ($y = $yearStart; $y <= $yearEnd; $y++):
      ?>
      <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-secondary" style="padding:0.5rem 0.65rem; display:flex; align-items:center;" title="Bulan berikutnya"><i class="ri-arrow-right-s-line"></i></a>
    <a href="?month=<?= (int)$jakartaNow->format('n') ?>&year=<?= (int)$jakartaNow->format('Y') ?>" class="btn btn-secondary" style="padding:0.5rem 0.85rem; font-size:0.85rem;">Hari Ini</a>
  </form>
</div>

<?php if ($errorMessage): ?>
<div class="alert alert-danger" style="margin-bottom:1rem; padding:1rem; border-radius:12px; background:#fee2e2; color:#991b1b;">
  <strong>Terjadi kesalahan:</strong> <?= htmlspecialchars($errorMessage) ?>
  <?php if (!empty($errorRaw)): ?>
  <div style="margin-top:0.75rem; font-size:0.90rem; color:#7f1d1d;">Detail: <?= htmlspecialchars($errorRaw) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Kegiatan</h3><div class="stat-value"><?= $totalCount ?></div><div class="stat-sub">Bulan ini</div></div><div class="stat-icon blue"><i class="ri-calendar-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Live Streaming</h3><div class="stat-value"><?= $liveCount ?></div><div class="stat-sub">Sesi tayang</div></div><div class="stat-icon red"><i class="ri-live-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Event Khusus</h3><div class="stat-value"><?= $eventCount ?></div><div class="stat-sub">Di luar jadwal rutin</div></div><div class="stat-icon orange"><i class="ri-star-line"></i></div></div>
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
      <?php if (isset($events[$d])):
        $dayEvents = $events[$d];
        $shownEvents = array_slice($dayEvents, 0, 3);
        $moreCount = count($dayEvents) - count($shownEvents);
        foreach ($shownEvents as $ev): ?>
        <div class="cal-event <?= $ev['type'] === 'live' ? 'live' : ($ev['type'] === 'event' ? 'event' : '') ?>"><?= htmlspecialchars($ev['label']) ?></div>
      <?php endforeach;
        if ($moreCount > 0): ?>
        <div class="cal-event" style="color:var(--text-secondary); background:transparent;">+<?= $moreCount ?> lainnya</div>
      <?php endif; endif; ?>
    </div>
    <?php endfor; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>