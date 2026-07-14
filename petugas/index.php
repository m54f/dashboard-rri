<?php
$base = '..'; $pageTitle = 'Petugas'; $activeMenu = 'petugas';

$petugas = [
    ['name'=>'Alif Saiful','role'=>'Operator Siaran','shift'=>'Shift Pagi','status'=>'Aktif','init'=>'AS'],
    ['name'=>'Yanuar Pratama','role'=>'Teknisi Jaringan','shift'=>'Shift Pagi','status'=>'Aktif','init'=>'YP'],
    ['name'=>'Alfan Ramadhan','role'=>'Produser Konten','shift'=>'Shift Siang','status'=>'Aktif','init'=>'AR'],
    ['name'=>'Saiful Anwar','role'=>'Operator Studio','shift'=>'Shift Siang','status'=>'Aktif','init'=>'SA'],
    ['name'=>'Yayang Kurnia','role'=>'Editor & Produksi','shift'=>'Shift Malam','status'=>'Aktif','init'=>'YK'],
    ['name'=>'Dimas Setiawan','role'=>'Teknisi Perangkat','shift'=>'Shift Malam','status'=>'Cuti','init'=>'DS'],
    ['name'=>'Rini Anggraini','role'=>'Admin & Dokumentasi','shift'=>'Shift Pagi','status'=>'Aktif','init'=>'RA'],
    ['name'=>'Budi Santoso','role'=>'Kepala Divisi TMB','shift'=>'Fleksibel','status'=>'Aktif','init'=>'BS'],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Petugas</h2>
    <p>Daftar tim Divisi Teknologi &amp; Media Baru.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tambah Petugas</button>
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Petugas</h3><div class="stat-value">8</div><div class="stat-sub">Anggota tim TMB</div></div><div class="stat-icon blue"><i class="ri-team-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Bertugas Hari Ini</h3><div class="stat-value">7</div><div class="stat-sub">Aktif shift</div></div><div class="stat-icon green"><i class="ri-user-follow-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Sedang Cuti</h3><div class="stat-value">1</div><div class="stat-sub">Tidak bertugas</div></div><div class="stat-icon orange"><i class="ri-user-unfollow-line"></i></div></div>
</div>

<div class="person-grid">
  <?php foreach ($petugas as $p): ?>
  <div class="person-card">
    <div class="person-avatar"><?= htmlspecialchars($p['init']) ?></div>
    <div class="person-name"><?= htmlspecialchars($p['name']) ?></div>
    <div class="person-role"><?= htmlspecialchars($p['role']) ?></div>
    <div style="display:flex;justify-content:center;gap:8px;margin-bottom:12px;">
      <span class="tag gray"><?= htmlspecialchars($p['shift']) ?></span>
      <span class="status-tag <?= $p['status']==='Aktif' ? 'baik' : 'proses' ?>"><?= htmlspecialchars($p['status']) ?></span>
    </div>
    <div class="person-contact">
      <button class="icon-action view" title="Detail"><i class="ri-eye-line"></i></button>
      <button class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal-overlay" id="petugasModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tambah Petugas</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Nama Lengkap</label><input type="text" placeholder="Nama petugas" required></div>
      <div class="form-row">
        <div class="form-group"><label>Jabatan / Peran</label><input type="text" placeholder="Contoh: Operator Siaran" required></div>
        <div class="form-group"><label>Shift</label><select><option>Shift Pagi</option><option>Shift Siang</option><option>Shift Malam</option><option>Fleksibel</option></select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('petugasModal').classList.add('active'); }
function closeModal(){ document.getElementById('petugasModal').classList.remove('active'); }
document.getElementById('petugasModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
