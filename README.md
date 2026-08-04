# 📡 Logbook TMB RRI Malang

Dashboard operasional Divisi **Teknologi & Media Baru (TMB)** — RRI Malang.  
Sistem pencatatan kegiatan siaran, monitoring perangkat, manajemen troubleshoot, dan integrasi tiket CakraBot berbasis web.

## 🔐 Role-Based Access Control (RBAC)

Sistem mendukung 2 mode akses:
- **🌐 Open Mode** (Default): Akses publik untuk view & input data
- **🛡️ Admin Mode**: Akses penuh untuk edit, delete, dan manajemen

**Quick Start RBAC:**
1. Copy `config/auth.php.example` → `config/auth.php`
2. Set admin credentials dengan password hash
3. Klik icon Login di topbar untuk masuk Admin mode

📖 **[Baca dokumentasi lengkap RBAC](RBAC-GUIDE.md)**

## 🏗️ Struktur Proyek

```
logbook-rri/
├── index.php                 ← Entry point (redirect ke dashboard)
├── README.md                 ← Dokumentasi proyek
│
├── config/
│   ├── notion.php            ← Konfigurasi API Notion (token & database IDs)
│   ├── supabase.php          ← Konfigurasi koneksi Supabase PostgreSQL
│   ├── database.php          ← PDO helper untuk query Supabase
│   ├── auth.php              ← Admin credentials (NOT in git)
│   └── auth.php.example      ← Template credentials
│
├── includes/                 ← Komponen layout yang digunakan semua halaman
│   ├── header.php            ← <head>, CSS, sidebar, topbar
│   ├── sidebar.php           ← Navigasi sidebar
│   ├── topbar.php            ← Top bar (search, notifikasi, user info)
│   ├── footer.php            ← Penutup tag HTML
│   ├── auth.php              ← Role management functions
│   └── api-auth.php          ← API protection helper
│
├── pages/                    ← Semua halaman modul
│   ├── auth/                 ← Authentication pages
│   │   ├── login.php         ← Admin login page
│   │   └── logout.php        ← Logout handler
│   ├── dashboard.php         ← Ringkasan operasional harian
│   ├── agenda.php            ← Daily agenda kegiatan siaran
│   ├── checklist.php         ← Checklist kondisi perangkat
│   ├── troubleshoot.php      ← Catatan & tracking kendala teknis
│   ├── logbook.php           ← Logbook digital (kronologis)
│   ├── peminjaman.php        ← Peminjaman & pengembalian alat
│   ├── jurnal.php            ← Jurnal dokumentasi kegiatan
│   ├── gallery.php           ← Gallery siaran YouTube
│   ├── calendar.php          ← Kalender recap bulanan
│   ├── petugas.php           ← Data tim, laporan kinerja & tiket CakraBot
│   └── personal.php          ← Catatan pribadi operator
│
├── api/                      ← Backend API (Notion + Supabase integration)
│   ├── supabase-tickets.php  ← Ambil tiket CakraBot dari Supabase
│   ├── laporan-individu.php  ← Laporan kinerja per petugas (gabungan)
│   ├── troubleshoot.php      ← Fetch data troubleshoot dari Notion
│   ├── create-*.php          ← Create operations (dengan tracking)
│   ├── update-*.php          ← Update operations (admin-only)
│   └── delete-*.php          ← Delete operations (admin-only)
│
└── assets/
    └── css/
        └── style.css         ← Stylesheet utama
```

## ⚙️ Requirements

- **XAMPP** (Apache + PHP 8.0+)
- **PHP Extensions:**
  - `curl` — untuk Notion API (aktif by default di XAMPP)
  - `pdo_mysql` — aktif by default di XAMPP
  - `pdo_pgsql` — **wajib diaktifkan manual** (untuk Supabase/CakraBot)
  - `pgsql` — **wajib diaktifkan manual** (untuk Supabase/CakraBot)
- Koneksi internet (untuk Notion API & Supabase)

## 🚀 Cara Menjalankan

1. Pastikan XAMPP sudah terinstall dan Apache berjalan
2. Clone/copy folder ini ke `C:\xampp\htdocs\logbook-rri\`
3. Aktifkan extension `pdo_pgsql` (lihat seksi di bawah)
4. Salin dan isi file konfigurasi (lihat seksi Konfigurasi)
5. Buka browser → `http://localhost/logbook-rri/`
6. Dashboard akan terbuka secara otomatis

## 🐘 Mengaktifkan PostgreSQL (pdo_pgsql) di XAMPP

