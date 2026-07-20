<?php
 $base = '..'; 
 $pageTitle = 'Form Peminjaman'; 
 $activeMenu = 'peminjaman';

require_once __DIR__.'/../config/notion.php';
require_once __DIR__.'/../api/peminjaman.php';

if (!function_exists('notionCurl')) {
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
        } elseif ($method === 'PATCH') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }
}

 $isEdit = isset($_GET['id']);
 $editData = [
    'page_id' => '', 'kegiatan' => '', 'pic' => '', 'lokasi' => '',
    'tgl_kegiatan' => '', 'tgl_pinjam' => '', 'durasi' => '', 
    'keterangan' => '', 'alat_arr' => []
];

if ($isEdit) {
    $items = getPeminjaman();
    if (is_array($items)) {
        foreach ($items as $it) {
            if ($it['page_id'] === $_GET['id']) {
                $editData = $it;
                break;
            }
        }
    }
}

 $picList  = ['Alfian','Alif','Saiful','Yhanuar','Yayang','Yudha','Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Dayat','Winsa','Selvy'];
 $alatList = ['Mixer', 'Mic', 'Laptop', 'Tripod', 'Kamera', 'Kabel', 'Soundcard', 'Speaker', 'HT', 'Headphone', 'Modem', 'Video Switcher', 'Video Sender', 'Converter', 'Baterai', 'Lampu', 'Overlength', 'Earpik', 'Stand Mic', 'PC Desktop', 'Monitor', 'Keyboard', 'Mouse', 'Audio Distribution', 'Lainnya'];

// Ambil stok dan keterangan (jenis) dari Inventaris
 $stockData = [];
 $jenisOptions = []; // Array untuk menampung opsi jenis per alat

