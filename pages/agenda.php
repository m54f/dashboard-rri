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
  display: inline-flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 0px 22px 0px 8px !important;
  gap: 8px;
  min-width: 50px;
  height: 24px;
  border-radius: 4px;
  font-family: 'Figtree', sans-serif;
  font-style: normal;
  font-weight: 400;
  font-size: 14px;
  line-height: 20px;
  text-align: center;
  color: #FFFFFF !important;
  border: none;
  outline: none;
  cursor: pointer;
  box-sizing: border-box;
  transition: opacity .15s ease, filter .15s ease;
}
.status-select:hover {
  filter: brightness(0.92);
}
.status-select:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 115, 234, 0.4);
}
.status-select:disabled { cursor: wait; opacity: 0.6; }
.status-select option {
  background: #ffffff;
  color: #0f172a;
  font-family: 'Figtree', sans-serif;
  font-size: 14px;
}
.status-select-arrow {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  font-size: 12px;
  line-height: 1;
  color: #FFFFFF;
  opacity: 0.9;
}

/* Hilangkan panah atas-bawah (spinner) pada input pagination */
.page-input-clean::-webkit-outer-spin-button,
.page-input-clean::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}
.page-input-clean {
  -moz-appearance: textfield !important;
  appearance: textfield !important;
}

/* Stil khusus form filter tanggal */
.date-picker-form {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  margin-left: auto;
  flex-wrap: wrap;
}
.date-picker-form label {
  margin: 0;
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--text-secondary, #4b5563);
}
.date-picker-form input[type="date"] {
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--border-color, #d1d5db);
  font-size: 0.85rem;
  outline: none;
  background: #fff;
}

/* Optimasi Responsif Layar Mobile & Tablet */
@media (max-width: 768px) {
  .page-head {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
    margin-bottom: 1rem !important;
  }
  .page-head > div:first-child {
    width: 100%;
  }
  .date-picker-form {
    margin-left: 0 !important;
    width: 100% !important;
    justify-content: space-between;
    background: #f8fafc;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-sizing: border-box;
  }
  .date-picker-form input[type="date"] {
    flex: 1;
    min-width: 130px;
  }
  .page-head .btn-primary {
    width: 100% !important;
    justify-content: center !important;
  }
  
  /* Filter Tabs scroll horizontal halus di HP */
  .filter-tabs {
    display: flex !important;
    overflow-x: auto !important;
    white-space: nowrap !important;
    flex-wrap: nowrap !important;
    padding-bottom: 8px !important;
    margin-bottom: 1rem !important;
    -webkit-overflow-scrolling: touch;
    gap: 8px !important;
    scrollbar-width: thin;
  }
  .filter-tab {
    flex-shrink: 0 !important;
  }

  /* Tabel agenda responsif di HP */
  .table-wrap {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 12px 12px;
  }
  #agendaTable {
    min-width: 750px !important;
  }
  #agendaTable th, #agendaTable td {
    padding: 0.75rem 0.6rem !important;
    font-size: 0.83rem !important;
  }

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

@media (max-width: 640px) {
  .stats-row {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
  }
  .stats-row > *:last-child:nth-child(odd) {
    grid-column: span 2;
  }
  .form-row {
    flex-direction: column !important;
    gap: 10px !important;
  }
}
</style>

