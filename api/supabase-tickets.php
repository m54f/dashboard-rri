<?php
/**
 * api/supabase-tickets.php
 *
 * Helper: ambil data tiket dari Supabase PostgreSQL untuk laporan petugas.
 * Hanya anggota yang terdaftar di tabel `users` dengan is_active = true yang diproses.
 *
 * Fungsi utama:
 *   - getSupabaseMembers() → array user aktif dari Supabase, di-index by telegram_id
 *   - getTicketsForLaporan(string $namaInput) → array riwayat tiket siap-pakai
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Ambil semua anggota aktif dari Supabase.
 * Filter: is_active = true (tidak ada filter role agar fleksibel).
 *
 * @return array  Asosiatif: [telegram_id => ['full_name', 'username', 'role', ...]]
 */
function getSupabaseMembers(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        $rows = db_query(
            "SELECT id, telegram_id, username, full_name, role, is_active
             FROM users
             WHERE is_active = true
             ORDER BY full_name"
        );

        $cache = [];
        foreach ($rows as $row) {
            $cache[(string)$row['telegram_id']] = $row;
        }
        return $cache;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Cek apakah telegram_id adalah anggota aktif.
 *
 * @param  string|int|null $telegramId
 * @return bool
 */
function isActiveMember($telegramId): bool
{
    if (empty($telegramId)) return false;
    $members = getSupabaseMembers();
    return isset($members[(string)$telegramId]);
}

/**
 * Cek apakah nama dari Notion cocok dengan salah satu anggota Supabase.
 * Pencocokan case-insensitive dan parsial (substring).
 *
 * @param  string $namaNotion   Nama dari filter laporan (bisa kosong = semua)
 * @param  string $fullName     Nama lengkap dari tabel users Supabase
 * @return bool
 */
function namaMatchMember(string $namaNotion, string $fullName): bool
{
    if (empty(trim($namaNotion))) return true; // Kosong = semua anggota

    $a = strtolower(trim($namaNotion));
    $b = strtolower(trim($fullName));

    // Cocok jika salah satu substring dari yang lain
    return strpos($a, $b) !== false || strpos($b, $a) !== false;
}

/**
 * Hitung durasi antara dua timestamp PostgreSQL.
 * Mengembalikan string "Xj Ym" atau '-' jika tidak bisa dihitung.
 *
 * @param  string|null $start  Timestamp ISO dari created_at
 * @param  string|null $end    Timestamp ISO dari resolved_at
 * @return array  ['text' => string, 'menit' => int]
 */
function hitungDurasiTiket(?string $start, ?string $end): array
{
    if (empty($start) || empty($end)) {
        return ['text' => '-', 'menit' => 0];
    }
    try {
        // Supabase menyimpan dalam format: "2026-08-04 04:26:08.224"
        $dtStart = new DateTime($start, new DateTimeZone('UTC'));
        $dtEnd   = new DateTime($end,   new DateTimeZone('UTC'));
        $diff    = $dtStart->diff($dtEnd);

        // Hindari durasi negatif
        if ($dtEnd < $dtStart) {
            return ['text' => '-', 'menit' => 0];
        }

        $totalMenit = ($diff->days * 1440) + ($diff->h * 60) + $diff->i;
        $jam        = floor($totalMenit / 60);
        $sisa       = $totalMenit % 60;

        return [
            'text'  => $jam > 0 ? "{$jam}j {$sisa}m" : "{$sisa}m",
            'menit' => $totalMenit,
        ];
    } catch (Throwable $e) {
        return ['text' => '-', 'menit' => 0];
    }
}

/**
 * Format timestamp Supabase menjadi string tanggal yang mudah dibaca.
 * Contoh: "2026-08-04 04:26:08.224" → "4 Agustus 2026"
 *
 * @param  string|null $ts
 * @return string
 */
function formatTanggalSupabase(?string $ts): string
{
    if (empty($ts)) return '-';
    try {
        $dt = new DateTime($ts, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return (int)$dt->format('j') . ' ' . $bulan[(int)$dt->format('n')] . ' ' . $dt->format('Y');
    } catch (Throwable $e) {
        return substr((string)$ts, 0, 10);
    }
}

/**
 * Format timestamp ke ISO date (Y-m-d) untuk sorting.
 *
 * @param  string|null $ts
 * @return string
 */
function tsToIsoDate(?string $ts): string
{
    if (empty($ts)) return '1970-01-01';
    try {
        $dt = new DateTime($ts, new DateTimeZone('UTC'));
        return $dt->format('Y-m-d');
    } catch (Throwable $e) {
        return substr((string)$ts, 0, 10);
    }
}

/**
 * Ambil riwayat tiket dari Supabase, diformat siap-pakai untuk laporan-individu.php
 *
 * Filter keamanan:
 *   - Hanya tiket yang assignee/creator-nya adalah anggota aktif (is_active = true)
 *   - Jika $namaInput tidak kosong, tambah filter nama vs full_name
 *
 * @param  string $namaInput  Nama petugas dari query (?nama=...) atau '' untuk semua
 * @return array  Array of riwayat tiket
 */
function getTicketsForLaporan(string $namaInput): array
{
    $members = getSupabaseMembers();
    if (empty($members)) return [];

    $riwayat      = [];
    $totalDurasi  = 0;

    try {
        // Ambil semua tiket beserta info assignee dari tabel users
        // LEFT JOIN agar tiket OPEN tanpa assignee tetap diambil (via creator)
        $tickets = db_query(
            "SELECT
                t.id,
                t.ticket_code,
                t.description,
                t.status,
                t.creator_id,
                t.assignee_id,
                t.created_at,
                t.taken_at,
                t.resolved_at,
                -- Ambil info assignee dari tabel users
                ua.full_name   AS assignee_name,
                ua.username    AS assignee_username,
                ua.role        AS assignee_role,
                ua.is_active   AS assignee_active,
                -- Ambil info creator dari tabel users
                uc.full_name   AS creator_name,
                uc.username    AS creator_username,
                uc.role        AS creator_role,
                uc.is_active   AS creator_active
             FROM tickets t
             LEFT JOIN users ua ON ua.telegram_id::text = t.assignee_id::text
             LEFT JOIN users uc ON uc.telegram_id::text = t.creator_id::text
             ORDER BY t.created_at DESC"
        );

        foreach ($tickets as $t) {
            // ── FILTER 1: Tentukan "penanggung jawab" tiket ──────────────────
            // Prioritas: assignee → creator (jika assignee kosong/bukan anggota)
            $namaPertugas   = null;
            $rolePertugas   = null;
            $isActivePertugas = false;

            if (!empty($t['assignee_id']) && !empty($t['assignee_name'])) {
                // Punya assignee yang terdaftar di tabel users
                $namaPertugas     = $t['assignee_name'];
                $rolePertugas     = $t['assignee_role'];
                $isActivePertugas = (bool)$t['assignee_active'];
            } elseif (!empty($t['creator_id']) && !empty($t['creator_name'])) {
                // Tidak ada assignee → gunakan creator
                $namaPertugas     = $t['creator_name'];
                $rolePertugas     = $t['creator_role'];
                $isActivePertugas = (bool)$t['creator_active'];
            }

            // ── FILTER 2: Harus anggota aktif ───────────────────────────────
            if (empty($namaPertugas) || !$isActivePertugas) {
                // Bukan anggota terdaftar atau sudah tidak aktif → lewati
                continue;
            }

            // ── FILTER 3: Cocokkan nama (jika filter spesifik diberikan) ────
            if (!namaMatchMember($namaInput, $namaPertugas)) {
                continue;
            }

            // ── Format data tiket ────────────────────────────────────────────
            $durasi = hitungDurasiTiket($t['created_at'], $t['resolved_at'] ?? null);

            // Status mapping
            $statusMap = [
                'RESOLVED' => 'Selesai',
                'OPEN'     => 'Menunggu',
                'ASSIGNED' => 'Dalam Proses',
            ];
            $statusTampil = $statusMap[strtoupper($t['status'] ?? '')] ?? ($t['status'] ?? '-');

            // Deskripsi: kode tiket + isi laporan
            $kode      = htmlspecialchars($t['ticket_code'] ?? '???', ENT_QUOTES, 'UTF-8');
            $deskripsi = htmlspecialchars(
                mb_substr($t['description'] ?? '(tanpa deskripsi)', 0, 200),
                ENT_QUOTES, 'UTF-8'
            );

            // Label nama di mode "semua pegawai"
            $namaLabel = empty(trim($namaInput))
                ? ' (Oleh: ' . htmlspecialchars($namaPertugas, ENT_QUOTES, 'UTF-8') . ')'
                : '';

            $riwayat[] = [
                'id'          => (int)$t['id'],
                'ticket_code' => $t['ticket_code'] ?? '',
                'raw_status'  => $t['status'] ?? 'OPEN',
                'raw_description' => $t['description'] ?? '',
                'assignee_id' => $t['assignee_id'] ? (string)$t['assignee_id'] : '',
                'creator_id'  => $t['creator_id'] ? (string)$t['creator_id'] : '',
                'assignee_name' => $t['assignee_name'] ?? '',
                'creator_name'  => $t['creator_name'] ?? '',
                'tanggal'     => formatTanggalSupabase($t['created_at']),
                'tanggal_raw' => tsToIsoDate($t['created_at']),  // untuk sorting
                'kategori'    => 'Tiket CakraBot',
                'deskripsi'   => "[{$kode}] {$deskripsi}{$namaLabel}",
                'status'      => $statusTampil,
                'durasi'      => $durasi['text'],
                'url'         => '#',   // CakraBot tidak punya URL publik
                'foto'        => '',
                'fotos'       => [],
            ];

            $totalDurasi += $durasi['menit'];
        }
    } catch (Throwable $e) {
        // Gagal koneksi atau query error — return array kosong agar tidak crash
        return [];
    }

    return $riwayat;
}

/**
 * Ambil daftar anggota aktif Supabase sebagai array sederhana untuk opsi dropdown.
 *
 * @return array  Array of ['telegram_id' => string, 'full_name' => string, 'role' => string]
 */
function getSupabaseUsersList(): array
{
    try {
        $rows = db_query(
            "SELECT telegram_id, full_name, role
             FROM users
             WHERE is_active = true
             ORDER BY full_name"
        );
        return array_map(function($r) {
            return [
                'telegram_id' => (string)$r['telegram_id'],
                'full_name'   => $r['full_name'],
                'role'        => $r['role'],
            ];
        }, $rows);
    } catch (Throwable $e) {
        return [];
    }
}

