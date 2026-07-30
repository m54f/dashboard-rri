<?php
// ==========================================
// HANDLER AJAX: TARIK & PARSING RSS YOUTUBE
// (Dipindahkan dari test-rss-autofill.php)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_rss') {
    header('Content-Type: application/json; charset=utf-8');

    $channelId = $_POST['channel_id'] ?? '';
    $targetDate = $_POST['target_date'] ?? date('Y-m-d');

    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'ID Channel tidak boleh kosong.']);
        exit;
    }

    $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id=" . urlencode($channelId);

    $ch = curl_init($rssUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $xmlContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($xmlContent)) {
        echo json_encode(['success' => false, 'error' => 'Gagal mengambil RSS feed. Pastikan Channel ID benar.']);
        exit;
    }

    $xml = @simplexml_load_string($xmlContent);
    if (!$xml) {
        echo json_encode(['success' => false, 'error' => 'Format XML tidak valid.']);
        exit;
    }

    $namespaces = $xml->getNamespaces(true);
    $videos = [];

    foreach ($xml->entry as $entry) {
        $media = $entry->children($namespaces['media']);
        $yt    = $entry->children($namespaces['yt'] ?? 'http://www.youtube.com/xml/schemas/2015');
        $title = (string)$entry->title;
        $url   = (string)$entry->link['href'];

        // Ambil video ID langsung dari <yt:videoId> — lebih reliable dari parsing URL
        $videoId = isset($yt->videoId) ? (string)$yt->videoId : '';

        // Fallback: ekstrak dari URL jika <yt:videoId> tidak tersedia
        if (empty($videoId)) {
            preg_match('/[?&]v=([^&]+)/', $url, $match);
            $videoId = $match[1] ?? '';
        }

        $description = (string)$media->group->description;

        // Bangun thumbnail resolusi tinggi dari video ID
        $thumbnailUrl = '';
        if (!empty($videoId)) {
            $thumbnailUrl = "https://i.ytimg.com/vi/{$videoId}/maxresdefault.jpg";
        } elseif (isset($media->group->thumbnail)) {
            // Fallback ke thumbnail dari RSS jika video ID tidak diketahui
            $attrs = $media->group->thumbnail->attributes();
            $thumbnailUrl = (string)$attrs['url'];
        }

        // 1. Ekstrak data dari deskripsi (termasuk mencari tanggal asli yang tertulis)
        $parsed = parseDeskripsiRss($description);

        // 2. Ambil waktu sistem YouTube sebagai cadangan (dikonversi ke WIB)
        $dt = new DateTime((string)$entry->published);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        $fallbackDate = $dt->format('Y-m-d');

        // 3. PRIORITAS: Gunakan tanggal dari deskripsi jika ada, jika tidak pakai cadangan
        $realDate = !empty($parsed['tanggal_asli']) ? $parsed['tanggal_asli'] : $fallbackDate;

        // 4. Filter berdasarkan tanggal yang benar-benar tayang
        if ($realDate === $targetDate) {
            $parsed['title_asli'] = $title;
            $parsed['url']        = $url;
            $parsed['video_id']   = $videoId; // langsung dari <yt:videoId>
            $parsed['tanggal']    = $realDate;
            $parsed['flyer']      = $thumbnailUrl;

            $videos[] = $parsed;
        }
    }

    echo json_encode(['success' => true, 'data' => $videos]);
    exit;
}

// Fungsi Mesin Regex untuk parsing deskripsi video Youtube
function parseDeskripsiRss($description) {
    $data = [
        'tema' => '', 'penyiar' => '', 'narasumber' => '',
        'pt' => [], 'pa' => [], 'programa' => '', 'jamMulai' => '',
        'tanggal_asli' => ''
    ];

    if (empty($description)) return $data;

    $cleanDesc = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $description);

    // 1. Ekstrak Tanggal dari Teks (Misal: "Selasa, 14 Juli 2026" atau "15 Juli 2026")
    $bulanIndo = [
        'januari'=>'01', 'februari'=>'02', 'maret'=>'03', 'april'=>'04',
        'mei'=>'05', 'juni'=>'06', 'juli'=>'07', 'agustus'=>'08',
        'september'=>'09', 'oktober'=>'10', 'november'=>'11', 'desember'=>'12'
    ];

    if (preg_match('/(?:\b(?:Senin|Selasa|Rabu|Kamis|Jum\'?at|Sabtu|Minggu)\b\s*,?\s*)?(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $cleanDesc, $m)) {
        $tgl = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $bln = $bulanIndo[strtolower($m[2])] ?? '01';
        $thn = $m[3];
        $data['tanggal_asli'] = "$thn-$bln-$tgl";
    }

    // 2. Ekstrak Programa
    if (preg_match('/Pro\s*1/i', $cleanDesc)) $data['programa'] = 'Pro 1 FM 94.6 Mhz';
    elseif (preg_match('/Pro\s*2/i', $cleanDesc)) $data['programa'] = 'Pro 2 FM 87.9 Mhz';
    elseif (preg_match('/Pro\s*4/i', $cleanDesc)) $data['programa'] = 'Pro 4 FM 105.3 Mhz';

    // 3. Ekstrak Jam (Contoh: 15.00 - 16.00, 15.00 – 16.00, atau 06.00 s/d Selesai)
    if (preg_match('/(\d{2}[.:]\d{2})\s*(?:s\/d|-|–|—|sd)\s*(Selesai|\d{2}[.:]\d{2})?/i', $cleanDesc, $m)) {
        $data['jamMulai'] = str_replace('.', ':', $m[1]);
    }

    // 4. Pemandu
    if (preg_match('/(?:Presenter|Penyiar|Host)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['penyiar'] = trim(str_replace(['(Presenter)', '(Host)'], '', $m[1]));
    } elseif (preg_match('/Dipandu oleh:?\s*\r?\n([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['penyiar'] = trim(str_replace(['(Presenter)', '(Host)'], '', $m[1]));
    }

    // 5. PT Setting (Dikosongkan / tidak diambil dari deskripsi sesuai permintaan)
    $data['pt'] = [];

    // 6. PT Produksi / PA (Diambil dari label "Petugas Teknik" atau sejenisnya yang ada di deskripsi)
    if (preg_match('/(?:Petugas Teknik|Petugas PT(?: Setting)?|Pengarah Teknik)\s*[:\-]?\s*([^\n\r]+)|\bPT(?: Setting)?\b\s*[:\-]\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        $paString = !empty($m[1]) ? $m[1] : (isset($m[2]) ? $m[2] : '');
        $data['pa'] = mapPetugasToMasterRss($paString);
    } else {
        $data['pa'] = [];
    }
    

    // 7. Tema
    if (preg_match('/Tema\s*[:\-]?\s*"?([^"\n\r]+)"?/i', $cleanDesc, $m)) {
        $data['tema'] = trim($m[1], " \t\n\r\0\x0B\"");
    } else {
        $lines = preg_split('/\r\n|\r|\n/', $cleanDesc);
        $nonEmptyLines = array_values(array_filter(array_map('trim', $lines)));

        if (count($nonEmptyLines) >= 2) {
            $potensiTema = $nonEmptyLines[1];
            if (!preg_match('/(?:Senin|Selasa|Rabu|Kamis|Jum\'?at|Sabtu|Minggu|Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|\d{2}[.:]\d{2})/i', $potensiTema)) {
                $data['tema'] = trim($potensiTema, " \t\n\r\0\x0B\"'”’“‘");
            }
        }
    }

    // 8. Narasumber
    if (preg_match('/(?:Narasumber|Bersama)\s*[:\-]?\s*\r?\n?([\s\S]*?)(?=\r?\n\s*(?:Dipandu|Presenter|Penyiar|Host|Petugas|Telepon|WhatsApp|Dapatkan|PT|PA|Pengarah|Line|Pro\s*[124])|$)/i', $cleanDesc, $m)) {
        $rawNarasumber = ltrim(trim($m[1]), ": \t\n\r\0\x0B");
        $lines = explode("\n", $rawNarasumber);
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^[\p{So}\p{S}\p{P}\d]+\s*/u', '', $line);
            if (!empty($line)) {
                $cleanLines[] = $line;
            }
        }

        $data['narasumber'] = implode("\n", $cleanLines);
    }

    // 8. Narasumber (Mendukung label "Narasumber:" satu baris maupun multi-line ke bawah)
    if (preg_match('/Narasumber\s*[:\-]?\s*\r?\n?([\s\S]*?)(?=\r?\n\s*(?:Presenter|Penyiar|Host|Petugas|Pengarah|Telepon|WhatsApp|Dapatkan|PT|PA|Line|Pro\s*[124])|$)/i', $cleanDesc, $m)) {
        $rawNarasumber = ltrim(trim($m[1]), ": \t\n\r\0\x0B");
        $lines = explode("\n", $rawNarasumber);
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // Membersihkan penomoran ganda seperti "1. 1." atau simbol urut di awal baris
            $line = preg_replace('/^(\d+\.\s*)+/', '', $line);
            if (!empty($line)) {
                $cleanLines[] = $line;
            }
        }

        $data['narasumber'] = implode("\n", $cleanLines);
    } elseif (preg_match('/(?:Bersama)\s*[:\-]?\s*\r?\n?([\s\S]*?)(?=\r?\n\s*(?:Dipandu|Presenter|Penyiar|Host|Petugas|Telepon|WhatsApp|Dapatkan|PT|PA|Pengarah|Line|Pro\s*[124])|$)/i', $cleanDesc, $m)) {
        $rawNarasumber = ltrim(trim($m[1]), ": \t\n\r\0\x0B");
        $lines = explode("\n", $rawNarasumber);
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^[\p{So}\p{S}\p{P}\d]+\s*/u', '', $line);
            if (!empty($line)) {
                $cleanLines[] = $line;
            }
        }

        $data['narasumber'] = implode("\n", $cleanLines);
    }
    return $data;
}

// Fungsi untuk mencocokkan nama dengan master data (Fuzzy Matching)
// Fungsi untuk mencocokkan nama dengan master data (Fuzzy Matching) diambil dari master-data.json
function mapPetugasToMasterRss($rawString) {
    $masterPetugas = [];
    $jsonPath = __DIR__ . '/../data/master-data.json';
    
    // Membaca file master-data.json dan mengambil data pada key 'petugas'
    if (file_exists($jsonPath)) {
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        if (isset($jsonData['petugas']) && is_array($jsonData['petugas'])) {
            $masterPetugas = $jsonData['petugas'];
        }
    }

    $results = [];
    $rawNames = array_map('trim', preg_split('/[,&]|\bdan\b/i', $rawString));

    foreach ($rawNames as $name) {
        if (empty($name)) continue;

        $bestMatch = ucwords(strtolower($name));
        $highestSim = 0;
        $nameLower = strtolower($name);

        foreach ($masterPetugas as $master) {
            $masterLower = strtolower($master);

            if (strpos($nameLower, $masterLower) !== false) {
                $bestMatch = $master;
                break;
            }

            similar_text($nameLower, $masterLower, $percent);
            if ($percent > $highestSim) {
                $highestSim = $percent;
                if ($percent >= 65) {
                    $bestMatch = $master;
                }
            }
        }
        $results[] = $bestMatch;
    }
    return $results;
}

require_once __DIR__ . '/../api/youtube.php';

$base = '..'; $pageTitle = 'Rekap Youtube'; $activeMenu = 'gallery';

$videos = getYoutubeVideos();
$errorMessage = null;

if (!is_array($videos)) {
    $errorMessage = 'Gagal memuat data dari Notion.';
    $videos = [];
} elseif (isset($videos['error'])) {
    $errorMessage = $videos['error'];
    $videos = [];
}

