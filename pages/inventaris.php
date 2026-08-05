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

// Variabel untuk pagination
$has_more = true;
$start_cursor = null;

while ($has_more) {
   $queryBody = $baseQueryBody;
   
   // Tambahkan start_cursor jika ini bukan halaman pertama
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
               'cls'     => $statCls,
               'notes'   => $getText($props['Keterangan'])
           ];
       }
       
       // Cek apakah masih ada halaman berikutnya
       $has_more = $dbData['has_more'] ?? false;
       $start_cursor = $dbData['next_cursor'] ?? null;
   } else {
       $errorMessage = "Gagal memuat data dari Notion.";
       if (isset($dbData['message'])) $errorMessage .= " Pesan Notion: " . $dbData['message'];
       $has_more = false; // Hentikan loop jika terjadi error agar tidak infinite loop
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

 $categories = array_unique(array_map(fn($i) => $i['tag'], $inventaris));
sort($categories);

 $perPage = 8;
 $totalPages = max(1, (int)ceil($totalItems / $perPage));
 $currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
 $offset = ($currentPage - 1) * $perPage;
 $invPage = array_slice($inventaris, $offset, $perPage);
 $showingFrom = $totalItems === 0 ? 0 : $offset + 1;
 $showingTo = min($offset + $perPage, $totalItems);

// Pagination dipadatkan supaya muat di layar HP (halaman pertama, halaman
// terakhir, dan beberapa halaman di sekitar halaman aktif — sisanya diwakili
// tanda "…"). Tanpa ini, kalau ada banyak halaman semua nomornya kelist penuh
// dan bikin harus scroll horizontal di HP (Prev/Next jadi ketutup/kegeser).
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

function invPageUrl($page, $search) {
    $params = ['page' => $page];
    if ($search !== '') $params['search'] = $search;
    return '?' . http_build_query($params);
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= $base ?>/assets/css/inventaris.css">

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
  }
  .page-head > div:first-child {
    width: 100%;
  }
  .inv-search-bar {
    margin-left: 0 !important;
    width: 100% !important;
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-sizing: border-box;
  }
  .inv-search-bar input[type="text"] {
    flex: 1;
    width: 100% !important;
  }
  .page-head .btn-primary {
    width: 100% !important;
    justify-content: center !important;
  }
  .filter-tabs {
    display: flex !important;
    overflow-x: auto !important;
    white-space: nowrap !important;
    flex-wrap: nowrap !important;
    padding-bottom: 8px !important;
    margin-bottom: 1rem !important;
    -webkit-overflow-scrolling: touch;
    gap: 8px !important;
  }
  .filter-tab {
    flex-shrink: 0 !important;
  }
  .table-wrap {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 12px 12px;
  }
  #invTable {
    min-width: 700px !important;
  }
  #invTable th, #invTable td {
    padding: 0.75rem 0.6rem !important;
    font-size: 0.83rem !important;
  }
  .pagination-wrap {
    flex-direction: column;
    align-items: stretch !important;
    text-align: center;
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
}
</style>

<div class="page-head">
  <div>
    <h2>Inventaris Alat</h2>
    <p>Manajemen stok dan ketersediaan peralatan studio.</p>
  </div>
  <div class="page-head-actions">
    <form class="search-box page-head-search" method="get">
      <i class="ri-search-line"></i>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/kode alat..." style="border:none !important; outline:none !important; box-shadow:none !important; background:transparent !important; width:100%;">
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

