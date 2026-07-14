<?php
require_once __DIR__.'/../api/troubleshoot.php';

// Ambil data dari Notion
$tickets = getTroubleshoot();

// Keamanan: Pastikan $tickets adalah array (jika API error, jadikan array kosong)
if (!is_array($tickets) || isset($tickets['error'])) {
    $tickets = [];
    $errorMessage = $tickets['error'] ?? 'Gagal memuat data';
}

// Hitung statistik secara DINAMIS berdasarkan data dari Notion
$total = count($tickets);
$selesai = count(array_filter($tickets, fn($t) => $t['status'] === 'Selesai'));
$proses = count(array_filter($tickets, fn($t) => $t['status'] === 'Proses'));
$belum = count(array_filter($tickets, fn($t) => $t['status'] === 'Belum Selesai' || $t['status'] === 'Pending'));

$stats = [
    ['label' => 'Total Kasus', 'value' => $total, 'sub' => 'Data real-time dari Notion', 'icon' => 'ri-tools-line', 'tone' => 'blue'],
    ['label' => 'Selesai', 'value' => $selesai, 'sub' => $total > 0 ? round(($selesai / $total) * 100) . '% resolved' : '0%', 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
    ['label' => 'Proses', 'value' => $proses, 'sub' => 'Sedang ditangani', 'icon' => 'ri-loader-4-line', 'tone' => 'orange'],
    ['label' => 'Belum Selesai', 'value' => $belum, 'sub' => 'Butuh tindak lanjut', 'icon' => 'ri-error-warning-line', 'tone' => 'red'],
];

$base = '..'; 
$pageTitle = 'Troubleshoot'; 
$activeMenu = 'troubleshoot';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h2>Troubleshoot</h2>
    <p>Catatan dan status penanganan kendala teknis siaran.</p>
  </div>
  <!-- Tombol ini saat ini hanya UI, butuh backend untuk benar-benar menyimpan ke Notion -->
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Lapor Kendala Baru</button>
</div>

<!-- Tampilkan pesan error jika API gagal (Opsional, untuk debugging) -->
<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ Gagal memuat data dari Notion: <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="stats-row">
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-info">
      <h3><?= htmlspecialchars($s['label']) ?></h3>
      <div class="stat-value"><?= htmlspecialchars($s['value']) ?></div>
      <div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div>
    </div>
    <div class="stat-icon <?= $s['tone'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="filter-tabs">
  <div class="filter-tab active" onclick="filterTicket('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterTicket('Belum Selesai', this)">Belum Selesai</div>
  <div class="filter-tab" onclick="filterTicket('Proses', this)">Proses</div>
  <div class="filter-tab" onclick="filterTicket('Selesai', this)">Selesai</div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-list-check-2"></i> Daftar Kendala</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="ticketTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Judul Kendala</th>
          <th>Kategori</th>
          <th>PIC</th>
          <th>Status</th>
          <th>Tanggal Lapor</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Tidak ada data kendala ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($tickets as $t): ?>
          <tr data-status="<?= htmlspecialchars($t['status']) ?>">
            <td style="font-weight:700; color:var(--text-secondary);"><?= htmlspecialchars($t['id']) ?></td>
            <td class="cell-title"><?= htmlspecialchars($t['title']) ?></td>
            <td><span class="tag gray"><?= htmlspecialchars($t['cat']) ?></span></td>
            <td>
              <span class="avatar-sm"><?= strtoupper(substr($t['pic'], 0, 2)) ?></span> 
              <?= htmlspecialchars($t['pic']) ?>
            </td>
            <td><span class="status-tag <?= htmlspecialchars($t['cls']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
            <td class="cell-sub"><?= htmlspecialchars($t['date']) ?></td>
            <td>
              <!-- Tombol Detail bisa diarahkan ke URL Notion atau modal detail -->
              <a href="<?= htmlspecialchars($t['url'] ?? '#') ?>" target="_blank" class="icon-action view" title="Buka di Notion">
                <i class="ri-external-link-line"></i>
             a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Lapor Kendala -->
<div class="modal-overlay" id="ticketModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Lapor Kendala Baru</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="newTicketForm" onsubmit="handleNewTicket(event)">
      <div class="form-group">
        <label>Judul Kendala</label>
        <input type="text" name="title" placeholder="Contoh: Internet studio terputus" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Kategori</label>
          <select name="cat" required>
            <option value="Studio">Studio</option>
            <option value="Sarana Prasarana">Sarana Prasarana</option>
            <option value="Pemancar">Pemancar</option>
          </select>
        </div>
        <div class="form-group">
          <label>PIC Penanganan</label>
          <input type="text" name="pic" placeholder="Nama petugas" required>
        </div>
      </div>
      <div class="form-group">
        <label>Deskripsi Kendala</label>
        <textarea name="deskripsi" rows="3" placeholder="Jelaskan kronologi & gejala masalah..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Laporan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(){ document.getElementById('ticketModal').classList.add('active'); }
function closeModal(){ document.getElementById('ticketModal').classList.remove('active'); }

// Tutup modal jika klik di luar area konten
document.getElementById('ticketModal').addEventListener('click', e => { 
  if (e.target === e.currentTarget) closeModal(); 
});

// Filter tabel berdasarkan status
function filterTicket(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  
  document.querySelectorAll('#ticketTable tbody tr').forEach(row => {
    // Jika status 'Semua' atau data-status baris cocok dengan filter
    if (status === 'Semua' || row.dataset.status === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// ⚠️ CATATAN: Fungsi ini masih placeholder. 
// Untuk benar-benar menyimpan ke Notion, Anda perlu membuat endpoint API terpisah (misal: api/create-troubleshoot.php)
function handleNewTicket(event) {
  event.preventDefault();
  alert('Fitur simpan ke Notion belum diaktifkan di backend. Silakan buat laporan langsung di Notion untuk saat ini.');
  closeModal();
  // event.target.reset(); // Reset form jika diperlukan
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>