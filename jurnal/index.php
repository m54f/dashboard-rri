<?php
$base = '..'; $pageTitle = 'Jurnal Kegiatan'; $activeMenu = 'jurnal';

$entries = [
    ['title'=>'Podcast Luar Studio: Obrolan Psikologi','desc'=>'Dokumentasi rekaman podcast bersama psikolog klinis membahas deteksi dini kekerasan pada anak, dilaksanakan di Balai Kota Malang.','tag'=>'Podcast','tone'=>'purple','op'=>'Alfan','date'=>'8 Jul 2026'],
    ['title'=>'Setup Sound System Senam Sore RRI','desc'=>'Pemasangan dan pengecekan sound system lapangan untuk kegiatan senam sore rutin komunitas RRI Malang.','tag'=>'Event','tone'=>'orange','op'=>'Yanuar','date'=>'8 Jul 2026'],
    ['title'=>'Live Youtube Sore Ceria - Kampanye CBP Rupiah','desc'=>'Siaran langsung kolaborasi dengan Bank Indonesia membahas program Cinta Bangga Paham Rupiah, ditayangkan di kanal YouTube RRI Malang.','tag'=>'Live','tone'=>'red','op'=>'Alif','date'=>'7 Jul 2026'],
    ['title'=>'Produksi Ruatan Malam','desc'=>'Proses rekaman dan editing segmen budaya Ruatan Malam untuk tayang minggu depan.','tag'=>'Produksi','tone'=>'blue','op'=>'Yayang','date'=>'7 Jul 2026'],
    ['title'=>'Perbaikan Capture Card Studio B','desc'=>'Penggantian capture card yang rusak setelah dilaporkan tidak terdeteksi sejak 6 Juli, koordinasi dengan vendor.','tag'=>'Teknis','tone'=>'green','op'=>'Saiful','date'=>'6 Jul 2026'],
    ['title'=>'Catur Laras: Jagongan UMKM','desc'=>'Liputan dan dokumentasi program pemberdayaan UMKM lokal bersama pelaku usaha Malang Raya.','tag'=>'Liputan','tone'=>'gray','op'=>'Alfan','date'=>'5 Jul 2026'],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Jurnal Kegiatan</h2>
    <p>Dokumentasi kegiatan operasional tim TMB.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tulis Jurnal</button>
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Dokumentasi</h3><div class="stat-value">28</div><div class="stat-sub">Sepanjang bulan ini</div></div><div class="stat-icon blue"><i class="ri-file-text-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Minggu Ini</h3><div class="stat-value">6</div><div class="stat-sub">Entri baru</div></div><div class="stat-icon green"><i class="ri-calendar-check-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Kontributor Aktif</h3><div class="stat-value">5</div><div class="stat-sub">Operator menulis jurnal</div></div><div class="stat-icon purple"><i class="ri-user-star-line"></i></div></div>
</div>

<div class="gallery-grid" style="grid-template-columns:repeat(3,1fr);">
  <?php foreach ($entries as $e): ?>
  <div class="feed-card">
    <div class="feed-thumb">
      <span class="live-badge" style="background:var(--<?= $e['tone']==='gray' ? 'secondary' : $e['tone'] ?>);"><?= strtoupper($e['tag']) ?></span>
      <span class="overlay-label"><?= htmlspecialchars($e['title']) ?></span>
    </div>
    <div class="feed-info">
      <p style="font-size:12px;color:var(--text-secondary);line-height:1.5;margin-bottom:10px;"><?= htmlspecialchars($e['desc']) ?></p>
      <div class="meta">
        <span><span class="avatar-sm" style="width:22px;height:22px;font-size:9.5px;"><?= strtoupper(substr($e['op'],0,2)) ?></span> <?= htmlspecialchars($e['op']) ?></span>
        <span><?= htmlspecialchars($e['date']) ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal-overlay" id="jurnalModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tulis Jurnal Kegiatan</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Judul Kegiatan</label><input type="text" placeholder="Contoh: Podcast Luar Studio" required></div>
      <div class="form-row">
        <div class="form-group"><label>Kategori</label><select><option>Podcast</option><option>Event</option><option>Live</option><option>Produksi</option><option>Teknis</option><option>Liputan</option></select></div>
        <div class="form-group"><label>Tanggal</label><input type="date" required></div>
      </div>
      <div class="form-group"><label>Deskripsi</label><textarea rows="4" placeholder="Ceritakan detail kegiatan..."></textarea></div>
      <div class="form-group"><label>Foto Dokumentasi</label><input type="file" accept="image/*"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Publikasikan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('jurnalModal').classList.add('active'); }
function closeModal(){ document.getElementById('jurnalModal').classList.remove('active'); }
document.getElementById('jurnalModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