<div class="filter-tabs" id="filterTabs">
  <div class="filter-tab active" onclick="filterInventaris('Semua', this)">Semua</div>
  <?php foreach ($categories as $cat): ?>
  <div class="filter-tab" onclick="filterInventaris('<?= htmlspecialchars($cat, ENT_QUOTES) ?>', this)"><?= htmlspecialchars($cat) ?></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-archive-2-line"></i>Daftar Peralatan</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="invTable">
      <thead>
        <tr><th>Nama Alat</th><th>Kode</th><th>Kategori</th><th>Qty</th><th>Lokasi</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (empty($invPage)): ?>
        <tr><td colspan="8" style="text-align:center; padding:2rem; color:#6b7280;">Tidak ada data inventaris ditemukan.</td></tr>
        <?php else: ?>
        <?php foreach ($invPage as $i): ?>
        <tr data-tag="<?= htmlspecialchars($i['tag'], ENT_QUOTES) ?>" data-id="<?= htmlspecialchars($i['page_id'], ENT_QUOTES) ?>">
          <td class="cell-title"><?= htmlspecialchars($i['title']) ?></td>
          <td style="font-weight:600; color:#4b5563; font-family:monospace;"><?= htmlspecialchars($i['kode']) ?: '-' ?></td>
          <td><span class="tag <?= $i['tone'] ?>"><?= htmlspecialchars($i['tag']) ?></span></td>
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
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination-wrap">
    <div style="font-size:0.9rem; color:#6b7280;">
      Menampilkan <?= $showingFrom ?>-<?= $showingTo ?> dari <?= $totalItems ?> alat
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
      <a href="<?= htmlspecialchars(invPageUrl(max(1, $currentPage - 1), $search)) ?>" class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">&laquo; Prev</a>
      <input type="text" inputmode="numeric" pattern="[0-9]*" class="page-input-clean" value="<?= $currentPage ?>" style="width:50px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#2563eb; outline:none; font-weight:600;" onfocus="this.select()" onchange="let p=parseInt(this.value); if(!isNaN(p) && p>=1 && p<=<?= $totalPages ?>) location.href='<?= htmlspecialchars(invPageUrl(999999, $search)) ?>'.replace('999999', p);" onkeydown="if(event.key === 'Enter'){ let p=parseInt(this.value); if(!isNaN(p) && p>=1 && p<=<?= $totalPages ?>) location.href='<?= htmlspecialchars(invPageUrl(999999, $search)) ?>'.replace('999999', p); }">
      <span style="font-size:0.9rem; color:#475569;">dari <?= $totalPages ?></span>
      <a href="<?= htmlspecialchars(invPageUrl(min($totalPages, $currentPage + 1), $search)) ?>" class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage >= $totalPages ? 'pointer-events:none; opacity:0.5;' : '' ?>">Next &raquo;</a>
    </div>
    <?php endif; ?>
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
      <div class="form-group"><label>Nama Alat</label><input type="text" name="nama_alat" placeholder="Contoh: Mixer Ashley" required></div>
      <div class="form-row">
        <div class="form-group"><label>Kode Inventaris</label><input type="text" name="kode" placeholder="Contoh: GM-042"></div>
        <div class="form-group"><label>Jumlah (Qty)</label><input type="number" name="qty" min="1" value="1" required></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Kategori</label>
          <select name="kategori">
            <option>Audio</option><option>Kamera</option><option>Komputer</option><option>Kabel</option>
            <option>Tripod</option><option>Lighting</option><option>Video</option><option>Komunikasi</option>
            <option>Aksesoris</option><option>Power</option><option>Jaringan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Lokasi</label>
          <select name="lokasi">
            <option>Gudang Musik 1</option><option>Gudang Musik 1 - Lemari Besi</option><option>Gudang Musik 1 - Lemari Kayu Kiri</option>
            <option>Studio PRO 1</option><option>Studio PRO 2</option><option>Studio PRO 4</option><option>Studio Podcast</option>
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
      </div>
      <div class="form-group"><label>Keterangan</label><textarea name="catatan" rows="2" placeholder="Kelengkapan, IP address, dll..."></textarea></div>
      <input type="hidden" name="page_id" value="">
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Alat</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE = '<?= $base ?>';
// Kirim data PHP ke JS agar form Edit bisa terisi otomatis
const INVENTARIS_DATA = <?= json_encode($invPage) ?>;

// ===== Fungsi Modal =====
function openModal(mode) {
  const form = document.getElementById('invForm');
  form.reset();
  document.querySelector('input[name="page_id"]').value = '';
  document.getElementById('modalTitle').textContent = 'Tambah Alat';
  document.getElementById('invModal').classList.add('active');
}

function closeModal() {
  document.getElementById('invModal').classList.remove('active');
}

// Tutup modal jika klik di luar area kotak modal
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
  form.catatan.value = item.notes;
  form.page_id.value = item.page_id;

  document.getElementById('modalTitle').textContent = 'Edit Alat';
  document.getElementById('invModal').classList.add('active');
}

// ===== Fungsi Filter Tab =====
function filterInventaris(tag, el) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#invTable tbody tr').forEach(row => {
    row.style.display = (tag === 'Semua' || row.dataset.tag === tag) ? '' : 'none';
  });
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
      // Tampilkan notifikasi gagal
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
      console.error(data);
      return;
    }
    
    // Tampilkan notifikasi sukses
    const msg = isUpdate ? '✅ Data alat berhasil diperbarui!' : '✅ Alat baru berhasil ditambahkan!';
    showToast(msg, 'success');
    
    closeModal();
    // Delay reload 1.2 detik agar notifikasi sempat terbaca
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
  if (!confirm('Yakin mau hapus alat ini? Data akan dipindah ke Trash Notion.')) return;
  
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
    
    // Tampilkan notifikasi sukses hapus
    showToast('🗑️ Alat berhasil dihapus!', 'success');
    
    row.remove();
    // Delay reload 1.2 detik agar notifikasi sempat terbaca
    setTimeout(() => location.reload(), 1200);

  } catch (err) {
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
  } finally {
    btn.disabled = false;
  }
}

// showToast() is provided globally by /assets/css/js/sonner.js
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>