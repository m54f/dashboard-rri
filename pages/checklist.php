<?php
$base = '..'; $pageTitle = 'Checklist Perangkat'; $activeMenu = 'checklist';

$categories = [
    'Studio Utama' => [
        ['name'=>'Mixer Audio Yamaha MG12', 'status'=>'Baik', 'cls'=>'baik', 'checked'=>'08.10 WIB oleh Alfan'],
        ['name'=>'Microphone Shure SM7B',   'status'=>'Baik', 'cls'=>'baik', 'checked'=>'08.10 WIB oleh Alfan'],
        ['name'=>'PC Streaming',            'status'=>'Baik', 'cls'=>'baik', 'checked'=>'08.05 WIB oleh Saiful'],
        ['name'=>'Encoder OBS',             'status'=>'Perlu Dicek', 'cls'=>'belum', 'checked'=>'Belum dicek hari ini'],
    ],
    'Jaringan & Streaming' => [
        ['name'=>'Internet Telkom (IP 60)', 'status'=>'Baik', 'cls'=>'baik', 'checked'=>'08.00 WIB oleh Yanuar'],
        ['name'=>'Router Backup',           'status'=>'Baik', 'cls'=>'baik', 'checked'=>'08.00 WIB oleh Yanuar'],
        ['name'=>'Capture Card',            'status'=>'Rusak', 'cls'=>'rusak', 'checked'=>'Dilaporkan 6 Jul 2026'],
    ],
    'Alat Lapangan' => [
        ['name'=>'Wireless Mic Set',   'status'=>'Baik', 'cls'=>'baik', 'checked'=>'Kemarin, 16.30'],
        ['name'=>'Speaker Portable',   'status'=>'Baik', 'cls'=>'baik', 'checked'=>'Kemarin, 16.30'],
        ['name'=>'Kamera DSLR',        'status'=>'Baik', 'cls'=>'baik', 'checked'=>'Kemarin, 16.30'],
    ],
];

$totalItem = 0; $totalOk = 0;
foreach ($categories as $items) { foreach ($items as $it) { $totalItem++; if ($it['cls']==='baik') $totalOk++; } }
$pct = round($totalOk / $totalItem * 100);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Checklist Perangkat</h2>
    <p>Pastikan seluruh perangkat siaran dalam kondisi siap pakai.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tambah Perangkat</button>
</div>

<div class="grid-2" style="grid-template-columns: 280px 1fr; margin-bottom:20px;">
  <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;">
    <div class="ring" style="width:110px;height:110px;">
      <svg width="110" height="110" viewBox="0 0 110 110">
        <circle cx="55" cy="55" r="46" fill="none" stroke="#e2e8f0" stroke-width="11"/>
        <circle cx="55" cy="55" r="46" fill="none" stroke="var(--success)" stroke-width="11"
          stroke-dasharray="<?= round(2*pi()*46) ?>"
          stroke-dashoffset="<?= round(2*pi()*46*(1-$pct/100)) ?>" stroke-linecap="round"/>
      </svg>
      <div class="ring-pct" style="font-size:22px;"><?= $pct ?>%</div>
    </div>
    <h4 style="margin-top:14px;font-size:14px;font-weight:700;">Perangkat Siap</h4>
    <p style="font-size:11.5px;color:var(--text-secondary);"><?= $totalOk ?> dari <?= $totalItem ?> perangkat baik</p>
  </div>
  <div class="stats-row" style="margin-bottom:0;">
    <div class="stat-card">
      <div class="stat-info"><h3>Kondisi Baik</h3><div class="stat-value"><?= $totalOk ?></div><div class="stat-sub">Siap digunakan</div></div>
      <div class="stat-icon green"><i class="ri-checkbox-circle-line"></i></div>
    </div>
    <div class="stat-card">
      <div class="stat-info"><h3>Perlu Dicek</h3><div class="stat-value">1</div><div class="stat-sub">Menunggu verifikasi</div></div>
      <div class="stat-icon orange"><i class="ri-error-warning-line"></i></div>
    </div>
    <div class="stat-card">
      <div class="stat-info"><h3>Rusak</h3><div class="stat-value">1</div><div class="stat-sub">Perlu perbaikan</div></div>
      <div class="stat-icon red"><i class="ri-close-circle-line"></i></div>
    </div>
  </div>
</div>

<?php foreach ($categories as $catName => $items): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><div class="card-title"><i class="ri-folder-line"></i><?= htmlspecialchars($catName) ?></div></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Nama Perangkat</th><th>Status</th><th>Terakhir Dicek</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td class="cell-title"><?= htmlspecialchars($it['name']) ?></td>
          <td><span class="status-tag <?= $it['cls'] ?>"><?= htmlspecialchars($it['status']) ?></span></td>
          <td class="cell-sub"><?= htmlspecialchars($it['checked']) ?></td>
          <td>
            <button class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></button>
            <button class="icon-action view" title="Tandai Dicek"><i class="ri-checkbox-circle-line"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<div class="modal-overlay" id="itemModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tambah Perangkat</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Nama Perangkat</label><input type="text" placeholder="Contoh: Mixer Audio" required></div>
      <div class="form-row">
        <div class="form-group"><label>Kategori</label><select><option>Studio Utama</option><option>Jaringan & Streaming</option><option>Alat Lapangan</option></select></div>
        <div class="form-group"><label>Status</label><select><option>Baik</option><option>Perlu Dicek</option><option>Rusak</option></select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(){ document.getElementById('itemModal').classList.add('active'); }
function closeModal(){ document.getElementById('itemModal').classList.remove('active'); }
document.getElementById('itemModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
