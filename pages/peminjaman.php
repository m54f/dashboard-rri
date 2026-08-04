<?php
 $base = '..'; 
 $pageTitle = 'Peminjaman Alat'; 
 $activeMenu = 'peminjaman';

require_once __DIR__.'/../config/notion.php';
require_once __DIR__.'/../api/peminjaman.php';

// Fungsi cURL (diperlukan untuk ambil stok)
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

 $items = getPeminjaman();

if (!is_array($items) || isset($items['error'])) {
    $errorMessage = $items['error'] ?? 'Gagal memuat data';
    $items = [];
}

 $total       = count($items);
 $dipinjam    = count(array_filter($items, fn($i) => $i['status'] === 'Dipinjam'));
 $terlambat   = count(array_filter($items, fn($i) => $i['status'] === 'Terlambat'));
 $dikembalikan = count(array_filter($items, fn($i) => $i['status'] === 'Dikembalikan'));

 $stats = [
    ['label' => 'Sedang Dipinjam', 'value' => $dipinjam, 'sub' => 'Di lapangan', 'icon' => 'ri-hand-heart-line', 'tone' => 'orange'],
    ['label' => 'Dikembalikan', 'value' => $dikembalikan, 'sub' => 'Sudah selesai', 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
    ['label' => 'Terlambat', 'value' => $terlambat, 'sub' => 'Perlu ditindaklanjuti', 'icon' => 'ri-alarm-warning-line', 'tone' => 'red'],
    ['label' => 'Total Transaksi', 'value' => $total, 'sub' => 'Data real-time dari Notion', 'icon' => 'ri-exchange-line', 'tone' => 'blue'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h2>Peminjaman Alat</h2>
    <p>Kelola peminjaman dan pengembalian alat siaran.</p>
  </div>
  <a href="peminjaman_form.php" class="btn btn-primary"><i class="ri-add-line"></i> Catat Peminjaman</a>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ Gagal memuat data dari Notion: <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="stats-row">
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-info"><h3><?= htmlspecialchars($s['label']) ?></h3><div class="stat-value"><?= htmlspecialchars($s['value']) ?></div><div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div></div>
    <div class="stat-icon <?= $s['tone'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Container Filter dan Search -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1rem;">
    <div class="filter-tabs" style="margin-bottom:0;">
      <div class="filter-tab active" onclick="filterPinjam('Semua', this)">Semua</div>
      <div class="filter-tab" onclick="filterPinjam('Dipinjam', this)">Dipinjam</div>
      <div class="filter-tab" onclick="filterPinjam('Terlambat', this)">Terlambat</div>
      <div class="filter-tab" onclick="filterPinjam('Dikembalikan', this)">Dikembalikan</div>
    </div>
    
    <!-- Kotak Pencarian -->
    <div style="flex:1; min-width:200px; display:flex; justify-content:flex-end;">
        <input type="text" id="searchInput" placeholder="Cari kegiatan/PIC/alat..." oninput="handleSearch()" style="padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0); font-size: 0.85rem; outline: none; width:100%; max-width: 300px;">
    </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-service-line"></i> Riwayat Peminjaman</div></div>
  <div class="table-wrap">
    <table class="data-table" id="pinjamTable">
      <thead><tr><th>Kegiatan</th><th>PIC</th><th>Alat</th><th>Lokasi</th><th>Tgl Pinjam</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-secondary);">Tidak ada data peminjaman.</td></tr>
        <?php else: foreach ($items as $it): ?>
        <tr data-status="<?= htmlspecialchars($it['status']) ?>">
          <td class="cell-title"><?= htmlspecialchars($it['kegiatan']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['pic']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['alat_display']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['lokasi']) ?></td>
          <td><?= htmlspecialchars($it['tgl_pinjam'] ?? '-') ?></td>
          <td><span class="status-tag <?= $it['cls'] ?>"><?= htmlspecialchars($it['status']) ?></span></td>
          <td class="cell-actions">
            <a href="<?= htmlspecialchars($it['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion"><i class="ri-external-link-line"></i></a>
            <?php if (getRole() === 'admin'): ?>
            <?php if ($it['status'] === 'Dipinjam' || $it['status'] === 'Terlambat'): ?>
            <button type="button" class="icon-action return" title="Tandai Dikembalikan" onclick="markReturned('<?= htmlspecialchars($it['page_id']) ?>', '<?= htmlspecialchars(addslashes($it['kegiatan'])) ?>')"><i class="ri-checkbox-circle-line"></i></button>
            <?php endif; ?>
            <a href="peminjaman_form.php?id=<?= htmlspecialchars($it['page_id']) ?>" class="icon-action edit" title="Edit"><i class="ri-pencil-line"></i></a>
            <button type="button" class="icon-action delete" title="Hapus" onclick="deleteItem('<?= htmlspecialchars($it['page_id']) ?>', '<?= htmlspecialchars(addslashes($it['kegiatan'])) ?>')"><i class="ri-delete-bin-line"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  
  <!-- PAGING CONTAINER -->
  <div class="pagination-wrap" id="paginationWrap" style="display: none; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid #e2e8f0; background: #fff; border-radius: 0 0 8px 8px;">
    <div class="pagination-info" id="paginationInfo" style="font-size: 0.85rem; color: #64748b;">Menampilkan data</div>
    <div class="pagination-buttons" id="paginationButtons" style="display: flex; gap: 8px; align-items: center;"></div>
  </div>
  
</div>

<style>
  .cell-actions { display:flex; gap:0.4rem; align-items:center; }
  .icon-action { background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; text-decoration: none; }
  .icon-action.view { color:#6b7280; }
  .icon-action.edit { color:#3b82f6; }
  .icon-action.return { color:#22c55e; }
  .icon-action.delete { color:#ef4444; }
  .icon-action:hover { background:rgba(0,0,0,0.05); }
</style>

<script>
// Variabel State Pagination & Filter
let currentPage = 1;
const rowsPerPage = 10;
let currentFilter = 'Semua';
let searchQuery = '';

function handleSearch() {
  searchQuery = document.getElementById('searchInput').value.toLowerCase();
  currentPage = 1;
  updateTablePagination();
}

function filterPinjam(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  currentFilter = status;
  currentPage = 1;
  updateTablePagination();
}

function changePage(page) {
  currentPage = page;
  updateTablePagination();
}

function jumpToPage(val, maxPage) {
  let p = parseInt(val, 10);
  if (!isNaN(p) && p >= 1 && p <= maxPage) {
    changePage(p);
  } else if (p > maxPage) {
    changePage(maxPage);
  } else {
    changePage(1); 
  }
}

function updateTablePagination() {
  const allRows = Array.from(document.querySelectorAll('#pinjamTable tbody tr'));
  const paginationWrap = document.getElementById('paginationWrap');

  // Abaikan baris pesan kosong
  if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
    if (paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  // Filter gabungan: Tab Filter & Teks Pencarian
  const filteredRows = allRows.filter(row => {
    const statusMatch = currentFilter === 'Semua' || row.dataset.status === currentFilter;
    const textContent = row.textContent.toLowerCase();
    const searchMatch = !searchQuery || textContent.includes(searchQuery);
    return statusMatch && searchMatch;
  });

  const totalRows = filteredRows.length;
  const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  allRows.forEach(row => row.style.display = 'none');

  const startIndex = (currentPage - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, totalRows);
  for (let i = startIndex; i < endIndex; i++) {
    filteredRows[i].style.display = '';
  }

  const paginationInfo = document.getElementById('paginationInfo');
  const paginationButtons = document.getElementById('paginationButtons');

  if (totalRows === 0 || !paginationWrap) {
    if (paginationWrap) paginationWrap.style.display = 'none';
  } else {
    paginationWrap.style.display = 'flex';
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} peminjaman`;

    let buttonsHTML = '';

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const prevStyle = currentPage === 1 ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
    buttonsHTML += `<button type="button" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:0.9rem; transition:0.2s; ${prevStyle}" onclick="changePage(${currentPage - 1})" ${prevDisabled}>&laquo; Prev</button>`;

    buttonsHTML += `<input type="number" min="1" max="${totalPages}" value="${currentPage}" style="width:55px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#3b82f6; outline:none; font-weight:600;" onchange="jumpToPage(this.value, ${totalPages})" onkeydown="if(event.key === 'Enter') jumpToPage(this.value, ${totalPages})">`;

    buttonsHTML += `<span style="font-size:0.9rem; color:#475569;">dari ${totalPages}</span>`;

    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    const nextStyle = currentPage === totalPages ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
    buttonsHTML += `<button type="button" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:0.9rem; transition:0.2s; ${nextStyle}" onclick="changePage(${currentPage + 1})" ${nextDisabled}>Next &raquo;</button>`;

    paginationButtons.innerHTML = buttonsHTML;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateTablePagination();
});

async function markReturned(pageId, kegiatan) {
  if (!confirm(`Tandai "${kegiatan}" sebagai sudah dikembalikan?`)) return;
  try {
    const res = await fetch('../api/mark-returned.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ page_id: pageId })
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert('Gagal: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    alert('Gagal menghubungi server: ' + err.message);
  }
}

async function deleteItem(pageId, kegiatan) {
  if (!confirm(`Hapus data peminjaman "${kegiatan}"?\n\nData akan dipindahkan ke Trash di Notion.`)) return;
  try {
    const res = await fetch('../api/delete-peminjaman.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ page_id: pageId })
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert('Gagal: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    alert('Gagal menghubungi server: ' + err.message);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>