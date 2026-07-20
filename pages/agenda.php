<?php
$base = '..'; $pageTitle = 'Daily Agenda'; $activeMenu = 'agenda';

$stats = [
    ['labemmdl' => 'Agenda Hari Ini',   'value' => '6',  'sub' => 'Terjadwal',      'icon' => 'ri-calendar-line',     'tone' => 'blue'],
    ['label' => 'Sudah Berlangsung', 'value' => '2',  'sub' => 'Selesai dijalankan','icon' => 'ri-checkbox-circle-line','tone' => 'green'],
    ['label' => 'Menunggu',         'value' => '4',  'sub' => 'Belum dimulai',  'icon' => 'ri-time-line',         'tone' => 'orange'],
    ['label' => 'Live Streaming',   'value' => '2',  'sub' => 'Hari ini',       'icon' => 'ri-live-line',         'tone' => 'purple'],
];

require_once __DIR__ . '/../api/agenda.php';

$selectedDate = $_GET['date'] ?? '';
$selectedSort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$agenda = getAgenda($selectedDate ?: null, $selectedSort);
$errorMessage = null;
$errorRaw = null;
if (!is_array($agenda) || isset($agenda['error'])) {
  $errorMessage = $agenda['error'] ?? 'Gagal memuat data agenda dari Notion';
  $errorRaw = $agenda['raw'] ?? null;
  $agenda = [];
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
    ['label' => 'Live Streaming',   'value' => (string)$liveCount,  'sub' => 'Hari ini',       'icon' => 'ri-live-line',         'tone' => 'purple'],
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

function agendaPageUrl($page, $selectedDate, $selectedSort)
{
    $params = [];
    if ($selectedDate !== '') $params['date'] = $selectedDate;
    $params['sort'] = $selectedSort;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Daily Agenda</h2>
    <p>Jadwal kegiatan operasional siaran hari ini.</p>
  </div>
  <form class="date-picker" method="get" style="display:inline-flex; align-items:center; gap:0.75rem; margin-left:1rem;">
    <label for="dateFilter" style="margin:0; font-weight:600;">Tanggal:</label>
    <input id="dateFilter" type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()" style="padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #d1d5db;">
    <label for="sortFilter" style="margin:0; font-weight:600;">Urutan:</label>
    <select id="sortFilter" name="sort" onchange="this.form.submit()" style="padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #d1d5db;">
      <option value="asc" <?= $selectedSort === 'asc' ? 'selected' : '' ?>>Terlama</option>
      <option value="desc" <?= $selectedSort === 'desc' ? 'selected' : '' ?>>Terbaru</option>
    </select>
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
    <div class="card-title"><i class="ri-calendar-line"></i>Jadwal — <?= htmlspecialchars($selectedDate ? date('l, d F Y', strtotime($selectedDate)) : 'Semua Tanggal') ?></div>
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
          <td><span class="avatar-sm"><?= strtoupper(substr($a['pic'],0,2)) ?></span> <?= htmlspecialchars($a['pic']) ?></td>
          <td><span class="status-tag <?= $a['cls'] ?>"><?= htmlspecialchars($a['status']) ?></span></td>
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
      <a href="<?= htmlspecialchars(agendaPageUrl(max(1, $currentPage - 1), $selectedDate, $selectedSort)) ?>"
         class="btn btn-secondary" style="padding:0.4rem 0.8rem; <?= $currentPage <= 1 ? 'pointer-events:none; opacity:0.5;' : '' ?>">&laquo; Prev</a>
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a href="<?= htmlspecialchars(agendaPageUrl($p, $selectedDate, $selectedSort)) ?>"
         class="btn <?= $p === $currentPage ? 'btn-primary' : 'btn-secondary' ?>" style="padding:0.4rem 0.8rem;"><?= $p ?></a>
      <?php endfor; ?>
      <a href="<?= htmlspecialchars(agendaPageUrl(min($totalPages, $currentPage + 1), $selectedDate, $selectedSort)) ?>"
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
      <div class="form-row">
        <div class="form-group">
          <label>Kategori</label>
          <input type="text" name="kategori" list="kategoriList" placeholder="Pilih dari saran atau ketik kategori baru" required>
          <datalist id="kategoriList">
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status"><option>Belum</option><option>Selesai</option><option>Cancel</option></select>
        </div>
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
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Agenda</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE = '<?= $base ?>';
const CURRENT_SORT = '<?= $selectedSort ?>';

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
  console.log('[deleteAgendaRow] diklik untuk id:', id);
  const ok = confirm('Yakin mau hapus agenda ini? Data akan dipindah ke Trash Notion.');
  console.log('[deleteAgendaRow] hasil confirm():', ok);
  if (!ok) { console.log('[deleteAgendaRow] dibatalkan (confirm() false / dialog diblokir browser)'); return; }

  const row = btn.closest('tr');
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('page_id', id);

  btn.disabled = true;
  try {
    console.log('[deleteAgendaRow] mengirim request ke', `${BASE}/api/agenda.php`);
    const res = await fetch(`${BASE}/api/agenda.php`, { method: 'POST', body: formData });
    console.log('[deleteAgendaRow] status response:', res.status);
    const data = await res.json();
    console.log('[deleteAgendaRow] isi response:', data);
    if (!res.ok || data.error) {
      alert('Gagal menghapus agenda: ' + JSON.stringify(data));
      return;
    }
    row.remove();
    console.log('[deleteAgendaRow] baris berhasil dihapus dari tabel');
  } catch (err) {
    console.error('[deleteAgendaRow] exception:', err);
    alert('Gagal menghapus agenda: ' + err.message);
  } finally {
    btn.disabled = false;
  }
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
function escapeHtml(str){
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
function formatDateID(ymd){
  if (!ymd) return '-';
  const parts = ymd.split('-');
  if (parts.length !== 3) return ymd;
  const [y, m, d] = parts;
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const mi = parseInt(m, 10) - 1;
  if (isNaN(mi) || mi < 0 || mi > 11) return ymd;
  return `${d} ${months[mi]} ${y}`;
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
  tr.innerHTML = `
    <td style="font-weight:600; white-space:nowrap;">${escapeHtml(formatDateID(entry.tanggal))}</td>
    <td style="font-weight:700;">${escapeHtml(entry.waktu)}</td>
    <td class="cell-title">${escapeHtml(entry.kegiatan)}</td>
    <td><span class="tag ${categoryTone(entry.kategori)}">${escapeHtml(entry.kategori)}</span></td>
    <td><span class="avatar-sm">${escapeHtml(entry.lokasi.slice(0,2).toUpperCase())}</span> ${escapeHtml(entry.lokasi)}</td>
    <td><span class="status-tag ${statusTone(entry.status)}">${escapeHtml(entry.status)}</span></td>
    <td class="cell-sub">${entry.catatan ? escapeHtml(entry.catatan) : '—'}</td>
    <td>${renderDokumenCell(dokumentasi)}</td>
    <td>
      <a class="icon-action" title="Buka di Notion" href="${notionUrl}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i></a>
      <button class="icon-action edit" title="Edit" onclick="openEditModal('${pageId}')"><i class="ri-pencil-line"></i></button>
      <button class="icon-action delete" title="Hapus" onclick="deleteAgendaRow('${pageId}', this)"><i class="ri-delete-bin-line"></i></button>
    </td>
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

  // FormData (bukan URLSearchParams) supaya file ikut terkirim sebagai multipart/form-data
  const formData = new FormData(form);

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

    if (isUpdate) {
      const pageId = formData.get('page_id');
      const entry = {
        kegiatan: formData.get('kegiatan'),
        tanggal:  formData.get('tanggal'),
        waktu:    formData.get('waktu'),
        lokasi:   formData.get('lokasi_kegiatan'),
        kategori: formData.get('kategori'),
        status:   formData.get('status'),
        catatan:  formData.get('catatan'),
      };
      const dokumentasi = data.dokumentasi || [];
      updateRowInTable(pageId, entry, dokumentasi);

      // Sinkronkan juga ke AGENDA_DATA supaya kalau dibuka Edit lagi datanya sudah yang terbaru
      const idx = AGENDA_DATA.findIndex(a => a.id === pageId);
      const updatedRecord = {
        id: pageId, title: entry.kegiatan, date: entry.tanggal, time: entry.waktu,
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

    const entry = {
      kegiatan: formData.get('kegiatan'),
      tanggal:  formData.get('tanggal'),
      waktu:    formData.get('waktu'),
      lokasi:   formData.get('lokasi_kegiatan'),
      kategori: formData.get('kategori'),
      status:   formData.get('status'),
      catatan:  formData.get('catatan'),
    };
    const dokumentasi = data.dokumentasi || [];
    addRowToTable(entry, data.id, dokumentasi);
    AGENDA_DATA.push({
      id: data.id,
      title: entry.kegiatan,
      date: entry.tanggal,
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