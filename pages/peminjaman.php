<?php
$base = '..'; $pageTitle = 'Peminjaman Alat'; $activeMenu = 'peminjaman';

$stats = [
    ['label'=>'Alat Dipinjam','value'=>'5','sub'=>'Sedang di lapangan','icon'=>'ri-hand-heart-line','tone'=>'orange'],
    ['label'=>'Tersedia di Gudang','value'=>'23','sub'=>'Siap dipinjam','icon'=>'ri-archive-line','tone'=>'green'],
    ['label'=>'Terlambat Kembali','value'=>'1','sub'=>'Perlu ditindaklanjuti','icon'=>'ri-alarm-warning-line','tone'=>'red'],
    ['label'=>'Total Peminjaman Bulan Ini','value'=>'19','sub'=>'Transaksi tercatat','icon'=>'ri-exchange-line','tone'=>'blue'],
];

$peminjaman = [
    ['alat'=>'Wireless Mic Set (2 unit)','peminjam'=>'Yanuar','tujuan'=>'Liputan Senam Sore RRI','tgl_pinjam'=>'8 Jul 2026','tgl_kembali'=>'8 Jul 2026','status'=>'Dipinjam','cls'=>'dipinjam'],
    ['alat'=>'Kamera DSLR Canon 80D','peminjam'=>'Alfan','tujuan'=>'Podcast Luar Studio','tgl_pinjam'=>'8 Jul 2026','tgl_kembali'=>'8 Jul 2026','status'=>'Dipinjam','cls'=>'dipinjam'],
    ['alat'=>'Speaker Portable JBL','peminjam'=>'Saiful','tujuan'=>'Persiapan Senam Pagi','tgl_pinjam'=>'8 Jul 2026','tgl_kembali'=>'8 Jul 2026','status'=>'Dipinjam','cls'=>'dipinjam'],
    ['alat'=>'Tripod Manfrotto','peminjam'=>'Yayang','tujuan'=>'Produksi Ruatan Malam','tgl_pinjam'=>'8 Jul 2026','tgl_kembali'=>'9 Jul 2026','status'=>'Dipinjam','cls'=>'dipinjam'],
    ['alat'=>'Recorder Zoom H5','peminjam'=>'Alif','tujuan'=>'Liputan lapangan','tgl_pinjam'=>'5 Jul 2026','tgl_kembali'=>'6 Jul 2026','status'=>'Terlambat','cls'=>'terlambat'],
    ['alat'=>'Lighting LED Panel','peminjam'=>'Alfan','tujuan'=>'Live Youtube Sore Ceria','tgl_pinjam'=>'3 Jul 2026','tgl_kembali'=>'3 Jul 2026','status'=>'Dikembalikan','cls'=>'dikembalikan'],
    ['alat'=>'Microphone Clip-on','peminjam'=>'Saiful','tujuan'=>'Wawancara narasumber','tgl_pinjam'=>'2 Jul 2026','tgl_kembali'=>'2 Jul 2026','status'=>'Dikembalikan','cls'=>'dikembalikan'],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Peminjaman Alat</h2>
    <p>Kelola peminjaman dan pengembalian alat siaran.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Catat Peminjaman</button>
</div>

<div class="stats-row">
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-info"><h3><?= htmlspecialchars($s['label']) ?></h3><div class="stat-value"><?= htmlspecialchars($s['value']) ?></div><div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div></div>
    <div class="stat-icon <?= $s['tone'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="filter-tabs">
  <div class="filter-tab active" onclick="filterPinjam('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterPinjam('Dipinjam', this)">Dipinjam</div>
  <div class="filter-tab" onclick="filterPinjam('Terlambat', this)">Terlambat</div>
  <div class="filter-tab" onclick="filterPinjam('Dikembalikan', this)">Dikembalikan</div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-service-line"></i>Riwayat Peminjaman</div></div>
  <div class="table-wrap">
    <table class="data-table" id="pinjamTable">
      <thead><tr><th>Alat</th><th>Peminjam</th><th>Tujuan</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($peminjaman as $p): ?>
        <tr data-status="<?= $p['status'] ?>">
          <td class="cell-title"><?= htmlspecialchars($p['alat']) ?></td>
          <td><span class="avatar-sm"><?= strtoupper(substr($p['peminjam'],0,2)) ?></span> <?= htmlspecialchars($p['peminjam']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($p['tujuan']) ?></td>
          <td><?= htmlspecialchars($p['tgl_pinjam']) ?></td>
          <td><?= htmlspecialchars($p['tgl_kembali']) ?></td>
          <td><span class="status-tag <?= $p['cls'] ?>"><?= htmlspecialchars($p['status']) ?></span></td>
          <td>
            <?php if ($p['status'] === 'Dipinjam' || $p['status'] === 'Terlambat'): ?>
            <button class="icon-action view" title="Tandai Dikembalikan"><i class="ri-checkbox-circle-line"></i></button>
            <?php endif; ?>
            <button class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="pinjamModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Catat Peminjaman Alat</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Nama Alat</label><input type="text" placeholder="Contoh: Wireless Mic Set" required></div>
      <div class="form-row">
        <div class="form-group"><label>Nama Peminjam</label><input type="text" placeholder="Nama petugas" required></div>
        <div class="form-group"><label>Tujuan Penggunaan</label><input type="text" placeholder="Contoh: Liputan lapangan" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Tanggal Pinjam</label><input type="date" required></div>
        <div class="form-group"><label>Estimasi Kembali</label><input type="date" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('pinjamModal').classList.add('active'); }
function closeModal(){ document.getElementById('pinjamModal').classList.remove('active'); }
document.getElementById('pinjamModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
function filterPinjam(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#pinjamTable tbody tr').forEach(row => {
    row.style.display = (status === 'Semua' || row.dataset.status === status) ? '' : 'none';
  });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
