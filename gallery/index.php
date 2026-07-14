<?php
$base = '..'; $pageTitle = 'Gallery Youtube'; $activeMenu = 'gallery';

$videos = [
    ['title'=>'Deteksi Dini Kekerasan Pada Anak','show'=>'Obrolan Psikologi','time'=>'20:10','date'=>'9 Jul 2026','live'=>true,'views'=>'—'],
    ['title'=>'Sore Ceria - Sinergi Radio Dalam Kampanye CBP Rupiah','show'=>'Sore Ceria','time'=>'16:00','date'=>'9 Jul 2026','live'=>true,'views'=>'—'],
    ['title'=>'Berita Siang RRI Malang','show'=>'Berita Siang','time'=>'15:30','date'=>'8 Jul 2026','live'=>true,'views'=>'—'],
    ['title'=>'Catur Laras Jagongan UMKM','show'=>'Catur Laras','time'=>'18:45','date'=>'7 Jul 2026','live'=>false,'views'=>'842x'],
    ['title'=>'Simphoni Keroncong Malam Minggu','show'=>'Simphoni Keroncong','time'=>'21:00','date'=>'6 Jul 2026','live'=>false,'views'=>'1.2rb x'],
    ['title'=>'Ruatan Malam: Cerita Rakyat Malang','show'=>'Ruatan Malam','time'=>'19:00','date'=>'5 Jul 2026','live'=>false,'views'=>'956x'],
    ['title'=>'Dialog Publik: Ketahanan Pangan Kota Malang','show'=>'Dialog Publik','time'=>'10:00','date'=>'4 Jul 2026','live'=>false,'views'=>'674x'],
    ['title'=>'Sore Ceria: Musik Nostalgia 90an','show'=>'Sore Ceria','time'=>'16:00','date'=>'3 Jul 2026','live'=>false,'views'=>'1.5rb x'],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Gallery Youtube</h2>
    <p>Koleksi siaran live dan rekaman yang telah tayang.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tambah Video</button>
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Live Bulan Ini</h3><div class="stat-value">15</div><div class="stat-sub">+3 dari bulan lalu</div></div><div class="stat-icon red"><i class="ri-live-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Total Video</h3><div class="stat-value">142</div><div class="stat-sub">Sejak awal tahun</div></div><div class="stat-icon blue"><i class="ri-play-circle-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Program Aktif</h3><div class="stat-value">8</div><div class="stat-sub">Tayang rutin</div></div><div class="stat-icon purple"><i class="ri-tv-2-line"></i></div></div>
</div>

<div class="filter-tabs">
  <div class="filter-tab active" onclick="filterVideo('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterVideo('Live', this)">Sedang Live</div>
  <div class="filter-tab" onclick="filterVideo('Rekaman', this)">Rekaman</div>
</div>

<div class="gallery-grid" id="videoGrid">
  <?php foreach ($videos as $g): ?>
  <div class="feed-card" data-type="<?= $g['live'] ? 'Live' : 'Rekaman' ?>">
    <div class="feed-thumb">
      <?php if ($g['live']): ?><span class="live-badge">LIVE</span><?php endif; ?>
      <span class="feed-badge-time"><?= htmlspecialchars($g['time']) ?></span>
      <span class="overlay-label"><?= htmlspecialchars($g['show']) ?></span>
    </div>
    <div class="feed-info">
      <div class="title"><?= htmlspecialchars($g['title']) ?></div>
      <div class="meta">
        <span><?= htmlspecialchars($g['date']) ?></span>
        <span><i class="ri-eye-line"></i> <?= htmlspecialchars($g['views']) ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal-overlay" id="videoModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tambah Video</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Judul Video</label><input type="text" placeholder="Judul tayangan" required></div>
      <div class="form-row">
        <div class="form-group"><label>Nama Program</label><input type="text" placeholder="Contoh: Sore Ceria" required></div>
        <div class="form-group"><label>Jam Tayang</label><input type="time" required></div>
      </div>
      <div class="form-group"><label>Link Youtube</label><input type="url" placeholder="https://youtube.com/..."></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('videoModal').classList.add('active'); }
function closeModal(){ document.getElementById('videoModal').classList.remove('active'); }
document.getElementById('videoModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
function filterVideo(type, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#videoGrid .feed-card').forEach(card => {
    card.style.display = (type === 'Semua' || card.dataset.type === type) ? '' : 'none';
  });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
