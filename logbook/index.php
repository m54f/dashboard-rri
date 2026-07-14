<?php
$base = '..'; $pageTitle = 'Logbook Digital'; $activeMenu = 'logbook';

$logsByDate = [
    '8 Juli 2026' => [
        ['time'=>'08.00','title'=>'Serah terima shift pagi','desc'=>'Kondisi perangkat studio utama baik, encoder perlu dicek ulang.','op'=>'Saiful'],
        ['time'=>'09.05','title'=>'Mulai rekaman Podcast Luar Studio','desc'=>'Lokasi: Balai Kota Malang, narasumber hadir tepat waktu.','op'=>'Alfan'],
        ['time'=>'11.20','title'=>'Selesai setup sound system Senam Sore','desc'=>'Speaker & mic wireless diuji, kondisi normal.','op'=>'Yanuar'],
        ['time'=>'14.10','title'=>'Laporan kendala internet studio','desc'=>'IP 60 Telkom tidak tersambung, eskalasi ke troubleshoot TB-012.','op'=>'Yanuar'],
    ],
    '7 Juli 2026' => [
        ['time'=>'16.00','title'=>'Live Sore Ceria dimulai','desc'=>'Kolaborasi dengan tim CBP Rupiah, koneksi live stabil.','op'=>'Alif'],
        ['time'=>'20.00','title'=>'Serah terima shift malam','desc'=>'Seluruh perangkat kondisi baik, tidak ada catatan khusus.','op'=>'Yayang'],
    ],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Logbook Digital</h2>
    <p>Riwayat aktivitas operator secara kronologis.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tambah Catatan</button>
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Entri Hari Ini</h3><div class="stat-value">4</div><div class="stat-sub">Tercatat sejak 08.00</div></div><div class="stat-icon blue"><i class="ri-book-2-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Total Bulan Ini</h3><div class="stat-value">64</div><div class="stat-sub">Semua operator</div></div><div class="stat-icon green"><i class="ri-file-list-3-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Operator Aktif</h3><div class="stat-value">5</div><div class="stat-sub">Shift bergilir</div></div><div class="stat-icon purple"><i class="ri-team-line"></i></div></div>
</div>

<?php foreach ($logsByDate as $date => $logs): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><div class="card-title"><i class="ri-calendar-line"></i><?= htmlspecialchars($date) ?></div></div>
  <div class="timeline">
    <?php foreach ($logs as $l): ?>
    <div class="timeline-item">
      <div class="timeline-dot"></div>
      <div class="timeline-time"><?= htmlspecialchars($l['time']) ?> WIB &middot; <?= htmlspecialchars($l['op']) ?></div>
      <div class="timeline-title"><?= htmlspecialchars($l['title']) ?></div>
      <div class="timeline-desc"><?= htmlspecialchars($l['desc']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<div class="modal-overlay" id="logModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tambah Catatan Logbook</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-row">
        <div class="form-group"><label>Waktu</label><input type="time" required></div>
        <div class="form-group"><label>Operator</label><input type="text" placeholder="Nama operator" required></div>
      </div>
      <div class="form-group"><label>Judul Aktivitas</label><input type="text" placeholder="Contoh: Serah terima shift" required></div>
      <div class="form-group"><label>Deskripsi</label><textarea rows="4" placeholder="Detail aktivitas atau kondisi..."></textarea></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Catatan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('logModal').classList.add('active'); }
function closeModal(){ document.getElementById('logModal').classList.remove('active'); }
document.getElementById('logModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
