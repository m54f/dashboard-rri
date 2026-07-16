<?php
require_once __DIR__ . '/../api/youtube.php';

$base = '..'; $pageTitle = 'Gallery Youtube'; $activeMenu = 'gallery';

$videos = getYoutubeVideos();
$errorMessage = null;

if (!is_array($videos)) {
    $errorMessage = 'Gagal memuat data dari Notion.';
    $videos = [];
} elseif (isset($videos['error'])) {
    $errorMessage = $videos['error'];
    $videos = [];
}

$totalCount = count($videos);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Rekap Youtube</h2>
    <p>Koleksi siaran yang telah tayang.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal()"><i class="ri-add-line"></i> Tambah Video</button>
</div>

<!-- <div class="stats-row">
  <div class="stat-card"><div class="stat-info"><h3>Total Video</h3><div class="stat-value"><?= htmlspecialchars($totalCount) ?></div><div class="stat-sub">Dari database YouTube Notion</div></div><div class="stat-icon blue"><i class="ri-play-circle-line"></i></div></div>
</div> -->

<div class="gallery-grid" id="videoGrid">
  <?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem; width:100%;">
      ⚠️ <?= htmlspecialchars($errorMessage) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($videos)): ?>
    <div style="grid-column:1 / -1; text-align:center; color: var(--text-secondary); padding: 2rem;">
      Data video belum tersedia.
    </div>
  <?php else: ?>
    <?php foreach ($videos as $video): ?>
      <?php
        $title = isset($video['title']) && $video['title'] !== '' ? $video['title'] : 'Tanpa Judul';
        $namaAcara = isset($video['namaAcara']) && $video['namaAcara'] !== '' ? $video['namaAcara'] : '';
        $programa = isset($video['programa']) && $video['programa'] !== '' ? $video['programa'] : 'Umum';
        $programaColor = isset($video['programaColor']) ? $video['programaColor'] : 'gray';
        $date = isset($video['date']) && $video['date'] !== '' ? $video['date'] : 'TBA';
        $time = isset($video['time']) && $video['time'] !== '' ? $video['time'] : 'TBA';
        $url = isset($video['url']) && $video['url'] !== '' ? $video['url'] : '';
        $flyer = isset($video['flyer']) && $video['flyer'] !== '' ? $video['flyer'] : '';
        $penyiar = isset($video['penyiar']) && $video['penyiar'] !== '' ? $video['penyiar'] : '';
        $narasumber = isset($video['narasumber']) && is_array($video['narasumber']) ? $video['narasumber'] : [];
        
        // Map Notion colors to theme palette
        $colorMap = [
            'gray'    => '#64748b',  // --secondary
            'brown'   => '#92400e',  // warm brown
            'orange'  => '#f59e0b',  // --warning
            'yellow'  => '#fbbf24',  // amber
            'green'   => '#10b981',  // --success
            'blue'    => '#2563eb',  // --primary
            'purple'  => '#8b5cf6',  // --purple
            'pink'    => '#ec4899',  // rose-500
            'red'     => '#ef4444',  // --danger
            'default' => '#64748b'   // --secondary
        ];
        $bgColor = isset($colorMap[$programaColor]) ? $colorMap[$programaColor] : $colorMap['default'];
      ?>
      <?php $videoData = htmlspecialchars(json_encode([
        'id' => $video['id'],
        'namaAcara' => $namaAcara,
        'title' => $title,
        'programa' => $programa,
        'programaColor' => $programaColor,
        'flyer' => $flyer,
        'date' => $date,
        'time' => $time,
        'url' => $url,
        'penyiar' => $penyiar,
        'narasumber' => $narasumber
      ]), ENT_QUOTES, 'UTF-8'); ?>
      <div class="feed-card" data-video="<?= $videoData ?>" style="cursor:pointer; display:flex; flex-direction:column; height:100%;">
        <div class="feed-thumb" style="display:block; position:relative; overflow:hidden; background:#f0f0f0; min-height:200px;">
          <?php if ($flyer !== ''): ?>
            <img src="<?= htmlspecialchars($flyer, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#e0e0e0; color:#999;">
              <i class="ri-image-line" style="font-size:3rem;"></i>
            </div>
          <?php endif; ?>
          <span class="overlay-label" style="position:absolute; top:10px; left:10px; background:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; color:#fff; padding:4px 12px; border-radius:4px; font-size:12px; font-weight:bold;">
            <?= htmlspecialchars($programa, ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
        <div class="feed-info" style="flex:1; display:flex; flex-direction:column;">
          <div style="flex:1;">
            <?php if ($namaAcara !== ''): ?>
              <div style="font-size:12px; color:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; font-weight:bold; margin-bottom:4px;">
                <?= htmlspecialchars($namaAcara, ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>
            <div class="title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <div class="meta" style="font-size:13px; color:var(--text-secondary); border-top:1px solid var(--border-color); padding-top:12px; margin-top:12px;">
            <span><i class="ri-calendar-line"></i> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
            <span><i class="ri-time-line"></i> <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="videoModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Tambah Video</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form onsubmit="event.preventDefault(); closeModal();">
      <div class="form-group"><label>Judul Video</label><input type="text" placeholder="Judul tayangan" required></div>
      <div class="form-row">
        <div class="form-group"><label>Nama Program</label><input type="text" placeholder="Contoh: Sore Ceria" required></div>
        <div class="form-group"><label>Jam Tayang</label><input type="time" required></div>
      </div>
      <div class="form-group"><label>Link Youtube</label><input type="url" placeholder="https://youtube.com/..."></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="detailModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Detail Video</h3><button class="modal-close" onclick="closeDetailModal()">&times;</button></div>
    <div id="detailContent" style="padding:0;">
      <div style="max-height:500px; overflow-y:auto;">
        <!-- Flyer at top without label -->
        <div id="detail-flyer-group" style="display:none; margin-bottom:0;">
          <div style="max-height:300px; overflow:hidden;" id="detail-flyer"></div>
        </div>
        
        <!-- Content section -->
        <div style="padding:20px;">
          <div id="detail-title" style="font-size:16px; font-weight:700; color:var(--text-primary); margin-bottom:16px;"></div>
          
          <div id="detail-namaAcara-group" style="display:none; margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Nama Acara</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-namaAcara"></div>
          </div>
          
          <div style="margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Program</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-programa"></div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
            <div>
              <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Tanggal</div>
              <div style="font-size:14px; color:var(--text-primary);" id="detail-date"></div>
            </div>
            <div>
              <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Jam</div>
              <div style="font-size:14px; color:var(--text-primary);" id="detail-time"></div>
            </div>
          </div>

          <div id="detail-penyiar-group" style="display:none; margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Penyiar</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-penyiar"></div>
          </div>

          <div id="detail-narasumber-group" style="display:none; margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Narasumber</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-narasumber"></div>
          </div>
          
          <div id="detail-url-group" style="display:none; margin-top:16px; padding-top:16px; border-top:1px solid var(--border-color);">
            <!-- <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:8px;">Link Youtube</div> -->
            <div style="word-break:break-all;">
              <a id="detail-url" href="" target="_blank" style="color:var(--primary); text-decoration:none; font-size:13px;"></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
      <a id="detail-open-url" href="" target="_blank" class="btn btn-primary" style="text-decoration:none; display:none;"><i class="ri-external-link-line"></i> Buka Video</a>
    </div>
  </div>
</div>
<script>
function openModal(){ 
    document.getElementById('videoModal').classList.add('active'); 
}

function closeModal(){ 
    document.getElementById('videoModal').classList.remove('active'); 
}

function openDetailModal(videoData) {
    document.getElementById('detail-title').textContent = videoData.title;
    
    var namaAcaraGroup = document.getElementById('detail-namaAcara-group');
    if (videoData.namaAcara && videoData.namaAcara !== '') {
        namaAcaraGroup.style.display = 'block';
        document.getElementById('detail-namaAcara').textContent = videoData.namaAcara;
    } else {
        namaAcaraGroup.style.display = 'none';
    }
    
    document.getElementById('detail-programa').textContent = videoData.programa;
    document.getElementById('detail-date').textContent = videoData.date;
    document.getElementById('detail-time').textContent = videoData.time;

    var penyiarGroup = document.getElementById('detail-penyiar-group');
    if (videoData.penyiar && videoData.penyiar !== '') {
        penyiarGroup.style.display = 'block';
        document.getElementById('detail-penyiar').textContent = videoData.penyiar;
    } else {
        penyiarGroup.style.display = 'none';
    }

    var narasumberGroup = document.getElementById('detail-narasumber-group');
    if (videoData.narasumber && Array.isArray(videoData.narasumber) && videoData.narasumber.length > 0) {
        narasumberGroup.style.display = 'block';
        var listEl = document.createElement('ul');
        listEl.style.margin = '0';
        listEl.style.paddingLeft = '18px';
        videoData.narasumber.forEach(function(nama) {
            var li = document.createElement('li');
            li.textContent = nama;
            listEl.appendChild(li);
        });
        var container = document.getElementById('detail-narasumber');
        container.innerHTML = '';
        container.appendChild(listEl);
    } else {
        narasumberGroup.style.display = 'none';
    }
    
    var flyerGroup = document.getElementById('detail-flyer-group');
    if (videoData.flyer && videoData.flyer !== '') {
        flyerGroup.style.display = 'block';
        document.getElementById('detail-flyer').innerHTML = '<img src="' + videoData.flyer + '" style="width:100%; height:auto;" alt="Flyer">';
    } else {
        flyerGroup.style.display = 'none';
    }
    
    var urlGroup = document.getElementById('detail-url-group');
    var openUrlBtn = document.getElementById('detail-open-url');
    if (videoData.url && videoData.url !== '' && videoData.url !== '#') {
        // urlGroup.style.display = 'block';
        document.getElementById('detail-url').href = videoData.url;
        document.getElementById('detail-url').textContent = videoData.url;
        openUrlBtn.href = videoData.url;
        openUrlBtn.style.display = 'inline-flex';
    } else {
        urlGroup.style.display = 'none';
        openUrlBtn.style.display = 'none';
    }
    
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal(){
    document.getElementById('detailModal').classList.remove('active');
}

// Modal overlay click handlers
document.getElementById('videoModal').addEventListener('click', function(e) { 
    if (e.target === e.currentTarget) closeModal(); 
});

document.getElementById('detailModal').addEventListener('click', function(e) { 
    if (e.target === e.currentTarget) closeDetailModal(); 
});

// Video card click handler
document.querySelectorAll('.feed-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var videoDataStr = this.getAttribute('data-video');
        if (videoDataStr) {
            try {
                var videoData = JSON.parse(videoDataStr);
                openDetailModal(videoData);
            } catch(e) {
                console.error('Error parsing video data:', e);
            }
        }
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>