include __DIR__ . '/../includes/header.php';
?>
<div id="galleryMainView">
<div class="page-head">
  <div>
    <h2>Rekap Youtube</h2>
    <p>Koleksi siaran yang telah tayang.</p>
  </div>
  <div class="gallery-header-actions">
    <div class="gallery-filter-group">
        <div class="filter-input-group filter-set">
            <span class="filter-prefix prefix-set">Set</span>
            <select id="filterPtSetting" class="header-filter-select" onchange="filterTable()">
                <option value="">Semua</option>
            </select>
        </div>
        <div class="filter-input-group filter-prod">
            <span class="filter-prefix prefix-prod">Prod</span>
            <select id="filterPtProduksi" class="header-filter-select" onchange="filterTable()">
                <option value="">Semua</option>
            </select>
        </div>
        <div class="filter-input-group filter-penyiar">
            <span class="filter-prefix prefix-penyiar">Host</span>
            <select id="filterPenyiar" class="header-filter-select" onchange="filterTable()">
                <option value="">Semua</option>
            </select>
        </div>
        <div class="filter-input-group filter-pro">
            <span class="filter-prefix prefix-pro">Pro</span>
            <select id="filterPrograma" class="header-filter-select" onchange="filterTable()">
                <option value="">Semua</option>
                <option value="Pro 1">Pro 1</option>
                <option value="Pro 2">Pro 2</option>
                <option value="Pro 4">Pro 4</option>
            </select>
        </div>
    </div>
    <button class="btn btn-primary" style="height:40px;" onclick="showAddView()"><i class="ri-add-line"></i> Tambah Video</button>
  </div>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-play-list-2-line"></i> Daftar Video</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="videoTable">
      <thead>
        <tr>
          <th>Thumbnail</th>
          <th>Judul Video</th>
          <th>Program</th>
          <th>Tanggal</th>
          <th>Jam</th>
          <th>Penyiar</th>
          <th>Narasumber</th>
          <th>Petugas PT Setting</th>
          <th>Petugas PT Produksi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($videos)): ?>
          <tr>
            <td colspan="10" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Data video gallery belum tersedia.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($videos as $video): ?>
            <?php
              $title = isset($video['title']) && $video['title'] !== '' ? $video['title'] : 'Tanpa Judul';
              $namaAcara = isset($video['namaAcara']) && $video['namaAcara'] !== '' ? $video['namaAcara'] : '';
              $tema = isset($video['tema']) ? $video['tema'] : '';
              $programa = isset($video['programa']) && $video['programa'] !== '' ? $video['programa'] : 'Umum';
              $programaColor = isset($video['programaColor']) ? $video['programaColor'] : 'gray';
              $date = isset($video['date']) && $video['date'] !== '' ? $video['date'] : 'TBA';
              $time = isset($video['time']) && $video['time'] !== '' ? $video['time'] : 'TBA';
              $url = isset($video['url']) && $video['url'] !== '' ? $video['url'] : '';
              $flyer = isset($video['flyer']) && $video['flyer'] !== '' ? $video['flyer'] : '';
              $penyiar = isset($video['penyiar']) && $video['penyiar'] !== '' ? $video['penyiar'] : '';
              $narasumber = isset($video['narasumber']) && is_array($video['narasumber']) ? $video['narasumber'] : [];
              $teknik = isset($video['teknik']) ? $video['teknik'] : '';
              $pa = isset($video['pa']) ? $video['pa'] : '';

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
              'tema' => $tema,
              'programa' => $programa,
              'programaColor' => $programaColor,
              'flyer' => $flyer,
              'date' => $date,
              'time' => $time,
              'url' => $url,
              'penyiar' => $penyiar,
              'narasumber' => $narasumber,
              'teknik' => $teknik,
              'pa' => $pa
            ]), ENT_QUOTES, 'UTF-8'); ?>
            <tr class="video-row" data-video="<?= $videoData ?>">
              <td>
                <?php if ($flyer !== ''): ?>
                  <img src="<?= htmlspecialchars($flyer, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" class="video-thumb">
                <?php else: ?>
                  <div class="video-thumb video-thumb-empty"><i class="ri-image-line"></i></div>
                <?php endif; ?>
              </td>
              <td class="cell-title">
                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($namaAcara !== ''): ?>
                  <div class="cell-sub"><?= htmlspecialchars($namaAcara, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="tag" style="background:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>1a; color:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; border:1px solid <?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>4d;">
                  <?= htmlspecialchars($programa, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td class="cell-sub"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="cell-sub"><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="cell-sub"><?= $penyiar !== '' ? htmlspecialchars($penyiar, ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-sub">
                <?php if (!empty($narasumber)): ?>
                    <ul style="margin: 0; padding-left: 1.2rem; list-style-type: disc;">
                    <?php foreach ($narasumber as $item): ?>
                        <li style="margin-bottom: 6px;"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    —
                <?php endif; ?>
                </td>
              <td class="cell-sub"><?= $teknik !== '' ? htmlspecialchars($teknik, ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-sub"><?= $pa !== '' ? htmlspecialchars($pa, ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-actions">
                <?php if ($url !== ''): ?>
                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="icon-action view" title="Buka di Youtube" onclick="event.stopPropagation();">
                  <i class="ri-external-link-line"></i>
                </a>
                <?php endif; ?>
                <?php if (getRole() === 'admin'): ?>
                <button type="button" class="icon-action edit" title="Edit" onclick="event.stopPropagation(); openEditView(JSON.parse(this.closest('tr').dataset.video));">
                  <i class="ri-edit-line"></i>
                </button>
                <button type="button" class="icon-action delete" title="Hapus" onclick="event.stopPropagation(); deleteVideoRow('<?= htmlspecialchars($video['id'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($title)) ?>');">
                  <i class="ri-delete-bin-line"></i>
                </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
  .gallery-header-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .gallery-filter-group { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }

  .filter-input-group {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 9px;
    height: 40px;
    width: 155px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    overflow: hidden;
  }
  
  .filter-prefix {
    padding: 0 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    height: 100%;
    display: flex;
    align-items: center;
    letter-spacing: 0.5px;
    user-select: none;
    flex-shrink: 0;
  }

  .filter-prefix.prefix-set { background: #eff6ff; color: #1d4ed8; border-right: 1px solid #dbeafe; }
  .filter-prefix.prefix-prod { background: #f5f3ff; color: #6d28d9; border-right: 1px solid #ddd6fe; }
  .filter-prefix.prefix-penyiar { background: #ecfdf5; color: #047857; border-right: 1px solid #a7f3d0; }
  .filter-prefix.prefix-pro { background: #fff7ed; color: #c2410c; border-right: 1px solid #fed7aa; }

  .filter-input-group.filter-set:hover { border-color: #93c5fd; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.1); }
  .filter-input-group.filter-prod:hover { border-color: #c4b5fd; box-shadow: 0 2px 6px rgba(139, 92, 246, 0.1); }
  .filter-input-group.filter-penyiar:hover { border-color: #6ee7b7; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1); }
  .filter-input-group.filter-pro:hover { border-color: #fde68a; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.1); }

  .filter-input-group.filter-set:focus-within { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
  .filter-input-group.filter-prod:focus-within { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15); }
  .filter-input-group.filter-penyiar:focus-within { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
  .filter-input-group.filter-pro:focus-within { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }

  .header-filter-select {
    height: 40px;
    width: 100%;
    flex: 1;
    min-width: 0;
    padding: 0 24px 0 8px;
    border: none;
    background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 6px center;
    background-size: 14px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-primary, #0f172a);
    cursor: pointer;
    outline: none;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
  }

  .video-thumb { width:64px; height:40px; object-fit:cover; border-radius:6px; display:block; }
  .video-thumb-empty { display:flex; align-items:center; justify-content:center; background:#e0e0e0; color:#999; font-size:1.1rem; }
  .video-row:hover { background:rgba(0,0,0,0.03); }
  .icon-action.view { color:#0ea5e9; background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; text-decoration:none; display:inline-flex; }
  .icon-action.view:hover { background:rgba(14,165,233,0.12); }
</style>
</div>

<div id="imageModal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.8); align-items:center; justify-content:center;" onclick="this.style.display='none'">
  <img id="imageModalSrc" src="" alt="Pop-up Flyer" style="max-width:90%; max-height:90%; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); cursor:zoom-out;">
</div>

<div id="addVideoView" style="display:none;">
  <div class="page-head">
    <div>
      <button class="btn btn-secondary" onclick="handleBackFromAddView()"><i class="ri-arrow-left-line"></i> Kembali</button>
    </div>
  </div>
  <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:24px;">
    <div>
      <h2 style="margin-bottom:4px;">Tambah Video</h2>
      <p style="color:var(--text-secondary); font-size: 0.95rem;">Tarik video dari RSS feed channel Youtube, cocokkan detailnya per baris, lalu simpan ke rekap.<br>Baris kosong bisa ditambah manual untuk video yang tidak muncul di RSS.</p>
    </div>
    <button type="button" class="btn btn-secondary" style="flex-shrink:0;" onclick="openImagePopup('../assets/images/shift/PT.jpeg')"><i class="ri-calendar-2-line"></i> Lihat Jadwal</button>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
      <div class="form-group" style="flex:2; min-width:250px; margin-bottom:0;">
        <label>ID Channel Youtube</label>
        <input type="text" id="rssChannelId" placeholder="Misal: UCPX... (cari di link channel)" value="UCa45qWSDXbdgLBjrGfQ7tmA">
      </div>
      <div class="form-group" style="flex:1; min-width:150px; margin-bottom:0;">
        <label>Pilih Tanggal</label>
        <input type="date" id="rssTargetDate" value="<?= date('Y-m-d') ?>">
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" onclick="tarikRss()"><i class="ri-refresh-line"></i> Tarik Data</button>
        <button type="button" class="btn btn-secondary" onclick="bersihkanTabel()"><i class="ri-eraser-line"></i> Clear Tabel</button>
        <button type="button" class="btn btn-primary" style="background:#16a34a;" onclick="simpanSemuaKeNotion()"><i class="ri-save-3-line"></i> Simpan Semua</button>
      </div>
      <div id="rssLoadingStatus" style="display:none; color:var(--primary); font-weight:600; align-self:center; font-size:13px;">Menghubungi Youtube &amp; mengekstrak data...</div>
    </div>

    <div class="table-wrap">
      <table class="data-table" id="rssTable">
        <!-- <thead>
          <tr>
            <th style="width:3%;">No.</th>
            <th style="width:19%;">Acara &amp; Tema</th>
            <th style="width:15%;">Jadwal &amp; Programa</th>
            <th style="width:20%;">Pemandu &amp; Narasumber</th>
            <th style="width:20%;">Petugas (PT Setting &amp; PT Produksi)</th>
            <th style="width:15%;">Media (URL &amp; Flyer)</th>
            <th style="width:8%;">Aksi</th>
          </tr>
        </thead> -->
        <tbody id="rssTableBody">
          <tr><td colspan="7" style="text-align:center; color:var(--text-secondary); padding:30px;">Belum ada data ditarik. Masukkan ID Channel dan klik Tarik Data.</td></tr>
        </tbody>
      </table>
    </div>

    <div style="padding:16px; display:flex; justify-content:flex-end;">
      <button type="button" class="btn btn-secondary" onclick="tambahBarisKosong()">+ Tambah Baris Kosong</button>
    </div>
  </div>
</div>

<div id="editVideoView" style="display:none;">
  <div class="page-head">
    <div>
      <button class="btn btn-secondary" onclick="hideEditView()"><i class="ri-arrow-left-line"></i> Kembali</button>
    </div>
  </div>
  <h2 id="addVideoTitle" style="margin-bottom:4px;">Edit Video</h2>
  <p style="color:var(--text-secondary); margin-bottom:24px; font-size: 0.95rem;">Perbarui detail video yang sudah ada di rekap.</p>

  <form id="addVideoForm" onsubmit="event.preventDefault(); submitAddVideo();">
    <input type="hidden" id="judulVideo" name="judul">
    <input type="hidden" id="namaAcaraDisplayHidden" name="namaAcaraDisplay">

    <div class="card" style="margin-bottom:20px;">
      <div class="table-wrap">
        <table class="data-table" id="editVideoTable">
          <!-- <thead>
            <tr>
              <th style="width:19%;">Acara &amp; Tema</th>
              <th style="width:15%;">Programa &amp; Jadwal</th>
              <th style="width:20%;">Pemandu &amp; Narasumber</th>
              <th style="width:20%;">Petugas (PT Setting &amp; PT Produksi)</th>
              <th style="width:18%;">Media (URL &amp; Flyer)</th>
              <th style="width:8%;">Aksi</th>
            </tr>
          </thead> -->
          <tbody>
            <tr>
              <td>
                <div class="row-group">
                  <label class="tabel-label">Nama Program / Acara</label>
                  <div id="editAcaraContainer"></div>
                  <input type="hidden" id="hidden_edit_acara" name="program">

                  <label class="tabel-label" style="margin-top:6px;">Tema Acara</label>
                  <textarea id="tema" name="tema" rows="3" placeholder="Contoh: MPLS Ramah 2026, Tanpa Perploncoan" style="resize:none;"></textarea>
                </div>
              </td>
              <td>
                <div class="row-group">
                  <label class="tabel-label">Live Dimana (Programa)</label>
                  <select id="live" name="live" onchange="handleLiveChange();" required>
                    <option value="94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official">Pro 1 FM 94.6 Mhz</option>
                    <option value="87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official">Pro 2 FM 87.9 Mhz</option>
                    <option value="105.3 FM Pro 4 RRI Malang dan Youtube RRI Malang Official">Pro 4 FM 105.3 Mhz</option>
                  </select>

                  <label class="tabel-label" style="margin-top:6px;">Tanggal</label>
                  <input type="date" id="tanggal" name="tanggal" required>

                  <label class="tabel-label">Jam Mulai</label>
                  <input type="time" id="jamMulai" name="jamMulai" required>
                </div>
              </td>
              <td>
                <div class="row-group">
                  <label class="tabel-label">Pemandu Acara</label>
                  <div id="editPemanduContainer"></div>
                  <input type="hidden" id="hidden_edit_pemandu" name="penyiar">

                  <label class="tabel-label" style="margin-top:6px;">Narasumber</label>
                  <div id="editNarasumberContainer" style="display:flex; flex-direction:column; gap:4px;"></div>
                  <button type="button" onclick="addNarasumberFieldEdit()" style="margin-top:4px; font-size:11px; padding:4px 8px; cursor:pointer; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; color:#334155; font-weight:600;">+ Tambah Narasumber</button>
                </div>
              </td>
              <td>
                <div class="row-group">
                  <label class="tabel-label">Petugas PT Setting</label>
                  <!-- Container untuk custom dropdown -->
                  <div id="teknikContainer"></div>
                  <input type="hidden" id="teknik" name="teknik">

                  <label class="tabel-label" style="margin-top:6px;">Petugas PT Produksi</label>
                  <!-- Container untuk custom dropdown -->
                  <div id="paContainer"></div>
                  <input type="hidden" id="pa" name="pa">
                </div>
              </td>
              <td>
                <div class="row-group">
                  <label class="tabel-label">Link Youtube</label>
                  <input type="url" name="link" placeholder="https://youtube.com/...">

                  <label class="tabel-label" style="margin-top:6px;">Flyer / Thumbnail</label>
                  <div id="dropzone" class="dropzone-mini" style="padding:0; overflow:hidden; position:relative; min-height:120px; display:flex; align-items:center; justify-content:center;" onclick="document.getElementById('field_flyer').click()">
                    <input type="file" id="field_flyer" name="flyer" accept="image/*" style="display:none;" onchange="handleFileSelectEdit(this.files)">
                    <span style="padding:10px;">Pilih / Tarik Flyer</span>
                  </div>
                </div>
              </td>
              <td style="vertical-align:top; text-align:center; padding-top:14px;">
                <button type="submit" id="addVideoSubmitBtn" class="btn btn-success" style="background:#16a34a; color:#fff; border:none; border-radius:4px; padding:9px 12px; cursor:pointer; font-weight:600;">Simpan Perubahan</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </form>
</div>

<div id="descToast" style="display:none; position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 16px; border-radius:4px; font-size:14px; z-index:1000;">Teks berhasil disalin ke clipboard!</div>

<script>

// ===== Data video yang sudah tersimpan di Notion (diinjek dari PHP) =====
// Pengecekan menggunakan video ID YouTube — lebih reliable dari URL penuh
<?php
    $savedIds = [];
    $savedMap = [];
    if (is_array($videos)) {
        foreach ($videos as $v) {
            $url = isset($v['url']) ? trim($v['url']) : '';
            if (empty($url) || $url === '#') continue;
            // Ekstrak video ID dari URL YouTube
            // Mendukung: ?v=xxx, /live/xxx, youtu.be/xxx
            $videoId = '';
            $parsed = parse_url($url);
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $params);
                $videoId = $params['v'] ?? '';
            }
            // Format /live/xxx atau /shorts/xxx atau youtu.be/xxx
            if (empty($videoId) && !empty($parsed['path'])) {
                $path = trim($parsed['path'], '/');
                $parts = explode('/', $path);
                $last = end($parts);
                if (!empty($last) && strlen($last) > 5) {
                    $videoId = $last;
                }
            }
            if (!empty($videoId)) {
                $savedIds[] = $videoId;
                $savedMap[$videoId] = $v;
            }
        }
    }
?>
const notionSavedVideoIds = <?= json_encode(array_values(array_unique($savedIds))) ?>;
const notionSavedVideosMap = <?= json_encode($savedMap) ?>;
console.log('[notionSavedVideoIds] ID video tersimpan di Notion:', notionSavedVideoIds);

// Ekstrak video ID dari URL YouTube
// Mendukung format:
//   youtube.com/watch?v=xxx
//   youtube.com/live/xxx
//   youtu.be/xxx
function extractYoutubeId(url) {
    if (!url) return '';
    try {
        var u = new URL(url);
        // Format: youtube.com/watch?v=xxx
        var v = u.searchParams.get('v');
        if (v) return v;
        // Format: youtube.com/live/xxx atau youtu.be/xxx
        var path = u.pathname.replace(/^\//, ''); // hapus leading slash
        // path bisa "live/ryajG5eSi6g" atau langsung "ryajG5eSi6g"
        var parts = path.split('/');
        var last = parts[parts.length - 1];
        if (last && last.length > 5) return last;
    } catch(e) {}
    // Fallback regex untuk format ?v= atau /live/ atau youtu.be/
    var m = url.match(/[?&]v=([^&]+)/) ||
            url.match(/\/live\/([^/?&]+)/) ||
            url.match(/youtu\.be\/([^/?&]+)/);
    return m ? m[1] : '';
}

// Cek apakah video dari RSS sudah ada di Notion berdasarkan video ID.
function isAlreadySaved(vidUrl, vidVideoId) {
    var id = vidVideoId || extractYoutubeId(vidUrl);
    console.log('[isAlreadySaved] url:', vidUrl, '| video_id dari RSS:', vidVideoId, '| id dipakai:', id, '| sudah ada:', notionSavedVideoIds.indexOf(id) !== -1);
    if (!id) return false;
    return notionSavedVideoIds.indexOf(id) !== -1;
}

// ===== Tambahkan State untuk file uploads (Kloning dari troubleshoot.php) =====
let selectedFiles = []; 
let editingPageId = null;

// ===== Master Data (Nama Acara & Nama Pemandu per Programa) =====
// Perbaikan: Ubah 'teknik' dan 'pa' menjadi array tunggal 'petugas' sesuai JSON
let masterData = { acara: { 'Pro 1': [], 'Pro 2': [], 'Pro 4': [] }, pemandu: { 'Pro 1': [], 'Pro 2': [], 'Pro 4': [] }, petugas: [] };
let masterDataLoaded = false;

async function loadMasterData(){
    try {
        const res = await fetch('../api/get-master-data.php');
        const data = await res.json();
        if (data.success) {
            // Perbaikan: Ambil data.petugas langsung dari hasil respons JSON
            masterData = { acara: data.acara, pemandu: data.pemandu, petugas: data.petugas };
            initFilterPetugas(); // Inisialisasi dropdown filter petugas setelah data dimuat
        } else {
            console.error('Gagal memuat master data:', data.error);
        }
    } catch (err) {
        console.error('Gagal memuat master data:', err);
    } finally {
        masterDataLoaded = true;
        // Nama Acara & Pemandu sudah tidak butuh "pre-populate" di sini --
        // keduanya sekarang dibangun on-demand langsung dari masterData lewat
        // createRssSingleSelect()/createRssMultiCreatableSelect() saat form
        // Edit atau tabel RSS dibuka. Yang masih perlu di-refresh di sini
        // cuma widget Petugas Teknik & PA (flat list, bukan grouped).
        populateTeknikOptions();
        populatePaOptions();
    }
}

// Fungsi untuk mengisi dropdown filter petugas & penyiar saat master data selesai dimuat
function initFilterPetugas() {
    initFilters();
}

function initFilters() {
    var ptSettingSelect = document.getElementById('filterPtSetting');
    var ptProduksiSelect = document.getElementById('filterPtProduksi');
    var penyiarSelect = document.getElementById('filterPenyiar');

    if (!ptSettingSelect || !ptProduksiSelect || !penyiarSelect) return;

    var ptSettingSet = new Set(masterData.petugas || []);
    var ptProduksiSet = new Set(masterData.petugas || []);
    
    var penyiarSet = new Set();
    if (masterData.pemandu) {
        Object.values(masterData.pemandu).forEach(function(list) {
            if (Array.isArray(list)) {
                list.forEach(function(name) { if (name) penyiarSet.add(name.trim()); });
            }
        });
    }

    // Scan table rows for any additional names in current video data
    var rows = document.querySelectorAll('.video-row');
    rows.forEach(function(row) {
        try {
            var v = JSON.parse(row.dataset.video);
            if (v.teknik) {
                v.teknik.split(/[,&]|\bdan\b/i).forEach(function(n) {
                    n = n.trim();
                    if (n) ptSettingSet.add(n);
                });
            }
            if (v.pa) {
                v.pa.split(/[,&]|\bdan\b/i).forEach(function(n) {
                    n = n.trim();
                    if (n) ptProduksiSet.add(n);
                });
            }
            if (v.penyiar) {
                v.penyiar.split(/[,&]|\bdan\b/i).forEach(function(n) {
                    n = n.trim();
                    if (n) penyiarSet.add(n);
                });
            }
        } catch (e) {}
    });

    populateSelectOptions(ptSettingSelect, Array.from(ptSettingSet).sort(), 'Semua');
    populateSelectOptions(ptProduksiSelect, Array.from(ptProduksiSet).sort(), 'Semua');
    populateSelectOptions(penyiarSelect, Array.from(penyiarSet).sort(), 'Semua');
}

function populateSelectOptions(selectEl, itemsArray, defaultLabel) {
    var currentValue = selectEl.value;
    selectEl.innerHTML = '';
    
    var defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = defaultLabel;
    selectEl.appendChild(defaultOpt);

    itemsArray.forEach(function(name) {
        if (!name) return;
        var opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        if (name === currentValue) opt.selected = true;
        selectEl.appendChild(opt);
    });
}

function getProgramaLabelFromLive(){
    var liveVal = document.getElementById('live').value;
    if (liveVal.indexOf('Pro 1') !== -1) return 'Pro 1';
    if (liveVal.indexOf('Pro 2') !== -1) return 'Pro 2';
    if (liveVal.indexOf('Pro 4') !== -1) return 'Pro 4';
    return 'Pro 1';
}

// Isi ulang dropdown multi-select Petugas Teknik (PT) sesuai Programa terpilih.
// --- FUNGSI CUSTOM MULTISELECT DROPDOWN (TAMPILAN SEPERTI GAMBAR) ---
function createCustomMultiSelect(containerId, hiddenInputId, options) {
    var container = document.getElementById(containerId);
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    
    // Ambil nilai yang mungkin sudah ada (misal saat edit video)
    var selectedValues = hiddenInput.value ? hiddenInput.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean) : [];

    // 1. Kotak Pilih (Visual pengganti select)
    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'width:100%; box-sizing:border-box; min-height:32px; border:1px solid #ced4da; border-radius:4px; padding:4px 24px 4px 8px; background:#fff; cursor:pointer; display:flex; align-items:center; position:relative; font-size:12px; font-weight:600; color:#334155;';

    // Panah dropdown
    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';
    selectBox.appendChild(arrow);

    // 2. Menu Dropdown (Search + Checkbox List)
    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu'; // Untuk identifikasi saat klik di luar
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    // Baris Search
    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:8px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search...';
    searchInput.style.cssText = 'width:100%; padding:6px 10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; outline:none; font-size:13px;';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    // Daftar List
    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:200px; overflow-y:auto; padding:4px 0;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    // Render Tag / Chip di kotak pilih
    function renderChips() {
        // Bersihkan area selectBox (kecuali panah)
        var elementsToRemove = [];
        for(var i = 0; i < selectBox.children.length; i++) {
            if (selectBox.children[i] !== arrow) elementsToRemove.push(selectBox.children[i]);
        }
        elementsToRemove.forEach(function(el){ selectBox.removeChild(el); });

        if (selectedValues.length === 0) {
            var placeholder = document.createElement('span');
            placeholder.textContent = 'Pilih petugas...';
            placeholder.style.cssText = 'color:#6c757d; font-size:14px;';
            selectBox.insertBefore(placeholder, arrow);
        } else {
            selectedValues.forEach(function(val) {
                var chip = document.createElement('span');
                // Styling mirip tag/chip seperti di referensi gambar
                chip.style.cssText = 'background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:4px; font-size:12px; display:inline-flex; align-items:center; gap:6px; border:1px solid #cbd5e1;';
                chip.textContent = val;
                
                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'cursor:pointer; font-size:14px; font-weight:bold; color:#64748b;';
                closeBtn.onclick = function(e) {
                    e.stopPropagation();
                    toggleValue(val, false);
                };
                chip.appendChild(closeBtn);
                selectBox.insertBefore(chip, arrow);
            });
        }
        
        hiddenInput.value = selectedValues.join(', ');
    }

    // Toggle (Centang / Hapus Centang)
    function toggleValue(val, isChecked) {
        if (isChecked && !selectedValues.includes(val)) {
            selectedValues.push(val);
        } else if (!isChecked) {
            selectedValues = selectedValues.filter(function(v){ return v !== val; });
        }
        renderChips();
        renderList(searchInput.value); // Update centang di list
    }

    // Render daftar nama (dengan filter search)
    // Render daftar nama (dengan filter search)
    function renderList(filterText) {
        if (!filterText) filterText = '';
        listDiv.innerHTML = '';
        listDiv.style.textAlign = 'left'; // Paksa bungkus utama ke kiri
        
        var filtered = options.filter(function(o){ 
            return o.toLowerCase().indexOf(filterText.toLowerCase()) !== -1; 
        });
        
        if (filtered.length === 0) {
            var empty = document.createElement('div');
            empty.textContent = 'Tidak ditemukan';
            empty.style.cssText = 'padding:8px; color:#999; text-align:center; font-size:13px;';
            listDiv.appendChild(empty);
            return;
        }

        filtered.forEach(function(opt) {
            var label = document.createElement('label');
            // Gunakan !important, width: 100%, dan flex-direction: row untuk melawan CSS template
            label.style.cssText = 'display:flex !important; flex-direction:row !important; align-items:center !important; justify-content:flex-start !important; text-align:left !important; padding:6px 12px !important; cursor:pointer !important; font-size:14px !important; margin:0 !important; width:100% !important; box-sizing:border-box !important; transition:background 0.2s;';
            label.onmouseover = function() { label.style.background = '#f8f9fa'; };
            label.onmouseout = function() { label.style.background = 'transparent'; };

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = opt;
            cb.checked = selectedValues.includes(opt);
            // Kunci posisi checkbox persis di ujung kiri
            cb.style.cssText = 'margin:0 10px 0 0 !important; position:static !important; float:none !important; width:auto !important; flex-shrink:0 !important;';
            
            cb.onchange = function(e) {
                e.stopPropagation();
                toggleValue(opt, e.target.checked);
            };

            // Bungkus teks dalam span agar tidak terpengaruh margin label bawaan
            var textSpan = document.createElement('span');
            textSpan.textContent = opt;
            textSpan.style.cssText = 'flex-grow:1 !important; text-align:left !important; margin:0 !important;';

            label.appendChild(cb);
            label.appendChild(textSpan); 
            listDiv.appendChild(label);
        });
    }

    // Event Klik untuk Buka/Tutup Dropdown
    selectBox.onclick = function() {
        var isOpen = dropdown.style.display === 'block';
        
        // Tutup semua dropdown custom lain yang sedang terbuka
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ d.style.display = 'none'; });
        
        if (!isOpen) {
            dropdown.style.display = 'block';
            searchInput.focus();
            renderList(searchInput.value);
        }
    };

    // Event Ketik di Search
    searchInput.oninput = function(e) {
        renderList(e.target.value);
    };

    renderChips();
    renderList('');
}

// Tutup dropdown jika user klik di luar area
document.addEventListener('click', function(e) {
    if (!e.target.closest('#teknikContainer') && !e.target.closest('#paContainer') &&
        !e.target.closest('[id^="pt_"]') && !e.target.closest('[id^="pa_"]')) {
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ 
            d.style.display = 'none'; 
        });
    }
});

// Panggil fungsi pembuat UI di fungsi standar yang sudah dipanggil sistem Anda
function populateTeknikOptions() {
    var names = (masterData.petugas) ? masterData.petugas : [];
    createCustomMultiSelect('teknikContainer', 'teknik', names);
}

function populatePaOptions() {
    var names = (masterData.petugas) ? masterData.petugas : [];
    createCustomMultiSelect('paContainer', 'pa', names);
}

// Dipanggil saat Live Dimana (Programa) berubah: isi ulang dropdown Nama Acara & Nama Pemandu
function handleLiveChange(){
    populateTeknikOptions();
    populatePaOptions();
}

document.addEventListener('DOMContentLoaded', () => {
  loadMasterData();
  initFilters();

  const dropzone = document.getElementById('dropzone');
  if (dropzone) {
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', e => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        var input = document.getElementById('field_flyer');
        input.files = files;
        handleFileSelectEdit(input.files);
      }
    });
  }
});

// ===== Handle Pilihan File dari Dropzone (Form Edit) =====
// Persis meniru pola handleFlyerSelect() milik baris tabel Tambah Video,
// hanya tanpa suffix index karena form edit cuma untuk 1 video.
function handleFileSelectEdit(files) {
  var file = files && files[0];
  if (!file) return;
  if (!file.type.startsWith('image/')) { alert('Harap pilih file gambar.'); return; }
  if (file.size > 10 * 1024 * 1024) { alert('Ukuran gambar maksimal 10MB.'); return; }

  selectedFiles = [file]; // Flyer video hanya butuh 1, reset setiap pilih baru

  var reader = new FileReader();
  reader.onload = function(e) {
    renderFlyerPreviewEdit(e.target.result);
  };
  reader.readAsDataURL(file);
}

// Menampilkan gambar di dalam kotak dropzone-mini + tombol hapus,
// persis seperti tampilan flyer pada baris tabel Tambah Video.
function renderFlyerPreviewEdit(src) {
  var dropzone = document.getElementById('dropzone');
  if (!dropzone) return;

  Array.from(dropzone.children).forEach(function(child) {
    if (child.tagName !== 'INPUT') dropzone.removeChild(child);
  });

  var img = document.createElement('img');
  img.src = src;
  img.style.cssText = "width:100%; height:100%; object-fit:cover; position:absolute;";
  img.alt = "Flyer";
  img.onclick = function(event) { event.stopPropagation(); openImagePopup(img.src); };

  var btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'remove-flyer-btn';
  btn.innerHTML = '&times;';
  btn.onclick = function(event) { hapusFlyerEdit(event); };

  dropzone.appendChild(img);
  dropzone.appendChild(btn);
}

function hapusFlyerEdit(event) {
  if (event) event.stopPropagation();

  var dropzone = document.getElementById('dropzone');
  var input = document.getElementById('field_flyer');
  if (input) input.value = '';
  selectedFiles = [];

  Array.from(dropzone.children).forEach(function(child) {
    if (child.tagName !== 'INPUT') dropzone.removeChild(child);
  });

  var span = document.createElement('span');
  span.style.padding = '10px';
  span.textContent = 'Pilih / Tarik Flyer';
  dropzone.appendChild(span);
}

// ===== Narasumber: daftar input dinamis (Form Edit) =====
// Persis meniru pola addNarasumberField() milik baris tabel Tambah Video,
// hanya tanpa suffix index karena form edit cuma untuk 1 video.
function addNarasumberFieldEdit(value) {
  if (value === undefined) value = '';
  var container = document.getElementById('editNarasumberContainer');
  if (!container) return;

  var wrapper = document.createElement('div');
  wrapper.style.cssText = 'display:flex; gap:4px; align-items:center;';

  var input = document.createElement('input');
  input.type = 'text';
  input.placeholder = 'Nama Narasumber & Jabatan';
  input.value = value;
  input.style.cssText = 'flex:1; padding:6px 8px; border:1px solid #ced4da; border-radius:4px; font-size:12px;';

  var btnRemove = document.createElement('button');
  btnRemove.type = 'button';
  btnRemove.innerHTML = '&times;';
  btnRemove.title = "Hapus baris ini";
  btnRemove.style.cssText = 'background:#ef4444; color:white; border:none; border-radius:4px; padding:0 8px; height:28px; cursor:pointer; font-weight:bold; font-size:14px;';
  btnRemove.onclick = function() { container.removeChild(wrapper); };

  wrapper.appendChild(input);
  wrapper.appendChild(btnRemove);
  container.appendChild(wrapper);
}

// ===== Fungsi Asynchronous Upload (Sesuai spesifikasi upload-to-notion.php Anda) =====
async function uploadFiles(files) {
  if (files.length === 0) return [];
  const formData = new FormData();
  
  // SINKRONISASI: Menggunakan key 'dokumentasi[]' agar terbaca oleh file upload-to-notion.php Anda
  files.forEach(f => formData.append('dokumentasi[]', f)); 
  
  const res = await fetch('../api/upload-to-notion.php', { method: 'POST', body: formData });
  const data = await res.json();
  if (!data.success) throw new Error(data.error || 'Upload ke Notion gagal');
  
  // Mengembalikan data hasil upload dari Notion (id, original)
  return data.files.map(f => ({ id: f.id, name: f.original })); 
}

// Kebalikan dari format gabungan "00.00-00.00" yang disimpan di Notion:
// pecah jadi { mulai, selesai } dalam format "HH:MM" untuk <input type="time">.
function parseJamGabungToInputs(gabung){
    if (!gabung) return { mulai: '', selesai: '' };
    var toHHMM = function(s){
        s = String(s).trim();
        var m = s.match(/^(\d{1,2})[.:](\d{2})$/);
        if (!m) return '';
        var hh = m[1].length < 2 ? '0' + m[1] : m[1];
        return hh + ':' + m[2];
    };
    var parts = String(gabung).split('-');
    return {
        mulai: toHHMM(parts[0]),
        selesai: parts.length > 1 ? toHHMM(parts[1]) : ''
    };
}

// Kebalikan dari formatTanggalIndonesia: menerima ISO ("2026-07-17"),
// format Indonesia ("17 Juli 2026"), atau string tanggal lain yang bisa
// di-parse Date(), lalu selalu mengembalikan "YYYY-MM-DD" untuk <input type="date">.
function parseTanggalToISO(str){
    if (!str) return '';
    str = String(str).trim();

    var isoMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) return isoMatch[1] + '-' + isoMatch[2] + '-' + isoMatch[3];

    var bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var parts = str.split(' ');
    if (parts.length === 3) {
        var day = parseInt(parts[0], 10);
        var monthIdx = bulanIndo.findIndex(function(m){ return m.toLowerCase() === parts[1].toLowerCase(); });
        var year = parseInt(parts[2], 10);
        if (!isNaN(day) && monthIdx !== -1 && !isNaN(year)) {
            var mm = (monthIdx + 1) < 10 ? '0' + (monthIdx + 1) : '' + (monthIdx + 1);
            var dd = day < 10 ? '0' + day : '' + day;
            return year + '-' + mm + '-' + dd;
        }
    }

    var d = new Date(str);
    if (!isNaN(d.getTime())) {
        var yyyy = d.getFullYear();
        var mo = (d.getMonth() + 1) < 10 ? '0' + (d.getMonth() + 1) : '' + (d.getMonth() + 1);
        var da = d.getDate() < 10 ? '0' + d.getDate() : '' + d.getDate();
        return yyyy + '-' + mo + '-' + da;
    }
    return '';
}

function showAddView(){
    document.getElementById('galleryMainView').style.display = 'none';
    document.getElementById('addVideoView').style.display = 'block';
    window.scrollTo(0, 0);

    // Reset tabel draft RSS ke kondisi awal setiap kali dibuka
    document.getElementById('rssTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-secondary); padding:30px;">Belum ada data ditarik. Masukkan ID Channel dan klik Tarik Data.</td></tr>';
}

function openEditView(videoData){
//   console.log("Data Video dari Notion:", videoData);
    editingPageId = videoData.id;

    document.getElementById('galleryMainView').style.display = 'none';
    document.getElementById('editVideoView').style.display = 'block';
    window.scrollTo(0, 0);

    document.getElementById('addVideoForm').reset();
    selectedFiles = [];

    // ---- Live Dimana (Programa) : diset LEBIH DULU karena menentukan isi dropdown Acara & Pemandu ----
    var liveSelect = document.getElementById('live');
    var liveIndexByProgram = { 'Pro 1': 0, 'Pro 2': 1, 'Pro 4': 2 };
    if (videoData.programa in liveIndexByProgram) {
        liveSelect.selectedIndex = liveIndexByProgram[videoData.programa];
    }

    // ---- Program / Nama Acara (dropdown diisi sesuai Programa di atas) ----
    var namaAcara = videoData.namaAcara || '';
    document.getElementById('hidden_edit_acara').value = namaAcara;
    createRssSingleSelect('editAcaraContainer', 'hidden_edit_acara', masterData.acara || {}, 'Pilih Acara');

    // ---- Pemandu Acara (dropdown diisi sesuai Programa di atas) ----
    var namaPenyiar = videoData.penyiar || '';
    document.getElementById('hidden_edit_pemandu').value = namaPenyiar;
    createRssMultiCreatableSelect('editPemanduContainer', 'hidden_edit_pemandu', masterData.pemandu || {}, 'Pilih Pemandu');

    // ---- Petugas Teknik & PA ----
    // 1. Masukkan data dari database ke dalam hidden input
    document.getElementById('teknik').value = videoData.teknik || '';
    document.getElementById('pa').value = videoData.pa || '';

    // 2. Baru panggil pembentuk UI (skrip kita otomatis membaca hidden input di atas)
    populateTeknikOptions();
    populatePaOptions();

    // ---- Tema (diambil langsung dari properti "Tema Acara" di Notion) ----
    var tema = videoData.tema || '';

    if (tema.trim() === '' && videoData.title) {
        // Fallback untuk data lama yang belum punya properti Tema Acara terisi --
        // coba turunkan dari judul "Nama Acara - Tema".
        var rawTitle = videoData.title;
        var normNamaAcara = (namaAcara || '').trim();
        var exactRegex = normNamaAcara !== ''
            ? new RegExp('^' + normNamaAcara.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s*[-–—]\\s*')
            : null;

        if (exactRegex && exactRegex.test(rawTitle)) {
            tema = rawTitle.replace(exactRegex, '');
        } else {
            var sepMatch = rawTitle.match(/\s[-–—]\s/);
            if (sepMatch) {
                tema = rawTitle.substring(sepMatch.index + sepMatch[0].length);
            }
        }
    }
    document.getElementById('tema').value = tema.trim();

    // ---- Tanggal & Jam ----
    document.getElementById('tanggal').value = parseTanggalToISO(videoData.date);
    var jamParsed = parseJamGabungToInputs(videoData.time);
    document.getElementById('jamMulai').value = jamParsed.mulai;

    // ---- Narasumber (daftar input dinamis, persis seperti form Tambah Video) ----
    var editNarasumberContainer = document.getElementById('editNarasumberContainer');
    editNarasumberContainer.innerHTML = '';
    var narasumberArr = Array.isArray(videoData.narasumber) ? videoData.narasumber : [];
    if (narasumberArr.length > 0) {
        narasumberArr.forEach(function(nama){ addNarasumberFieldEdit(nama); });
    } else {
        addNarasumberFieldEdit('');
    }

    // ---- Link Youtube ----
    var linkInput = document.querySelector('#addVideoForm input[name="link"]');
    if (linkInput) linkInput.value = (videoData.url && videoData.url !== '#') ? videoData.url : '';

    // ---- Flyer: tampilkan yang sudah ada via dropzone-mini, ganti hanya jika user pilih foto baru ----
    var editDropzone = document.getElementById('dropzone');
    Array.from(editDropzone.children).forEach(function(child) {
        if (child.tagName !== 'INPUT') editDropzone.removeChild(child);
    });
    if (videoData.flyer) {
        renderFlyerPreviewEdit(videoData.flyer);
    } else {
        var placeholder = document.createElement('span');
        placeholder.style.padding = '10px';
        placeholder.textContent = 'Pilih / Tarik Flyer';
        editDropzone.appendChild(placeholder);
    }
}

function hideAddView(){
    document.getElementById('addVideoView').style.display = 'none';
    document.getElementById('galleryMainView').style.display = 'block';
}

// Fungsi untuk menangani klik tombol Kembali pada form Tambah Video
function handleBackFromAddView() {
    // Cari tombol Simpan di dalam tabel yang statusnya sudah 'Tersimpan'
    var savedButtons = document.querySelectorAll('#rssTableBody .btn-success[disabled]');
    var isAnySaved = false;

    savedButtons.forEach(function(btn) {
        if (btn.textContent.trim() === 'Tersimpan') {
            isAnySaved = true;
        }
    });

    // Jika ada baris yang tersimpan, refresh halaman agar data rekap diperbarui
    if (isAnySaved) {
        location.reload();
    } else {
        // Jika belum ada data yang tersimpan, kembali ke tampilan rekap tanpa refresh
        hideAddView();
    }
}

function hideEditView(){
    document.getElementById('editVideoView').style.display = 'none';
    document.getElementById('galleryMainView').style.display = 'block';
    document.getElementById('addVideoForm').reset();

    selectedFiles = [];
    flyerBase64Data = null;
    flyerFileName = '';

    // Reset dropzone-mini kembali ke placeholder
    var dropzone = document.getElementById('dropzone');
    if (dropzone) {
        Array.from(dropzone.children).forEach(function(child) {
            if (child.tagName !== 'INPUT') dropzone.removeChild(child);
        });
        var placeholder = document.createElement('span');
        placeholder.style.padding = '10px';
        placeholder.textContent = 'Pilih / Tarik Flyer';
        dropzone.appendChild(placeholder);
    }

    // Reset daftar narasumber dinamis
    var narasumberContainer = document.getElementById('editNarasumberContainer');
    if (narasumberContainer) narasumberContainer.innerHTML = '';

    editingPageId = null;
}

// UBAH MENJADI ASYNC FUNCTION
async function submitAddVideo(){
    var form = document.getElementById('addVideoForm');
    var isEditMode = !!editingPageId;

    var formData = new FormData(form);
    var plainObject = {};
    formData.forEach(function(value, key){
        if (key !== 'flyer') {
            plainObject[key] = typeof value === 'string' ? value.trim() : value;
        }
    });

    // AMBIL NILAI DARI HIDDEN INPUT KUSTOM (Baik untuk mode Edit maupun Tambah jika menggunakan kustom)
    var hiddenAcara = document.getElementById('hidden_edit_acara');
    var hiddenPemandu = document.getElementById('hidden_edit_pemandu');
    
    // Fallback jika form edit aktif atau menggunakan elemen kustom
    var finalAcara = hiddenAcara ? hiddenAcara.value.trim() : (plainObject['program'] || '');
    var finalPemandu = hiddenPemandu ? hiddenPemandu.value.trim() : (plainObject['penyiar'] || '');

    plainObject['program'] = finalAcara;
    plainObject['namaAcaraDisplay'] = finalAcara;
    plainObject['penyiar'] = finalPemandu;

    // ---- Kumpulkan Narasumber dari daftar input dinamis ----
    // (textarea "narasumber" sudah diganti daftar input, jadi tidak lagi
    // otomatis ikut terbawa oleh FormData(form) di atas)
    var narasumberContainerEl = document.getElementById('editNarasumberContainer');
    var narasumberArrSubmit = [];
    if (narasumberContainerEl) {
        narasumberContainerEl.querySelectorAll('input[type="text"]').forEach(function(inp) {
            if (inp.value.trim() !== '') narasumberArrSubmit.push(inp.value.trim());
        });
    }
    plainObject['narasumber'] = narasumberArrSubmit.join('\n');

    // Set field 'judul' agar murni diisi nilai 'tema'
    plainObject['judul'] = plainObject['tema'] || '';

    var missing = [];
    if (!plainObject.namaAcaraDisplay) missing.push('Nama Program / Acara');
    if (!plainObject.tanggal) missing.push('Tanggal');
    if (!plainObject.jamMulai) missing.push('Jam Mulai');
    if (!plainObject.live) missing.push('Live Dimana');

    if (missing.length > 0) {
        alert('Mohon lengkapi dulu: ' + missing.join(', '));
        return;
    }

    var submitBtn = form.querySelector('button[type="submit"]');
    var originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';

    try {
        let uploadedFilesPayload = [];
        
        // Jalankan pre-upload ke Notion via AWS S3 (jika ada file baru di dropzone)
        if (selectedFiles.length > 0) {
            submitBtn.textContent = 'Menyimpan Gambar ke Notion S3...';
            uploadedFilesPayload = await uploadFiles(selectedFiles);
        }

        plainObject['flyer_urls'] = uploadedFilesPayload;
        submitBtn.textContent = 'Menyimpan ke Database...';

        var endpoint = '../api/add-video.php';
        if (isEditMode) {
            plainObject['page_id'] = editingPageId;
            endpoint = '../api/edit-video.php';
        }

        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(plainObject)
        });

        const data = await res.json();
        if (data.success) {
            alert(isEditMode ? 'Perubahan berhasil disimpan.' : 'Video berhasil disimpan ke Notion.');
            hideEditView();
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + (data.error || data.details || 'Kesalahan tidak diketahui'));
        }
    } catch (err) {
        console.error('Error:', err);
        alert('Terjadi kesalahan: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
}

// Hapus video langsung dari baris tabel (tanpa buka modal detail)
function deleteVideoRow(pageId, title) {
    if (!confirm(`Hapus video "${title}" dari rekap?`)) return;

    fetch('../api/delete-video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ page_id: pageId })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Video berhasil dihapus.');
            location.reload();
        } else {
            alert('Gagal menghapus: ' + (data.error || data.details || 'Kesalahan tidak diketahui'));
        }
    })
    .catch(function(err) {
        alert('Terjadi kesalahan jaringan: ' + err);
    });
}

const currentUserRole = <?= json_encode(getRole()) ?>;
let currentFilterController = null;

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function renderVideoRows(videos) {
    const tbody = document.querySelector('#videoTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!videos || videos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
                    Data video gallery tidak ditemukan di Notion untuk filter ini.
                </td>
            </tr>
        `;
        return;
    }

    const colorMap = {
        'gray': '#64748b', 'brown': '#92400e', 'orange': '#f59e0b',
        'yellow': '#fbbf24', 'green': '#10b981', 'blue': '#2563eb',
        'purple': '#8b5cf6', 'pink': '#ec4899', 'red': '#ef4444',
        'default': '#64748b'
    };

    videos.forEach(function(video) {
        var title = (video.title && video.title !== '') ? video.title : 'Tanpa Judul';
        var namaAcara = video.namaAcara || '';
        var tema = video.tema || '';
        var programa = video.programa || 'Umum';
        var programaColor = video.programaColor || 'gray';
        var date = video.date || 'TBA';
        var time = video.time || 'TBA';
        var url = video.url || '';
        var flyer = video.flyer || '';
        var penyiar = video.penyiar || '';
        var narasumber = Array.isArray(video.narasumber) ? video.narasumber : [];
        var teknik = video.teknik || '';
        var pa = video.pa || '';

        var bgColor = colorMap[programaColor] || colorMap['default'];

        var videoDataObj = {
            id: video.id,
            namaAcara: namaAcara,
            title: title,
            tema: tema,
            programa: programa,
            programaColor: programaColor,
            flyer: flyer,
            date: date,
            time: time,
            url: url,
            penyiar: penyiar,
            narasumber: narasumber,
            teknik: teknik,
            pa: pa
        };

        var tr = document.createElement('tr');
        tr.className = 'video-row';
        tr.dataset.video = JSON.stringify(videoDataObj);

        var flyerHtml = flyer !== ''
            ? `<img src="${escapeHtml(flyer)}" alt="${escapeHtml(title)}" class="video-thumb">`
            : `<div class="video-thumb video-thumb-empty"><i class="ri-image-line"></i></div>`;

        var subTitleHtml = namaAcara !== ''
            ? `<div class="cell-sub">${escapeHtml(namaAcara)}</div>`
            : '';

        var penyiarHtml = penyiar !== '' ? escapeHtml(penyiar) : '—';
        var teknikHtml = teknik !== '' ? escapeHtml(teknik) : '—';
        var paHtml = pa !== '' ? escapeHtml(pa) : '—';

        var narasumberHtml = '—';
        if (narasumber.length > 0) {
            narasumberHtml = '<ul style="margin: 0; padding-left: 1.2rem; list-style-type: disc;">' +
                narasumber.map(function(item){ return `<li style="margin-bottom: 6px;">${escapeHtml(item)}</li>`; }).join('') +
                '</ul>';
        }

        var actionsHtml = '';
        if (url !== '' && url !== '#') {
            actionsHtml += `<a href="${escapeHtml(url)}" target="_blank" class="icon-action view" title="Buka di Youtube" onclick="event.stopPropagation();">
                <i class="ri-external-link-line"></i>
            </a> `;
        }
        if (currentUserRole === 'admin') {
            actionsHtml += `<button type="button" class="icon-action edit" title="Edit" onclick="event.stopPropagation(); openEditView(JSON.parse(this.closest('tr').dataset.video));">
                <i class="ri-edit-line"></i>
            </button> `;
            actionsHtml += `<button type="button" class="icon-action delete" title="Hapus" onclick="event.stopPropagation(); deleteVideoRow('${escapeHtml(video.id)}', '${escapeJs(title)}');">
                <i class="ri-delete-bin-line"></i>
            </button>`;
        }

        tr.innerHTML = `
            <td>${flyerHtml}</td>
            <td class="cell-title">${escapeHtml(title)}${subTitleHtml}</td>
            <td>
                <span class="tag" style="background:${bgColor}1a; color:${bgColor}; border:1px solid ${bgColor}4d;">
                    ${escapeHtml(programa)}
                </span>
            </td>
            <td class="cell-sub">${escapeHtml(date)}</td>
            <td class="cell-sub">${escapeHtml(time)}</td>
            <td class="cell-sub">${penyiarHtml}</td>
            <td class="cell-sub">${narasumberHtml}</td>
            <td class="cell-sub">${teknikHtml}</td>
            <td class="cell-sub">${paHtml}</td>
            <td class="cell-actions">${actionsHtml}</td>
        `;

        tbody.appendChild(tr);
    });
}

async function filterTable() {
    var ptSettingFilter = document.getElementById('filterPtSetting') ? document.getElementById('filterPtSetting').value : "";
    var ptProduksiFilter = document.getElementById('filterPtProduksi') ? document.getElementById('filterPtProduksi').value : "";
    var penyiarFilter = document.getElementById('filterPenyiar') ? document.getElementById('filterPenyiar').value : "";
    var programFilter = document.getElementById('filterPrograma') ? document.getElementById('filterPrograma').value : "";

    var tbody = document.querySelector('#videoTable tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
                    <i class="ri-loader-4-line spin" style="display:inline-block; animation: spin 1s linear infinite;"></i> Memuat data dari Notion...
                </td>
            </tr>
        `;
    }

    if (currentFilterController) {
        currentFilterController.abort();
    }
    currentFilterController = new AbortController();

    try {
        var params = new URLSearchParams({
            pt_setting: ptSettingFilter,
            pt_produksi: ptProduksiFilter,
            penyiar: penyiarFilter,
            programa: programFilter
        });

        var res = await fetch('../api/filter-videos.php?' + params.toString(), {
            signal: currentFilterController.signal
        });
        var data = await res.json();

        if (data.success) {
            renderVideoRows(data.data);
        } else {
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" style="text-align:center; padding: 2rem; color: #ef4444;">
                            ⚠️ Gagal memuat data dari Notion: ${escapeHtml(data.error || 'Terjadi kesalahan')}
                        </td>
                    </tr>
                `;
            }
        }
    } catch (err) {
        if (err.name === 'AbortError') return;
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" style="text-align:center; padding: 2rem; color: #ef4444;">
                        ⚠️ Terjadi kesalahan jaringan saat memfilter data Notion.
                    </td>
                </tr>
            `;
        }
    }
}

// ==========================================
// RSS AUTO-FILL: TARIK & RENDER TABEL DRAFT
// (Dipindahkan & disesuaikan dari test-rss-autofill.php)
// ==========================================

// Bangun peta "Nama Acara" -> "Programa lengkap" dari masterData.acara yang
// sudah dimuat lewat get-master-data.php (sama persis dipakai form Edit).
function getAcaraToProgramaMapRss() {
    var programaFullNames = { 'Pro 1': 'Pro 1 FM 94.6 Mhz', 'Pro 2': 'Pro 2 FM 87.9 Mhz', 'Pro 4': 'Pro 4 FM 105.3 Mhz' };
    var map = {};
    Object.keys(masterData.acara || {}).forEach(function(prog) {
        var fullProg = programaFullNames[prog] || prog;
        (masterData.acara[prog] || []).forEach(function(acara) {
            map[acara] = fullProg;
        });
    });
    return map;
}

function normalizeTextRss(text) {
    if (!text) return '';
    return String(text).toLowerCase()
               .replace(/&/g, 'dan')
               .replace(/berbicara/g, 'bicara')
               .replace(/talkshow/g, 'dialog')
               .replace(/[^a-z0-9]/g, '');
}

function updatePrograma(index, selectedAcara) {
    var progSelect = document.getElementById('programa_' + index);
    var map = getAcaraToProgramaMapRss();
    if (progSelect && map[selectedAcara]) {
        progSelect.value = map[selectedAcara];
    }
}

function addNarasumberField(index, value) {
    if (value === undefined) value = '';
    var container = document.getElementById('narasumber_container_' + index);
    if (!container) return;

    var wrapper = document.createElement('div');
    wrapper.style.cssText = 'display:flex; gap:4px; align-items:center;';

    var input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Nama Narasumber & Jabatan';
    input.value = value;
    input.style.cssText = 'flex:1; padding:6px 8px; border:1px solid #ced4da; border-radius:4px; font-size:12px;';

    var btnRemove = document.createElement('button');
    btnRemove.type = 'button';
    btnRemove.innerHTML = '&times;';
    btnRemove.title = "Hapus baris ini";
    btnRemove.style.cssText = 'background:#ef4444; color:white; border:none; border-radius:4px; padding:0 8px; height:28px; cursor:pointer; font-weight:bold; font-size:14px;';
    btnRemove.onclick = function() { container.removeChild(wrapper); };

    wrapper.appendChild(input);
    wrapper.appendChild(btnRemove);
    container.appendChild(wrapper);
}

async function tarikRss() {
    var channelId = document.getElementById('rssChannelId').value.trim();
    var targetDate = document.getElementById('rssTargetDate').value;

    if (!channelId) { alert("ID Channel wajib diisi!"); return; }

    document.getElementById('rssLoadingStatus').style.display = 'block';
    var tbody = document.getElementById('rssTableBody');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-secondary); padding:20px;">Memproses RSS...</td></tr>';

    var formData = new URLSearchParams();
    formData.append('action', 'fetch_rss');
    formData.append('channel_id', channelId);
    formData.append('target_date', targetDate);

    try {
        var res = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        });
        var data = await res.json();

        if (data.success) {
            renderTabelRss(data.data);
        } else {
            tbody.innerHTML = '<tr><td colspan="7" style="color:#ef4444; text-align:center; padding:20px;">Error: ' + data.error + '</td></tr>';
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="7" style="color:#ef4444; text-align:center; padding:20px;">Terjadi kesalahan jaringan.</td></tr>';
    } finally {
        document.getElementById('rssLoadingStatus').style.display = 'none';
    }
}

function renderTabelRss(videos) {
    var tbody = document.getElementById('rssTableBody');
    tbody.innerHTML = '';

    if (videos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-secondary); padding:20px;">Tidak ada video ditemukan untuk tanggal tersebut.</td></tr>';
        return;
    }

    var acaraGrouped = masterData.acara || {};
    var penyiarGrouped = masterData.pemandu || {};
    var petugasList = masterData.petugas || [];

    videos.forEach(function(vid, index) {
        var idPT = 'pt_' + index;
        var idPA = 'pa_' + index;
        var hidPT = 'hidden_pt_' + index;
        var hidPA = 'hidden_pa_' + index;

        // ── CEK DUPLIKAT: bandingkan video ID dari RSS dengan yang sudah ada di Notion ──
        var vidUrl = vid.url || '';
        var alreadySaved = isAlreadySaved(vidUrl, vid.video_id || '');

        // Catatan: baris tetap dirender lengkap, hanya flag alreadySaved yang berbeda
        var extractedTitle = (vid.title_asli || '').split('-')[0].trim();
        var normalizedTitle = normalizeTextRss(extractedTitle);
        var optionsAcara = '<option value="">-- Pilih Acara --</option>';

        var extractedPenyiar = vid.penyiar || '';
        var normalizedPenyiarTitle = normalizeTextRss(extractedPenyiar);
        var optionsPenyiar = '<option value="">-- Pilih Pemandu --</option>';

        Object.keys(penyiarGrouped).forEach(function(programa) {
            optionsPenyiar += '<optgroup label="=== ' + programa + ' ===">';
            (penyiarGrouped[programa] || []).forEach(function(penyiar) {
                var normalizedMaster = normalizeTextRss(penyiar);
                var isSelected = '';
                if (normalizedPenyiarTitle !== '' && normalizedMaster !== '' &&
                    (normalizedPenyiarTitle.includes(normalizedMaster) || normalizedMaster.includes(normalizedPenyiarTitle))) {
                    isSelected = 'selected';
                }
                optionsPenyiar += '<option value="' + penyiar + '" ' + isSelected + '>' + penyiar + '</option>';
            });
            optionsPenyiar += '</optgroup>';
        });

        Object.keys(acaraGrouped).forEach(function(programa) {
            optionsAcara += '<optgroup label="=== ' + programa + ' ===">';
            (acaraGrouped[programa] || []).forEach(function(acara) {
                var normalizedAcara = normalizeTextRss(acara);
                var isSelected = '';
                if (normalizedTitle !== '' && normalizedAcara !== '' &&
                    (normalizedTitle.includes(normalizedAcara) || normalizedAcara.includes(normalizedTitle))) {
                    isSelected = 'selected';
                }
                optionsAcara += '<option value="' + acara + '" ' + isSelected + '>' + acara + '</option>';
            });
            optionsAcara += '</optgroup>';
        });

        var cellStyle = 'vertical-align:top; padding:10px 8px; font-size:12px; color:#334155;';
        var labelStyle = 'font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.4px; display:block; margin-bottom:3px;';
        var valueStyle = 'font-size:12px; color:#334155; line-height:1.5;';

        var tr = document.createElement('tr');
        if (alreadySaved) {
            tr.style.background = '#f8fafc';
            tr.style.opacity = '0.82';

            var vId = vid.video_id || extractYoutubeId(vidUrl);
            var savedVid = (typeof notionSavedVideosMap !== 'undefined' && notionSavedVideosMap[vId]) ? notionSavedVideosMap[vId] : null;

            // Prioritaskan data yang sudah benar-benar tersimpan di Notion
            var namaAcaraText = savedVid && savedVid.namaAcara ? savedVid.namaAcara : (vid.title_asli ? vid.title_asli.split('-')[0].trim() : '—');
            var temaText = savedVid ? (savedVid.tema || savedVid.title || '—') : (vid.tema || '—');
            
            var rawProg = savedVid && savedVid.programa ? savedVid.programa : (vid.programa || '—');
            var progLabel = rawProg.includes('Pro 1') ? 'Pro 1'
                          : rawProg.includes('Pro 2') ? 'Pro 2'
                          : rawProg.includes('Pro 4') ? 'Pro 4' : rawProg;

            var tglText = savedVid && savedVid.date ? savedVid.date : (vid.tanggal || '—');
            var jamText = savedVid && savedVid.time ? savedVid.time : (vid.jamMulai || '—');

            var pemanduText = savedVid && savedVid.penyiar ? savedVid.penyiar : (vid.penyiar || '—');

            var narasumberText = '—';
            if (savedVid && Array.isArray(savedVid.narasumber) && savedVid.narasumber.length > 0) {
                narasumberText = savedVid.narasumber.map(function(s){ return '<div>' + escapeHtml(s) + '</div>'; }).join('');
            } else if (vid.narasumber) {
                narasumberText = vid.narasumber.split('\n').filter(function(s){ return s.trim(); }).map(function(s){ return '<div>' + escapeHtml(s) + '</div>'; }).join('');
            }

            var ptText = savedVid && savedVid.teknik ? savedVid.teknik : ((vid.pt && vid.pt.length) ? vid.pt.join(', ') : '—');
            var paText = savedVid && savedVid.pa ? savedVid.pa : ((vid.pa && vid.pa.length) ? vid.pa.join(', ') : '—');

            var flyerUrl = savedVid && savedVid.flyer ? savedVid.flyer : (vid.flyer || '');
            var linkUrl = savedVid && savedVid.url ? savedVid.url : (vid.url || '');

            tr.innerHTML = `
                <td style="${cellStyle} text-align:center; padding-top:12px;">
                    <span style="font-weight:700; font-size:14px;">${index + 1}</span><br>
                    <span style="font-size:10px; background:#e2e8f0; color:#64748b; padding:1px 6px; border-radius:3px; font-weight:500; margin-top:4px; display:inline-block;">tersimpan</span>
                </td>
                <td style="${cellStyle}">
                    <span style="${labelStyle}">Nama Acara</span>
                    <span style="${valueStyle}">${escapeHtml(namaAcaraText)}</span>
                    <span style="${labelStyle} margin-top:8px;">Tema</span>
                    <span style="${valueStyle}">${escapeHtml(temaText)}</span>
                </td>
                <td style="${cellStyle}">
                    <span style="${labelStyle}">Programa</span>
                    <span style="${valueStyle}">${escapeHtml(progLabel)}</span>
                    <span style="${labelStyle} margin-top:8px;">Tanggal</span>
                    <span style="${valueStyle}">${escapeHtml(tglText)}</span>
                    <span style="${labelStyle} margin-top:8px;">Jam Mulai</span>
                    <span style="${valueStyle}">${escapeHtml(jamText)}</span>
                </td>
                <td style="${cellStyle}">
                    <span style="${labelStyle}">Pemandu / Host / Penyiar</span>
                    <span style="${valueStyle}">${escapeHtml(pemanduText)}</span>
                    <span style="${labelStyle} margin-top:8px;">Narasumber</span>
                    <div style="${valueStyle}">${narasumberText}</div>
                </td>
                <td style="${cellStyle}">
                    <span style="${labelStyle}">Petugas PT Setting</span>
                    <span style="${valueStyle}">${escapeHtml(ptText)}</span>
                    <span style="${labelStyle} margin-top:8px;">Petugas PT Produksi</span>
                    <span style="${valueStyle}">${escapeHtml(paText)}</span>
                </td>
                <td style="${cellStyle}">
                    <span style="${labelStyle}">Link YouTube</span>
                    ${linkUrl && linkUrl !== ''
                        ? `<a href="${escapeHtml(linkUrl)}" target="_blank" rel="noopener" style="font-size:12px; color:#2563eb; word-break:break-all;">${escapeHtml(linkUrl)}</a>`
                        : '<span style="' + valueStyle + '">—</span>'
                    }
                    <span style="${labelStyle} margin-top:8px;">Flyer</span>
                    ${flyerUrl
                        ? `<img src="${escapeHtml(flyerUrl)}" style="width:100%; max-width:120px; height:70px; object-fit:cover; border-radius:4px; cursor:zoom-in; display:block;" onclick="openImagePopup('${escapeHtml(flyerUrl)}')" alt="Flyer">`
                        : '<span style="' + valueStyle + '">—</span>'
                    }
                </td>
                <td style="vertical-align:top; text-align:center; padding-top:12px;">
                    <button class="btn" disabled style="background:#94a3b8; color:#fff; border:none; border-radius:4px; padding:9px 12px; font-weight:600; cursor:not-allowed; font-size:12px;">Sudah Tersimpan</button>
                </td>
            `;
            tbody.appendChild(tr);
            return;
        }

        tr.innerHTML = `
            <td style="text-align:center; font-weight:bold; font-size:14px; vertical-align:top; padding-top:14px;">${index + 1}</td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Nama Acara</label>
                    <div id="acara_container_${index}"></div>
                    <input type="hidden" id="hidden_acara_${index}" value="">
                    
                    <label class="tabel-label" style="margin-top:6px;">Tema</label>
                    <textarea rows="3" placeholder="Tema Acara" style="resize:none;">${vid.tema || ''}</textarea>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Programa</label>
                    <select id="programa_${index}">
                        <option value="Pro 1 FM 94.6 Mhz" ${(vid.programa||'').includes('Pro 1') ? 'selected' : ''}>Pro 1</option>
                        <option value="Pro 2 FM 87.9 Mhz" ${(vid.programa||'').includes('Pro 2') ? 'selected' : ''}>Pro 2</option>
                        <option value="Pro 4 FM 105.3 Mhz" ${(vid.programa||'').includes('Pro 4') ? 'selected' : ''}>Pro 4</option>
                    </select>
                    <label class="tabel-label" style="margin-top:6px;">Tanggal Tayang</label>
                    <input type="date" value="${vid.tanggal || ''}">
                    <label class="tabel-label">Jam Mulai</label>
                    <input type="time" value="${vid.jamMulai || ''}" title="Jam Mulai" required>
                    </div>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Pemandu / Host / Penyiar</label>
                    <div id="pemandu_container_${index}"></div>
                    <input type="hidden" id="hidden_pemandu_${index}" value="">
                    
                    <label class="tabel-label" style="margin-top:6px;">Narasumber</label>
                    <div id="narasumber_container_${index}" style="display:flex; flex-direction:column; gap:4px;"></div>
                    <button type="button" onclick="addNarasumberField(${index})" style="margin-top:4px; font-size:11px; padding:4px 8px; cursor:pointer; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; color:#334155; font-weight:600;">+ Tambah Narasumber</button>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Petugas PT Setting</label>
                    <div id="${idPT}"></div>
                    <input type="hidden" id="${hidPT}" value="${(vid.pt || []).join(', ')}">
                    <label class="tabel-label" style="margin-top:6px;">Petugas PT Produksi</label>
                    <div id="${idPA}"></div>
                    <input type="hidden" id="${hidPA}" value="${(vid.pa || []).join(', ')}">
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Link YouTube</label>
                    <input type="url" value="${vid.url || ''}" placeholder="URL YouTube">
                    <label class="tabel-label" style="margin-top:6px;">Flyer / Thumbnail</label>
                    <div id="dropzone_${index}" class="dropzone-mini" style="padding:0; overflow:hidden; position:relative; min-height:120px; display:flex; align-items:center; justify-content:center;" onclick="document.getElementById('flyer_input_${index}').click()">
                        <input type="file" id="flyer_input_${index}" accept="image/*" style="display:none;" onchange="handleFlyerSelect(this, '${index}')">
                        ${vid.flyer
                            ? `<img src="${vid.flyer}" style="width:100%; height:100%; object-fit:cover; position:absolute;" alt="Flyer" onclick="event.stopPropagation(); openImagePopup(this.src)"><button type="button" class="remove-flyer-btn" onclick="hapusFlyer('${index}', event)">&times;</button>`
                            : `<span style="padding:10px;">Pilih / Tarik Flyer</span>`
                        }
                    </div>
                    <input type="hidden" id="hidden_flyer_${index}" class="hidden-flyer-url" value="${vid.flyer || ''}">
                </div>
            </td>
            <td style="vertical-align:top; text-align:center; padding-top:14px;">
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <button class="btn btn-success" style="background:#16a34a; color:#fff; border:none; border-radius:4px; padding:9px 12px; cursor:pointer; font-weight:600;" onclick="simpanKeNotion(this, '${index}')">Simpan</button>
                    <button class="btn" style="background:#ef4444; color:white; border:none; border-radius:4px; padding:9px 12px; cursor:pointer; font-weight:600;" onclick="hapusBaris(this)">Hapus</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);

        var narasumberList = vid.narasumber ? vid.narasumber.split('\n') : [''];
        narasumberList.forEach(function(nama) {
            if (nama.trim() !== '') addNarasumberField(index, nama.trim());
        });
        var narasumberContainer = document.getElementById('narasumber_container_' + index);
        if (narasumberContainer && narasumberContainer.children.length === 0) {
            addNarasumberField(index, '');
        }

        createRssMultiSelect(idPT, hidPT, petugasList);
        createRssMultiSelect(idPA, hidPA, petugasList);
        attachDropzoneEvents(index);
        // Pencocokan otomatis judul RSS ke master data
        var matchedAcaraName = "";
        Object.keys(acaraGrouped).forEach(function(prog) {
            (acaraGrouped[prog] || []).forEach(function(ac) {
                var norm = normalizeTextRss(ac);
                if (normalizedTitle && norm && (normalizedTitle.includes(norm) || norm.includes(normalizedTitle))) {
                    matchedAcaraName = ac;
                }
            });
        });

        var matchedPemanduNames = [];
        // Pecah string penyiar dari RSS berdasarkan separator umum (& , / dan)
        var rawPemanduParts = extractedPenyiar
            ? extractedPenyiar.split(/[,&\/]|\bdan\b/i).map(function(s){ return s.trim(); }).filter(Boolean)
            : [];

        rawPemanduParts.forEach(function(part) {
            var normPart = normalizeTextRss(part);
            var matched = '';
            Object.keys(penyiarGrouped).forEach(function(prog) {
                (penyiarGrouped[prog] || []).forEach(function(p) {
                    var norm = normalizeTextRss(p);
                    if (normPart && norm && (normPart.includes(norm) || norm.includes(normPart))) {
                        matched = p;
                    }
                });
            });
            // Jika tidak cocok di master, pakai nama asli dari RSS
            matchedPemanduNames.push(matched || part);
        });

        // Fallback: jika tidak ada satu pun nama terpecah, gunakan string mentah
        if (matchedPemanduNames.length === 0 && extractedPenyiar) {
            matchedPemanduNames = [extractedPenyiar];
        }

        // Masukkan nilai ke hidden input
        document.getElementById('hidden_acara_' + index).value = matchedAcaraName;
        document.getElementById('hidden_pemandu_' + index).value = matchedPemanduNames.join(', ');

        // Render komponen select kustom
        createRssSingleSelect('acara_container_' + index, 'hidden_acara_' + index, acaraGrouped, 'Pilih Acara');
        createRssMultiCreatableSelect('pemandu_container_' + index, 'hidden_pemandu_' + index, penyiarGrouped, 'Pilih Pemandu');
    });
    perbaruiNomorUrut();
}

// ==========================================
// CUSTOM DROPDOWN MULTISELECT KHUSUS BARIS RSS
// (Terpisah dari createCustomMultiSelect single-form supaya tidak ikut
// memanggil generateDescription() yang tidak relevan di konteks tabel ini)
// ==========================================
function createRssMultiSelect(containerId, hiddenInputId, options) {
    var container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    var selectedValues = hiddenInput.value ? hiddenInput.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean) : [];

    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'min-height:32px; border:1px solid #ced4da; border-radius:4px; padding:4px 24px 4px 6px; background:#fff; cursor:pointer; display:flex; flex-wrap:wrap; align-items:center; position:relative; gap:4px; font-size:12px;';

    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';
    selectBox.appendChild(arrow);

    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu';
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; width:220px; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:6px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search...';
    searchInput.style.cssText = 'width:100%; padding:5px 8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; outline:none; font-size:12px;';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:150px; overflow-y:auto; padding:4px 0; text-align:left;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    function renderChips() {
        Array.from(selectBox.children).forEach(function(el){ if (el !== arrow) selectBox.removeChild(el); });

        if (selectedValues.length === 0) {
            var placeholder = document.createElement('span');
            placeholder.textContent = 'Pilih...';
            placeholder.style.color = '#999';
            selectBox.insertBefore(placeholder, arrow);
        } else {
            selectedValues.forEach(function(val) {
                var chip = document.createElement('span');
                chip.style.cssText = 'background:#e2e8f0; color:#334155; padding:2px 6px; border-radius:4px; font-size:11px; display:inline-flex; align-items:center; gap:4px; border:1px solid #cbd5e1;';
                chip.textContent = val;

                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'cursor:pointer; font-size:12px; font-weight:bold; color:#64748b;';
                closeBtn.onclick = function(e) { e.stopPropagation(); toggleValue(val, false); };
                chip.appendChild(closeBtn);
                selectBox.insertBefore(chip, arrow);
            });
        }
        hiddenInput.value = selectedValues.join(', ');
    }

    function toggleValue(val, isChecked) {
        if (isChecked && !selectedValues.includes(val)) selectedValues.push(val);
        else if (!isChecked) selectedValues = selectedValues.filter(function(v){ return v !== val; });
        renderChips();
        renderList(searchInput.value);
    }

    function renderList(filterText) {
        listDiv.innerHTML = '';
        var filtered = options.filter(function(o){ return o.toLowerCase().indexOf((filterText||'').toLowerCase()) !== -1; });
        filtered.forEach(function(opt) {
            var label = document.createElement('label');
            label.style.cssText = 'display:flex; align-items:center; padding:4px 8px; cursor:pointer; font-size:12px; margin:0; width:100%; box-sizing:border-box;';
            label.onmouseover = function(){ label.style.background = '#f8f9fa'; };
            label.onmouseout = function(){ label.style.background = 'transparent'; };

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = opt;
            cb.checked = selectedValues.includes(opt);
            cb.style.cssText = 'margin:0 8px 0 0;';
            cb.onchange = function(e){ e.stopPropagation(); toggleValue(opt, e.target.checked); };

            label.appendChild(cb);
            label.appendChild(document.createTextNode(opt));
            listDiv.appendChild(label);
        });
    }

    // Event Klik untuk Buka/Tutup Dropdown
    selectBox.onclick = function(e) {
        e.stopPropagation(); // Mencegah klik menutup dropdown seketika
        var isOpen = dropdown.style.display === 'block';
        
        // Tutup semua dropdown custom lain yang sedang terbuka
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ 
            d.style.display = 'none'; 
        });
        
        if (!isOpen) {
            dropdown.style.display = 'block';
            searchInput.value = '';
            searchInput.focus();
            renderList('');
        }
    };

    // Mencegah klik di dalam kotak pencarian menutup dropdown
    searchInput.onclick = function(e) { 
        e.stopPropagation(); 
    };
    searchInput.oninput = function(e){ renderList(e.target.value); };
    renderChips();
    renderList('');
}

// ==========================================
// TAMBAH BARIS KOSONG & NOMOR URUT
// ==========================================
function tambahBarisKosong() {
    var tbody = document.getElementById('rssTableBody');

    if (tbody.firstElementChild && tbody.firstElementChild.innerHTML.includes('colspan')) {
        tbody.innerHTML = '';
    }

    var uniqueId = 'm_' + Date.now();
    var idPT = 'pt_' + uniqueId;
    var idPA = 'pa_' + uniqueId;
    var hidPT = 'hidden_pt_' + uniqueId;
    var hidPA = 'hidden_pa_' + uniqueId;

    var acaraGrouped = masterData.acara || {};
    var penyiarGrouped = masterData.pemandu || {};
    var petugasList = masterData.petugas || [];

    var optionsAcara = '<option value="">-- Pilih Acara --</option>';
    Object.keys(acaraGrouped).forEach(function(programa) {
        optionsAcara += '<optgroup label="=== ' + programa + ' ===">';
        (acaraGrouped[programa] || []).forEach(function(acara) {
            optionsAcara += '<option value="' + acara + '">' + acara + '</option>';
        });
        optionsAcara += '</optgroup>';
    });

    var optionsPenyiar = '<option value="">-- Pilih Pemandu --</option>';
    Object.keys(penyiarGrouped).forEach(function(programa) {
        optionsPenyiar += '<optgroup label="=== ' + programa + ' ===">';
        (penyiarGrouped[programa] || []).forEach(function(penyiar) {
            optionsPenyiar += '<option value="' + penyiar + '">' + penyiar + '</option>';
        });
        optionsPenyiar += '</optgroup>';
    });

    var targetDateVal = document.getElementById('rssTargetDate').value;

    var tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="text-align:center; font-weight:bold; font-size:14px; vertical-align:top; padding-top:14px;"></td>
        <td>
            <div class="row-group">
                <label class="tabel-label">Nama Acara</label>
                <div id="acara_container_${uniqueId}"></div>
                <input type="hidden" id="hidden_acara_${uniqueId}" value="">
                <label class="tabel-label" style="margin-top:6px;">Tema</label>
                <textarea rows="3" placeholder="Tema Acara" style="resize:none;"></textarea>
            </div>
        </td>
        <td>
            <div class="row-group">
                <label class="tabel-label">Programa</label>
                <select id="programa_${uniqueId}">
                    <option value="Pro 1 FM 94.6 Mhz">Pro 1</option>
                    <option value="Pro 2 FM 87.9 Mhz">Pro 2</option>
                    <option value="Pro 4 FM 105.3 Mhz">Pro 4</option>
                </select>
                <label class="tabel-label" style="margin-top:6px;">Tanggal Tayang</label>
                <input type="date" value="${targetDateVal}">
                <label class="tabel-label">Jam Mulai</label>
                <input type="time" required>
            </div>
        </td>
        <td>
            <div class="row-group">
                <label class="tabel-label">Pemandu / Host / Penyiar</label>
                <div id="pemandu_container_${uniqueId}"></div>
                <input type="hidden" id="hidden_pemandu_${uniqueId}" value="">
                <label class="tabel-label" style="margin-top:6px;">Narasumber</label>
                <div id="narasumber_container_${uniqueId}" style="display:flex; flex-direction:column; gap:4px;"></div>
                <button type="button" onclick="addNarasumberField('${uniqueId}')" style="margin-top:4px; font-size:11px; padding:4px 8px; cursor:pointer; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; color:#334155; font-weight:600;">+ Tambah Narasumber</button>
            </div>
        </td>
        <td>
            <div class="row-group">
                <label class="tabel-label">Petugas PT Setting</label>
                <div id="${idPT}"></div>
                <input type="hidden" id="${hidPT}" value="">
                <label class="tabel-label" style="margin-top:6px;">Petugas PT Produksi</label>
                <div id="${idPA}"></div>
                <input type="hidden" id="${hidPA}" value="">
            </div>
        </td>
        <td>
            <div class="row-group">
                <label class="tabel-label">Link YouTube</label>
                <input type="url" placeholder="URL YouTube">
                <label class="tabel-label" style="margin-top:6px;">Flyer / Thumbnail</label>
                <div id="dropzone_${uniqueId}" class="dropzone-mini" style="padding:0; overflow:hidden; position:relative; min-height:120px; display:flex; align-items:center; justify-content:center;" onclick="document.getElementById('flyer_input_${uniqueId}').click()">
                    <input type="file" id="flyer_input_${uniqueId}" accept="image/*" style="display:none;" onchange="handleFlyerSelect(this, '${uniqueId}')">
                    <span style="padding:10px;">Pilih / Tarik Flyer</span>
                </div>
                <input type="hidden" id="hidden_flyer_${uniqueId}" class="hidden-flyer-url" value="">
            </div>
        </td>
        <td style="vertical-align:top; text-align:center; padding-top:14px;">
            <div style="display:flex; flex-direction:column; gap:8px;">
                <button class="btn btn-success" style="background:#16a34a; color:#fff; border:none; border-radius:4px; padding:9px 12px; cursor:pointer; font-weight:600;" onclick="simpanKeNotion(this, '${uniqueId}')">Simpan</button>
                <button class="btn" style="background:#ef4444; color:white; border:none; border-radius:4px; padding:9px 12px; cursor:pointer; font-weight:600;" onclick="hapusBaris(this)">Hapus</button>
            </div>
        </td>
    `;

    tbody.appendChild(tr);

    createRssSingleSelect('acara_container_' + uniqueId, 'hidden_acara_' + uniqueId, acaraGrouped, 'Pilih Acara');
    createRssMultiCreatableSelect('pemandu_container_' + uniqueId, 'hidden_pemandu_' + uniqueId, penyiarGrouped, 'Pilih Pemandu');

    addNarasumberField(uniqueId, '');
    createRssMultiSelect(idPT, hidPT, petugasList);
    createRssMultiSelect(idPA, hidPA, petugasList);
    attachDropzoneEvents(uniqueId);

    perbaruiNomorUrut();
}

function perbaruiNomorUrut() {
    var rows = document.querySelectorAll('#rssTableBody tr');
    var counter = 1;
    rows.forEach(function(row) {
        var cell = row.querySelector('td:first-child');
        if (cell && !cell.hasAttribute('colspan')) {
            cell.innerHTML = counter++;
        }
    });
}

// ==========================================
// FLYER: DRAG & DROP, PREVIEW, POP-UP
// ==========================================
function attachDropzoneEvents(id) {
    var zone = document.getElementById('dropzone_' + id);
    if (!zone) return;

    zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function() { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.classList.remove('dragover');
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            var input = document.getElementById('flyer_input_' + id);
            input.files = files;
            handleFlyerSelect(input, id);
        }
    });
}

