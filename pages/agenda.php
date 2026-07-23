<?php
$base = '..'; $pageTitle = 'Daily Agenda'; $activeMenu = 'agenda';

$stats = [
    ['label' => 'Agenda Hari Ini',   'value' => '6',  'sub' => 'Terjadwal',      'icon' => 'ri-calendar-line',     'tone' => 'blue'],
    ['label' => 'Sudah Berlangsung', 'value' => '2',  'sub' => 'Selesai dijalankan','icon' => 'ri-checkbox-circle-line','tone' => 'green'],
    ['label' => 'Menunggu',         'value' => '4',  'sub' => 'Belum dimulai',  'icon' => 'ri-time-line',         'tone' => 'orange'],
];

require_once __DIR__ . '/../api/agenda.php';

$filterDate = $_GET['date'] ?? ''; // '' atau format 'Y-m-d' (dari input type="date")
$selectedSort = 'desc'; // fitur pilih Urutan dihapus — tabel selalu tampilkan yang PALING BARU dulu

// Ambil SEMUA data sekali jalan, lalu filter di PHP berdasarkan tanggal yang
// dipilih di satu field input type="date". Widget bawaan browser ini sudah
// punya navigasi bulan & tahun (klik header kalender) plus tombol Clear/Today
// sendiri, jadi user gampang cari tanggal yang diinginkan dari satu field aja.
$allAgenda = getAgenda(null, $selectedSort);
$errorMessage = null;
$errorRaw = null;
if (!is_array($allAgenda) || isset($allAgenda['error'])) {
    $errorMessage = $allAgenda['error'] ?? 'Gagal memuat data agenda dari Notion';
    $errorRaw = $allAgenda['raw'] ?? null;
    $allAgenda = [];
}

$agenda = $allAgenda;
if ($filterDate !== '') {
    $agenda = array_values(array_filter($agenda, fn($a) => $a['date'] === $filterDate));
}

// Statistik kartu di atas SELALU berdasarkan HARI INI (bukan ikut filter
// tabel di bawah) — supaya tetap relevan berapa pun tanggal yang lagi
// difilter user, dan otomatis ganti tiap hari karena date('Y-m-d') selalu
// pakai tanggal server saat ini.
$todayStr = date('Y-m-d');
$todayAgenda = getAgenda($todayStr, 'asc');
if (!is_array($todayAgenda) || isset($todayAgenda['error'])) {
    $todayAgenda = [];
}
$agendaCount  = count($todayAgenda);
$doneCount    = count(array_filter($todayAgenda, fn($a) => stripos($a['status'], 'selesai') !== false));
$waitingCount = count(array_filter($todayAgenda, fn($a) => preg_match('/\b(belum|menunggu)\b/i', $a['status'])));
$liveCount    = count(array_filter($todayAgenda, fn($a) => stripos($a['tag'], 'live') !== false));

$stats = [
    ['label' => 'Agenda Hari Ini',   'value' => (string)$agendaCount, 'sub' => 'Terjadwal',      'icon' => 'ri-calendar-line',     'tone' => 'blue'],
    ['label' => 'Sudah Berlangsung', 'value' => (string)$doneCount,  'sub' => 'Selesai dijalankan','icon' => 'ri-checkbox-circle-line','tone' => 'green'],
    ['label' => 'Menunggu',         'value' => (string)$waitingCount, 'sub' => 'Belum dimulai',  'icon' => 'ri-time-line',         'tone' => 'orange'],
];

// Daftar kategori unik dari data yang ada — dipakai buat tab filter & saran
// input Kategori di form.
$categories = [];
foreach ($agenda as $a) {
    if ($a['tag'] !== '' && !in_array($a['tag'], $categories, true)) {
        $categories[] = $a['tag'];
    }
}
sort($categories);

// Pagination — 8 per halaman, sama seperti di Checklist Perangkat
$perPage = 8;
$totalItems = count($agenda);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($currentPage - 1) * $perPage;
$agendaPage = array_slice($agenda, $offset, $perPage);
$showingFrom = $totalItems === 0 ? 0 : $offset + 1;
$showingTo = min($offset + $perPage, $totalItems);

