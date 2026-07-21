<?php
require_once __DIR__.'/../api/petugas.php';

 $petugas = getPetugas();

// Cek jika ada error dari Notion
if (!is_array($petugas) || isset($petugas['error'])) {
    $errorMessage = $petugas['error'] ?? 'Gagal memuat data';
    $petugas = [];
}

// Hitung statistik dinamis
 $totalPetugas = count($petugas);
 $totalPosisi  = count(array_unique(array_filter(array_column($petugas, 'posisi'), fn($v) => $v !== '-')));
 $totalJabatan = count(array_unique(array_filter(array_column($petugas, 'role'), fn($v) => $v !== '-')));

 $base = '..'; 
 $pageTitle = 'Petugas'; 
 $activeMenu = 'petugas';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h2>Petugas</h2>
    <p>Daftar tim Divisi Teknologi &amp; Media Baru.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tambah Petugas</button>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ Gagal memuat data dari Notion: <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="stats-row">
  <div class="stat-card">
    <div class="stat-info">
      <h3>Total Petugas</h3>
      <div class="stat-value"><?= $totalPetugas ?></div>
      <div class="stat-sub">Anggota tim TMB</div>
    </div>
    <div class="stat-icon blue"><i class="ri-team-line"></i></div>
  </div>
  <div class="stat-card">
    <div class="stat-info">
      <h3>Total Posisi</h3>
      <div class="stat-value"><?= $totalPosisi ?></div>
      <div class="stat-sub">Penempatan area kerja</div>
    </div>
    <div class="stat-icon green"><i class="ri-user-follow-line"></i></div>
  </div>
  <div class="stat-card">
    <div class="stat-info">
      <h3>Total Jabatan</h3>
      <div class="stat-value"><?= $totalJabatan ?></div>
      <div class="stat-sub">Jenjang karir</div>
    </div>
    <div class="stat-icon orange"><i class="ri-user-unfollow-line"></i></div>
  </div>
</div>

<div class="person-grid">
  <?php if (empty($petugas)): ?>
    <p style="grid-column: 1 / -1; text-align: center; color: var(--text-secondary); margin-top: 2rem;">
      Tidak ada data petugas ditemukan.
    </p>
  <?php else: ?>
    <?php foreach ($petugas as $p): ?>
    <div class="person-card">
      <div class="person-avatar"><?= htmlspecialchars($p['init']) ?></div>
      <div class="person-name"><?= htmlspecialchars($p['name']) ?></div>
      <div class="person-role"><?= htmlspecialchars($p['role']) ?></div>
      <div style="display:flex;justify-content:center;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
        <span class="tag gray"><?= htmlspecialchars($p['posisi']) ?></span>
        <span class="status-tag baik"><?= htmlspecialchars($p['pangkat']) ?></span>
      </div>
      <div class="person-contact">
        <a href="https://www.notion.so/<?= htmlspecialchars($p['page_id']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
          <i class="ri-external-link-line"></i>
        </a>
        <button class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></button>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="petugasModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tambah Petugas</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); alert('Fitur tambah petugas belum dihubungkan ke API Notion.'); closeModal();">
      <div class="form-group"><label>Nama Lengkap</label><input type="text" placeholder="Nama petugas" required></div>
      <div class="form-row">
        <div class="form-group"><label>Jabatan</label><input type="text" placeholder="Contoh: Asisten Teknisi" required></div>
        <div class="form-group"><label>Posisi</label><input type="text" placeholder="Contoh: Studio" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>NIP / NIPPK / NRP</label><input type="text" placeholder="Nomor induk"></div>
        <div class="form-group"><label>Pangkat / Gol. Ruang</label><input type="text" placeholder="Contoh: III / a"></div>
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