function handleFlyerSelect(input, id) {
    var file = input.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { alert('Harap pilih file gambar.'); return; }
    if (file.size > 10 * 1024 * 1024) { alert('Ukuran gambar maksimal 10MB.'); return; }

    var reader = new FileReader();
    reader.onload = function(e) {
        var dropzone = document.getElementById('dropzone_' + id);
        var hidden = document.getElementById('hidden_flyer_' + id);

        Array.from(dropzone.children).forEach(function(child) {
            if (child.tagName !== 'INPUT') dropzone.removeChild(child);
        });

        var img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = "width:100%; height:100%; object-fit:cover; position:absolute;";
        img.alt = "Flyer";
        img.onclick = function(event) { event.stopPropagation(); openImagePopup(img.src); };

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'remove-flyer-btn';
        btn.innerHTML = '&times;';
        btn.onclick = function(event) { hapusFlyer(id, event); };

        dropzone.appendChild(img);
        dropzone.appendChild(btn);

        if (hidden) hidden.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

function hapusFlyer(id, event) {
    if (event) event.stopPropagation();

    var dropzone = document.getElementById('dropzone_' + id);
    var input = document.getElementById('flyer_input_' + id);
    var hidden = document.getElementById('hidden_flyer_' + id);

    if (input) input.value = '';
    if (hidden) hidden.value = '';

    Array.from(dropzone.children).forEach(function(child) {
        if (child.tagName !== 'INPUT') dropzone.removeChild(child);
    });

    var span = document.createElement('span');
    span.style.padding = '10px';
    span.textContent = 'Pilih / Tarik Flyer';
    dropzone.appendChild(span);
}

function openImagePopup(src) {
    var modal = document.getElementById('imageModal');
    var modalImg = document.getElementById('imageModalSrc');
    modalImg.src = src;
    modal.style.display = 'flex';
}

// ==========================================
// HAPUS BARIS & CLEAR TABEL
// ==========================================
function hapusBaris(btn) {
    if (confirm("Apakah Anda yakin ingin menghapus baris ini?")) {
        var row = btn.closest('tr');
        if (row) {
            row.remove();
            perbaruiNomorUrut();
        }
    }
}

function bersihkanTabel() {
    if (confirm("Apakah Anda yakin ingin menghapus semua isian di tabel ini?")) {
        document.getElementById('rssTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-secondary); padding:30px;">Belum ada data ditarik. Masukkan ID Channel dan klik Tarik Data.</td></tr>';
    }
}

// ==========================================
// SIMPAN KE NOTION (per baris & semua baris)
// ==========================================
async function simpanKeNotion(btn, id) {
    var originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Mengekstrak...';
    btn.style.opacity = '0.7';

    try {
        var row = btn.closest('tr');
        if (!row) throw new Error("Baris tidak ditemukan.");

        var namaAcara = document.getElementById('hidden_acara_' + id) ? document.getElementById('hidden_acara_' + id).value : '';

        var temaInput = row.querySelector('textarea[placeholder="Tema Acara"]');
        var tema = temaInput ? temaInput.value : '';

        var programaSelect = document.getElementById('programa_' + id);
        var programaRaw = programaSelect ? programaSelect.value : '';
        var liveValue = '';
        if (programaRaw.includes('Pro 1')) liveValue = '94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official';
        else if (programaRaw.includes('Pro 2')) liveValue = '87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official';
        else if (programaRaw.includes('Pro 4')) liveValue = '105.3 FM Pro 4 RRI Malang dan Youtube RRI Malang Official';

        var tglInput = row.querySelector('input[type="date"]');
        var tanggal = tglInput ? tglInput.value : '';

        var timeInputs = row.querySelectorAll('input[type="time"]');
        var jamMulai = timeInputs[0] ? timeInputs[0].value : '';

        var pemandu = document.getElementById('hidden_pemandu_' + id) ? document.getElementById('hidden_pemandu_' + id).value : '';

        var narasumberContainer = document.getElementById('narasumber_container_' + id);
        var narasumberText = '';
        if (narasumberContainer) {
            var nsInputs = narasumberContainer.querySelectorAll('input[type="text"]');
            var nsArray = [];
            nsInputs.forEach(function(input) {
                if (input.value.trim() !== '') nsArray.push(input.value.trim());
            });
            narasumberText = nsArray.join('\n');
        }

        var teknik = document.getElementById('hidden_pt_' + id).value;
        var pa = document.getElementById('hidden_pa_' + id).value;

        var urlInput = row.querySelector('input[type="url"]');
        var link = urlInput ? urlInput.value : '';

        var plainObject = {
            live: liveValue,
            program: namaAcara,
            namaAcaraDisplay: namaAcara,
            tema: tema,
            judul: tema,
            tanggal: tanggal,
            jamMulai: jamMulai,
            penyiar: pemandu,
            narasumber: narasumberText,
            teknik: teknik,
            pa: pa,
            link: link
        };

        var fileInput = document.getElementById('flyer_input_' + id);
        var hiddenFlyerEl = document.getElementById('hidden_flyer_' + id);
        var uploadedFilesPayload = [];

        if (fileInput && fileInput.files.length > 0) {
            // Jalur 1: User memilih file baru secara manual
            btn.textContent = 'Upload Flyer...';
            var uploadFormData = new FormData();
            uploadFormData.append('dokumentasi[]', fileInput.files[0]);

            var uploadRes = await fetch('../api/upload-to-notion.php', { method: 'POST', body: uploadFormData });
            var uploadData = await uploadRes.json();

            if (!uploadData.success) {
                throw new Error(uploadData.error || 'Gagal mengupload flyer ke Notion');
            }
            uploadedFilesPayload = uploadData.files.map(function(f){ return { id: f.id, name: f.name || f.original }; });

        } else if (hiddenFlyerEl && hiddenFlyerEl.value && hiddenFlyerEl.value.startsWith('http')) {
            // Jalur 2: Flyer dari RSS (thumbnail YouTube) — kirim URL ke server untuk di-fetch & upload
            btn.textContent = 'Upload Thumbnail...';
            var urlFormData = new FormData();
            urlFormData.append('flyer_url', hiddenFlyerEl.value);

            var urlUploadRes = await fetch('../api/upload-to-notion.php', { method: 'POST', body: urlFormData });
            var urlUploadData = await urlUploadRes.json();

            if (!urlUploadData.success) {
                // Jika gagal upload thumbnail RSS, lanjutkan simpan tanpa flyer (tidak fatal)
                console.warn('Gagal upload thumbnail RSS ke Notion:', urlUploadData.error);
            } else {
                uploadedFilesPayload = urlUploadData.files.map(function(f){ return { id: f.id, name: f.name || f.original }; });
            }
        }

        plainObject['flyer_urls'] = uploadedFilesPayload;

        btn.textContent = 'Menyimpan ...';
        var res = await fetch('../api/add-video.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(plainObject)
        });

        var data = await res.json();

        if (data.success) {
            btn.textContent = 'Tersimpan';
            btn.style.background = '#475569';
            btn.disabled = true;
            btn.style.cursor = 'not-allowed';

            // Daftarkan video ID ke notionSavedVideoIds agar tarik RSS berikutnya ikut terdeteksi
            var savedId = extractYoutubeId(link);
            if (savedId) {
                if (notionSavedVideoIds.indexOf(savedId) === -1) {
                    notionSavedVideoIds.push(savedId);
                }
                if (typeof notionSavedVideosMap !== 'undefined') {
                    notionSavedVideosMap[savedId] = {
                        id: (data && data.page_id) ? data.page_id : id,
                        namaAcara: namaAcara,
                        title: tema || namaAcara,
                        tema: tema,
                        programa: liveValue,
                        date: tanggal,
                        time: jamMulai,
                        url: link,
                        flyer: (uploadedFilesPayload && uploadedFilesPayload.length > 0) ? (uploadedFilesPayload[0].name || '') : (hiddenFlyerEl ? hiddenFlyerEl.value : ''),
                        penyiar: pemandu,
                        narasumber: narasumberText ? narasumberText.split('\n') : [],
                        teknik: teknik,
                        pa: pa
                    };
                }
            }

            // Sembunyikan tombol Hapus
            var deleteBtn = row.querySelector('button.btn[style*="background:#ef4444"]') || row.querySelector('button.btn-danger');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
            }
        } else {
            throw new Error(data.error || data.details || 'Kesalahan API Notion');
        }

    } catch (err) {
        console.error(err);
        alert('Gagal menyimpan: ' + err.message);
        btn.disabled = false;
        btn.textContent = originalText;
        btn.style.opacity = '1';
    }
}

function simpanSemuaKeNotion() {
    var saveButtons = document.querySelectorAll('#rssTableBody .btn-success');
    var jumlahDiproses = 0;

    saveButtons.forEach(function(btn) {
        if (!btn.disabled && btn.textContent.includes('Simpan')) {
            jumlahDiproses++;
        }
    });

    if (jumlahDiproses === 0) {
        alert('Tidak ada baris baru yang bisa disimpan (tabel kosong atau semua data sudah tersimpan).');
        return;
    }

    if (confirm('Apakah Anda yakin ingin menyimpan ' + jumlahDiproses + ' baris ini ke database Notion secara bersamaan?')) {
        saveButtons.forEach(function(btn) {
            if (!btn.disabled && btn.textContent.includes('Simpan')) {
                btn.click();
            }
        });
    }
}

// ==========================================
// CUSTOM SINGLE SELECT DENGAN INPUT MANUAL
// ==========================================
function createRssSingleSelect(containerId, hiddenInputId, optionsGrouped, placeholderText) {
    var container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    var selectedValue = hiddenInput.value || '';

    // 1. Box Tampilan Utama
    // 1. Box Tampilan Utama
    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'width:100%; box-sizing:border-box; min-height:32px; border:1px solid #ced4da; border-radius:4px; padding:4px 24px 4px 8px; background:#fff; cursor:pointer; display:flex; align-items:center; position:relative; font-size:12px; font-weight:600; color:#334155;';

    var displayLabel = document.createElement('span');
    displayLabel.style.cssText = 'display:block; width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';
    displayLabel.textContent = selectedValue || ('-- ' + placeholderText + ' --');
    if (!selectedValue) displayLabel.style.color = '#94a3b8';

    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';

    selectBox.appendChild(displayLabel);
    selectBox.appendChild(arrow);

    // 2. Menu Dropdown & Search Input
    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu';
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; width:260px; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:6px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Ketik untuk cari / input manual...';
    searchInput.style.cssText = 'width:100%; padding:5px 8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; outline:none; font-size:12px;';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:180px; overflow-y:auto; padding:4px 0; text-align:left;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    // Set nilai terpilih
    function setValue(val) {
        selectedValue = val;
        hiddenInput.value = val;
        displayLabel.textContent = val || ('-- ' + placeholderText + ' --');
        displayLabel.style.color = val ? '#334155' : '#94a3b8';
        dropdown.style.display = 'none';
        
        // Triggers otomatis penyesuaian programa jika ini dropdown Acara
        if (containerId.startsWith('acara_container_')) {
            var index = containerId.replace('acara_container_', '');
            if (typeof updatePrograma === 'function') updatePrograma(index, val);
        }
    }

    // Render List Items
    function renderList(filterText) {
        listDiv.innerHTML = '';
        var query = (filterText || '').toLowerCase().trim();
        var exactMatch = false;

        Object.keys(optionsGrouped).forEach(function(group) {
            var items = optionsGrouped[group] || [];
            var filteredItems = items.filter(function(item) {
                if (item.toLowerCase() === query) exactMatch = true;
                return item.toLowerCase().includes(query);
            });

            if (filteredItems.length > 0) {
                var groupHeader = document.createElement('div');
                groupHeader.style.cssText = 'padding:4px 8px; background:#f8fafc; font-weight:bold; font-size:11px; color:#64748b; border-bottom:1px solid #e2e8f0; border-top:1px solid #e2e8f0;';
                groupHeader.textContent = '=== ' + group + ' ===';
                listDiv.appendChild(groupHeader);

                filteredItems.forEach(function(item) {
                    var itemEl = document.createElement('div');
                    itemEl.style.cssText = 'padding:6px 12px; cursor:pointer; font-size:12px; color:#334155;';
                    itemEl.textContent = item;
                    itemEl.onmouseover = function() { itemEl.style.background = '#e2e8f0'; };
                    itemEl.onmouseout = function() { itemEl.style.background = 'transparent'; };
                    itemEl.onclick = function() { setValue(item); };
                    listDiv.appendChild(itemEl);
                });
            }
        });

        // OPSI INPUT MANUAL: Jika yang diketik belum ada di daftar dropdown
        if (query !== '' && !exactMatch) {
            var manualOpt = document.createElement('div');
            manualOpt.style.cssText = 'padding:8px 12px; cursor:pointer; font-size:12px; background:#f0fdf4; color:#166534; font-weight:bold; border-top:1px solid #bbf7d0;';
            manualOpt.innerHTML = 'Gunakan Manual: "<i>' + filterText + '</i>"';
            manualOpt.onclick = function(e) { e.stopPropagation(); setValue(filterText); };
            listDiv.appendChild(manualOpt);
        } else if (listDiv.children.length === 0) {
            var empty = document.createElement('div');
            empty.textContent = 'Tidak ditemukan';
            empty.style.cssText = 'padding:8px; color:#999; text-align:center; font-size:12px;';
            listDiv.appendChild(empty);
        }
    }

    selectBox.onclick = function(e) {
        e.stopPropagation();
        var isOpen = dropdown.style.display === 'block';
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ d.style.display = 'none'; });
        if (!isOpen) {
            dropdown.style.display = 'block';
            searchInput.value = '';
            searchInput.focus();
            renderList('');
        }
    };

    searchInput.onclick = function(e) { e.stopPropagation(); };
    searchInput.oninput = function(e) { renderList(e.target.value); };
}