function agendaPageUrl($page, $filterDate)
{
    $params = [];
    if ($filterDate !== '') $params['date'] = $filterDate;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Pagination dipadatkan supaya muat di layar HP (halaman pertama, halaman
// terakhir, dan beberapa halaman di sekitar halaman aktif — sisanya diwakili
// tanda "…"). Tanpa ini, kalau ada 20 halaman semua nomornya kelist penuh dan
// bikin harus scroll horizontal di HP.
function paginationRange($current, $total, $delta = 1)
{
    $result = [];
    $prev = 0;
    for ($i = 1; $i <= $total; $i++) {
        if ($i === 1 || $i === $total || ($i >= $current - $delta && $i <= $current + $delta)) {
            if ($prev && $i - $prev > 1) {
                $result[] = '...';
            }
            $result[] = $i;
            $prev = $i;
        }
    }
    return $result;
}

include __DIR__ . '/../includes/header.php';
?>
<style>
/* Dropdown status inline di tabel Daily Agenda — tampilannya tetap pakai
   warna & bentuk yang sama seperti badge status-tag lama, tapi jadi <select>
   supaya bisa langsung diganti (Belum/Selesai) tanpa buka modal Edit.
   Ditambah border putus-putus + panah kecil supaya keliatan bisa diklik
   (sebelumnya terlalu mirip teks/badge statis biasa). */
.status-select-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
}
.status-select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  border: 1px dashed rgba(0,0,0,0.25);
  outline: none;
  cursor: pointer;
  font: inherit;
  font-weight: 600;
  padding-right: 20px !important;
  transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
}
.status-select:hover {
  border-color: rgba(37,99,235,0.55);
  box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
}
.status-select:focus {
  border-color: rgba(37,99,235,0.7);
  box-shadow: 0 0 0 3px rgba(37,99,235,0.22);
}
.status-select:disabled { cursor: wait; opacity: 0.6; }
.status-select-arrow {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  font-size: 13px;
  line-height: 1;
  opacity: 0.7;
}

/* Pagination di layar HP — ditumpuk & di-tengah biar rapi, nggak mepet kanan-kiri */
@media (max-width: 640px) {
  .pagination-wrap {
    flex-direction: column;
    align-items: stretch !important;
    text-align: center;
  }
  .pagination-wrap > div:first-child {
    text-align: center;
  }
  .pagination-wrap > div:last-child {
    justify-content: center !important;
  }
}
</style>
<div class="page-head">
  <div>
    <h2>Daily Agenda</h2>
    <p>Jadwal kegiatan operasional siaran hari ini.</p>
  </div>
  <form class="date-picker" method="get" id="filterForm" style="display:inline-flex; align-items:center; gap:0.75rem; margin-left:1rem; flex-wrap:wrap;">
    <label for="dateFilter" style="margin:0; font-weight:600;">Filter Tanggal:</label>
    <input id="dateFilter" type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit()" style="padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #d1d5db;">
  </form>
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

