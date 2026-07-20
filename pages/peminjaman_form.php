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

// Ambil stok
 $stockData = [];
if (defined('DATABASE_ID_INVENTARIS')) {
    $invQueryBody = ["page_size" => 100, "filter" => ["property" => "Status", "select" => ["equals" => "Tersedia"]]];
    $invDbData = notionCurl('POST', 'https://api.notion.com/v1/databases/' . DATABASE_ID_INVENTARIS . '/query', $invQueryBody);
    if (isset($invDbData['results'])) {
        foreach ($invDbData['results'] as $invPage) {
            $invName = !empty($invPage['properties']['Nama Alat']['title'][0]['plain_text']) ? $invPage['properties']['Nama Alat']['title'][0]['plain_text'] : '';
            foreach ($alatList as $alat) {
                if (stripos($invName, $alat) !== false) {
                    if (!isset($stockData[$alat])) $stockData[$alat] = 0;
                    $stockData[$alat]++;
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

<div class="card">
  <div class="card-body" style="padding: 2rem;">
    <form id="pinjamForm" onsubmit="handleSubmit(event)">
      <input type="hidden" id="field_page_id" value="<?= htmlspecialchars($editData['page_id']) ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Nama Kegiatan</label>
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
          <label>Nama PIC PT</label>
          <select id="field_pic" multiple required>
            <?php foreach ($picList as $nama): ?>
              <option value="<?= $nama ?>"><?= $nama ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tanggal Kegiatan</label>
          <input type="date" id="field_tgl_kegiatan">
        </div>
        <div class="form-group">
          <label>Tanggal Pinjam</label>
          <input type="date" id="field_tgl_pinjam" required>
        </div>
        <div class="form-group">
          <label>Lokasi</label>
          <input type="text" id="field_lokasi" placeholder="Contoh: Auditorium">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Durasi (jam)</label>
          <input type="number" id="field_durasi" min="0" step="0.5" placeholder="Contoh: 3">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <input type="text" id="field_keterangan" placeholder="Catatan tambahan">
        </div>
      </div>

      <div class="form-group">
        <label>Alat yang Dipinjam <span style="color:#ef4444;">*</span></label>
        <div id="alatContainer" class="alat-grid"></div>
      </div>

      <div style="margin-top:1.5rem; display:flex; justify-content:flex-end; gap:0.5rem;">
        <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" id="submitBtn"><span id="submitBtnLabel">Simpan</span></button>
      </div>
    </form>
  </div>
</div>

<style>
  .alat-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:0.75rem; max-height:400px; overflow-y:auto; padding:0.5rem; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; }
  .alat-checkbox-item { background:#fff; padding:0.75rem; border-radius:8px; border:1px solid #e5e7eb; }
  .alat-cb-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem; }
  .alat-cb-top label { margin-left:0.5rem; font-weight:600; flex-grow:1; cursor:pointer; }
  .btn-add-alat { background:#3b82f6; color:#fff; border:none; border-radius:4px; width:24px; height:24px; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; }
  .btn-remove-alat { background:#ef4444; color:#fff; border:none; border-radius:4px; width:24px; height:24px; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; margin-left:auto; }
  .alat-cb-inputs { display:flex; flex-direction:column; gap:0.4rem; }
  .alat-cb-inputs input { padding:0.4rem 0.6rem; font-size:0.85rem; border:1px solid #d1d5db; border-radius:6px; width:100%; box-sizing:border-box; }
  .alat-cb-inputs input:disabled { background:#f3f4f6; cursor:not-allowed; }
  .select2-container { width: 100% !important; margin-bottom: 0.5rem; }
  .form-row { display:grid; grid-template-columns: 1fr 1fr; gap:1rem; }
  .form-group { margin-bottom: 1rem; }
  .form-group label { display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:0.5rem; }
  .form-group input, .form-group select { width: 100%; padding:0.75rem 1rem; border-radius:8px; border:1px solid #d1d5db; }
  @media (max-width:768px) { .form-row { grid-template-columns: 1fr; } .alat-grid { grid-template-columns: 1fr; } }
</style>

<script>
const ALAT_LIST = <?= json_encode($alatList) ?>;
const STOCK_DATA = <?= json_encode($stockData) ?>;
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

 $(document).ready(function() {
  $('#field_pic').select2({ placeholder: "Pilih PIC PT", allowClear: true, width: '100%', closeOnSelect: false });
  $('#field_kegiatan').select2({ placeholder: "Pilih Kegiatan", allowClear: true, width: '100%' });

  $('#field_kegiatan').on('change', function() {
    if (!IS_EDIT) updateAlatCheckboxes(this.value);
  });

  generateAlatCheckboxes();
  initCheckboxListeners();

  // Jika mode Edit, isi data langsung
  if (IS_EDIT) {
    $('#field_kegiatan').val(EDIT_DATA.kegiatan).trigger('change');
    $('#field_pic').val(EDIT_DATA.pic ? EDIT_DATA.pic.split(',').map(p => p.trim()) : []).trigger('change');
    document.getElementById('field_lokasi').value = EDIT_DATA.lokasi || '';
    document.getElementById('field_tgl_kegiatan').value = EDIT_DATA.tgl_kegiatan || '';
    document.getElementById('field_tgl_pinjam').value = EDIT_DATA.tgl_pinjam || '';
    document.getElementById('field_durasi').value = EDIT_DATA.durasi ?? '';
    document.getElementById('field_keterangan').value = EDIT_DATA.keterangan || '';
    
    document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';

    // Isi checkbox alat
    updateAlatCheckboxes(''); // Reset dulu
    if (EDIT_DATA.alat_arr && EDIT_DATA.alat_arr.length > 0) {
      EDIT_DATA.alat_arr.forEach(a => {
        let cb = [...document.querySelectorAll('.alat-checkbox')].find(x => x.value === a.alat && !x.checked);
        if (cb) {
          cb.checked = true;
          document.getElementById(cb.id + '_jenis').disabled = false;
          document.getElementById(cb.id + '_jenis').value = a.jenis;
          document.getElementById(cb.id + '_qty').disabled = false;
          document.getElementById(cb.id + '_qty').value = a.qty;
        } else {
          addDuplicateRow(a.alat, a.jenis, a.qty);
        }
      });
    }
  }
});

function generateAlatCheckboxes() {
  const container = document.getElementById('alatContainer');
  container.innerHTML = '';
  ALAT_LIST.forEach((alat, index) => {
    const id = 'alat' + index;
    const stock = STOCK_DATA[alat] || 0;
    const isOutOfStock = stock === 0 && !IS_EDIT; // Saat edit, biarkan dicentang walau stok 0
    
    const stockBadge = `<small style="margin-left:auto; font-size:0.75rem; font-weight:700; color:${isOutOfStock ? '#ef4444' : '#22c55e'};">${isOutOfStock ? 'Habis' : 'Stok: ' + stock}</small>`;
    const div = document.createElement('div');
    div.className = 'alat-checkbox-item';
    div.innerHTML = `
      <div class="alat-cb-top">
        <input class="form-check-input alat-checkbox" type="checkbox" id="${id}" value="${alat}" ${isOutOfStock ? 'disabled' : ''}>
        <label for="${id}">${alat}</label>
        ${stockBadge}
        ${!isOutOfStock ? `<button type="button" class="btn-add-alat" data-alat="${alat}">+</button>` : ''}
      </div>
      <div class="alat-cb-inputs">
        <input type="text" class="alat-jenis" id="${id}_jenis" placeholder="Jenis/Tipe" disabled>
        <input type="number" min="1" max="${stock}" class="alat-qty" id="${id}_qty" placeholder="Qty (Max ${stock})" disabled>
      </div>`;
    container.appendChild(div);
  });
}

function updateAlatCheckboxes(selectedEvent) {
  const defaultAlat = alatPerKegiatan[selectedEvent] || [];
  document.querySelectorAll('.alat-checkbox-item').forEach(item => {
    if (item.querySelector('.btn-remove-alat')) item.remove(); 
    const cb = item.querySelector('.alat-checkbox');
    if(cb) {
      cb.checked = false;
      document.getElementById(cb.id + '_jenis').disabled = true;
      document.getElementById(cb.id + '_jenis').value = '';
      document.getElementById(cb.id + '_qty').disabled = true;
      document.getElementById(cb.id + '_qty').value = '';
    }
  });

  defaultAlat.forEach(d => {
    let cb = [...document.querySelectorAll('.alat-checkbox:not([id*="dup"])')].find(x => x.value === d.nama);
    if (cb && !cb.checked) {
      cb.checked = true;
      document.getElementById(cb.id + '_jenis').disabled = false;
      document.getElementById(cb.id + '_jenis').value = d.jenis;
      document.getElementById(cb.id + '_qty').disabled = false;
      document.getElementById(cb.id + '_qty').value = d.qty;
    } else if (cb && cb.checked) {
      addDuplicateRow(d.nama, d.jenis, d.qty);
    }
  });
}

function initCheckboxListeners() {
  const container = document.getElementById('alatContainer');
  container.addEventListener('change', e => {
    if (e.target.classList.contains('alat-checkbox')) {
      const id = e.target.id;
      const checked = e.target.checked;
      document.getElementById(id + '_jenis').disabled = !checked;
      document.getElementById(id + '_qty').disabled = !checked;
      if(!checked) {
        document.getElementById(id + '_jenis').value = '';
        document.getElementById(id + '_qty').value = '';
      }
    }
  });

  container.addEventListener('click', e => {
    if (e.target.classList.contains('btn-add-alat')) {
      addDuplicateRow(e.target.dataset.alat, '', '');
    } else if (e.target.classList.contains('btn-remove-alat')) {
      e.target.closest('.alat-checkbox-item').remove();
    }
  });
}

function addDuplicateRow(alat, jenisVal, qtyVal) {
  const newIndex = 'dup' + Date.now();
  const div = document.createElement('div');
  div.className = 'alat-checkbox-item';
  div.innerHTML = `
    <div class="alat-cb-top">
      <input class="form-check-input alat-checkbox" type="checkbox" id="${newIndex}" value="${alat}" checked>
      <label for="${newIndex}">${alat}</label>
      <button type="button" class="btn-remove-alat">×</button>
    </div>
    <div class="alat-cb-inputs">
      <input type="text" class="alat-jenis" id="${newIndex}_jenis" placeholder="Jenis/Tipe" value="${jenisVal}">
      <input type="number" min="1" max="9" class="alat-qty" id="${newIndex}_qty" placeholder="Qty" value="${qtyVal}">
    </div>`;
  document.getElementById('alatContainer').appendChild(div);
}

function collectAlatRows() {
  let rows = [];
  document.querySelectorAll('.alat-checkbox-item').forEach(item => {
    const cb = item.querySelector('.alat-checkbox');
    if (cb && cb.checked) {
      rows.push({
        alat: cb.value,
        jenis: item.querySelector('.alat-jenis').value.trim(),
        qty: item.querySelector('.alat-qty').value
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
      window.location.href = 'peminjaman.php'; // Redirect kembali ke list
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