// ==========================================
// MULTI-SELECT + CREATABLE (chip multi-pilih, plus bisa ketik nama baru
// yang belum ada di master data) -- dipakai untuk Pemandu/Host/Penyiar,
// gabungan pola createRssMultiSelect (petugas) & createRssSingleSelect (acara)
// ==========================================
function createRssMultiCreatableSelect(containerId, hiddenInputId, optionsGrouped, placeholderText) {
    var container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    var selectedValues = hiddenInput.value ? hiddenInput.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean) : [];

    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'min-height:32px; border:1px solid #ced4da; border-radius:4px; padding:4px 24px 4px 6px; background:#fff; cursor:pointer; display:flex; flex-wrap:wrap; align-items:center; position:relative; gap:4px; font-size:12px;';

    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';
    selectBox.appendChild(arrow);

    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu';
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; width:260px; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:6px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Ketik untuk cari / input manual...';
    searchInput.style.cssText = 'width:100%; padding:5px 8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; outline:none; font-size:12px;';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:180px; overflow-y:auto; padding:4px 0; text-align:left;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    function renderChips() {
        Array.from(selectBox.children).forEach(function(el){ if (el !== arrow) selectBox.removeChild(el); });

        if (selectedValues.length === 0) {
            var placeholder = document.createElement('span');
            placeholder.textContent = '-- ' + placeholderText + ' --';
            placeholder.style.color = '#94a3b8';
            selectBox.insertBefore(placeholder, arrow);
        } else {
            selectedValues.forEach(function(val) {
                var chip = document.createElement('span');
                chip.style.cssText = 'background:#e2e8f0; color:#334155; padding:2px 6px; border-radius:4px; font-size:11px; display:inline-flex; align-items:center; gap:4px; border:1px solid #cbd5e1;';
                chip.textContent = val;

                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'cursor:pointer; font-size:12px; font-weight:bold; color:#64748b;';
                closeBtn.onclick = function(e) { e.stopPropagation(); toggleValue(val, false); };
                chip.appendChild(closeBtn);
                selectBox.insertBefore(chip, arrow);
            });
        }
        hiddenInput.value = selectedValues.join(', ');
    }

    function toggleValue(val, isChecked) {
        val = (val || '').trim();
        if (val === '') return;
        if (isChecked && !selectedValues.includes(val)) selectedValues.push(val);
        else if (!isChecked) selectedValues = selectedValues.filter(function(v){ return v !== val; });
        renderChips();
        renderList(searchInput.value);
    }

    function renderList(filterText) {
        listDiv.innerHTML = '';
        var query = (filterText || '').toLowerCase().trim();
        var exactMatch = false;

        Object.keys(optionsGrouped).forEach(function(group) {
            var items = optionsGrouped[group] || [];
            var filteredItems = items.filter(function(item) {
                if (item.toLowerCase() === query) exactMatch = true;
                return item.toLowerCase().indexOf(query) !== -1;
            });

            if (filteredItems.length > 0) {
                var groupHeader = document.createElement('div');
                groupHeader.style.cssText = 'padding:4px 8px; background:#f8fafc; font-weight:bold; font-size:11px; color:#64748b; border-bottom:1px solid #e2e8f0; border-top:1px solid #e2e8f0;';
                groupHeader.textContent = '=== ' + group + ' ===';
                listDiv.appendChild(groupHeader);

                filteredItems.forEach(function(item) {
                    var label = document.createElement('label');
                    label.style.cssText = 'display:flex; align-items:center; padding:6px 12px; cursor:pointer; font-size:12px; color:#334155; margin:0;';
                    label.onmouseover = function(){ label.style.background = '#f8f9fa'; };
                    label.onmouseout = function(){ label.style.background = 'transparent'; };

                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.value = item;
                    cb.checked = selectedValues.includes(item);
                    cb.style.cssText = 'margin:0 8px 0 0;';
                    cb.onchange = function(e){ e.stopPropagation(); toggleValue(item, e.target.checked); };

                    label.appendChild(cb);
                    label.appendChild(document.createTextNode(item));
                    listDiv.appendChild(label);
                });
            }
        });

        // OPSI INPUT MANUAL: kalau yang diketik belum ada persis di daftar
        if (query !== '' && !exactMatch) {
            var manualOpt = document.createElement('div');
            manualOpt.style.cssText = 'padding:8px 12px; cursor:pointer; font-size:12px; background:#f0fdf4; color:#166534; font-weight:bold; border-top:1px solid #bbf7d0;';
            manualOpt.innerHTML = '+ Tambah Manual: "<i>' + filterText + '</i>"';
            manualOpt.onclick = function(e) {
                e.stopPropagation();
                toggleValue(filterText, true);
                searchInput.value = '';
                searchInput.focus();
            };
            listDiv.appendChild(manualOpt);
        } else if (listDiv.children.length === 0) {
            var empty = document.createElement('div');
            empty.textContent = 'Tidak ditemukan';
            empty.style.cssText = 'padding:8px; color:#999; text-align:center; font-size:12px;';
            listDiv.appendChild(empty);
        }
    }

    selectBox.onclick = function(e) {
        e.stopPropagation();
        var isOpen = dropdown.style.display === 'block';
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ d.style.display = 'none'; });
        if (!isOpen) {
            dropdown.style.display = 'block';
            searchInput.value = '';
            searchInput.focus();
            renderList('');
        }
    };

    searchInput.onclick = function(e) { e.stopPropagation(); };
    searchInput.oninput = function(e) { renderList(e.target.value); };
    // Enter = tambah manual langsung tanpa perlu klik opsi "Tambah Manual"
    searchInput.onkeydown = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var val = searchInput.value.trim();
            if (val !== '') {
                toggleValue(val, true);
                searchInput.value = '';
                renderList('');
            }
        }
    };

    renderChips();
}

document.addEventListener('click', function(e) {
    // Tutup dropdown jika yang diklik berada di luar menu kustom dan di luar kotak pilihan
    if (!e.target.closest('.custom-dropdown-menu') && 
        !e.target.closest('[id$="_container_"]') &&
        !e.target.closest('[id^="pt_"]') &&
        !e.target.closest('[id^="pa_"]') &&
        !e.target.closest('#teknikContainer') &&
        !e.target.closest('#paContainer')) {
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ 
            d.style.display = 'none'; 
        });
    }
});

</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>