<div class="page-head">
  <div>
    <h2>Daily Agenda</h2>
    <p>Jadwal kegiatan operasional siaran hari ini.</p>
  </div>
  <form class="date-picker-form" method="get" id="filterForm">
    <label for="dateFilter">Filter Tanggal:</label>
    <input id="dateFilter" type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit()">
  </form>
  <?php if (getRole() === 'admin'): ?>
  <button class="btn btn-primary" onclick="openModal('create')"><i class="ri-add-line"></i> Tambah Agenda</button>
  <?php endif; ?>
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
          <td style="font-weight:600; white-space:nowrap;">
            <?php
              $dateDisplay = '-';
              if (!empty($a['date'])) {
                $dateDisplay = date('d M Y', strtotime($a['date']));
                if (!empty($a['date_end']) && $a['date_end'] !== $a['date']) {
                  $dateDisplay .= ' s/d ' . date('d M Y', strtotime($a['date_end']));
                }
              }
              echo htmlspecialchars($dateDisplay);
            ?>
          </td>
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
                <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" rel="noopener" title="<?= htmlspecialchars($doc['name']) ?>" class="doc-icon-link" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:6px; text-decoration:none;">
                  <i class="ri-file-text-line"></i>
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
            <?php if (getRole() === 'admin'): ?>
            <button class="icon-action edit" title="Edit" onclick="openEditModal('<?= htmlspecialchars($a['page_id'], ENT_QUOTES) ?>')"><i class="ri-pencil-line"></i></button>
            <button class="icon-action delete" title="Hapus" onclick="deleteAgendaRow('<?= htmlspecialchars($a['page_id'], ENT_QUOTES) ?>', this)"><i class="ri-delete-bin-line"></i></button>
            <?php endif; ?>
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
    <div style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
      <a href="<?= htmlspecialchars(agendaPageUrl(max(1, $currentPage - 1), $filterDate)) ?>"
         class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">&laquo; Prev</a>
      <input type="text" inputmode="numeric" pattern="[0-9]*" class="page-input-clean" value="<?= $currentPage ?>" style="width:50px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#2563eb; outline:none; font-weight:600;" onfocus="this.select()" onchange="let p=parseInt(this.value); if(!isNaN(p) && p>=1 && p<=<?= $totalPages ?>) location.href='<?= htmlspecialchars(agendaPageUrl(999999, $filterDate)) ?>'.replace('999999', p);" onkeydown="if(event.key === 'Enter'){ let p=parseInt(this.value); if(!isNaN(p) && p>=1 && p<=<?= $totalPages ?>) location.href='<?= htmlspecialchars(agendaPageUrl(999999, $filterDate)) ?>'.replace('999999', p); }">
      <span style="font-size:0.9rem; color:#475569;">dari <?= $totalPages ?></span>
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
      <div class="modal-body">
        <div class="form-group"><label>Judul Kegiatan <span class="required-star">*</span></label><input type="text" name="kegiatan" placeholder="Contoh: Podcast Luar Studio" required></div>
        <div class="form-row">
          <div class="form-group">
            <label>Tanggal Mulai <span class="required-star">*</span></label>
            <input type="date" name="tanggal" required>
          </div>
          <div class="form-group">
            <label>Tanggal Selesai <span style="font-weight:400; color:#6b7280; font-size:11px;">(Opsional, jika > 1 hari)</span></label>
            <input type="date" name="tanggal_selesai">
          </div>
        </div>
        <div class="form-group">
          <label>Waktu Kegiatan <span class="required-star">*</span></label>
          <input type="time" name="waktu" required>
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
      </div>
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
const USER_ROLE = '<?= getRole() ?>'; // Pass role to JavaScript

