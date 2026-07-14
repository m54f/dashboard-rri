<?php
$base = '..'; $pageTitle = 'Jurnal Pribadi'; $activeMenu = 'personal';

$notes = [
    ['title'=>'Catatan shift pagi','desc'=>'Perlu follow up ke vendor soal capture card yang belum diganti sejak minggu lalu. Cek juga stok kabel XLR di gudang.','date'=>'8 Jul 2026, 09.30','pinned'=>true],
    ['title'=>'Ide segmen baru untuk Sore Ceria','desc'=>'Coba usulkan segmen interaktif dengan polling pendengar via WhatsApp, mirip yang dipakai radio komunitas lain.','date'=>'6 Jul 2026, 15.10','pinned'=>false],
    ['title'=>'Refleksi live streaming CBP Rupiah','desc'=>'Koneksi sempat drop 2 menit di awal, kemungkinan karena bandwidth terbagi dengan upload dokumentasi. Perlu jalur terpisah untuk live berikutnya.','date'=>'7 Jul 2026, 21.00','pinned'=>true],
    ['title'=>'Reminder pribadi','desc'=>'Ambil sertifikat pelatihan broadcasting di kantor pusat sebelum akhir bulan.','date'=>'3 Jul 2026, 08.15','pinned'=>false],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Jurnal Pribadi</h2>
    <p>Catatan personal — hanya terlihat oleh kamu, Alif Saiful.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tulis Catatan</button>
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Catatan</h3><div class="stat-value">24</div><div class="stat-sub">Sejak bergabung</div></div><div class="stat-icon blue"><i class="ri-file-user-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Disematkan</h3><div class="stat-value">2</div><div class="stat-sub">Catatan penting</div></div><div class="stat-icon orange"><i class="ri-pushpin-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Bulan Ini</h3><div class="stat-value">6</div><div class="stat-sub">Catatan baru</div></div><div class="stat-icon green"><i class="ri-quill-pen-line"></i></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-file-user-line"></i>Catatan Saya</div></div>
  <?php foreach ($notes as $n): ?>
  <div style="padding:14px 0;border-bottom:1px solid var(--border-color);">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
      <div style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;">
        <?php if ($n['pinned']): ?><i class="ri-pushpin-fill" style="color:var(--warning);font-size:14px;"></i><?php endif; ?>
        <?= htmlspecialchars($n['title']) ?>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0;">
        <button class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></button>
        <button class="icon-action delete" title="Hapus"><i class="ri-delete-bin-line"></i></button>
      </div>
    </div>
    <p style="font-size:12.5px;color:var(--text-secondary);line-height:1.6;margin:6px 0;"><?= htmlspecialchars($n['desc']) ?></p>
    <div style="font-size:11px;color:var(--text-secondary);"><i class="ri-time-line"></i> <?= htmlspecialchars($n['date']) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal-overlay" id="noteModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tulis Catatan</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Judul</label><input type="text" placeholder="Judul catatan" required></div>
      <div class="form-group"><label>Isi Catatan</label><textarea rows="5" placeholder="Tulis catatan pribadi kamu..."></textarea></div>
      <div class="form-group" style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" id="pinNote" style="width:auto;">
        <label for="pinNote" style="margin:0;">Sematkan catatan ini</label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Catatan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('noteModal').classList.add('active'); }
function closeModal(){ document.getElementById('noteModal').classList.remove('active'); }
document.getElementById('noteModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
