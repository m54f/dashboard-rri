<?php
$base = '..'; 
$pageTitle = 'Inventaris'; 
$activeMenu = 'inventaris';

require_once __DIR__ . '/../config/notion.php';

function notionCurl($method, $url, $body = []) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . NOTION_TOKEN,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json',
        ],
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
$search = $_GET['search'] ?? '';
$selectedCategory = $_GET['kategori'] ?? 'Semua';
$selectedLokasi   = $_GET['lokasi'] ?? 'Semua';

$inventaris = [];
$errorMessage = null;

// Base query body (tanpa page_size agar Notion pakai default 100 per halaman)
$baseQueryBody = [
   "sorts" => [["property" => "Nama Alat", "direction" => "ascending"]]
];

if (!empty($search)) {
   $baseQueryBody["filter"] = [
       "or" => [
           ["property" => "Nama Alat", "title" => ["contains" => $search]],
           ["property" => "Kode", "rich_text" => ["contains" => $search]]
       ]
   ];
}

// Variabel untuk pagination Notion API
$has_more = true;
$start_cursor = null;

while ($has_more) {
   $queryBody = $baseQueryBody;
   
   if ($start_cursor !== null) {
       $queryBody["start_cursor"] = $start_cursor;
   }

   $dbData = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', $queryBody);

   if (isset($dbData['results'])) {
       foreach ($dbData['results'] as $page) {
           $props = $page['properties'];
           
           $getTitle = fn($p) => !empty($p['title'][0]['plain_text']) ? $p['title'][0]['plain_text'] : '-';
           $getText = fn($p) => !empty($p['rich_text'][0]['plain_text']) ? $p['rich_text'][0]['plain_text'] : '';
           $getSelect = fn($p) => !empty($p['select']['name']) ? $p['select']['name'] : '-';
           $getNumber = fn($p) => isset($p['number']) ? $p['number'] : 0;

           $kategori = $getSelect($props['Kategori']);
           $status = $getSelect($props['Status']);
           $lokasi = $getSelect($props['Lokasi']);
           $type   = !empty($props['Type']['rich_text'][0]['plain_text']) ? $props['Type']['rich_text'][0]['plain_text'] : (!empty($props['Type']['select']['name']) ? $props['Type']['select']['name'] : '');

           $katTone = match(strtolower($kategori)) {
               'audio' => 'purple', 'kamera' => 'blue', 'komputer' => 'green',
               'kabel' => 'orange', 'lighting' => 'red', 'video' => 'pink',
               default => 'gray'
           };
           $statCls = match(strtolower($status)) {
               'tersedia' => 'green', 'dipinjam' => 'orange', 'rusak' => 'red',
               'perbaikan' => 'yellow', default => 'gray'
           };

           $inventaris[] = [
               'page_id' => $page['id'],
               'title'   => $getTitle($props['Nama Alat']),
               'kode'    => $getText($props['Kode']),
               'tag'     => $kategori,
               'tone'    => $katTone,
               'qty'     => $getNumber($props['Qty']),
               'lokasi'  => $lokasi,
               'status'  => $status,
               'type'    => $type,
               'cls'     => $statCls,
               'notes'   => $getText($props['Keterangan'])
           ];
       }
       
       $has_more = $dbData['has_more'] ?? false;
       $start_cursor = $dbData['next_cursor'] ?? null;
   } else {
       $errorMessage = "Gagal memuat data dari Notion.";
       if (isset($dbData['message'])) $errorMessage .= " Pesan Notion: " . $dbData['message'];
       $has_more = false;
   }
}

$totalItems = count($inventaris);
$tersediaCount = count(array_filter($inventaris, fn($i) => stripos($i['status'], 'Tersedia') !== false));
$dipinjamCount = count(array_filter($inventaris, fn($i) => stripos($i['status'], 'Dipinjam') !== false));
$rusakCount = count(array_filter($inventaris, fn($i) => stripos($i['status'], 'Rusak') !== false || stripos($i['status'], 'Perbaikan') !== false));

