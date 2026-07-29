# 📡 Logbook TMB RRI Malang

Dashboard operasional Divisi **Teknologi & Media Baru (TMB)** — RRI Malang.  
Sistem pencatatan kegiatan siaran, monitoring perangkat, dan manajemen troubleshoot berbasis web.

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
│   ├── petugas.php           ← Data tim & jadwal shift
│   └── personal.php          ← Catatan pribadi operator
│
├── api/                      ← Backend API (Notion integration)
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

- **XAMPP** (Apache + PHP 7.4+)
- **PHP cURL extension** (aktif secara default di XAMPP)
- Koneksi internet (untuk API Notion)

## 🚀 Cara Menjalankan

1. Pastikan XAMPP sudah terinstall dan Apache berjalan
2. Clone/copy folder ini ke `C:\xampp\htdocs\logbook-rri\`
3. Buka browser → `http://localhost/logbook-rri/`
4. Dashboard akan terbuka secara otomatis

## 🔑 Konfigurasi

### 1. Notion API
Edit file `config/notion.php` untuk mengatur:
- `NOTION_TOKEN` — Integration token dari Notion
- `DATABASE_ID_*` — ID database Notion untuk setiap modul

### 2. Admin Credentials (RBAC)
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

### Security & Access Control
- 🔐 Role-Based Access Control (RBAC)
- 🛡️ Admin mode untuk edit/delete operations
- 🌐 Open mode untuk public access
- 📝 Activity tracking dengan "Created By"
- 🔒 API endpoint protection
- 🔑 Bcrypt password hashing

## 📦 Tech Stack

| Komponen    | Teknologi                     |
|-------------|-------------------------------|
| Backend     | PHP (vanilla, tanpa framework)|
| Frontend    | HTML, CSS, JavaScript         |
| Font        | Inter (Google Fonts)          |
| Icons       | Remix Icon 3.5                |
| Charts      | Chart.js                      |
| Data Source | Notion API                    |
