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

    $startDateObj = new DateTime($a['date']);
    $endDateObj = (!empty($a['date_end']) && $a['date_end'] !== $a['date']) ? new DateTime($a['date_end']) : clone $startDateObj;

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

    $curr = clone $startDateObj;
    while ($curr <= $endDateObj) {
        $cy = (int)$curr->format('Y');
        $cm = (int)$curr->format('m');
        $cd = (int)$curr->format('d');
        if ($cy === $year && $cm === $month) {
            $events[$cd][] = ['label' => $a['title'], 'type' => $type];
        }
        $curr->modify('+1 day');
    }
}

$dows = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];

include __DIR__ . '/../includes/header.php';
?>
<style>
/* Stil Form Navigasi Bulan & Tahun */
.cal-nav-form {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
}
.cal-nav-form select {
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--border-color, #d1d5db);
  font-weight: 600;
  font-size: 0.88rem;
  background: #fff;
  outline: none;
  cursor: pointer;
}

/* Accent Hari Ini */
.cal-cell.today {
  background: #eff6ff !important;
  border: 2px solid #3b82f6 !important;
  border-radius: 8px;
}
.cal-cell.today .cal-daynum {
  color: #2563eb !important;
  font-weight: 800 !important;
}

/* Tampilan Calendar Recap di layar HP & Tablet */
@media (max-width: 768px) {
  .page-head {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
  }
  .cal-nav-form {
    margin-left: 0 !important;
    width: 100% !important;
    justify-content: space-between;
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-sizing: border-box;
  }
  .cal-nav-form select {
    flex: 1;
  }
  .cal-grid { gap: 3px !important; }
  .cal-dow { font-size: 10px !important; padding: 4px 0 !important; }
  .cal-cell { padding: 4px 3px !important; min-height: 56px !important; }
  .cal-daynum { font-size: 11px !important; margin-bottom: 2px !important; }

  .cal-event-list { display: none !important; }
  .cal-event-dots { display: flex !important; }
  .cal-cell.has-events { cursor: pointer; }
  .cal-cell.has-events:active { background: var(--bg-color); }
  .stats-row {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
  }
}

