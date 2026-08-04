<?php
/**
 * Database Helper — PDO PostgreSQL via Supabase
 *
 * Cara pakai:
 *   require_once __DIR__ . '/database.php';
 *   $pdo = db();
 *   $rows = db_query("SELECT * FROM users WHERE active = ?", [true]);
 */

require_once __DIR__ . '/supabase.php';

/**
 * Membuat (atau mengambil) instance PDO singleton.
 *
 * @param  bool $direct  Jika true, pakai koneksi langsung (port 5432) — untuk DDL/migrations.
 * @return PDO
 * @throws RuntimeException jika koneksi gagal.
 */
function db(bool $direct = false): PDO
{
    static $pool        = null;
    static $direct_conn = null;

    // FIX: Tidak boleh pakai `&` pada ternary.
    // Langsung cek berdasarkan flag $direct dan return koneksi yang sesuai.
    if ($direct) {
        if ($direct_conn === null) {
            $direct_conn = create_pdo_connection(SUPABASE_DSN_DIRECT);
        }
        return $direct_conn;
    }

    if ($pool === null) {
        $pool = create_pdo_connection(SUPABASE_DSN);
    }
    return $pool;
}

/**
 * Helper internal untuk membuat instance PDO dengan konfigurasi standar.
 *
 * @param  string $dsn
 * @return PDO
 * @throws RuntimeException
 */
function create_pdo_connection(string $dsn): PDO
{
    if (!defined('SUPABASE_USER') || !defined('SUPABASE_PASS')) {
        throw new RuntimeException('Konstanta SUPABASE_USER dan SUPABASE_PASS belum didefinisikan di supabase.php');
    }

    try {
        $pdo = new PDO(
            $dsn,
            SUPABASE_USER,
            SUPABASE_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Gunakan prepared statement native PostgreSQL
                PDO::ATTR_PERSISTENT         => false,
                // PDO::ATTR_TIMEOUT dihapus karena TIDAK EFEKTIF di driver PostgreSQL.
                // Untuk set timeout di Postgres, gunakan parameter DSN: pgsql:host=...;connect_timeout=10
            ]
        );
        
        // Set timezone & encoding setelah connect
        $pdo->exec("SET client_encoding TO 'UTF8'");
        $pdo->exec("SET timezone TO 'Asia/Jakarta'");
        
        return $pdo;
    } catch (PDOException $e) {
        throw new RuntimeException('Koneksi Supabase gagal: ' . $e->getMessage(), 0, $e);
    }
}

/**
 * Jalankan query SELECT dan kembalikan semua baris.
 *
 * @param  string $sql    Query SQL dengan placeholder '?' atau '$1, $2'
 * @param  array  $params Parameter untuk binding
 * @return array          Array of rows (associative)
 */
function db_query(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Jalankan query SELECT dan kembalikan satu baris saja.
 *
 * @param  string $sql
 * @param  array  $params
 * @return array|null  Satu row atau null jika tidak ada.
 */
function db_first(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/**
 * Jalankan query INSERT / UPDATE / DELETE.
 *
 * @param  string $sql
 * @param  array  $params
 * @return int  Jumlah baris yang terpengaruh.
 */
function db_execute(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Mengambil satu nilai tunggal (scalar) dari kolom pertama pada baris pertama.
 * Berguna untuk query seperti COUNT(*), MAX(), atau mengambil satu field tertentu.
 *
 * @param  string $sql
 * @param  array  $params
 * @return mixed|null Nilai scalar atau null jika tidak ada baris.
 */
function db_value(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : null;
}

/**
 * Daftar semua tabel publik di database Supabase.
 * Menggunakan pg_catalog agar reliabel via connection pooler.
 *
 * @return array  Array of ['table_name' => string]
 */
function db_list_tables(): array
{
    return db_query(
        "SELECT tablename AS table_name
         FROM pg_catalog.pg_tables
         WHERE schemaname = 'public'
         ORDER BY tablename"
    );
}