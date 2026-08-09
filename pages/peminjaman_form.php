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

$picList = ['Alfian','Alif','Saiful','Yhanuar','Yayang','Yudha','Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Dayat','Winsa','Selvy'];

// Fetch all available inventory items from Notion
$inventoryCatalog = [];

if (defined('DATABASE_ID_INVENTARIS')) {
    $has_more = true;
    $start_cursor = null;
    
    while ($has_more) {
        $queryBody = [
            "filter" => ["property" => "Status", "select" => ["equals" => "Tersedia"]],
            "sorts"  => [["property" => "Nama Alat", "direction" => "ascending"]]
        ];
        if ($start_cursor) {
            $queryBody["start_cursor"] = $start_cursor;
        }
        
        $dbData = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', $queryBody);
        
        if (isset($dbData['results'])) {
            foreach ($dbData['results'] as $page) {
                $props = $page['properties'];
                $invName = !empty($props['Nama Alat']['title'][0]['plain_text']) ? trim($props['Nama Alat']['title'][0]['plain_text']) : '';
                if (empty($invName)) continue;
                
                $invKategori = !empty($props['Kategori']['select']['name']) ? trim($props['Kategori']['select']['name']) : 'Lainnya';
                
                $invType = '';
                if (!empty($props['Type']['rich_text'][0]['plain_text'])) {
                    $invType = trim($props['Type']['rich_text'][0]['plain_text']);
                } elseif (!empty($props['Type']['select']['name'])) {
                    $invType = trim($props['Type']['select']['name']);
                } elseif (!empty($props['Keterangan']['rich_text'][0]['plain_text'])) {
                    $invType = trim($props['Keterangan']['rich_text'][0]['plain_text']);
                }
                if (empty($invType)) $invType = 'Standar';
                
                $invQty = isset($props['Qty']['number']) ? (int)$props['Qty']['number'] : 1;
                if ($invQty <= 0) $invQty = 1;
                
                $inventoryCatalog[] = [
                    'id'       => $page['id'],
                    'nama'     => $invName,
                    'kategori' => $invKategori,
                    'tipe'     => $invType,
                    'qty'      => $invQty
                ];
            }
            $has_more = $dbData['has_more'] ?? false;
            $start_cursor = $dbData['next_cursor'] ?? null;
        } else {
            $has_more = false;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Dependencies Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="page-head">
  <div>
    <h2><?= $isEdit ? 'Edit Peminjaman Alat' : 'Catat Peminjaman Alat' ?></h2>
    <p>Pilih kategori, nama perangkat, dan tipenya dengan presisi.</p>
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
      <!-- Baris 1: Kegiatan, PIC PT, Lokasi -->
      <div class="form-grid cols-3">
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
        <div class="form-group">
          <label>Lokasi</label>
          <input type="text" id="field_lokasi" placeholder="Contoh: Auditorium">
        </div>
      </div>

      <!-- Baris 2: Tgl Kegiatan, Tgl Pinjam, Durasi -->
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

      <!-- Baris 3: Keterangan -->
      <div class="form-group" style="margin-bottom:0;">
        <label>Keterangan / Catatan Tambahan</label>
        <textarea id="field_keterangan" rows="2" placeholder="Catatan tambahan (bebas isi banyak karakter)..." style="width:100%; resize:vertical;"></textarea>
      </div>
    </div>
  </div>

  <!-- SECTION 2: PEMILIHAN ALAT BERHIRARKI -->
  <div class="card section-card">
    <div class="section-header">
      <i class="ri-tools-line"></i>
      <span>Alat yang Dipinjam (Kategori &rarr; Perangkat &rarr; Tipe)</span>
      <span class="section-badge" id="selectedCount">0 item</span>
    </div>
    <div class="section-body">

      <div class="alat-table-wrapper">
        <table class="alat-table" id="alatTable">
          <thead>
            <tr>
              <th style="width: 22%;">Kategori</th>
              <th style="width: 32%;">Nama Perangkat</th>
              <th style="width: 26%;">Tipe / Model</th>
              <th style="width: 10%; text-align: center;">Stok</th>
              <th style="width: 10%;">Qty</th>
              <th style="width: 45px; text-align: center;"></th>
            </tr>
          </thead>
          <tbody id="alatTableBody">
            <!-- Rows generated by JS -->
          </tbody>
        </table>
      </div>

      <div class="table-actions" style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" class="btn btn-outline-primary" onclick="addAlatRow()">
          <i class="ri-add-line"></i> Tambah Perangkat
        </button>
        <small style="color: var(--text-secondary, #6b7280);">
          <i class="ri-information-line"></i> Pilih Kategori &rarr; Perangkat &rarr; Tipe/Model untuk melihat sisa stok.
        </small>
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
  }

  /* Section Cards */
  .section-card { margin-bottom: 1.25rem; border-radius: var(--radius); border: 1px solid var(--border-color, #e5e7eb); overflow: hidden; box-shadow: var(--shadow); background: var(--card-bg, #fff); }
  .section-header { display: flex; align-items: center; gap: 0.5rem; padding: 1rem 1.5rem; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-bottom: 1px solid var(--border-color, #e5e7eb); font-weight: 700; font-size: 1rem; color: var(--text-primary, #111827); }
  .section-header i { font-size: 1.25rem; color: var(--primary); }
  .section-badge { margin-left: auto; background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 999px; }
  .section-body { padding: 1.5rem; background: var(--card-bg, #fff); }

  /* Form Grid */
  .form-grid { display: grid; gap: 1rem; margin-bottom: 1rem; }
  .form-grid:last-child { margin-bottom: 0; }
  .form-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
  
  .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-primary, #374151); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.03em; }
  .req { color: var(--danger); }
  .form-group input, .form-group select, .form-group textarea { 
    width: 100%; height: 42px; min-height: 42px; padding: 0 0.9rem; border-radius: 8px; border: 1px solid var(--border-color, #d1d5db); 
    font-size: 0.9rem; transition: all 0.2s; box-sizing: border-box; background-color: var(--input-bg, #fff); color: var(--text-primary, #0f172a); 
  }
  .form-group textarea { height: auto; min-height: 60px; padding: 0.6rem 0.9rem; }
  .form-group select {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 2.2rem;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.85rem center;
    background-size: 1rem;
    cursor: pointer;
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

  /* Select2 Styling */
  .select2-container { width: 100% !important; }
  .select2-container--default .select2-selection--multiple { border: 1px solid var(--border-color, #d1d5db); border-radius: 8px; min-height: 42px; padding: 4px; background-color: var(--input-bg, #fff); }
  .select2-container--default .select2-selection--multiple .select2-selection__choice { background: var(--primary); color: #fff; border: none; border-radius: 6px; padding: 2px 8px; font-size: 0.85rem; }
  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #fff; margin-right: 4px; }
  .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

  /* Dynamic Table */
  .alat-table-wrapper { border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; overflow-x: auto; background: var(--card-bg, #fff); }
  .alat-table { width: 100%; border-collapse: collapse; min-width: 650px; }
  .alat-table thead th { background: var(--gray-100); padding: 0.75rem 0.9rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary, #6b7280); border-bottom: 1px solid var(--border-color, #e5e7eb); }
  .alat-table tbody td { padding: 0.6rem 0.75rem; border-bottom: 1px solid var(--border-color, #f3f4f6); vertical-align: middle; }
  
  .alat-table select, .alat-table input[type="number"], .alat-table input[type="text"] {
    width: 100%; padding: 0.45rem 0.6rem; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px;
    font-size: 0.85rem; box-sizing: border-box; transition: all 0.2s; background-color: var(--input-bg, #fff); color: var(--text-primary, #0f172a);
  }
  .alat-table select:focus, .alat-table input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }
  .alat-table select:disabled, .alat-table input:disabled { background: var(--gray-100); color: var(--text-secondary, #9ca3af); cursor: not-allowed; }

  .stock-badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; min-width: 50px; }
  .stock-badge.available { background: #dcfce7; color: #15803d; }
  .stock-badge.low { background: #fef3c7; color: #b45309; }
  .stock-badge.out { background: #fee2e2; color: #b91c1c; }

  .btn-remove-row { background: var(--danger); color: #fff; border: none; border-radius: 50%; width: 26px; height: 26px; cursor: pointer; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
  .btn-remove-row:hover { background: #dc2626; transform: scale(1.05); }

  .btn-outline-primary { border: 1px dashed var(--primary); color: var(--primary); background: transparent; font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; font-size: 0.85rem; }
  .btn-outline-primary:hover { background: rgba(59,130,246,0.08); }

  /* Submit Bar */
  .submit-bar { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding: 1rem 1.5rem; background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: var(--radius); box-shadow: var(--shadow); }

  /* Dark Mode Overrides */
  body.dark-mode .section-card { background: #161f30 !important; border-color: #2b3952 !important; }
  body.dark-mode .section-header { background: #1a263d !important; border-bottom: 1px solid #2b3952 !important; color: #f8fafc !important; }
  body.dark-mode .section-body { background: #161f30 !important; }

  body.dark-mode .form-group label { color: #f8fafc !important; }
  body.dark-mode .form-group input, body.dark-mode .form-group select, body.dark-mode .form-group textarea,
  body.dark-mode .alat-table select, body.dark-mode .alat-table input[type="number"], body.dark-mode .alat-table input[type="text"] {
    background-color: #0f172a !important; color: #f8fafc !important; border-color: #2b3952 !important; color-scheme: dark !important;
  }
  body.dark-mode .alat-table select:disabled, body.dark-mode .alat-table input:disabled { background-color: #1e293b !important; color: #64748b !important; }
  body.dark-mode .alat-table-wrapper { border-color: #2b3952 !important; background: #161f30 !important; }
  body.dark-mode .alat-table thead th { background: #1a263d !important; color: #94a3b8 !important; border-bottom: 1px solid #2b3952 !important; }
  body.dark-mode .alat-table tbody td { border-bottom: 1px solid #2b3952 !important; }
  body.dark-mode .submit-bar { background: #161f30 !important; border-color: #2b3952 !important; }

  @media (max-width: 900px) {
    .form-grid.cols-3 { grid-template-columns: 1fr; }
  }
</style>

<script>
const INVENTORY_CATALOG = <?= json_encode($inventoryCatalog) ?>;
const EDIT_DATA = <?= json_encode($editData) ?>;
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;

// Master Kategori Standar
const MASTER_CATEGORIES = ['Audio', 'Kamera', 'Komputer', 'Kabel', 'Tripod', 'Lighting', 'Video', 'Komunikasi', 'Aksesoris', 'Power', 'Jaringan', 'Lainnya'];

// Membangun HIERARCHY: HIERARCHY[kategori][namaPerangkat][tipe] = { stock }
const HIERARCHY = {};
INVENTORY_CATALOG.forEach(item => {
  const cat = item.kategori || 'Lainnya';
  const name = item.nama;
  const tipe = item.tipe || 'Standar';

  if (!HIERARCHY[cat]) HIERARCHY[cat] = {};
  if (!HIERARCHY[cat][name]) HIERARCHY[cat][name] = {};
  if (!HIERARCHY[cat][name][tipe]) HIERARCHY[cat][name][tipe] = { stock: 0 };
  
  HIERARCHY[cat][name][tipe].stock += item.qty;
});

// Mapping Preset Per Kegiatan (Kategori -> Nama Perangkat -> Tipe -> Qty)
const alatPerKegiatan = {
  "Upacara / Apel Pagi": [
    { kategori: "Audio", nama: "Mic", tipe: "Kabel", qty: 3 },
    { kategori: "Audio", nama: "Speaker", tipe: "Huper", qty: 1 },
    { kategori: "Kabel", nama: "Overlength", tipe: "Putih", qty: 1 },
    { kategori: "Audio", nama: "Mixer", tipe: "8 chanel", qty: 1 },
    { kategori: "Aksesoris", nama: "Stand Mic", tipe: "Mic", qty: 3 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Power", qty: 2 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Roll Biru", qty: 1 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Male Female", qty: 4 }
  ],
  "Acara Live / Dialog Luar / Tapping": [
    { kategori: "Audio", nama: "Mic", tipe: "Kabel", qty: 3 },
    { kategori: "Kamera", nama: "Kamera", tipe: "Mirrorless", qty: 2 },
    { kategori: "Kamera", nama: "Kamera", tipe: "PXW", qty: 1 },
    { kategori: "Tripod", nama: "Tripod", tipe: "kamera", qty: 3 },
    { kategori: "Kabel", nama: "Kabel", tipe: "HDMI", qty: 2 },
    { kategori: "Kabel", nama: "Kabel", tipe: "SDI", qty: 1 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Male Female", qty: 3 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Roll Biru", qty: 1 },
    { kategori: "Video", nama: "Video Switcher", tipe: "Avmatrik", qty: 1 },
    { kategori: "Audio", nama: "Soundcard", tipe: "Behringer", qty: 1 },
    { kategori: "Power", nama: "Baterai", tipe: "Dummy Kingma", qty: 1 },
    { kategori: "Video", nama: "Converter", tipe: "HDMI-SDI", qty: 2 },
    { kategori: "Audio", nama: "Mixer", tipe: "Digital Ashley", qty: 1 },
    { kategori: "Audio", nama: "Headphone", tipe: "Kabel", qty: 1 },
    { kategori: "Komputer", nama: "Laptop", tipe: "TUF", qty: 1 },
    { kategori: "Jaringan", nama: "Modem", tipe: "Orbit", qty: 1 },
    { kategori: "Lighting", nama: "Lampu", tipe: "Inbex", qty: 2 },
    { kategori: "Lighting", nama: "Lampu", tipe: "Godox", qty: 1 },
    { kategori: "Kabel", nama: "Overlength", tipe: "MCB", qty: 1 },
    { kategori: "Komunikasi", nama: "HT", tipe: "WLN", qty: 6 },
    { kategori: "Komunikasi", nama: "Earpik", tipe: "HT", qty: 6 }
  ],
  "Podcast / Tapping": [
    { kategori: "Audio", nama: "Mic", tipe: "Saramonic Sepaket", qty: 1 },
    { kategori: "Kamera", nama: "Kamera", tipe: "Mirrorless", qty: 2 },
    { kategori: "Kamera", nama: "Kamera", tipe: "PXW", qty: 1 },
    { kategori: "Tripod", nama: "Tripod", tipe: "kamera", qty: 3 },
    { kategori: "Kabel", nama: "Kabel", tipe: "HDMI", qty: 2 },
    { kategori: "Kabel", nama: "Kabel", tipe: "SDI", qty: 1 },
    { kategori: "Kabel", nama: "Overlength", tipe: "putih", qty: 2 },
    { kategori: "Video", nama: "Video Switcher", tipe: "Avmatrik", qty: 1 },
    { kategori: "Audio", nama: "Soundcard", tipe: "Behringer", qty: 1 },
    { kategori: "Power", nama: "Baterai", tipe: "Dummy Kingma", qty: 1 },
    { kategori: "Komputer", nama: "Laptop", tipe: "TUF", qty: 1 },
    { kategori: "Lighting", nama: "Lampu", tipe: "Inbex", qty: 2 },
    { kategori: "Lighting", nama: "Lampu", tipe: "Godox", qty: 1 }
  ],
  "Keroncong": [
    { kategori: "Kamera", nama: "Kamera", tipe: "PXW", qty: 1 },
    { kategori: "Tripod", nama: "Tripod", tipe: "kamera", qty: 1 },
    { kategori: "Audio", nama: "Mic", tipe: "Wireless", qty: 1 },
    { kategori: "Audio", nama: "Mic", tipe: "Kabel", qty: 1 },
    { kategori: "Komunikasi", nama: "HT", tipe: "WLN", qty: 2 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Male Female", qty: 6 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Male TS", qty: 5 },
    { kategori: "Kabel", nama: "Overlength", tipe: "putih", qty: 1 },
    { kategori: "Audio", nama: "Headphone", tipe: "Senheizer", qty: 1 }
  ],
  "Senandung Rindu": [
    { kategori: "Kamera", nama: "Kamera", tipe: "MD", qty: 1 },
    { kategori: "Kamera", nama: "Kamera", tipe: "PXW", qty: 1 },
    { kategori: "Tripod", nama: "Tripod", tipe: "kamera", qty: 2 },
    { kategori: "Audio", nama: "Mic", tipe: "Wireless", qty: 1 },
    { kategori: "Audio", nama: "Mic", tipe: "Kabel", qty: 1 },
    { kategori: "Komunikasi", nama: "HT", tipe: "WLN", qty: 2 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Male TS", qty: 1 },
    { kategori: "Kabel", nama: "Overlength", tipe: "putih", qty: 2 },
    { kategori: "Kabel", nama: "Kabel", tipe: "Roll Biru", qty: 1 }
  ],
  "Zoom Meeting": [
    { kategori: "Komputer", nama: "Laptop", tipe: "MSI", qty: 1 }
  ],
  "Kunjungan": [
    { kategori: "Komputer", nama: "Laptop", tipe: "MSI", qty: 1 },
    { kategori: "Power", nama: "Baterai", tipe: "Charge", qty: 4 }
  ]
};

let rowCounter = 0;

$(document).ready(function() {
  $('#field_pic').select2({ placeholder: "Pilih PIC PT", allowClear: true, width: '100%', closeOnSelect: false });
  $('#field_kegiatan').select2({ placeholder: "Pilih Kegiatan", allowClear: true, width: '100%' });

  $('#field_kegiatan').on('change', function() {
    if (!IS_EDIT && this.value) applyEventDefaults(this.value);
  });

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
      document.getElementById('alatTableBody').innerHTML = '';
      EDIT_DATA.alat_arr.forEach(item => {
        const detectedKat = detectCategory(item.alat);
        addAlatRow(detectedKat, item.alat, item.jenis, item.qty);
      });
    } else {
      addAlatRow();
    }
  } else {
    // Tambah 1 baris kosong default jika baru
    addAlatRow();
  }

  updateSelectedCount();
});

// Deteksi kategori otomatis dari nama alat
function detectCategory(namaAlat) {
  if (!namaAlat) return 'Audio';
  const nameLower = namaAlat.toLowerCase();
  for (const cat in HIERARCHY) {
    for (const devName in HIERARCHY[cat]) {
      if (devName.toLowerCase().includes(nameLower) || nameLower.includes(devName.toLowerCase())) {
        return cat;
      }
    }
  }
  return 'Audio';
}

function getAvailableCategories() {
  const cats = new Set(MASTER_CATEGORIES);
  Object.keys(HIERARCHY).forEach(c => cats.add(c));
  return Array.from(cats);
}

function getPerangkatOptions(kategori) {
  if (!kategori || !HIERARCHY[kategori]) {
    // Jika tidak ketemu di HIERARCHY, kumpulkan perangkat global
    const globalNames = new Set();
    Object.keys(HIERARCHY).forEach(c => {
      Object.keys(HIERARCHY[c]).forEach(n => globalNames.add(n));
    });
    return Array.from(globalNames);
  }
  return Object.keys(HIERARCHY[kategori]);
}

function getTipeOptions(kategori, perangkat) {
  if (!kategori || !perangkat || !HIERARCHY[kategori] || !HIERARCHY[kategori][perangkat]) {
    // Fallback pencarian global jika kategori berbeda
    for (const c in HIERARCHY) {
      if (HIERARCHY[c][perangkat]) {
        return HIERARCHY[c][perangkat];
      }
    }
    return {};
  }
  return HIERARCHY[kategori][perangkat];
}

function addAlatRow(selKat = '', selNama = '', selTipe = '', selQty = 1) {
  rowCounter++;
  const rowId = 'row_' + rowCounter;
  const tbody = document.getElementById('alatTableBody');

  const categories = getAvailableCategories();
  let katOptionsHtml = `<option value="">-- Pilih Kategori --</option>`;
  categories.forEach(c => {
    katOptionsHtml += `<option value="${c}" ${c === selKat ? 'selected' : ''}>${c}</option>`;
  });

  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.innerHTML = `
    <td>
      <select class="item-kat" onchange="onKategoriChange('${rowId}')">
        ${katOptionsHtml}
      </select>
    </td>
    <td>
      <select class="item-nama" onchange="onPerangkatChange('${rowId}')" disabled>
        <option value="">-- Pilih Perangkat --</option>
      </select>
    </td>
    <td>
      <select class="item-tipe" onchange="onTipeChange('${rowId}')" disabled>
        <option value="">-- Pilih Tipe --</option>
      </select>
    </td>
    <td style="text-align: center;">
      <span class="stock-badge out">-</span>
    </td>
    <td>
      <input type="number" min="1" value="${selQty}" class="item-qty" placeholder="1" disabled>
    </td>
    <td style="text-align: center;">
      <button type="button" class="btn-remove-row" onclick="removeAlatRow('${rowId}')" title="Hapus baris">&times;</button>
    </td>
  `;
  tbody.appendChild(tr);

  if (selKat) {
    onKategoriChange(rowId, selNama, selTipe);
  }

  updateSelectedCount();
}

function onKategoriChange(rowId, targetNama = '', targetTipe = '') {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const kat = tr.querySelector('.item-kat').value;
  const namaSelect = tr.querySelector('.item-nama');
  const tipeSelect = tr.querySelector('.item-tipe');
  const qtyInput = tr.querySelector('.item-qty');

  namaSelect.innerHTML = `<option value="">-- Pilih Perangkat --</option>`;
  tipeSelect.innerHTML = `<option value="">-- Pilih Tipe --</option>`;
  tipeSelect.disabled = true;
  qtyInput.disabled = true;

  if (!kat) {
    namaSelect.disabled = true;
    updateStockDisplay(rowId, 0);
    return;
  }

  const perangkatList = getPerangkatOptions(kat);
  let html = `<option value="">-- Pilih Perangkat --</option>`;
  
  if (targetNama && !perangkatList.includes(targetNama)) {
    perangkatList.push(targetNama);
  }

  perangkatList.forEach(p => {
    html += `<option value="${p}" ${p === targetNama ? 'selected' : ''}>${p}</option>`;
  });
  
  namaSelect.innerHTML = html;
  namaSelect.disabled = false;

  if (targetNama) {
    onPerangkatChange(rowId, targetTipe);
  } else {
    updateStockDisplay(rowId, 0);
  }
}

function onPerangkatChange(rowId, targetTipe = '') {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const kat = tr.querySelector('.item-kat').value;
  const nama = tr.querySelector('.item-nama').value;
  const tipeSelect = tr.querySelector('.item-tipe');
  const qtyInput = tr.querySelector('.item-qty');

  tipeSelect.innerHTML = `<option value="">-- Pilih Tipe --</option>`;

  if (!nama) {
    tipeSelect.disabled = true;
    qtyInput.disabled = true;
    updateStockDisplay(rowId, 0);
    return;
  }

  const tipeObj = getTipeOptions(kat, nama);
  const tipeKeys = Object.keys(tipeObj);

  let html = `<option value="">-- Pilih Tipe --</option>`;
  if (targetTipe && !tipeKeys.includes(targetTipe)) {
    tipeKeys.push(targetTipe);
  }

  tipeKeys.forEach(t => {
    const stock = tipeObj[t] ? tipeObj[t].stock : (IS_EDIT ? 99 : 0);
    const stockLabel = stock > 0 ? `(Stok: ${stock})` : `(Stok Habis)`;
    html += `<option value="${t}" ${t === targetTipe ? 'selected' : ''}>${t} ${stockLabel}</option>`;
  });

  tipeSelect.innerHTML = html;
  tipeSelect.disabled = false;

  if (targetTipe) {
    onTipeChange(rowId);
  } else if (tipeKeys.length === 1) {
    tipeSelect.value = tipeKeys[0];
    onTipeChange(rowId);
  } else {
    updateStockDisplay(rowId, 0);
  }
}

function onTipeChange(rowId) {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const kat = tr.querySelector('.item-kat').value;
  const nama = tr.querySelector('.item-nama').value;
  const tipe = tr.querySelector('.item-tipe').value;
  const qtyInput = tr.querySelector('.item-qty');

  if (!tipe) {
    qtyInput.disabled = true;
    updateStockDisplay(rowId, 0);
    return;
  }

  const tipeObj = getTipeOptions(kat, nama);
  const stock = (tipeObj[tipe] ? tipeObj[tipe].stock : 0) || (IS_EDIT ? 99 : 0);

  updateStockDisplay(rowId, stock);
  qtyInput.disabled = false;
  if (stock > 0) {
    qtyInput.max = stock;
    if (parseInt(qtyInput.value, 10) > stock && !IS_EDIT) {
      qtyInput.value = stock;
    }
  }
}

function updateStockDisplay(rowId, stock) {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const badge = tr.querySelector('.stock-badge');
  if (stock === 0) {
    badge.className = 'stock-badge out';
    badge.textContent = IS_EDIT ? 'Edit' : 'Habis';
  } else if (stock <= 2) {
    badge.className = 'stock-badge low';
    badge.textContent = stock;
  } else {
    badge.className = 'stock-badge available';
    badge.textContent = stock;
  }
}

function removeAlatRow(rowId) {
  const tr = document.getElementById(rowId);
  if (tr) {
    tr.remove();
    updateSelectedCount();
  }
}

function applyEventDefaults(selectedEvent) {
  const tbody = document.getElementById('alatTableBody');
  tbody.innerHTML = '';

  const defaults = alatPerKegiatan[selectedEvent] || [];
  if (defaults.length === 0) {
    addAlatRow();
    return;
  }

  defaults.forEach(d => {
    addAlatRow(d.kategori, d.nama, d.tipe, d.qty);
  });
  updateSelectedCount();
}

function updateSelectedCount() {
  const count = document.querySelectorAll('#alatTableBody tr').length;
  document.getElementById('selectedCount').textContent = count + ' item';
}

function collectAlatRows() {
  const rows = [];
  document.querySelectorAll('#alatTableBody tr').forEach(tr => {
    const kat = tr.querySelector('.item-kat').value;
    const nama = tr.querySelector('.item-nama').value;
    const tipe = tr.querySelector('.item-tipe').value;
    const qty = tr.querySelector('.item-qty').value;

    if (nama) {
      rows.push({
        kategori: kat,
        alat: nama,
        tipe: tipe,
        jenis: tipe,
        qty: qty || 1
      });
    }
  });
  return rows;
}

async function handleSubmit(event) {
  event.preventDefault();
  const alatRows = collectAlatRows();
  
  if (alatRows.length === 0) {
    showToast('⚠️ Pilih minimal satu alat dengan memilih Nama Perangkat!', 'error');
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
      showToast('✅ ' + (data.message || 'Berhasil disimpan!'), 'success');
      setTimeout(() => {
        window.location.href = 'peminjaman.php';
      }, 1200);
    } else {
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
      submitBtn.disabled = false;
      label.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan';
    }
  } catch (err) {
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
    submitBtn.disabled = false;
    label.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan';
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>