if (defined('DATABASE_ID_INVENTARIS')) {
    $invQueryBody = ["page_size" => 100, "filter" => ["property" => "Status", "select" => ["equals" => "Tersedia"]]];
    $invDbData = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', $invQueryBody);
    if (isset($invDbData['results'])) {
        foreach ($invDbData['results'] as $invPage) {
            $invName = !empty($invPage['properties']['Nama Alat']['title'][0]['plain_text']) ? $invPage['properties']['Nama Alat']['title'][0]['plain_text'] : '';
            $invKet = !empty($invPage['properties']['Keterangan']['rich_text'][0]['plain_text']) ? $invPage['properties']['Keterangan']['rich_text'][0]['plain_text'] : '';
            
            foreach ($alatList as $alat) {
                if (stripos($invName, $alat) !== false) {
                    if (!isset($stockData[$alat])) $stockData[$alat] = 0;
                    $stockData[$alat]++;
                    
                    // Simpan keterangan sebagai opsi dropdown
                    if (!empty($invKet)) {
                        if (!isset($jenisOptions[$alat])) $jenisOptions[$alat] = [];
                        if (!in_array($invKet, $jenisOptions[$alat])) {
                            $jenisOptions[$alat][] = $invKet;
                        }
                    }
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Dependencies untuk Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="page-head">
  <div>
    <h2><?= $isEdit ? 'Edit Peminjaman Alat' : 'Catat Peminjaman Alat' ?></h2>
    <p>Isi data dengan lengkap dan pastikan stok alat mencukupi.</p>
  </div>
  <a href="peminjaman.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Kembali ke Daftar</a>
</div>

<form id="pinjamForm" onsubmit="handleSubmit(event)">
  <input type="hidden" id="field_page_id" value="<?= htmlspecialchars($editData['page_id']) ?>">

  <!-- SECTION 1: INFO KEGIATAN -->
  <div class="card section-card">
    <div class="section-header">
      <i class="ri-calendar-event-line"></i>
      <span>Informasi Kegiatan</span>
    </div>
    <div class="section-body">
      <div class="form-grid cols-2">
        <div class="form-group">
          <label>Nama Kegiatan <span class="req">*</span></label>
          <select id="field_kegiatan" required>
            <option value="">Pilih Kegiatan</option>
            <option value="Event Kantor">Event Kantor</option>
            <option value="Troubleshooting / Maintenance">Troubleshooting / Maintenance</option>
            <option value="Upacara / Apel Pagi">Upacara / Apel Pagi</option>
            <option value="Acara Live / Dialog Luar / Tapping">Acara Live / Dialog Luar / Tapping</option>
            <option value="Podcast / Tapping">Podcast / Tapping</option>
            <option value="Keroncong">Keroncong</option>
            <option value="Senandung Rindu">Senandung Rindu</option>
            <option value="Zoom Meeting">Zoom Meeting</option>
            <option value="Kunjungan">Kunjungan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nama PIC PT <span class="req">*</span></label>
          <select id="field_pic" multiple required>
            <?php foreach ($picList as $nama): ?>
              <option value="<?= $nama ?>"><?= $nama ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid cols-3">
        <div class="form-group">
          <label>Tanggal Kegiatan</label>
          <input type="date" id="field_tgl_kegiatan">
        </div>
        <div class="form-group">
          <label>Tanggal Pinjam <span class="req">*</span></label>
          <input type="date" id="field_tgl_pinjam" required>
        </div>
        <div class="form-group">
          <label>Durasi (jam)</label>
          <input type="number" id="field_durasi" min="0" step="0.5" placeholder="Contoh: 3">
        </div>
      </div>
      <div class="form-grid cols-2">
        <div class="form-group">
          <label>Lokasi</label>
          <input type="text" id="field_lokasi" placeholder="Contoh: Auditorium">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <input type="text" id="field_keterangan" placeholder="Catatan tambahan">
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION 2: PEMILIHAN ALAT -->
  <div class="card section-card">
    <div class="section-header">
      <i class="ri-tools-line"></i>
      <span>Alat yang Dipinjam</span>
      <span class="section-badge" id="selectedCount">0 dipilih</span>
    </div>
    <div class="section-body">
      <!-- Toolbar -->
      <div class="alat-toolbar">
        <div class="search-box">
          <i class="ri-search-line"></i>
          <input type="text" id="searchAlat" placeholder="Cari alat...">
        </div>
        <div class="filter-group">
          <button type="button" class="filter-btn active" data-filter="all">Semua</button>
          <button type="button" class="filter-btn" data-filter="available">Tersedia</button>
          <button type="button" class="filter-btn" data-filter="selected">Dipilih</button>
        </div>
      </div>

      <!-- Alat Grid -->
      <div id="alatContainer" class="alat-table-wrapper">
        <table class="alat-table">
          <thead>
            <tr>
              <th class="col-check"></th>
              <th class="col-name">Nama Alat</th>
              <th class="col-stock">Stok</th>
              <th class="col-jenis">Jenis / Tipe</th>
              <th class="col-qty">Qty</th>
              <th class="col-action"></th>
            </tr>
          </thead>
          <tbody id="alatTableBody">
            <!-- Rows will be generated by JS -->
          </tbody>
        </table>
        <div class="empty-state" id="emptyState" style="display:none;">
          <i class="ri-inbox-line"></i>
          <p>Tidak ada alat ditemukan</p>
        </div>
      </div>
    </div>
  </div>

  <!-- SUBMIT BUTTONS -->
  <div class="submit-bar">
    <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
    <button type="submit" class="btn btn-primary" id="submitBtn">
      <i class="ri-save-line"></i>
      <span id="submitBtnLabel">Simpan</span>
    </button>
  </div>
</form>

<style>
  :root {
    --primary: #3b82f6;
    --primary-dark: #2563eb;
    --success: #22c55e;
    --danger: #ef4444;
    --warning: #f59e0b;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-500: #6b7280;
    --gray-700: #374151;
    --gray-900: #111827;
    --radius: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
  }

  /* Section Cards */
  .section-card { margin-bottom: 1.25rem; border-radius: var(--radius); border: 1px solid var(--gray-200); overflow: hidden; box-shadow: var(--shadow); }
  .section-header { display: flex; align-items: center; gap: 0.5rem; padding: 1rem 1.5rem; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-bottom: 1px solid var(--gray-200); font-weight: 700; font-size: 1rem; color: var(--gray-900); }
  .section-header i { font-size: 1.25rem; color: var(--primary); }
  .section-badge { margin-left: auto; background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 999px; }
  .section-body { padding: 1.5rem; background: #fff; }

  /* Form Grid */
  .form-grid { display: grid; gap: 1rem; margin-bottom: 1rem; }
  .form-grid:last-child { margin-bottom: 0; }
  .form-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
  .form-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
  
  .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.03em; }
  .req { color: var(--danger); }
  .form-group input, .form-group select { width: 100%; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid var(--gray-300); font-size: 0.9rem; transition: all 0.2s; box-sizing: border-box; background-color: #fff; }
  .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

  .select2-container { width: 100% !important; }
  .select2-container--default .select2-selection--multiple { border: 1px solid var(--gray-300); border-radius: 8px; min-height: 42px; padding: 4px; }
  .select2-container--default .select2-selection--multiple .select2-selection__choice { background: var(--primary); color: #fff; border: none; border-radius: 6px; padding: 2px 8px; font-size: 0.85rem; }
  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #fff; margin-right: 4px; }
  .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

  /* Toolbar */
  .alat-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-200); }
  .search-box { display: flex; align-items: center; gap: 0.5rem; background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 0.5rem 0.85rem; flex: 1; max-width: 320px; }
  .search-box i { color: var(--gray-500); }
  .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.9rem; }
  
  .filter-group { display: flex; gap: 0.25rem; background: var(--gray-100); border-radius: 8px; padding: 3px; }
  .filter-btn { border: none; background: transparent; padding: 0.4rem 0.9rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: var(--gray-500); cursor: pointer; transition: all 0.2s; }
  .filter-btn.active { background: #fff; color: var(--primary); box-shadow: var(--shadow); }
  .filter-btn:hover:not(.active) { color: var(--gray-700); }

  /* Table */
  .alat-table-wrapper { max-height: 500px; overflow-y: auto; border: 1px solid var(--gray-200); border-radius: 8px; }
  .alat-table { width: 100%; border-collapse: collapse; }
  .alat-table thead { position: sticky; top: 0; z-index: 2; }
  .alat-table thead th { background: var(--gray-100); padding: 0.75rem 0.9rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); letter-spacing: 0.05em; border-bottom: 1px solid var(--gray-200); }
  .alat-table tbody td { padding: 0.6rem 0.9rem; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
  .alat-table tbody tr:hover { background: var(--gray-50); }
  .alat-table tbody tr.selected-row { background: rgba(59,130,246,0.05); }
  .alat-table tbody tr.out-of-stock { opacity: 0.5; }

  .col-check { width: 40px; text-align: center; }
  .col-name { min-width: 140px; }
  .col-stock { width: 90px; text-align: center; }
  .col-jenis { width: 200px; }
  .col-qty { width: 100px; }
  .col-action { width: 50px; text-align: center; }

  .alat-name { font-weight: 600; color: var(--gray-900); }
  .stock-badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; min-width: 55px; }
  .stock-badge.available { background: #dcfce7; color: #15803d; }
  .stock-badge.low { background: #fef3c7; color: #b45309; }
  .stock-badge.out { background: #fee2e2; color: #b91c1c; }

  /* Styling untuk Input & Select di dalam Tabel */
  .alat-table input[type="text"], .alat-table input[type="number"], .alat-table select { 
    width: 100%; padding: 0.45rem 0.6rem; border: 1px solid var(--gray-300); border-radius: 6px; 
    font-size: 0.85rem; box-sizing: border-box; transition: all 0.2s; background-color: #fff; 
  }
  .alat-table input:focus, .alat-table select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }
  .alat-table input:disabled, .alat-table select:disabled { background: var(--gray-100); cursor: not-allowed; color: var(--gray-500); }

  /* Custom Checkbox */
  .custom-cb { appearance: none; width: 20px; height: 20px; border: 2px solid var(--gray-300); border-radius: 5px; cursor: pointer; transition: all 0.2s; position: relative; }
  .custom-cb:checked { background: var(--primary); border-color: var(--primary); }
  .custom-cb:checked::after { content: '✓'; position: absolute; color: #fff; font-size: 14px; font-weight: bold; top: 50%; left: 50%; transform: translate(-50%, -50%); }
  .custom-cb:disabled { cursor: not-allowed; opacity: 0.5; }

  /* Add Button */
  .btn-add-row { background: transparent; border: 1px dashed var(--gray-300); color: var(--gray-500); border-radius: 6px; width: 28px; height: 28px; cursor: pointer; font-size: 1.1rem; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
  .btn-add-row:hover { border-color: var(--primary); color: var(--primary); background: rgba(59,130,246,0.05); }
  .btn-add-row:disabled { opacity: 0.4; cursor: not-allowed; }
  
  .btn-remove-row { background: var(--danger); color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
  .btn-remove-row:hover { background: #dc2626; }

  /* Empty State */
  .empty-state { text-align: center; padding: 3rem 1rem; color: var(--gray-500); }
  .empty-state i { font-size: 3rem; margin-bottom: 0.5rem; display: block; opacity: 0.5; }

  /* Submit Bar */
  .submit-bar { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding: 1rem 1.5rem; background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius); box-shadow: var(--shadow); }

  /* Responsive */
  @media (max-width: 900px) {
    .form-grid.cols-2, .form-grid.cols-3 { grid-template-columns: 1fr; }
    .alat-toolbar { flex-direction: column; align-items: stretch; }
    .search-box { max-width: 100%; }
    
    /* Table becomes card-like on mobile */
    .alat-table thead { display: none; }
    .alat-table, .alat-table tbody, .alat-table tr, .alat-table td { display: block; width: 100%; }
    .alat-table tbody tr { margin-bottom: 0.75rem; padding: 0.75rem; border: 1px solid var(--gray-200); border-radius: 8px; background: #fff; }
    .alat-table tbody td { border: none; padding: 0.3rem 0; display: flex; justify-content: space-between; align-items: center; }
    .alat-table tbody td::before { content: attr(data-label); font-size: 0.75rem; font-weight: 600; color: var(--gray-500); text-transform: uppercase; }
    .col-check, .col-action { justify-content: flex-end !important; }
    .col-check::before, .col-action::before { display: none; }
    .col-name::before { content: 'Alat'; }
    .col-stock::before { content: 'Stok'; }
    .col-jenis::before { content: 'Jenis'; }
    .col-qty::before { content: 'Qty'; }
  }
</style>

<script>
const ALAT_LIST = <?= json_encode($alatList) ?>;
const STOCK_DATA = <?= json_encode($stockData) ?>;
const JENIS_OPTIONS = <?= json_encode($jenisOptions) ?>; // Data opsi keterangan dari inventaris
const EDIT_DATA = <?= json_encode($editData) ?>;
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;

// Mapping Alat Default
const alatPerKegiatan = {
  "Upacara / Apel Pagi": [
    { nama: "Mic", jenis: "Kabel", qty: 3 }, { nama: "Speaker", jenis: "Huper", qty: 1 },
    { nama: "Overlength", jenis: "Putih", qty: 1 }, { nama: "Mixer", jenis: "8 chanel", qty: 1 },
    { nama: "Stand Mic", jenis: "Mic", qty: 3 }, { nama: "Kabel", jenis: "Power", qty: 2 },
    { nama: "Kabel", jenis: "Roll Biru", qty: 1 }, { nama: "Kabel", jenis: "Male Female", qty: 4 }
  ],
  "Acara Live / Dialog Luar / Tapping": [
    { nama: "Mic", jenis: "Kabel", qty: 3 }, { nama: "Kamera", jenis: "Mirrorless", qty: 2 },
    { nama: "Kamera", jenis: "PXW", qty: 1 }, { nama: "Tripod", jenis: "kamera", qty: 3 },
    { nama: "Kabel", jenis: "HDMI", qty: 2 }, { nama: "Kabel", jenis: "SDI", qty: 1 },
    { nama: "Kabel", jenis: "Male Female", qty: 3 }, { nama: "Kabel", jenis: "Roll Biru", qty: 1 },
    { nama: "Video Switcher", jenis: "Avmatrik", qty: 1 }, { nama: "Soundcard", jenis: "Behringer", qty: 1 },
    { nama: "Baterai", jenis: "Dummy Kingma", qty: 1 }, { nama: "Converter", jenis: "HDMI-SDI", qty: 2 },
    { nama: "Mixer", jenis: "Digital Ashley", qty: 1 }, { nama: "Headphone", jenis: "Kabel", qty: 1 },
    { nama: "Laptop", jenis: "TUF", qty: 1 }, { nama: "Modem", jenis: "Orbit", qty: 1 },
    { nama: "Lampu", jenis: "Inbex", qty: 2 }, { nama: "Lampu", jenis: "Godox", qty: 1 },
    { nama: "Overlength", jenis: "MCB", qty: 1 }, { nama: "HT", jenis: "WLN", qty: 6 },
    { nama: "Earpik", jenis: "HT", qty: 6 }
  ],
  "Podcast / Tapping": [
    { nama: "Mic", jenis: "Saramonic Sepaket", qty: 1 }, { nama: "Kamera", jenis: "Mirrorless", qty: 2 },
    { nama: "Kamera", jenis: "PXW", qty: 1 }, { nama: "Tripod", jenis: "kamera", qty: 3 },
    { nama: "Kabel", jenis: "HDMI", qty: 2 }, { nama: "Kabel", jenis: "SDI", qty: 1 },
    { nama: "Overlength", jenis: "putih", qty: 2 }, { nama: "Video Switcher", jenis: "Avmatrik", qty: 1 },
    { nama: "Soundcard", jenis: "Behringer", qty: 1 }, { nama: "Baterai", jenis: "Dummy Kingma", qty: 1 },
    { nama: "Laptop", jenis: "TUF", qty: 1 }, { nama: "Lampu", jenis: "Inbex", qty: 2 },
    { nama: "Lampu", jenis: "Godox", qty: 1 }
  ],
  "Keroncong": [
    { nama: "Kamera", jenis: "PXW", qty: 1 }, { nama: "Tripod", jenis: "kamera", qty: 1 },
    { nama: "Mic", jenis: "Wireless", qty: 1 }, { nama: "Mic", jenis: "Kabel", qty: 1 },
    { nama: "HT", jenis: "WLN", qty: 2 }, { nama: "Kabel", jenis: "Male Female", qty: 6 },
    { nama: "Kabel", jenis: "Male TS", qty: 5 }, { nama: "Overlength", jenis: "putih", qty: 1 },
    { nama: "Headphone", jenis: "Senheizer", qty: 1 }
  ],
  "Senandung Rindu": [
    { nama: "Kamera", jenis: "MD", qty: 1 }, { nama: "Kamera", jenis: "PXW", qty: 1 },
    { nama: "Tripod", jenis: "kamera", qty: 2 }, { nama: "Mic", jenis: "Wireless", qty: 1 },
    { nama: "Mic", jenis: "Kabel", qty: 1 }, { nama: "HT", jenis: "WLN", qty: 2 },
    { nama: "Kabel", jenis: "Male TS", qty: 1 }, { nama: "Overlength", jenis: "putih", qty: 2 },
    { nama: "Kabel", jenis: "Roll Biru", qty: 1 }
  ],
  "Zoom Meeting": [ { nama: "Laptop", jenis: "MSI", qty: 1 } ],
  "Kunjungan": [ { nama: "Laptop", jenis: "MSI", qty: 1 }, { nama: "Baterai", jenis: "Charge", qty: 4 } ]
};

let rowCounter = 0;

// Fungsi untuk membuat HTML Select atau Input Text jika tidak ada opsi
function getJenisFieldHTML(alat, selectedVal = '', isDisabled = true) {
  let options = JENIS_OPTIONS[alat] || [];
  
  // Jika ada selectedVal (dari default/edit) tapi tidak ada di opsi inventaris, tetap ditambahkan
  if (selectedVal && !options.includes(selectedVal)) {
    options.push(selectedVal);
  }
  
  if (options.length > 0) {
    let html = `<select class="alat-jenis" ${isDisabled ? 'disabled' : ''}>`;
    html += `<option value="">Pilih Jenis...</option>`;
    options.forEach(opt => {
      html += `<option value="${opt}" ${opt === selectedVal ? 'selected' : ''}>${opt}</option>`;
    });
    html += `</select>`;
    return html;
  } else {
    // Fallback ke text input jika inventaris tidak punya keterangan untuk alat ini
    return `<input type="text" class="alat-jenis" placeholder="Jenis/Tipe" value="${selectedVal}" ${isDisabled ? 'disabled' : ''}>`;
  }
}

 $(document).ready(function() {
  $('#field_pic').select2({ placeholder: "Pilih PIC PT", allowClear: true, width: '100%', closeOnSelect: false });
  $('#field_kegiatan').select2({ placeholder: "Pilih Kegiatan", allowClear: true, width: '100%' });

  $('#field_kegiatan').on('change', function() {
    if (!IS_EDIT) applyEventDefaults(this.value);
  });

  generateAlatTable();
  initListeners();

  // Edit mode
  if (IS_EDIT) {
    $('#field_kegiatan').val(EDIT_DATA.kegiatan).trigger('change');
    $('#field_pic').val(EDIT_DATA.pic ? EDIT_DATA.pic.split(',').map(p => p.trim()) : []).trigger('change');
    $('#field_lokasi').val(EDIT_DATA.lokasi || '');
    $('#field_tgl_kegiatan').val(EDIT_DATA.tgl_kegiatan || '');
    $('#field_tgl_pinjam').val(EDIT_DATA.tgl_pinjam || '');
    $('#field_durasi').val(EDIT_DATA.durasi ?? '');
    $('#field_keterangan').val(EDIT_DATA.keterangan || '');
    
    $('#submitBtnLabel').text('Simpan Perubahan');

    if (EDIT_DATA.alat_arr && EDIT_DATA.alat_arr.length > 0) {
      EDIT_DATA.alat_arr.forEach(a => {
        const rows = document.querySelectorAll(`tr[data-alat="${a.alat}"]:not(.dup-row)`);
        let filled = false;
        for (const row of rows) {
          const cb = row.querySelector('.custom-cb');
          if (cb && !cb.checked && !cb.disabled) {
            cb.checked = true;
            row.querySelector('.alat-jenis').value = a.jenis; // Ini akan men-set value select
            row.querySelector('.alat-qty').value = a.qty;
            enableInputs(row, true);
            row.classList.add('selected-row');
            filled = true;
            break;
          }
        }
        if (!filled) {
          addDuplicateRow(a.alat, a.jenis, a.qty);
        }
      });
    }
    updateSelectedCount();
  }

  // Search
  $('#searchAlat').on('input', filterAlat);
  
  // Filter buttons
  $('.filter-btn').on('click', function() {
    $('.filter-btn').removeClass('active');
    $(this).addClass('active');
    filterAlat();
  });
});

function generateAlatTable() {
  const tbody = document.getElementById('alatTableBody');
  tbody.innerHTML = '';
  
  ALAT_LIST.forEach(alat => {
    const stock = STOCK_DATA[alat] || 0;
    const isOut = stock === 0 && !IS_EDIT;
    rowCounter++;
    
    const tr = document.createElement('tr');
    tr.dataset.alat = alat;
    tr.dataset.rowId = 'row' + rowCounter;
    if (isOut) tr.classList.add('out-of-stock');
    
    const stockClass = stock === 0 ? 'out' : (stock <= 2 ? 'low' : 'available');
    const stockText = stock === 0 ? 'Habis' : stock;
    
    tr.innerHTML = `
      <td class="col-check" data-label="">
        <input type="checkbox" class="custom-cb alat-checkbox" ${isOut ? 'disabled' : ''} data-stock="${stock}">
      </td>
      <td class="col-name" data-label="Alat">
        <span class="alat-name">${alat}</span>
      </td>
      <td class="col-stock" data-label="Stok">
        <span class="stock-badge ${stockClass}">${stockText}</span>
      </td>
      <td class="col-jenis" data-label="Jenis">
        ${getJenisFieldHTML(alat, '', true)}
      </td>
      <td class="col-qty" data-label="Qty">
        <input type="number" min="1" max="${stock}" class="alat-qty" placeholder="0" disabled>
      </td>
      <td class="col-action" data-label="">
        <button type="button" class="btn-add-row" data-alat="${alat}" title="Tambah baris" ${isOut ? 'disabled' : ''}>+</button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function applyEventDefaults(selectedEvent) {
  document.querySelectorAll('#alatTableBody tr').forEach(row => {
    const cb = row.querySelector('.custom-cb');
    if (cb) {
      cb.checked = false;
      row.classList.remove('selected-row');
      enableInputs(row, false);
      row.querySelector('.alat-jenis').value = '';
      row.querySelector('.alat-qty').value = '';
    }
  });
  document.querySelectorAll('.dup-row').forEach(r => r.remove());

  const defaults = alatPerKegiatan[selectedEvent] || [];
  defaults.forEach(d => {
    const rows = document.querySelectorAll(`tr[data-alat="${d.nama}"]:not(.dup-row)`);
    let filled = false;
    for (const row of rows) {
      const cb = row.querySelector('.custom-cb');
      if (cb && !cb.checked && !cb.disabled) {
        cb.checked = true;
        row.querySelector('.alat-jenis').value = d.jenis;
        row.querySelector('.alat-qty').value = d.qty;
        enableInputs(row, true);
        row.classList.add('selected-row');
        filled = true;
        break;
      }
    }
    if (!filled) {
      addDuplicateRow(d.nama, d.jenis, d.qty);
    }
  });
  updateSelectedCount();
}

function enableInputs(row, enabled) {
  const jenis = row.querySelector('.alat-jenis');
  const qty = row.querySelector('.alat-qty');
  if (jenis) jenis.disabled = !enabled;
  if (qty) qty.disabled = !enabled;
}

function addDuplicateRow(alat, jenisVal, qtyVal) {
  rowCounter++;
  const stock = STOCK_DATA[alat] || 0;
  
  const tr = document.createElement('tr');
  tr.dataset.alat = alat;
  tr.dataset.rowId = 'dup' + rowCounter;
  tr.classList.add('dup-row', 'selected-row');
  
  tr.innerHTML = `
    <td class="col-check" data-label="">
      <input type="checkbox" class="custom-cb alat-checkbox" checked data-stock="${stock}">
    </td>
    <td class="col-name" data-label="Alat">
      <span class="alat-name">${alat} <small style="color:var(--gray-500);">(duplikat)</small></span>
    </td>
    <td class="col-stock" data-label="Stok">
      <span class="stock-badge ${stock === 0 ? 'out' : (stock <= 2 ? 'low' : 'available')}">${stock === 0 ? 'Habis' : stock}</span>
    </td>
    <td class="col-jenis" data-label="Jenis">
      ${getJenisFieldHTML(alat, jenisVal || '', false)}
    </td>
    <td class="col-qty" data-label="Qty">
      <input type="number" min="1" class="alat-qty" placeholder="0" value="${qtyVal || ''}">
    </td>
    <td class="col-action" data-label="">
      <button type="button" class="btn-remove-row" title="Hapus baris">×</button>
    </td>
  `;
  document.getElementById('alatTableBody').appendChild(tr);
  updateSelectedCount();
}

function initListeners() {
  const tbody = document.getElementById('alatTableBody');
  
  tbody.addEventListener('change', e => {
    if (e.target.classList.contains('alat-checkbox')) {
      const row = e.target.closest('tr');
      const checked = e.target.checked;
      enableInputs(row, checked);
      row.classList.toggle('selected-row', checked);
      if (!checked) {
        row.querySelector('.alat-jenis').value = '';
        row.querySelector('.alat-qty').value = '';
      }
      updateSelectedCount();
    }
  });

  tbody.addEventListener('click', e => {
    if (e.target.classList.contains('btn-add-row')) {
      addDuplicateRow(e.target.dataset.alat, '', '');
    } else if (e.target.classList.contains('btn-remove-row')) {
      e.target.closest('tr').remove();
      updateSelectedCount();
    }
  });
}

function filterAlat() {
  const query = $('#searchAlat').val().toLowerCase();
  const filter = $('.filter-btn.active').data('filter');
  let visibleCount = 0;
  
  document.querySelectorAll('#alatTableBody tr').forEach(row => {
    const alatName = row.dataset.alat.toLowerCase();
    const cb = row.querySelector('.custom-cb');
    const isChecked = cb && cb.checked;
    const isOut = row.classList.contains('out-of-stock');
    
    let show = true;
    if (query && !alatName.includes(query)) show = false;
    if (filter === 'available' && (isOut || isChecked)) show = false;
    if (filter === 'selected' && !isChecked) show = false;
    
    row.style.display = show ? '' : 'none';
    if (show) visibleCount++;
  });
  
  document.getElementById('emptyState').style.display = visibleCount === 0 ? '' : 'none';
}

function updateSelectedCount() {
  const count = document.querySelectorAll('.alat-checkbox:checked').length;
  document.getElementById('selectedCount').textContent = count + ' dipilih';
}

function collectAlatRows() {
  let rows = [];
  document.querySelectorAll('#alatTableBody tr').forEach(row => {
    const cb = row.querySelector('.custom-cb');
    if (cb && cb.checked) {
      rows.push({
        alat: row.dataset.alat,
        jenis: row.querySelector('.alat-jenis').value.trim(),
        qty: row.querySelector('.alat-qty').value
      });
    }
  });
  return rows;
}

async function handleSubmit(event) {
  event.preventDefault();
  const alatRows = collectAlatRows();
  if (alatRows.length === 0) {
    alert('⚠️ Pilih minimal satu alat!');
    return;
  }

  const submitBtn = document.getElementById('submitBtn');
  const label = document.getElementById('submitBtnLabel');
  submitBtn.disabled = true;
  label.textContent = 'Menyimpan...';

  const pageId = document.getElementById('field_page_id').value;
  const isEdit = pageId !== '';
  const picArr = $('#field_pic').val();
  const picString = picArr ? picArr.join(', ') : '';

  const payload = {
    kegiatan: document.getElementById('field_kegiatan').value.trim(),
    pic: picString,
    lokasi: document.getElementById('field_lokasi').value.trim(),
    tgl_kegiatan: document.getElementById('field_tgl_kegiatan').value,
    tgl_pinjam: document.getElementById('field_tgl_pinjam').value,
    durasi: document.getElementById('field_durasi').value,
    keterangan: document.getElementById('field_keterangan').value.trim(),
    alat: alatRows
  };
  if (isEdit) payload.page_id = pageId;

  const endpoint = isEdit ? '../api/update-peminjaman.php' : '../api/create-peminjaman.php';

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert(data.message);
      window.location.href = 'peminjaman.php';
    } else {
      alert('❌ Gagal: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    alert('❌ Gagal menghubungi server: ' + err.message);
  } finally {
    submitBtn.disabled = false;
    label.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan';
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>