<?php if ($errorMessage): ?>
<div class="alert alert-danger" style="margin-bottom:1rem; padding:1rem; border-radius:12px; background:#fee2e2; color:#991b1b;">
  <strong>Terjadi kesalahan:</strong> <?= htmlspecialchars($errorMessage) ?>
  <?php if (!empty($errorRaw)): ?>
  <div style="margin-top:0.75rem; font-size:0.90rem; color:#7f1d1d;">Detail: <?= htmlspecialchars($errorRaw) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="filter-tabs" id="filterTabs">
  <div class="filter-tab active" onclick="filterAgenda('Semua', this)">Semua</div>
  <?php foreach ($categories as $cat): ?>
  <div class="filter-tab" onclick="filterAgenda('<?= htmlspecialchars($cat, ENT_QUOTES) ?>', this)"><?= htmlspecialchars($cat) ?></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-calendar-line"></i>Jadwal — <?php
      if ($filterDate !== '') {
        echo htmlspecialchars(date('l, d F Y', strtotime($filterDate)));
      } else {
        echo 'Semua Tanggal';
      }
    ?></div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="agendaTable">
      <thead>
        <tr><th>Tanggal</th><th>Waktu</th><th>Kegiatan</th><th>Kategori</th><th>Lokasi Kegiatan</th><th>Status</th><th>Catatan</th><th>Dokumen</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($agendaPage as $a): ?>
        <tr data-tag="<?= htmlspecialchars($a['tag'], ENT_QUOTES) ?>" data-id="<?= htmlspecialchars($a['page_id'], ENT_QUOTES) ?>" data-date="<?= htmlspecialchars($a['date'], ENT_QUOTES) ?>" data-time="<?= htmlspecialchars($a['time'], ENT_QUOTES) ?>">
          <td style="font-weight:600; white-space:nowrap;"><?= htmlspecialchars($a['date'] ? date('d M Y', strtotime($a['date'])) : '-') ?></td>
          <td style="font-weight:700;"><?= htmlspecialchars($a['time']) ?></td>
          <td class="cell-title"><?= htmlspecialchars($a['title']) ?></td>
          <td><span class="tag <?= $a['tone'] ?>"><?= htmlspecialchars($a['tag']) ?></span></td>
          <td><?= strtoupper(substr($a['pic'],0,2)) ?></span> <?= htmlspecialchars($a['pic']) ?></td>
          <td>
            <?php $curStatus = stripos($a['status'], 'selesai') !== false ? 'Selesai' : 'Belum'; ?>
            <span class="status-select-wrap" title="Klik untuk ubah status">
              <select class="status-select status-tag <?= $a['cls'] ?>" data-id="<?= htmlspecialchars($a['page_id'], ENT_QUOTES) ?>" data-prev="<?= $curStatus ?>" onchange="updateStatusInline(this)">
                <option value="Belum" <?= $curStatus === 'Belum' ? 'selected' : '' ?>>Belum</option>
                <option value="Selesai" <?= $curStatus === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
              </select>
              <i class="ri-arrow-down-s-line status-select-arrow"></i>
            </span>
          </td>
          <td class="cell-sub"><?= $a['notes'] ? htmlspecialchars($a['notes']) : '—' ?></td>
          <td>
            <?php if (!empty($a['dokumentasi'])):
              $docs = $a['dokumentasi'];
              $shown = array_slice($docs, 0, 2);
              $remaining = count($docs) - count($shown);
            ?>
            <div style="display:flex; gap:4px; align-items:center; flex-wrap:wrap;">
              <?php foreach ($shown as $doc):
                $ext = strtolower(pathinfo(parse_url($doc['name'], PHP_URL_PATH) ?: $doc['name'], PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
              ?>
                <?php if ($isImage): ?>
                <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" rel="noopener" title="<?= htmlspecialchars($doc['name']) ?>">
                  <img src="<?= htmlspecialchars($doc['url']) ?>" alt="" style="width:32px; height:32px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; display:block;" onerror="this.outerHTML='<span style=&quot;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid #e5e7eb;background:#f3f4f6;&quot;><i class=&quot;ri-image-line&quot; style=&quot;color:#9ca3af;&quot;></i></span>';">
                </a>
                <?php else: ?>
                <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" rel="noopener" title="<?= htmlspecialchars($doc['name']) ?>" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:6px; border:1px solid #e5e7eb; background:#f3f4f6; text-decoration:none;">
                  <i class="ri-file-text-line" style="color:#6b7280;"></i>
                </a>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if ($remaining > 0): ?>
              <span style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:6px; background:#e5e7eb; font-size:0.75rem; font-weight:600; color:#4b5563;">+<?= $remaining ?></span>
              <?php endif; ?>
            </div>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <a class="icon-action" title="Buka di Notion" href="<?= htmlspecialchars($a['url']) ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i></a>
            <button class="icon-action edit" title="Edit" onclick="openEditModal('<?= htmlspecialchars($a['page_id'], ENT_QUOTES) ?>')"><i class="ri-pencil-line"></i></button>
            <button class="icon-action delete" title="Hapus" onclick="deleteAgendaRow('<?= htmlspecialchars($a['page_id'], ENT_QUOTES) ?>', this)"><i class="ri-delete-bin-line"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination-wrap" style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; flex-wrap:wrap; gap:0.75rem;">
    <div style="font-size:0.9rem; color:#6b7280;">
      Menampilkan <?= $showingFrom ?>-<?= $showingTo ?> dari <?= $totalItems ?> agenda
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
      <a href="<?= htmlspecialchars(agendaPageUrl(max(1, $currentPage - 1), $filterDate)) ?>"
         class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">&laquo; Prev</a>
      <?php foreach (paginationRange($currentPage, $totalPages) as $p): ?>
        <?php if ($p === '...'): ?>
        <span style="padding:0.4rem 0.3rem; color:#9ca3af; font-size:0.85rem;">&hellip;</span>
        <?php else: ?>
        <a href="<?= htmlspecialchars(agendaPageUrl($p, $filterDate)) ?>"
           class="btn <?= $p === $currentPage ? 'btn-primary' : 'btn-secondary' ?>" style="padding:0.4rem 0.8rem; min-width:36px; text-align:center;"><?= $p ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a href="<?= htmlspecialchars(agendaPageUrl(min($totalPages, $currentPage + 1), $filterDate)) ?>"
         class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage >= $totalPages ? 'pointer-events:none; opacity:0.5;' : '' ?>">Next &raquo;</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Tambah/Edit Agenda -->
<div class="modal-overlay" id="agendaModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Tambah Agenda</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="agendaForm" onsubmit="handleAgendaForm(event)">
      <div class="form-group"><label>Judul Kegiatan</label><input type="text" name="kegiatan" placeholder="Contoh: Podcast Luar Studio" required></div>
      <div class="form-row">
        <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" required></div>
        <div class="form-group"><label>Waktu</label><input type="time" name="waktu" required></div>
      </div>
      <div class="form-group"><label>Lokasi Kegiatan</label><input type="text" name="lokasi_kegiatan" placeholder="Nama Tempat" required></div>
      <div class="form-group">
        <label>Kategori</label>
        <input type="text" name="kategori" list="kategoriList" placeholder="Pilih dari saran atau ketik kategori baru" required>
        <datalist id="kategoriList">
          <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="form-group"><label>Catatan</label><textarea name="catatan" rows="3" placeholder="Detail lokasi, peralatan, dll..."></textarea></div>
      <div class="form-group">
        <label>Surat Tugas / Dokumen / Foto (opsional)</label>
        <input type="file" id="dokumenInput" name="dokumen[]" multiple accept=".pdf,.doc,.docx,image/*" style="display:none;">
        <div style="display:flex; gap:0.5rem;">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('dokumenInput').click()"><i class="ri-attachment-2"></i> Pilih File</button>
          <button type="button" class="btn btn-secondary" onclick="clearSelectedFiles()" title="Batalkan semua file yang dipilih">&times; Batalkan</button>
        </div>
        <div id="selectedFilesList" style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.5rem;"></div>
        <small style="color:#6b7280; display:block; margin-top:0.25rem;">Bisa pilih lebih dari satu file sekaligus (tahan Ctrl/Cmd waktu memilih di dialog), atau klik "Pilih File" berkali-kali untuk menambah satu-satu. Maks 5MB per file.</small>
        <small id="editFileNote" style="color:#9ca3af; display:none; margin-top:0.25rem;">Upload file baru di sini akan MENGGANTI dokumen lama pada agenda ini. Kosongkan kalau tidak mau mengubah dokumen yang sudah ada.</small>
      </div>
      <input type="hidden" name="page_id" value="">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="remove_existing_files" value="0">
      <input type="hidden" name="status" value="Belum">
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Agenda</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE = '<?= $base ?>';
const CURRENT_SORT = 'desc'; // fitur pilih Urutan sudah dihapus, tabel selalu tampilkan yang paling baru dulu

// Data agenda yang lagi tampil di halaman ini (buat isi form Edit)
const AGENDA_DATA = <?= json_encode(array_map(function ($a) {
    return [
        'id'       => $a['page_id'],
        'title'    => $a['title'],
        'date'     => $a['date'],
        'time'     => $a['time'],
        'lokasi'   => $a['pic'],
        'kategori' => $a['tag'],
        'status'   => $a['status'],
    'catatan'  => $a['notes'],
    'dokumentasi' => $a['dokumentasi'] ?? [],
    ];
}, $agendaPage)) ?>;

// ==== Manajemen file yang dipilih (chip list + tombol batal per file) ====
let selectedFiles = [];

function renderSelectedFiles(){
  const container = document.getElementById('selectedFilesList');
  container.innerHTML = '';
  selectedFiles.forEach((file, idx) => {
    const chip = document.createElement('span');
    chip.style.cssText = 'display:inline-flex; align-items:center; gap:6px; background:#f3f4f6; border-radius:6px; padding:4px 8px; font-size:0.85rem; color:#374151;';
    const nameSpan = document.createElement('span');
    nameSpan.textContent = file.name;
    chip.appendChild(nameSpan);
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.textContent = '×';
    removeBtn.title = 'Batalkan file ini';
    removeBtn.style.cssText = 'border:none; background:none; cursor:pointer; color:#ef4444; font-weight:700; font-size:1rem; line-height:1; padding:0 2px;';
    removeBtn.onclick = () => {
      selectedFiles.splice(idx, 1);
      syncFileInput();
      renderSelectedFiles();
    };
    chip.appendChild(removeBtn);
    container.appendChild(chip);
  });
}

function syncFileInput(){
  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  document.getElementById('dokumenInput').files = dt.files;
}

function clearSelectedFiles(){
  selectedFiles = [];
  syncFileInput();
  renderSelectedFiles();
}

document.getElementById('dokumenInput').addEventListener('change', function(){
  // Gabungkan file yang baru dipilih dengan yang sudah ada (bukan menimpa),
  // supaya bisa klik "Pilih File" berkali-kali untuk menambah satu-satu.
  const existingNames = new Set(selectedFiles.map(f => f.name + f.size));
  Array.from(this.files).forEach(f => {
    if (!existingNames.has(f.name + f.size)) selectedFiles.push(f);
  });
  syncFileInput();
  renderSelectedFiles();
});

function openModal(mode){
  if (mode === 'create') {
    const form = document.getElementById('agendaForm');
    form.reset();
    form.page_id.value = '';
    form.elements['action'].value = 'create';
    form.elements['remove_existing_files'].value = '0';
    document.getElementById('modalTitle').textContent = 'Tambah Agenda';
    document.getElementById('agendaForm').querySelector('button[type=submit]').textContent = 'Simpan Agenda';
    document.getElementById('editFileNote').style.display = 'none';