$stats = [
    ['label' => 'Total Alat', 'value' => (string)$totalItems, 'sub' => 'Seluruh Inventaris', 'icon' => 'ri-archive-line', 'tone' => 'blue'],
    ['label' => 'Tersedia', 'value' => (string)$tersediaCount, 'sub' => 'Siap dipakai', 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
    ['label' => 'Dipinjam', 'value' => (string)$dipinjamCount, 'sub' => 'Sedang keluar', 'icon' => 'ri-share-forward-line', 'tone' => 'orange'],
    ['label' => 'Rusak / Perbaikan', 'value' => (string)$rusakCount, 'sub' => 'Tidak tersedia', 'icon' => 'ri-tools-line', 'tone' => 'red'],
];

// Ekstraksi kategori unik
$categories = array_values(array_unique(array_filter(array_map(fn($i) => trim($i['tag']), $inventaris), fn($c) => $c !== '' && $c !== '-')));
sort($categories);

// Ekstraksi lokasi unik
$locations = array_values(array_unique(array_filter(array_map(fn($i) => trim($i['lokasi']), $inventaris), fn($l) => $l !== '' && $l !== '-')));
sort($locations);

include __DIR__ . '/../includes/header.php';
?>

<style>
/* Hilangkan tombol panah atas-bawah (spinner) pada input pagination */
.page-input-clean::-webkit-outer-spin-button,
.page-input-clean::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}
.page-input-clean {
  -moz-appearance: textfield !important;
  appearance: textfield !important;
}

/* Layout Filter Bar & Location Dropdown */
.inv-filter-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 1.25rem;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.location-filter-wrap {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: #ffffff;
  padding: 4px 12px;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease-in-out;
  max-width: 100%;
  box-sizing: border-box;
}

.location-filter-wrap:hover,
.location-filter-wrap:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
}

.location-filter-wrap label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  flex-shrink: 0;
}

.location-filter-wrap select {
  padding: 0.4rem 0.5rem 0.4rem 0.2rem;
  border: none !important;
  outline: none !important;
  font-size: 0.85rem;
  font-weight: 700;
  background: transparent !important;
  color: #1e293b;
  cursor: pointer;
  box-shadow: none !important;
  transition: color 0.15s ease;
  min-width: 0;
  max-width: 100%;
}

body.dark-mode .location-filter-wrap {
  background: #1e293b;
  border-color: #334155;
}
body.dark-mode .location-filter-wrap label {
  color: #94a3b8;
}
body.dark-mode .location-filter-wrap select {
  color: #f8fafc;
}
body.dark-mode .location-filter-wrap select option {
  background: #1e293b;
  color: #f8fafc;
}

/* Optimasi Responsif Layar Mobile & Tablet */
.inv-search-bar {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
}
.inv-search-bar input[type="text"] {
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  border: 1px solid #d1d5db;
  width: 200px;
  font-size: 0.85rem;
  outline: none;
  background: #fff;
}

@media (max-width: 768px) {
  .page-head {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
    margin-bottom: 1rem !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  .page-head > div:first-child {
    width: 100% !important;
  }
  .page-head-actions {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 10px !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  .page-head-search {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
  }
  .page-head .btn-primary {
    width: 100% !important;
    justify-content: center !important;
    box-sizing: border-box !important;
  }
  .inv-filter-container {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 10px !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
  }
  .location-filter-wrap {
    width: 100% !important;
    justify-content: space-between !important;
    box-sizing: border-box !important;
    min-width: 0 !important;
  }
  .location-filter-wrap select {
    flex: 1 !important;
    min-width: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    text-overflow: ellipsis;
  }
  .filter-tabs {
    display: flex !important;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    white-space: nowrap !important;
    flex-wrap: nowrap !important;
    padding-bottom: 8px !important;
    margin-bottom: 0.5rem !important;
    -webkit-overflow-scrolling: touch;
    gap: 8px !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
  }
  .filter-tab {
    flex-shrink: 0 !important;
  }
  .table-wrap {
    overflow-x: auto !important;
    width: 100% !important;
    max-width: 100% !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 12px 12px;
    box-sizing: border-box !important;
  }
  #invTable {
    min-width: 650px !important;
  }
  #invTable th, #invTable td {
    padding: 0.75rem 0.6rem !important;
    font-size: 0.83rem !important;
  }
  .pagination-wrap {
    flex-direction: column !important;
    align-items: stretch !important;
    text-align: center;
    gap: 10px !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  #paginationControls {
    justify-content: center !important;
    width: 100% !important;
  }
}

@media (max-width: 640px) {
  .stats-row {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 8px !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
  }
}
</style>

