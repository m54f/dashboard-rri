<?php
/**
 * Halaman Uji Koneksi Supabase
 * Akses: http://localhost/logbook-rri/test-supabase.php
 *
 * PENTING: Hapus atau lindungi file ini sebelum deploy ke produksi!
 */

require_once __DIR__ . '/config/database.php';

$results  = [];
$overallOk = true;

// ── Tes 1: Koneksi dasar ──────────────────────────────────────────────────────
$t1Start = microtime(true);
try {
    $pdo = db();
    $ping = db_first("SELECT NOW() AS server_time, version() AS pg_version");
    $t1ms = round((microtime(true) - $t1Start) * 1000, 1);
    $results['connection'] = [
        'ok'      => true,
        'label'   => 'Koneksi ke Supabase',
        'message' => "✅ Terhubung dalam {$t1ms} ms",
        'detail'  => "Server time: " . ($ping['server_time'] ?? '-') . "<br>PostgreSQL: " . substr($ping['pg_version'] ?? '-', 0, 50),
    ];
} catch (Throwable $e) {
    $overallOk = false;
    $results['connection'] = [
        'ok'      => false,
        'label'   => 'Koneksi ke Supabase',
        'message' => '❌ Gagal terhubung',
        'detail'  => htmlspecialchars($e->getMessage()),
    ];
}

// ── Tes 2: Daftar tabel ───────────────────────────────────────────────────────
$tableNames = [];
if ($results['connection']['ok']) {
    try {
        // Gunakan pg_catalog yang lebih reliabel daripada information_schema di Supabase
        $tables = db_query(
            "SELECT tablename AS table_name
             FROM pg_catalog.pg_tables
             WHERE schemaname = 'public'
             ORDER BY tablename"
        );
        $tableNames = array_column($tables, 'table_name');
        $results['tables'] = [
            'ok'      => true,
            'label'   => 'Daftar Tabel (public schema)',
            'message' => '✅ Ditemukan ' . count($tableNames) . ' tabel',
            'detail'  => empty($tableNames)
                ? '<em>Belum ada tabel — schema kosong.</em>'
                : implode(', ', array_map('htmlspecialchars', $tableNames)),
        ];
    } catch (Throwable $e) {
        $overallOk = false;
        $results['tables'] = [
            'ok'      => false,
            'label'   => 'Daftar Tabel',
            'message' => '❌ Gagal membaca schema',
            'detail'  => htmlspecialchars($e->getMessage()),
        ];
    }

    // ── Tes 3: Baca data dari tiap tabel (5 baris pertama) ───────────────────
    if (!empty($tableNames)) {
        foreach ($tableNames as $tbl) {
            try {
                $rows    = db_query("SELECT * FROM \"$tbl\" LIMIT 5");
                $cols    = empty($rows) ? [] : array_keys($rows[0]);
                $results["table_$tbl"] = [
                    'ok'      => true,
                    'label'   => "📋 Tabel: <code>$tbl</code>",
                    'message' => '✅ ' . count($rows) . ' baris (max 5)',
                    'detail'  => buildTable($cols, $rows),
                ];
            } catch (Throwable $e) {
                $results["table_$tbl"] = [
                    'ok'      => false,
                    'label'   => "📋 Tabel: <code>$tbl</code>",
                    'message' => '⚠️ Tidak dapat dibaca',
                    'detail'  => htmlspecialchars($e->getMessage()),
                ];
            }
        }
    }
}