.cal-event-dots {
  display: none;
  gap: 3px;
  flex-wrap: wrap;
  margin-top: 2px;
}
.cal-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary, #2563eb); display: inline-block; }
.cal-dot.live { background: var(--danger, #ef4444); }
.cal-dot.event { background: var(--warning, #f59e0b); }

/* Modal detail kegiatan per-tanggal */
#dayEventsModal .modal-content { max-width: 400px; }
#dayEventsModal .day-event-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 0; border-bottom: 1px solid var(--border-color);
}
#dayEventsModal .day-event-item:last-child { border-bottom: none; }
#dayEventsModal .day-event-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; background: var(--primary, #2563eb); }
#dayEventsModal .day-event-dot.live { background: var(--danger, #ef4444); }
#dayEventsModal .day-event-dot.event { background: var(--warning, #f59e0b); }
</style>

<div class="page-head">
  <div>
    <h2>Calendar Recap</h2>
    <p>Rekap kegiatan dan siaran dalam tampilan kalender bulanan.</p>
  </div>
  <div class="page-head-actions">
    <form method="get" class="cal-nav-form page-head-search" style="display:flex; align-items:center; gap:6px; margin:0; padding:4px 8px; border-radius:10px; background:var(--card-bg, #fff); border:1px solid var(--border-color, #e2e8f0);">
      <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-secondary" style="padding:0.4rem 0.6rem; display:flex; align-items:center;" title="Bulan sebelumnya"><i class="ri-arrow-left-s-line"></i></a>
      <select name="month" onchange="this.form.submit()" class="header-filter-select" style="border:none !important; outline:none !important; background:transparent !important; font-size:13px; font-weight:600; cursor:pointer;">
        <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
        <?php endfor; ?>
      </select>
      <select name="year" onchange="this.form.submit()" class="header-filter-select" style="border:none !important; outline:none !important; background:transparent !important; font-size:13px; font-weight:600; cursor:pointer;">
        <?php
          $yearNow = (int) $jakartaNow->format('Y');
          $yearStart = min($yearNow - 2, $year);
          $yearEnd = max($yearNow + 2, $year);
          for ($y = $yearStart; $y <= $yearEnd; $y++):
        ?>
        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-secondary" style="padding:0.4rem 0.6rem; display:flex; align-items:center;" title="Bulan berikutnya"><i class="ri-arrow-right-s-line"></i></a>
    </form>
  </div>
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
  <div class="stat-card"><div class="stat-info"><h3>Event Khusus</h3><div class="stat-value"><?= $eventCount ?></div><div class="stat-sub">Di luar jadwal rutin</div></div><div class="stat-icon orange"><i class="ri-star-line"></i></div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-calendar-2-line"></i><?= $monthNames[$month] ?> <?= $year ?></div>
    <div style="display:flex; gap:14px; font-size:11.5px; color:var(--text-secondary); align-items:center;">
      <span style="display:flex; align-items:center; gap:6px;"><i style="width:8px; height:8px; border-radius:50%; background:var(--primary, #2563eb); display:inline-block;"></i>Kegiatan</span>
      <span style="display:flex; align-items:center; gap:6px;"><i style="width:8px; height:8px; border-radius:50%; background:var(--danger, #ef4444); display:inline-block;"></i>Live</span>
      <span style="display:flex; align-items:center; gap:6px;"><i style="width:8px; height:8px; border-radius:50%; background:var(--warning, #f59e0b); display:inline-block;"></i>Event</span>
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
      $hasEvents = isset($events[$d]);
    ?>
    <div class="cal-cell <?= $isToday ? 'today' : '' ?> <?= $hasEvents ? 'has-events' : '' ?>" <?= $hasEvents ? 'onclick="showDayEvents(' . $d . ')"' : '' ?>>
      <div class="cal-daynum"><?= $d ?></div>
      <?php if ($hasEvents):
        $dayEvents = $events[$d];
        $shownEvents = array_slice($dayEvents, 0, 3);
        $moreCount = count($dayEvents) - count($shownEvents);
      ?>
      <div class="cal-event-list">
        <?php foreach ($shownEvents as $ev): ?>
        <div class="cal-event <?= $ev['type'] === 'live' ? 'live' : ($ev['type'] === 'event' ? 'event' : '') ?>"><?= htmlspecialchars($ev['label']) ?></div>
        <?php endforeach; ?>
        <?php if ($moreCount > 0): ?>
        <div class="cal-event" style="color:var(--text-secondary); background:transparent;">+<?= $moreCount ?> lainnya</div>
        <?php endif; ?>
      </div>
      <div class="cal-event-dots">
        <?php foreach (array_slice($dayEvents, 0, 4) as $ev): ?>
        <span class="cal-dot <?= $ev['type'] === 'live' ? 'live' : ($ev['type'] === 'event' ? 'event' : '') ?>"></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endfor; ?>
  </div>
</div>

<!-- Modal detail kegiatan per-tanggal, dipakai pas kotak tanggal di-tap (khususnya di HP) -->
<div class="modal-overlay" id="dayEventsModal">
  <div class="modal-content">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <div class="modal-title" id="dayEventsTitle">Kegiatan</div>
      <button class="modal-close" onclick="closeDayEvents()">&times;</button>
    </div>
    <div id="dayEventsBody"></div>
  </div>
</div>

<script>
// Data kegiatan per-tanggal (bulan yang lagi ditampilkan), dipakai modal detail hari
const CAL_EVENTS = <?= json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const CAL_MONTH_LABEL = <?= json_encode($monthNames[$month] . ' ' . $year, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function escapeHtmlCal(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function showDayEvents(day) {
  const list = CAL_EVENTS[day] || [];
  document.getElementById('dayEventsTitle').textContent = day + ' ' + CAL_MONTH_LABEL;
  document.getElementById('dayEventsBody').innerHTML = list.map(ev => {
    const dotClass = ev.type === 'live' ? 'live' : (ev.type === 'event' ? 'event' : '');
    return `<div class="day-event-item"><span class="day-event-dot ${dotClass}"></span><span>${escapeHtmlCal(ev.label)}</span></div>`;
  }).join('') || '<p style="color:var(--text-secondary);">Tidak ada kegiatan.</p>';
  document.getElementById('dayEventsModal').classList.add('active');
}

function closeDayEvents() {
  document.getElementById('dayEventsModal').classList.remove('active');
}

document.getElementById('dayEventsModal').addEventListener('click', function (e) {
  if (e.target === this) closeDayEvents();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeDayEvents();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>