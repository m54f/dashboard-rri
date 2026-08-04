<?php
/**
 * Konfigurasi Koneksi Supabase (PostgreSQL)
 *
 * DATABASE_URL  : Connection Pooler (Transaction, port 6543) — untuk query normal
 * DIRECT_URL    : Direct/Session pooler (port 5432) — untuk migrasi / DDL
 *
 * Password sudah di-URL-encode (%23 = '#')
 */

// ── Transaction Pooler (pakai ini untuk SELECT / INSERT / UPDATE / DELETE biasa) ──
define('SUPABASE_DSN', 'pgsql:host=aws-0-ap-northeast-1.pooler.supabase.com;port=6543;dbname=postgres');
define('SUPABASE_DSN_DIRECT', 'pgsql:host=aws-0-ap-northeast-1.pooler.supabase.com;port=5432;dbname=postgres');

define('SUPABASE_USER', 'postgres.bunpjuqkajoxsiudklqz');
define('SUPABASE_PASS', '55t#u5Gszq9rgkM');  // '%23' di-decode menjadi '#'

// Telegram Group ID (Divisi Teknik)
define('TEKNIK_GROUP_ID', '1466766232');
