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

 $queryBody = [
    "page_size" => 100,
    "sorts" => [["property" => "Nama Alat", "direction" => "ascending"]]
];

if (!empty($search)) {
    $queryBody["filter"] = [
        "or" => [
            ["property" => "Nama Alat", "title" => ["contains" => $search]],
            ["property" => "Kode", "rich_text" => ["contains" => $search]]
        ]
    ];
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
} else {
    $errorMessage = "Gagal memuat data dari Notion.";
    if (isset($dbData['message'])) $errorMessage .= " Pesan Notion: " . $dbData['message'];
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

function invPageUrl($page, $search) {
    $params = ['page' => $page];
    if ($search !== '') $params['search'] = $search;
    return '?' . http_build_query($params);
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= $base ?>/assets/css/inventaris.css">

<div class="page-head">
  <div>
    <h2>Inventaris Alat</h2>
    <p>Manajemen stok dan ketersediaan peralatan studio.</p>
  </div>
  <form class="search-bar" method="get" style="display:inline-flex; align-items:center; gap:0.5rem; margin-left:1rem;">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/kode alat..." style="padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #d1d5db; width:200px;">
    <button type="submit" class="btn btn-secondary"><i class="ri-search-line"></i></button>
  </form>
  <button class="btn btn-primary" onclick="openModal('create')"><i class="ri-add-line"></i> Tambah Alat</button>
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
            <button class="icon-action edit" title="Edit" onclick="openEditModal('<?= htmlspecialchars($i['page_id'], ENT_QUOTES) ?>')"><i class="ri-pencil-line"></i></button>
            <button class="icon-action delete" title="Hapus" onclick="deleteInvRow('<?= htmlspecialchars($i['page_id'], ENT_QUOTES) ?>', this)"><i class="ri-delete-bin-line"></i></button>
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
    <div style="display:flex; gap:0.4rem;">
      <a href="<?= htmlspecialchars(invPageUrl(max(1, $currentPage - 1), $search)) ?>" class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">&laquo; Prev</a>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="<?= htmlspecialchars(invPageUrl($p, $search)) ?>" class="btn <?= $p === $currentPage ? 'btn-primary' : 'btn-secondary' ?>" style="padding:0.4rem 0.8rem;"><?= $p ?></a>
      <?php endfor; ?>
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

// ===== Fungsi Toast Notification =====
function showToast(message, type) {
  const existing = document.querySelector('.toast-notification');
  if (existing) existing.remove();
  
  const toast = document.createElement('div');
  toast.className = 'toast-notification ' + type;
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;
    padding: 1rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.95rem;
    color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    animation: slideInToast 0.4s ease;
    ${type === 'success' ? 'background: linear-gradient(135deg, #22c55e, #16a34a);' : 'background: linear-gradient(135deg, #ef4444, #dc2626);'}
  `;
  document.body.appendChild(toast);
  
  // Hilangkan otomatis setelah 3 detik
  setTimeout(() => { 
    toast.style.opacity = '0'; 
    toast.style.transition = 'opacity 0.5s'; 
    setTimeout(() => toast.remove(), 500); 
  }, 3000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>