// ── Helper: render HTML table ─────────────────────────────────────────────────
function buildTable(array $cols, array $rows): string
{
    if (empty($rows)) {
        return '<em style="color:#64748b">Tabel kosong (0 baris).</em>';
    }
    $html = '<div style="overflow-x:auto"><table class="data-table"><thead><tr>';
    foreach ($cols as $c) {
        $html .= '<th>' . htmlspecialchars($c) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($cols as $c) {
            $val = $row[$c] ?? null;
            if ($val === null) {
                $html .= '<td><span class="null-val">NULL</span></td>';
            } elseif (is_bool($val)) {
                $html .= '<td><span class="bool-val">' . ($val ? 'true' : 'false') . '</span></td>';
            } else {
                $truncated = mb_strlen((string)$val) > 80 ? mb_substr((string)$val, 0, 80) . '…' : (string)$val;
                $html .= '<td>' . htmlspecialchars($truncated) . '</td>';
            }
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supabase Connection Test — Logbook RRI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', system-ui, sans-serif;
    background: #0f1117;
    color: #e2e8f0;
    min-height: 100vh;
    padding: 2rem 1rem;
  }

  .container { max-width: 900px; margin: 0 auto; }

  /* ── Header ── */
  .page-header {
    display: flex; align-items: center; gap: 1rem;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 16px;
    padding: 1.75rem 2rem;
    margin-bottom: 2rem;
    position: relative; overflow: hidden;
  }
  .page-header::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at top right, rgba(99,102,241,.15), transparent 60%);
    pointer-events: none;
  }
  .header-icon {
    width: 56px; height: 56px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem; flex-shrink: 0;
  }
  .header-text h1 { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.025em; }
  .header-text p  { font-size: 0.9rem; color: #94a3b8; margin-top: 4px; }

  /* ── Overall badge ── */
  .overall-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 99px;
    font-weight: 700; font-size: 0.95rem;
    margin-bottom: 1.75rem;
  }
  .overall-badge.ok  { background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(16,185,129,.3); }
  .overall-badge.err { background: rgba(239,68,68,.15);  color: #f87171; border: 1px solid rgba(239,68,68,.3); }

  /* ── Result cards ── */
  .result-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1rem;
    transition: border-color .2s ease;
  }
  .result-card.card-ok  { border-left: 4px solid #10b981; }
  .result-card.card-err { border-left: 4px solid #ef4444; }
  .result-card:hover { border-color: #6366f1; }

  .result-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.25rem;
    cursor: pointer; user-select: none;
  }
  .result-label { font-weight: 600; font-size: 0.95rem; }
  .result-status { font-size: 0.88rem; font-weight: 500; }
  .result-status.ok  { color: #34d399; }
  .result-status.err { color: #f87171; }

  .result-detail {
    border-top: 1px solid #334155;
    padding: 1rem 1.25rem;
    font-size: 0.85rem;
    color: #94a3b8;
    line-height: 1.6;
  }

  /* ── Data table ── */
  .data-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.78rem;
    margin-top: 0.5rem;
  }
  .data-table th {
    background: #0f172a;
    color: #6366f1;
    text-align: left;
    padding: 8px 12px;
    border-bottom: 1px solid #334155;
    font-weight: 600;
    white-space: nowrap;
  }
  .data-table td {
    padding: 7px 12px;
    border-bottom: 1px solid #1e2d3d;
    color: #cbd5e1;
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: top;
  }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table tr:hover td { background: rgba(99,102,241,.06); }
  .null-val { color: #475569; font-style: italic; }
  .bool-val { color: #f59e0b; font-weight: 600; }

  /* ── Warning banner ── */
  .warning-banner {
    background: rgba(245,158,11,.1);
    border: 1px solid rgba(245,158,11,.3);
    border-radius: 10px;
    padding: 0.85rem 1.25rem;
    font-size: 0.85rem;
    color: #fbbf24;
    margin-bottom: 1.5rem;
    display: flex; align-items: center; gap: 0.6rem;
  }
</style>
</head>
<body>
<div class="container">

  <div class="page-header">
    <div class="header-icon">🗄️</div>
    <div class="header-text">
      <h1>Supabase Connection Test</h1>
      <p>Logbook RRI — PostgreSQL via Supabase (ap-northeast-1)</p>
    </div>
  </div>

  <div class="warning-banner">
    ⚠️ <strong>File ini hanya untuk development!</strong> Hapus atau blokir aksesnya sebelum deploy ke server publik.
  </div>

  <div class="overall-badge <?= $overallOk ? 'ok' : 'err' ?>">
    <?= $overallOk ? '🟢 Semua tes berhasil' : '🔴 Ada kegagalan — periksa detail di bawah' ?>
  </div>

  <?php foreach ($results as $key => $r): ?>
  <div class="result-card <?= $r['ok'] ? 'card-ok' : 'card-err' ?>">
    <div class="result-header">
      <span class="result-label"><?= $r['label'] ?></span>
      <span class="result-status <?= $r['ok'] ? 'ok' : 'err' ?>"><?= $r['message'] ?></span>
    </div>
    <?php if (!empty($r['detail'])): ?>
    <div class="result-detail"><?= $r['detail'] ?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

</div>
</body>
</html>