Fitur **Laporan Petugas → Tab 🤖 CakraBot** membutuhkan koneksi ke Supabase PostgreSQL.  
Extension `pdo_pgsql` **tidak aktif** secara default di XAMPP — ikuti langkah berikut:

### Langkah 1 — Edit `php.ini`

Buka file: `C:\xampp\php\php.ini`

Cari baris berikut (sekitar baris 947) dan **hapus tanda `;` di depannya**:

```ini
# Sebelum (masih dicomment):
;extension=pdo_pgsql
;extension=pgsql

# Sesudah (sudah aktif):
extension=pdo_pgsql
extension=pgsql
```

> **Tips:** Gunakan `Ctrl+F` dan cari `pdo_pgsql` untuk langsung menemukan barisnya.

### Langkah 2 — Restart Apache

Buka **XAMPP Control Panel** → klik **Stop** lalu **Start** pada baris Apache.

Atau via Command Prompt (sebagai Administrator):

```bat
"C:\xampp\apache\bin\httpd.exe" -k restart
```

### Langkah 3 — Verifikasi

Buka: `http://localhost/logbook-rri/test-supabase.php`

Jika muncul **"🟢 Semua tes berhasil"** dan daftar tabel Supabase muncul, koneksi sudah berhasil.

> ⚠️ **Catatan penting untuk PHP Built-in Server (`php -S localhost:8000`):**  
> Server yang dijalankan via `php -S` **harus dihentikan dan dijalankan ulang** setelah mengubah `php.ini`.  
> Perubahan `php.ini` tidak akan terbaca oleh proses PHP yang sudah berjalan sebelumnya.  
> Cara restart: tekan `Ctrl+C` di terminal, lalu jalankan ulang perintah `php -S`-nya.

## 🔑 Konfigurasi

### 1. Notion API
Edit file `config/notion.php` untuk mengatur:
- `NOTION_TOKEN` — Integration token dari Notion
- `DATABASE_ID_*` — ID database Notion untuk setiap modul

### 2. Supabase PostgreSQL (Integrasi CakraBot)

Edit file `config/supabase.php` dan isi dengan connection string dari Supabase:

```php
// Connection Pooler (port 6543) — untuk SELECT/INSERT/UPDATE/DELETE sehari-hari
define('SUPABASE_DSN', 'pgsql:host=<host>.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require');

// Direct Connection (port 5432) — untuk migrasi schema / DDL
define('SUPABASE_DSN_DIRECT', 'pgsql:host=<host>.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require');

define('SUPABASE_USER', 'postgres.<project-ref>');
define('SUPABASE_PASS', '<your-password>');
```

Connection string lengkap tersedia di:  
**Supabase Dashboard → Project Settings → Database → Connection Pooling**

> ⚠️ **Keamanan:** Jangan commit `config/supabase.php` ke repository publik. Tambahkan ke `.gitignore`.

### 3. Admin Credentials (RBAC)

```bash
# Copy template
cp config/auth.php.example config/auth.php

# Edit config/auth.php dan set:
# - username
# - password hash (gunakan password_hash() PHP)
```

Lihat [RBAC-GUIDE.md](RBAC-GUIDE.md) untuk detail lengkap.

## 🎯 Fitur Utama

### Core Features
- ✅ Dashboard operasional real-time
- ✅ Checklist kondisi perangkat
- ✅ Tracking troubleshooting & kendala
- ✅ Manajemen peminjaman alat
- ✅ Jurnal kegiatan & dokumentasi
- ✅ Gallery siaran YouTube
- ✅ Manajemen data petugas
- ✅ **Laporan kinerja petugas** (gabungan Notion + Supabase)
- ✅ **Tiket CakraBot** — integrasi data tiket dari bot Telegram via Supabase

### Security & Access Control
- 🔐 Role-Based Access Control (RBAC)
- 🛡️ Admin mode untuk edit/delete operations
- 🌐 Open mode untuk public access
- 📝 Activity tracking dengan "Created By"
- 🔒 API endpoint protection
- 🔑 Bcrypt password hashing

## 📦 Tech Stack

| Komponen    | Teknologi                          |
|-------------|------------------------------------|
| Backend     | PHP 8+ (vanilla, tanpa framework)  |
| Frontend    | HTML, CSS, JavaScript              |
| Font        | Inter (Google Fonts)               |
| Icons       | Remix Icon 3.5                     |
| Charts      | Chart.js                           |
| Data Source | Notion API                         |
| Database    | Supabase (PostgreSQL 17) via PDO   |
| Bot         | CakraBot (Telegram, via Supabase)  |