<div class="page-head">
  <div>
    <h2>Inventaris Alat</h2>
    <p>Manajemen stok dan ketersediaan peralatan studio.</p>
  </div>
  <div class="page-head-actions">
    <form class="search-box page-head-search" method="get" onsubmit="event.preventDefault();">
      <i class="ri-search-line"></i>
      <input type="text" id="invSearchInput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/kode/lokasi..." oninput="handleSearchInput(this.value)" style="border:none !important; outline:none !important; box-shadow:none !important; background:transparent !important; width:100%;">
    </form>
    <?php if (getRole() === 'admin'): ?>
    <button class="btn btn-primary" onclick="openModal('create')"><i class="ri-add-line"></i> Tambah Alat</button>
    <?php endif; ?>
  </div>
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
</div>
<?php endif; ?>

<!-- Filter Bar: Kategori & Lokasi -->
<div class="inv-filter-container">
  <div class="filter-tabs" id="filterTabs" style="margin-bottom: 0;">
    <div class="filter-tab <?= $selectedCategory === 'Semua' ? 'active' : '' ?>" data-cat="Semua" onclick="filterCategory('Semua', this)">Semua</div>
    <?php foreach ($categories as $cat): ?>
    <div class="filter-tab <?= $selectedCategory === $cat ? 'active' : '' ?>" data-cat="<?= htmlspecialchars($cat, ENT_QUOTES) ?>" onclick="filterCategory('<?= htmlspecialchars($cat, ENT_QUOTES) ?>', this)"><?= htmlspecialchars($cat) ?></div>
    <?php endforeach; ?>
  </div>

  <div class="location-filter-wrap">
    <i class="ri-map-pin-2-line" style="color: #3b82f6; font-size: 1.1rem;"></i>
    <label for="filterLokasi">Lokasi:</label>
    <select id="filterLokasi" onchange="filterLocation(this.value)">
      <option value="Semua">Semua Lokasi</option>
      <?php foreach ($locations as $loc): ?>
      <option value="<?= htmlspecialchars($loc, ENT_QUOTES) ?>" <?= ($selectedLokasi === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-archive-2-line"></i>Daftar Peralatan</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="invTable">
      <thead>
        <tr><th>Kode</th><th>Nama Alat</th><th>Kategori</th><th>Tipe</th><th>Qty</th><th>Lokasi</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($inventaris as $i): ?>
        <tr data-tag="<?= htmlspecialchars($i['tag'], ENT_QUOTES) ?>" 
            data-lokasi="<?= htmlspecialchars($i['lokasi'], ENT_QUOTES) ?>" 
            data-id="<?= htmlspecialchars($i['page_id'], ENT_QUOTES) ?>">
          <td style="font-weight:600; color:#4b5563; font-family:monospace;"><?= htmlspecialchars($i['kode']) ?: '-' ?></td>
          <td class="cell-title"><?= htmlspecialchars($i['title']) ?></td>
          <td><span class="tag <?= $i['tone'] ?>"><?= htmlspecialchars($i['tag']) ?></span></td>
          <td><?= !empty($i['type']) && $i['type'] !== '-' ? htmlspecialchars($i['type']) : '<span style="color:#9ca3af;">—</span>' ?></td>
          <td style="text-align:center; font-weight:700;"><?= htmlspecialchars($i['qty']) ?></td>
          <td><?= htmlspecialchars($i['lokasi']) ?></td>
          <td><span class="status-tag <?= $i['cls'] ?>"><?= htmlspecialchars($i['status']) ?></span></td>
          <td class="cell-sub"><?= $i['notes'] ? htmlspecialchars($i['notes']) : '—' ?></td>
          <td>
            <?php if (getRole() === 'admin'): ?>
            <button class="icon-action edit" title="Edit" onclick="openEditModal('<?= htmlspecialchars($i['page_id'], ENT_QUOTES) ?>')"><i class="ri-pencil-line"></i></button>
            <button class="icon-action delete" title="Hapus" onclick="deleteInvRow('<?= htmlspecialchars($i['page_id'], ENT_QUOTES) ?>', this)"><i class="ri-delete-bin-line"></i></button>
            <?php else: ?>
            <span style="color: #9ca3af; font-size: 0.85rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr id="emptyRow" style="display:none;">
          <td colspan="9" style="text-align:center; padding:2rem; color:#6b7280;">Tidak ada data inventaris ditemukan.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="pagination-wrap">
    <div id="showingInfo" style="font-size:0.9rem; color:#6b7280;">
      Menampilkan data...
    </div>
    <div id="paginationControls" style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Alat -->
<div class="modal-overlay" id="invModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Tambah Alat</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="invForm" onsubmit="handleInvForm(event)">
      <div class="modal-body">
        <div class="form-group"><label>Nama Alat</label><input type="text" name="nama_alat" placeholder="Contoh: Mixer Ashley" required></div>
        <div class="form-row">
          <div class="form-group">
            <label style="display:flex; justify-content:space-between; align-items:center;">
              <span>Kode Inventaris</span>
              <button type="button" onclick="generateAutoKode()" style="background:none; border:none; color:#3b82f6; font-size:0.78rem; font-weight:600; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:2px;">
                <i class="ri-magic-line"></i> Auto Kode
              </button>
            </label>
            <div style="display:flex; gap:6px;">
              <input type="text" name="kode" id="invKodeInput" placeholder="Contoh: AUD-001" style="flex:1;">
              <button type="button" class="btn btn-secondary" onclick="generateAutoKode()" style="padding:0.4rem 0.75rem; font-size:0.82rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; flex-shrink:0;" title="Generate Kode Otomatis">
                <i class="ri-magic-line"></i> Auto
              </button>
            </div>
          </div>
          <div class="form-group"><label>Jumlah (Qty)</label><input type="number" name="qty" min="1" value="1" required></div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Kategori</label>
            <select name="kategori" onchange="onCategoryChangeInForm()">
              <option>Audio</option><option>Kamera</option><option>Komputer</option><option>Kabel</option>
              <option>Tripod</option><option>Lighting</option><option>Video</option><option>Komunikasi</option>
              <option>Aksesoris</option><option>Power</option><option>Jaringan</option>
            </select>
          </div>
          <div class="form-group">
            <label>Lokasi</label>
            <select name="lokasi">
              <option value="Gudang Musik 1">Gudang Musik 1</option>
              <option value="Gudang Musik 1 - Lemari Besi">Gudang Musik 1 - Lemari Besi</option>
              <option value="Gudang Musik 1 - Lemari Kayu Kiri">Gudang Musik 1 - Lemari Kayu Kiri</option>
              <option value="Studio PRO 1">Studio PRO 1</option>
              <option value="Studio PRO 2">Studio PRO 2</option>
              <option value="Studio PRO 4">Studio PRO 4</option>
              <option value="Studio Podcast">Studio Podcast</option>
              <?php foreach ($locations as $loc): ?>
                <?php if (!in_array($loc, ['Gudang Musik 1', 'Gudang Musik 1 - Lemari Besi', 'Gudang Musik 1 - Lemari Kayu Kiri', 'Studio PRO 1', 'Studio PRO 2', 'Studio PRO 4', 'Studio Podcast'])): ?>
                <option value="<?= htmlspecialchars($loc, ENT_QUOTES) ?>"><?= htmlspecialchars($loc) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option>Tersedia</option><option>Dipinjam</option><option>Rusak</option><option>Perbaikan</option>
            </select>
          </div>
          <div class="form-group">
            <label>Type / Tipe</label>
            <input type="text" name="type" placeholder="Contoh: Wireless, Portable, Type-A, dll">
          </div>
        </div>
        <div class="form-group"><label>Keterangan</label><textarea name="catatan" rows="2" placeholder="Kelengkapan, IP address, dll..."></textarea></div>
        <input type="hidden" name="page_id" value="">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Alat</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE = '<?= $base ?>';
const INVENTARIS_DATA = <?= json_encode($inventaris) ?>;

const CATEGORY_PREFIXES = {
  'Audio': 'AUD',
  'Kamera': 'KAM',
  'Komputer': 'KOM',
  'Kabel': 'KBL',
  'Tripod': 'TRP',
  'Lighting': 'LTG',
  'Video': 'VID',
  'Komunikasi': 'KMK',
  'Aksesoris': 'AKS',
  'Power': 'PWR',
  'Jaringan': 'JRG'
};

function generateAutoKode() {
  const form = document.getElementById('invForm');
  const cat = form.kategori.value || 'Audio';
  const prefix = CATEGORY_PREFIXES[cat] || (cat.substring(0, 3).toUpperCase());

  let maxNum = 0;
  const regex = new RegExp('^' + prefix + '-(\\d+)$', 'i');

  INVENTARIS_DATA.forEach(item => {
    if (item.kode) {
      const match = item.kode.trim().match(regex);
      if (match) {
        const num = parseInt(match[1], 10);
        if (!isNaN(num) && num > maxNum) {
          maxNum = num;
        }
      }
    }
  });

  const nextNum = maxNum + 1;
  const numStr = String(nextNum).padStart(3, '0');
  const newKode = `${prefix}-${numStr}`;

  const kodeInput = form.querySelector('input[name="kode"]');
  if (kodeInput) {
    kodeInput.value = newKode;
    kodeInput.style.transition = 'all 0.3s';
    kodeInput.style.borderColor = '#3b82f6';
    kodeInput.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.2)';
    setTimeout(() => {
      kodeInput.style.borderColor = '';
      kodeInput.style.boxShadow = '';
    }, 600);
  }
}