// Data agenda yang lagi tampil di halaman ini (buat isi form Edit)
const AGENDA_DATA = <?= json_encode(array_map(function ($a) {
    return [
        'id'          => $a['page_id'],
        'title'       => $a['title'],
        'date'        => $a['date'],
        'date_end'    => $a['date_end'] ?? '',
        'time'        => $a['time'],
        'lokasi'      => $a['pic'],
        'kategori'    => $a['tag'],
        'status'      => $a['status'],
        'catatan'     => $a['notes'],
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
    if (form.tanggal_selesai) form.tanggal_selesai.value = '';
    form.elements['action'].value = 'create';
    form.elements['remove_existing_files'].value = '0';
    document.getElementById('modalTitle').textContent = 'Tambah Agenda';
    document.getElementById('agendaForm').querySelector('button[type=submit]').textContent = 'Simpan Agenda';
    document.getElementById('editFileNote').style.display = 'none';
    clearSelectedFiles();
    // Hapus elemen daftar dokumen lama (jika ada) supaya tidak muncul saat Create
    const existing = document.getElementById('existingDocsList');
    if (existing) existing.remove();
  }
  document.getElementById('agendaModal').classList.add('active');
}
function closeModal(){ document.getElementById('agendaModal').classList.remove('active'); }
document.getElementById('agendaModal').addEventListener('click', e => {
  // HANYA saat klik area gelap di belakang modal (bukan konten di dalamnya,
  // termasuk tombol Simpan) — sebelumnya ini ke-trigger juga waktu klik
  // Simpan Agenda karena tombolnya ada di dalam .modal-overlay, jadi file
  // yang baru dipilih ikut kehapus SEBELUM form sempat submit.
  if (e.target === e.currentTarget) {
    closeModal();
    const existing = document.getElementById('existingDocsList');
    if (existing) existing.remove();
    clearSelectedFiles();
  }
});
document.querySelectorAll('.modal-close').forEach(el => {
  el.addEventListener('click', () => {
    const existing = document.getElementById('existingDocsList');
    if (existing) existing.remove();
    clearSelectedFiles();
  });
});

function openEditModal(id){
  const entry = AGENDA_DATA.find(a => a.id === id);
  if (!entry) { alert('Data agenda tidak ditemukan di halaman ini.'); return; }

  clearSelectedFiles();
  const form = document.getElementById('agendaForm');
  form.reset();
  form.kegiatan.value = entry.title;
  form.tanggal.value = entry.date;
  if (form.tanggal_selesai) form.tanggal_selesai.value = entry.date_end || '';
  form.waktu.value = entry.time;
  form.lokasi_kegiatan.value = entry.lokasi;
  form.kategori.value = entry.kategori;
  form.status.value = entry.status;
  form.catatan.value = entry.catatan || '';
  form.page_id.value = entry.id;
  form.elements['action'].value = 'update';
  form.elements['remove_existing_files'].value = '0';

  document.getElementById('modalTitle').textContent = 'Edit Agenda';
  form.querySelector('button[type=submit]').textContent = 'Simpan Perubahan';
  document.getElementById('editFileNote').style.display = 'block';

  // Tampilkan daftar dokumentasi yang sudah ada (jika ada)
  const existing = entry.dokumentasi || [];
  const containerId = 'existingDocsList';
  let container = document.getElementById(containerId);
  if (!container) {
    container = document.createElement('div');
    container.id = containerId;
    container.style.marginTop = '0.5rem';
    container.style.display = 'flex';
    container.style.gap = '0.5rem';
    container.style.flexWrap = 'wrap';
    form.querySelector('input[type=file][name="dokumen[]"]').after(container);
  }
  container.innerHTML = '';
  if (existing.length === 0) {
    container.innerHTML = '<div style="color:#9ca3af; font-size:0.9rem;">Tidak ada dokumen</div>';
  } else {
    const list = document.createElement('div');
    list.style.display = 'flex';
    list.style.gap = '0.5rem';
    list.style.flexWrap = 'wrap';
    existing.forEach(d => {
      const a = document.createElement('a');
      a.href = d.url;
      a.target = '_blank';
      a.rel = 'noopener';
      a.textContent = d.name || (d.url && d.url.split('/').pop()) || 'File';
      a.style.padding = '4px 8px';
      a.style.background = '#f3f4f6';
      a.style.borderRadius = '6px';
      a.style.fontSize = '0.85rem';
      a.style.color = '#374151';
      list.appendChild(a);
    });
    container.appendChild(list);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-secondary';
    removeBtn.style.marginTop = '0.4rem';
    removeBtn.style.fontSize = '0.85rem';
    removeBtn.textContent = '× Hapus dokumen lama';
    removeBtn.title = 'Menghapus SEMUA dokumen lama di agenda ini saat disimpan';
    removeBtn.onclick = () => {
      form.elements['remove_existing_files'].value = '1';
      list.style.opacity = '0.4';
      list.style.textDecoration = 'line-through';
      removeBtn.textContent = '✓ Akan dihapus saat disimpan (klik utk batal)';
      removeBtn.onclick = () => {
        form.elements['remove_existing_files'].value = '0';
        list.style.opacity = '1';
        list.style.textDecoration = 'none';
        openEditModal(id); // termudah: render ulang dari awal
      };
    };
    container.appendChild(removeBtn);
  }

  document.getElementById('agendaModal').classList.add('active');
}

async function deleteAgendaRow(id, btn){
  customConfirm(
    'Hapus Agenda',
    'Hapus agenda ini? Data akan dipindahkan ke Trash Notion.',
    async () => {
      const row = btn.closest('tr');
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('page_id', id);
      btn.disabled = true;
      try {
        const res = await fetch(`${BASE}/api/agenda.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if (!res.ok || data.error) {
          showToast('❌ Gagal menghapus agenda: ' + JSON.stringify(data), 'error');
          return;
        }
        row.remove();
        showToast('🗑️ Agenda berhasil dihapus', 'success');
      } catch (err) {
        showToast('❌ Gagal menghapus agenda: ' + err.message, 'error');
      } finally {
        btn.disabled = false;
      }
    },
    'Hapus'
  );
}

function filterAgenda(tag, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#agendaTable tbody tr').forEach(row => {
    row.style.display = (tag === 'Semua' || row.dataset.tag === tag) ? '' : 'none';
  });
}

// ==== Helper warna badge, sama persis logikanya dengan categoryTone()/statusClass() di api/agenda.php ====
function categoryTone(cat){
  const c = (cat || '').toLowerCase();
  if (c.includes('podcast')) return 'blue';
  if (c.includes('live')) return 'purple';
  if (c.includes('produksi')) return 'orange';
  if (c.includes('event') || c.includes('kunjungan')) return 'green';
  return 'gray';
}
function statusTone(status){
  const s = (status || '').toLowerCase();
  if (s.includes('selesai')) return 'green';
  if (s.includes('cancel')) return 'red';
  if (s.includes('proses')) return 'orange';
  if (s.includes('belum') || s.includes('menunggu')) return 'gray';
  return 'blue';
}
// Dropdown status yang dipakai di tiap baris tabel — cuma "Belum"/"Selesai",
// langsung bisa diganti dari tabel tanpa buka modal Edit.
function renderStatusSelect(pageId, status){
  const cur = (status || '').toLowerCase().includes('selesai') ? 'Selesai' : 'Belum';
  return `
    <span class="status-select-wrap" title="Klik untuk ubah status">
      <select class="status-select status-tag ${statusTone(cur)}" data-id="${pageId || ''}" data-prev="${cur}" onchange="updateStatusInline(this)">
        <option value="Belum" ${cur === 'Belum' ? 'selected' : ''}>Belum</option>
        <option value="Selesai" ${cur === 'Selesai' ? 'selected' : ''}>Selesai</option>
      </select>
      <i class="ri-arrow-down-s-line status-select-arrow"></i>
    </span>
  `;
}

// Ganti status langsung dari dropdown di tabel, kirim ke api/agenda.php
// (action=update) pakai data lengkap baris tsb dari AGENDA_DATA, tanpa perlu
// buka modal Edit. Kalau gagal, dropdown dikembalikan ke nilai semula.
async function updateStatusInline(selectEl){
  const pageId = selectEl.dataset.id;
  const newStatus = selectEl.value;
  const prevValue = selectEl.dataset.prev || (newStatus === 'Selesai' ? 'Belum' : 'Selesai');
  const entry = AGENDA_DATA.find(a => a.id === pageId);
  if (!entry) {
    alert('Data agenda tidak ditemukan di halaman ini, silakan reload halaman lalu coba lagi.');
    selectEl.value = prevValue;
    return;
  }

  selectEl.disabled = true;
  const formData = new FormData();
  formData.append('action', 'update');
  formData.append('page_id', pageId);
  formData.append('kegiatan', entry.title);
  formData.append('tanggal', entry.date);
  formData.append('waktu', entry.time);
  formData.append('lokasi_kegiatan', entry.lokasi);
  formData.append('kategori', entry.kategori);
  formData.append('status', newStatus);
  formData.append('catatan', entry.catatan || '');
  formData.append('remove_existing_files', '0');

  try {
    const res = await fetch(`${BASE}/api/agenda.php`, { method: 'POST', body: formData });
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(JSON.stringify(data));

    entry.status = newStatus;
    selectEl.className = `status-select status-tag ${statusTone(newStatus)}`;
    selectEl.dataset.prev = newStatus;
  } catch (err) {
    console.error('[updateStatusInline] gagal ubah status:', err);
    alert('Gagal mengubah status: ' + (err.message || err));
    selectEl.value = prevValue;
  } finally {
    selectEl.disabled = false;
  }
}

function escapeHtml(str){
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
function formatDateID(ymd, ymdEnd){
  if (!ymd) return '-';
  const formatSingle = (str) => {
    const parts = str.split('-');
    if (parts.length !== 3) return str;
    const [y, m, d] = parts;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const mi = parseInt(m, 10) - 1;
    if (isNaN(mi) || mi < 0 || mi > 11) return str;
    return `${d} ${months[mi]} ${y}`;
  };

  let res = formatSingle(ymd);
  if (ymdEnd && ymdEnd !== ymd) {
    res += ' s/d ' + formatSingle(ymdEnd);
  }
  return res;
}

function renderDokumenCell(docs){
  if (!docs || !docs.length) return '—';
  const shown = docs.slice(0, 2);
  const remaining = docs.length - shown.length;
  let html = '<div style="display:flex; gap:4px; align-items:center; flex-wrap:wrap;">';
  shown.forEach(doc => {
    const ext = (doc.name.split('.').pop() || '').toLowerCase();
    const isImage = ['jpg','jpeg','png','gif','webp','bmp'].includes(ext);
    const safeName = escapeHtml(doc.name);
    const safeUrl = escapeHtml(doc.url);
    if (isImage) {
      html += `<a href="${safeUrl}" target="_blank" rel="noopener" title="${safeName}"><img src="${safeUrl}" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;display:block;" onerror="this.style.display='none';"></a>`;
    } else {
      html += `<a href="${safeUrl}" target="_blank" rel="noopener" title="${safeName}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid #e5e7eb;background:#f3f4f6;text-decoration:none;"><i class="ri-file-text-line" style="color:#6b7280;"></i></a>`;
    }
  });
  if (remaining > 0) {
    html += `<span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;background:#e5e7eb;font-size:0.75rem;font-weight:600;color:#4b5563;">+${remaining}</span>`;
  }
  html += '</div>';
  return html;
}

function insertRowSorted(tr){
  const tbody = document.querySelector('#agendaTable tbody');
  const newKey = tr.dataset.date + tr.dataset.time;
  const rows = Array.from(tbody.children);
  for (const row of rows) {
    const key = row.dataset.date + row.dataset.time;
    const goesBefore = CURRENT_SORT === 'asc' ? (newKey < key) : (newKey > key);
    if (goesBefore) {
      tbody.insertBefore(tr, row);
      return;
    }
  }
  tbody.appendChild(tr);
}

// Tambahkan baris baru ke posisi yang sesuai urutan (Terlama/Terbaru) yang
// lagi aktif, bukan selalu di paling atas. Dokumen yang baru diupload belum
// langsung muncul di baris ini — baru kelihatan setelah reload, karena kita
// tidak tahu URL Notion-nya sampai halaman dimuat ulang.
function addRowToTable(entry, pageId, dokumentasi){
  const tr = document.createElement('tr');
  tr.dataset.tag = entry.kategori;
  tr.dataset.id = pageId || '';
  tr.dataset.date = entry.tanggal;
  tr.dataset.time = entry.waktu;
  const notionUrl = pageId ? `https://www.notion.so/${pageId.replace(/-/g, '')}` : '#';
  
  // Conditional buttons based on role
  let actionButtons = `<a class="icon-action" title="Buka di Notion" href="${notionUrl}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i></a>`;
  if (USER_ROLE === 'admin') {
    actionButtons += `
      <button class="icon-action edit" title="Edit" onclick="openEditModal('${pageId}')"><i class="ri-pencil-line"></i></button>
      <button class="icon-action delete" title="Hapus" onclick="deleteAgendaRow('${pageId}', this)"><i class="ri-delete-bin-line"></i></button>
    `;
  }
  
  tr.innerHTML = `
    <td style="font-weight:600; white-space:nowrap;">${escapeHtml(formatDateID(entry.tanggal, entry.tanggal_selesai))}</td>
    <td style="font-weight:700;">${escapeHtml(entry.waktu)}</td>
    <td class="cell-title">${escapeHtml(entry.kegiatan)}</td>
    <td><span class="tag ${categoryTone(entry.kategori)}">${escapeHtml(entry.kategori)}</span></td>
    <td><span class="avatar-sm">${escapeHtml(entry.lokasi.slice(0,2).toUpperCase())}</span> ${escapeHtml(entry.lokasi)}</td>
    <td>${renderStatusSelect(pageId, entry.status)}</td>
    <td class="cell-sub">${entry.catatan ? escapeHtml(entry.catatan) : '—'}</td>
    <td>${renderDokumenCell(dokumentasi)}</td>
    <td>${actionButtons}</td>
  `;
  insertRowSorted(tr);
}

// Update baris yang sudah ada (hasil Edit) langsung di tabel tanpa reload.
function updateRowInTable(pageId, entry, dokumentasi){
  const tr = document.querySelector(`#agendaTable tbody tr[data-id="${pageId}"]`);
  if (!tr) return false;
  tr.remove();
  addRowToTable(entry, pageId, dokumentasi);
  return true;
}

function ensureFilterTab(cat){
  if (!cat) return;
  const tabs = document.getElementById('filterTabs');
  const exists = Array.from(tabs.children).some(el => el.textContent === cat);
  if (!exists){
    const div = document.createElement('div');
    div.className = 'filter-tab';
    div.textContent = cat;
    div.addEventListener('click', () => filterAgenda(cat, div));
    tabs.appendChild(div);
  }
}
function ensureDatalistOption(cat){
  if (!cat) return;
  const list = document.getElementById('kategoriList');
  const exists = Array.from(list.options).some(o => o.value === cat);
  if (!exists){
    const opt = document.createElement('option');
    opt.value = cat;
    list.appendChild(opt);
  }
}

async function handleAgendaForm(event){
  event.preventDefault();
  const form = event.target;
  const isUpdate = form.elements['action'].value === 'update';

  const formData = new FormData(form);

  const tMulai = formData.get('tanggal');
  const tSelesai = formData.get('tanggal_selesai');
  if (tSelesai && tSelesai < tMulai) {
    alert('Tanggal Selesai tidak boleh lebih awal dari Tanggal Mulai.');
    return;
  }

  const submitBtn = form.querySelector('button[type=submit]');
  const origText = submitBtn.textContent;
  submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan...';

  try {
    const res = await fetch(`${BASE}/api/agenda.php`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    console.log('[handleAgendaForm] response dari api/agenda.php:', data);
    if (!res.ok || data.error) {
      throw new Error(JSON.stringify(data));
    }
    if (data.file_warnings && data.file_warnings.length) {
      alert('PERHATIAN: Agenda tersimpan, TAPI file gagal diupload ke Notion:\n\n' + data.file_warnings.join('\n') + '\n\nDetail lengkap ada di Console (F12).');
    }

    const entry = {
      kegiatan:        formData.get('kegiatan'),
      tanggal:         formData.get('tanggal'),
      tanggal_selesai: formData.get('tanggal_selesai') || '',
      waktu:           formData.get('waktu'),
      lokasi:          formData.get('lokasi_kegiatan'),
      kategori:        formData.get('kategori'),
      status:          formData.get('status'),
      catatan:         formData.get('catatan'),
    };
    const dokumentasi = data.dokumentasi || [];

    if (isUpdate) {
      const pageId = formData.get('page_id');
      updateRowInTable(pageId, entry, dokumentasi);

      // Sinkronkan juga ke AGENDA_DATA supaya kalau dibuka Edit lagi datanya sudah yang terbaru
      const idx = AGENDA_DATA.findIndex(a => a.id === pageId);
      const updatedRecord = {
        id: pageId, title: entry.kegiatan, date: entry.tanggal, date_end: entry.tanggal_selesai, time: entry.waktu,
        lokasi: entry.lokasi, kategori: entry.kategori, status: entry.status,
        catatan: entry.catatan, dokumentasi: dokumentasi,
      };
      if (idx >= 0) AGENDA_DATA[idx] = updatedRecord; else AGENDA_DATA.push(updatedRecord);

      ensureFilterTab(entry.kategori);
      ensureDatalistOption(entry.kategori);
      closeModal();
      form.reset();
      clearSelectedFiles();
      return;
    }

    addRowToTable(entry, data.id, dokumentasi);
    AGENDA_DATA.push({
      id: data.id,
      title: entry.kegiatan,
      date: entry.tanggal,
      date_end: entry.tanggal_selesai,
      time: entry.waktu,
      lokasi: entry.lokasi,
      kategori: entry.kategori,
      status: entry.status,
      catatan: entry.catatan,
      dokumentasi: dokumentasi,
    });
    ensureFilterTab(entry.kategori);
    ensureDatalistOption(entry.kategori);

    closeModal();
    form.reset();
    clearSelectedFiles();
  } catch (err) {
    console.error('[handleAgendaForm] exception:', err);
    alert('Gagal menyimpan agenda: ' + (err.message || err));
  } finally {
    submitBtn.disabled = false; submitBtn.textContent = origText;
  }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>