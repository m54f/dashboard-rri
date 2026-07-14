<?php
$base = '..'; $pageTitle = 'Daily Agenda'; $activeMenu = 'agenda';

$stats = [
    ['label' => 'Agenda Hari Ini',   'value' => '6',  'sub' => 'Terjadwal',      'icon' => 'ri-calendar-line',     'tone' => 'blue'],
    ['label' => 'Sudah Berlangsung', 'value' => '2',  'sub' => 'Selesai dijalankan','icon' => 'ri-checkbox-circle-line','tone' => 'green'],
    ['label' => 'Menunggu',         'value' => '4',  'sub' => 'Belum dimulai',  'icon' => 'ri-time-line',         'tone' => 'orange'],
    ['label' => 'Live Streaming',   'value' => '2',  'sub' => 'Hari ini',       'icon' => 'ri-live-line',         'tone' => 'purple'],
];

$agenda = [
    ['id'=>1,'time'=>'09.00','title'=>'Podcast Luar Studio','tag'=>'PODCAST','tone'=>'purple','pic'=>'Alfan','status'=>'Selesai','cls'=>'selesai','notes'=>'Lokasi: Balai Kota Malang'],
    ['id'=>2,'time'=>'11.00','title'=>'Persiapan Senam Sore RRI','tag'=>'EVENT','tone'=>'orange','pic'=>'Yanuar','status'=>'Selesai','cls'=>'selesai','notes'=>'Setup sound system lapangan'],
    ['id'=>3,'time'=>'13.00','title'=>'Persiapan Senam Pagi RRI','tag'=>'EVENT','tone'=>'orange','pic'=>'Saiful','status'=>'Proses','cls'=>'proses','notes'=>'Cek kabel & mixer portable'],
    ['id'=>4,'time'=>'15.30','title'=>'Live Youtube: Sore Ceria','tag'=>'LIVE','tone'=>'red','pic'=>'Alif','status'=>'Menunggu','cls'=>'belum','notes'=>'Kolaborasi dengan tim CBP Rupiah'],
    ['id'=>5,'time'=>'19.00','title'=>'Produksi Ruatan Malam','tag'=>'PRODUKSI','tone'=>'blue','pic'=>'Yayang','status'=>'Menunggu','cls'=>'belum','notes'=>''],
    ['id'=>6,'time'=>'21.00','title'=>'Live Youtube: Simphoni Keroncong','tag'=>'LIVE','tone'=>'red','pic'=>'Yanuar','status'=>'Menunggu','cls'=>'belum','notes'=>'Studio B'],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Daily Agenda</h2>
    <p>Jadwal kegiatan operasional siaran hari ini.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('create')"><i class="ri-add-line"></i> Tambah Agenda</button>
</div>

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
  <div class="filter-tab active" onclick="filterAgenda('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterAgenda('PODCAST', this)">Podcast</div>
  <div class="filter-tab" onclick="filterAgenda('EVENT', this)">Event</div>
  <div class="filter-tab" onclick="filterAgenda('LIVE', this)">Live</div>
  <div class="filter-tab" onclick="filterAgenda('PRODUKSI', this)">Produksi</div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-calendar-line"></i>Jadwal — Selasa, 8 Juli 2026</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="agendaTable">
      <thead>
        <tr><th>Waktu</th><th>Kegiatan</th><th>Kategori</th><th>PIC</th><th>Status</th><th>Catatan</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($agenda as $a): ?>
        <tr data-tag="<?= $a['tag'] ?>">
          <td style="font-weight:700;"><?= htmlspecialchars($a['time']) ?></td>
          <td class="cell-title"><?= htmlspecialchars($a['title']) ?></td>
          <td><span class="tag <?= $a['tone'] ?>"><?= $a['tag'] ?></span></td>
          <td><span class="avatar-sm"><?= strtoupper(substr($a['pic'],0,2)) ?></span> <?= htmlspecialchars($a['pic']) ?></td>
          <td><span class="status-tag <?= $a['cls'] ?>"><?= htmlspecialchars($a['status']) ?></span></td>
          <td class="cell-sub"><?= $a['notes'] ? htmlspecialchars($a['notes']) : '—' ?></td>
          <td>
            <button class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></button>
            <button class="icon-action delete" title="Hapus"><i class="ri-delete-bin-line"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah/Edit Agenda -->
<div class="modal-overlay" id="agendaModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Tambah Agenda</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Judul Kegiatan</label><input type="text" placeholder="Contoh: Podcast Luar Studio" required></div>
      <div class="form-row">
        <div class="form-group"><label>Waktu</label><input type="time" required></div>
        <div class="form-group"><label>PIC</label><input type="text" placeholder="Nama petugas" required></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Kategori</label>
          <select><option>PODCAST</option><option>EVENT</option><option>LIVE</option><option>PRODUKSI</option></select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select><option>Menunggu</option><option>Proses</option><option>Selesai</option></select>
        </div>
      </div>
      <div class="form-group"><label>Catatan</label><textarea rows="3" placeholder="Detail lokasi, peralatan, dll..."></textarea></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Agenda</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(){ document.getElementById('agendaModal').classList.add('active'); }
function closeModal(){ document.getElementById('agendaModal').classList.remove('active'); }
document.getElementById('agendaModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function filterAgenda(tag, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#agendaTable tbody tr').forEach(row => {
    row.style.display = (tag === 'Semua' || row.dataset.tag === tag) ? '' : 'none';
  });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
