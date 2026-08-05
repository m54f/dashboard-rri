<?php
$base = '..'; $pageTitle = 'Jadwal Operasional'; $activeMenu = 'jadwal';

/* ============================================================
   Konfigurasi upload
   ============================================================ */
$uploadDir      = __DIR__ . '/uploads/';
$uploadDirWeb   = 'uploads/'; // path relatif untuk ditampilkan di <img>
$allowedExt     = ['jpg','jpeg','png','webp','gif'];
$maxFileSizeMB  = 8;
$message = null;
$messageType = 'success'; // success | error

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/* ============================================================
   Handle upload (bisa banyak file sekaligus)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $files = $_FILES['images'];
    $totalFiles = count($files['name']);
    $uploaded = 0;
    $errors = [];

    for ($i = 0; $i < $totalFiles; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            if ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = $files['name'][$i] . ' gagal diupload.';
            }
            continue;
        }

        $originalName = $files['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            $errors[] = "$originalName: format tidak didukung.";
            continue;
        }
        if ($files['size'][$i] > $maxFileSizeMB * 1024 * 1024) {
            $errors[] = "$originalName: ukuran melebihi {$maxFileSizeMB}MB.";
            continue;
        }

        // Nama file unik biar tidak saling menimpa
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $newName  = date('Ymd_His') . '_' . $safeName . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;

        if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $newName)) {
            $uploaded++;
        } else {
            $errors[] = "$originalName: gagal disimpan di server.";
        }
    }

    if ($uploaded > 0 && empty($errors)) {
        $message = "$uploaded gambar berhasil ditambahkan.";
        $messageType = 'success';
    } elseif ($uploaded > 0 && !empty($errors)) {
        $message = "$uploaded gambar berhasil ditambahkan, tapi ada " . count($errors) . " yang gagal: " . implode(' ', $errors);
        $messageType = 'error';
    } elseif (!empty($errors)) {
        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}

/* ============================================================
   Handle hapus gambar
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $target = basename($_POST['delete_file']); // basename() mencegah path traversal
    $targetPath = $uploadDir . $target;
    if (is_file($targetPath) && strpos(realpath($targetPath), realpath($uploadDir)) === 0) {
        unlink($targetPath);
        $message = 'Gambar berhasil dihapus.';
        $messageType = 'success';
    }
}

/* ============================================================
   Ambil daftar gambar (terbaru dulu)
   ============================================================ */
$images = glob($uploadDir . '*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
usort($images, fn($a, $b) => filemtime($b) - filemtime($a));

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Jadwal Operasional</h2>
    <p>Kumpulan gambar jadwal siaran (Pro 1, Pro 2, Pro 4, PA, PT, Pemancar Ngadirejo, dll).</p>
  </div>
  <div class="page-head-actions">
    <button class="btn btn-primary" onclick="openModal()"><i class="ri-upload-2-line"></i> Tambah Gambar</button>
  </div>
</div>

<?php if ($message): ?>
<div class="card" style="margin-bottom:18px;border-color:<?= $messageType === 'success' ? 'var(--success)' : 'var(--danger)' ?>;background:<?= $messageType === 'success' ? '#ecfdf5' : '#fef2f2' ?>;">
  <div style="display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:<?= $messageType === 'success' ? 'var(--success)' : 'var(--danger)' ?>;">
    <i class="<?= $messageType === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line' ?>" style="font-size:18px;"></i>
    <?= htmlspecialchars($message) ?>
  </div>
</div>
<?php endif; ?>

<div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Gambar</h3><div class="stat-value"><?= count($images) ?></div><div class="stat-sub">Tersimpan di server</div></div><div class="stat-icon blue"><i class="ri-image-2-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Update Terakhir</h3><div class="stat-value" style="font-size:15px;"><?= $images ? date('d M Y', filemtime($images[0])) : '—' ?></div><div class="stat-sub">Otomatis dari upload</div></div><div class="stat-icon green"><i class="ri-time-line"></i></div></div>
  <div class="stat-card"><div class="stat-info"><h3>Format Didukung</h3><div class="stat-value" style="font-size:15px;">JPG · PNG · WEBP</div><div class="stat-sub">Maks <?= $maxFileSizeMB ?>MB per file</div></div><div class="stat-icon purple"><i class="ri-file-image-line"></i></div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-gallery-line"></i>Galeri Jadwal</div>
  </div>

  <?php if (empty($images)): ?>
    <div style="text-align:center;padding:50px 20px;color:var(--text-secondary);">
      <i class="ri-image-add-line" style="font-size:36px;display:block;margin-bottom:10px;"></i>
      Belum ada gambar. Klik <strong>Tambah Gambar</strong> untuk mulai mengunggah.
    </div>
  <?php else: ?>
  <div class="gallery-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));">
    <?php foreach ($images as $imgPath):
      $fileName = basename($imgPath);
      $webPath  = $base . '/jadwal/' . $uploadDirWeb . $fileName;
    ?>
    <div class="feed-card" style="position:relative;">
      <div class="feed-thumb" style="cursor:pointer;background:#fff;" onclick="openLightbox('<?= htmlspecialchars($webPath) ?>')">
        <img src="<?= htmlspecialchars($webPath) ?>" alt="<?= htmlspecialchars($fileName) ?>" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
      </div>
      <div class="feed-info">
        <div class="meta">
          <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:130px;" title="<?= htmlspecialchars($fileName) ?>"><?= htmlspecialchars($fileName) ?></span>
          <form method="post" onsubmit="return confirm('Hapus gambar ini?');" style="margin:0;">
            <input type="hidden" name="delete_file" value="<?= htmlspecialchars($fileName) ?>">
            <button type="submit" class="icon-action delete" title="Hapus" style="width:24px;height:24px;font-size:12px;"><i class="ri-delete-bin-line"></i></button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Upload -->
<div class="modal-overlay" id="uploadModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Tambah Gambar Jadwal</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form method="post" enctype="multipart/form-data" id="uploadForm">
      <div class="form-group">
        <label>Pilih Gambar (bisa lebih dari satu)</label>
        <input type="file" name="images[]" id="fileInput" accept="image/jpeg,image/png,image/webp,image/gif" multiple required onchange="renderPreview()">
      </div>
      <div id="previewGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="uploadBtn"><i class="ri-upload-2-line"></i> Unggah</button>
      </div>
    </form>
  </div>
</div>

<!-- Lightbox -->
<div class="modal-overlay" id="lightbox" onclick="closeLightbox()">
  <img id="lightboxImg" src="" alt="" style="max-width:92%;max-height:88vh;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,.4);">
</div>

<script>
function openModal(){ document.getElementById('uploadModal').classList.add('active'); }
function closeModal(){
  document.getElementById('uploadModal').classList.remove('active');
  document.getElementById('uploadForm').reset();
  document.getElementById('previewGrid').innerHTML = '';
}
document.getElementById('uploadModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function openLightbox(src){
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightbox').classList.add('active');
}
function closeLightbox(){ document.getElementById('lightbox').classList.remove('active'); }

// Preview semua gambar yang dipilih SEBELUM diupload, supaya user lihat dulu apa yang akan tampil
function renderPreview(){
  const input = document.getElementById('fileInput');
  const grid = document.getElementById('previewGrid');
  grid.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;border:1px solid var(--border-color);';
      grid.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

// Tampilkan status "Mengunggah..." saat submit supaya user tahu prosesnya berjalan
document.getElementById('uploadForm').addEventListener('submit', () => {
  const btn = document.getElementById('uploadBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Mengunggah...';
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