function onCategoryChangeInForm() {
  const form = document.getElementById('invForm');
  const pageId = form.querySelector('input[name="page_id"]').value;
  const kodeInput = form.querySelector('input[name="kode"]');
  if (!pageId || !kodeInput.value.trim()) {
    generateAutoKode();
  }
}

let currentCategory = '<?= htmlspecialchars($selectedCategory, ENT_QUOTES) ?>';
let currentLokasi   = '<?= htmlspecialchars($selectedLokasi, ENT_QUOTES) ?>';
let currentSearch   = '<?= htmlspecialchars(strtolower($search), ENT_QUOTES) ?>';
let currentPage     = 1;
const perPage       = 8;

// ===== Fungsi Modal =====
function openModal(mode) {
  const form = document.getElementById('invForm');
  form.reset();
  document.querySelector('input[name="page_id"]').value = '';
  document.getElementById('modalTitle').textContent = 'Tambah Alat';
  generateAutoKode();
  document.getElementById('invModal').classList.add('active');
}

function closeModal() {
  document.getElementById('invModal').classList.remove('active');
}

document.getElementById('invModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// ===== Fungsi Edit =====
function openEditModal(id) {
  const item = INVENTARIS_DATA.find(i => i.page_id === id);
  if (!item) return showToast('Data tidak ditemukan!', 'error');

  const form = document.getElementById('invForm');
  form.nama_alat.value = item.title;
  form.kode.value = item.kode;
  form.qty.value = item.qty;
  form.kategori.value = item.tag;
  form.lokasi.value = item.lokasi;
  form.status.value = item.status;
  if (form.type) form.type.value = item.type && item.type !== '-' ? item.type : '';
  form.catatan.value = item.notes;
  form.page_id.value = item.page_id;

  document.getElementById('modalTitle').textContent = 'Edit Alat';
  document.getElementById('invModal').classList.add('active');
}

// ===== Fungsi Filter & Pagination =====
function filterCategory(tag, el) {
  document.querySelectorAll('#filterTabs .filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  currentCategory = tag;
  currentPage = 1;
  applyInvFilters();
}

function filterLocation(loc) {
  currentLokasi = loc;
  currentPage = 1;
  applyInvFilters();
}

function handleSearchInput(val) {
  currentSearch = val.toLowerCase().trim();
  currentPage = 1;
  applyInvFilters();
}

function changeInvPage(page) {
  currentPage = page;
  applyInvFilters();
}

function jumpToInvPage(val, maxPage) {
  let p = parseInt(val, 10);
  if (!isNaN(p) && p >= 1 && p <= maxPage) {
    changeInvPage(p);
  } else if (p > maxPage) {
    changeInvPage(maxPage);
  } else {
    changeInvPage(1);
  }
}

function applyInvFilters() {
  const allRows = Array.from(document.querySelectorAll('#invTable tbody tr:not(#emptyRow)'));
  const emptyRow = document.getElementById('emptyRow');

  const filteredRows = allRows.filter(row => {
    const matchCat = currentCategory === 'Semua' || row.dataset.tag === currentCategory;
    const matchLoc = currentLokasi === 'Semua' || row.dataset.lokasi === currentLokasi;
    
    let matchSearch = true;
    if (currentSearch) {
      const text = row.textContent.toLowerCase();
      matchSearch = text.includes(currentSearch);
    }
    
    return matchCat && matchLoc && matchSearch;
  });

  const totalFiltered = filteredRows.length;
  const totalPages    = Math.ceil(totalFiltered / perPage) || 1;

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  allRows.forEach(row => row.style.display = 'none');

  if (totalFiltered === 0) {
    if (emptyRow) emptyRow.style.display = '';
  } else {
    if (emptyRow) emptyRow.style.display = 'none';
    const start = (currentPage - 1) * perPage;
    const end   = Math.min(start + perPage, totalFiltered);
    for (let i = start; i < end; i++) {
      filteredRows[i].style.display = '';
    }
  }

  const showingFrom = totalFiltered === 0 ? 0 : (currentPage - 1) * perPage + 1;
  const showingTo   = Math.min(currentPage * perPage, totalFiltered);

  const infoEl = document.getElementById('showingInfo');
  if (infoEl) {
    infoEl.textContent = `Menampilkan ${showingFrom}-${showingTo} dari ${totalFiltered} alat`;
  }

  const paginationControls = document.getElementById('paginationControls');
  if (paginationControls) {
    if (totalPages <= 1) {
      paginationControls.style.display = 'none';
    } else {
      paginationControls.style.display = 'flex';
      let html = '';
      
      const prevDisabled = currentPage === 1 ? 'disabled style="pointer-events:none; opacity:0.5;"' : '';
      html += `<button type="button" class="btn btn-secondary" style="padding:0.4rem 0.8rem;" onclick="changeInvPage(${currentPage - 1})" ${prevDisabled}>&laquo; Prev</button>`;
      
      html += `<input type="text" inputmode="numeric" pattern="[0-9]*" class="page-input-clean" value="${currentPage}" style="width:50px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#2563eb; outline:none; font-weight:600;" onfocus="this.select()" onchange="jumpToInvPage(this.value, ${totalPages})" onkeydown="if(event.key === 'Enter') jumpToInvPage(this.value, ${totalPages})">`;
      
      html += `<span style="font-size:0.9rem; color:#475569;">dari ${totalPages}</span>`;
      
      const nextDisabled = currentPage === totalPages ? 'disabled style="pointer-events:none; opacity:0.5;"' : '';
      html += `<button type="button" class="btn btn-secondary" style="padding:0.4rem 0.8rem;" onclick="changeInvPage(${currentPage + 1})" ${nextDisabled}>Next &raquo;</button>`;
      
      paginationControls.innerHTML = html;
    }
  }
}

// ===== Fungsi Submit (Create & Update) =====
async function handleInvForm(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  const isUpdate = formData.get('page_id') !== '';
  formData.append('action', isUpdate ? 'update' : 'create');
  
  const submitBtn = form.querySelector('button[type=submit]');
  const origText = submitBtn.textContent;
  submitBtn.disabled = true; 
  submitBtn.textContent = 'Menyimpan...';

  try {
    const res = await fetch(`${BASE}/api/inventaris.php`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (!res.ok || data.error) {
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
      console.error(data);
      return;
    }
    
    const msg = isUpdate ? '✅ Data alat berhasil diperbarui!' : '✅ Alat baru berhasil ditambahkan!';
    showToast(msg, 'success');
    
    closeModal();
    setTimeout(() => location.reload(), 1200);

  } catch (err) {
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false; 
    submitBtn.textContent = origText;
  }
}

// ===== Fungsi Delete =====
async function deleteInvRow(id, btn) {
  customConfirm(
    'Hapus Inventaris',
    'Yakin mau hapus alat ini? Data akan dipindah ke Trash Notion.',
    async () => {
      const row = btn.closest('tr');
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('page_id', id);

      btn.disabled = true;
      try {
        const res = await fetch(`${BASE}/api/inventaris.php`, { method: 'POST', body: formData });
        const data = await res.json();
        
        if (!res.ok || data.error) {
          showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
          console.error(data);
          return;
        }
        
        showToast('🗑️ Alat berhasil dihapus!', 'success');
        
        row.remove();
        setTimeout(() => location.reload(), 1200);

      } catch (err) {
        showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
      } finally {
        btn.disabled = false;
      }
    },
    'Hapus'
  );
}

// Inisialisasi filter saat DOM siap
document.addEventListener('DOMContentLoaded', applyInvFilters);
</script>

<script>
window.__tourKey   = 'tour_inventaris_v1';
window.__tourSteps = [
  { target: '.page-head',             title: 'Inventaris Alat',       body: 'Daftar lengkap semua peralatan studio beserta status ketersediaannya secara real-time.' },
  { target: '.stats-row',             title: 'Status Ketersediaan',   body: 'Lihat total alat, berapa yang tersedia, sedang dipinjam, dan yang sedang rusak atau dalam perbaikan.' },
  { target: '#invSearchInput',        title: 'Cari Alat',             body: 'Ketik nama atau kode inventaris di sini untuk menemukan alat tertentu dengan cepat.' },
  { target: '.filter-tabs',           title: 'Filter Kategori',       body: 'Klik tab kategori (Audio, Kamera, Komputer, dll.) untuk menyaring daftar berdasarkan jenis alat.' },
  { target: '.location-filter-wrap',  title: 'Filter Lokasi',         body: 'Gunakan dropdown ini untuk melihat alat berdasarkan lokasi penyimpanan seperti Studio atau Gudang.' },
];
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>