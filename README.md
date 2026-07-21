# 📡 Logbook TMB RRI Malang

Dashboard operasional Divisi **Teknologi & Media Baru (TMB)** — RRI Malang.  
Sistem pencatatan kegiatan siaran, monitoring perangkat, dan manajemen troubleshoot berbasis web.

## 🏗️ Struktur Proyek

```
logbook-rri/
├── index.php                 ← Entry point (redirect ke dashboard)
├── README.md                 ← Dokumentasi proyek
│
├── config/
│   └── notion.php            ← Konfigurasi API Notion (token & database IDs)
│
├── includes/                 ← Komponen layout yang digunakan semua halaman
│   ├── header.php            ← <head>, CSS, sidebar, topbar
│   ├── sidebar.php           ← Navigasi sidebar
│   ├── topbar.php            ← Top bar (search, notifikasi, user info)
│   └── footer.php            ← Penutup tag HTML
│
├── pages/                    ← Semua halaman modul
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
│   └── troubleshoot.php      ← Fetch data troubleshoot dari Notion
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

Edit file `config/notion.php` untuk mengatur:
- `NOTION_TOKEN` — Integration token dari Notion
- `DATABASE_ID_*` — ID database Notion untuk setiap modul

## 📦 Tech Stack

| Komponen    | Teknologi                     |
|-------------|-------------------------------|
| Backend     | PHP (vanilla, tanpa framework)|
| Frontend    | HTML, CSS, JavaScript         |
| Font        | Inter (Google Fonts)          |
| Icons       | Remix Icon 3.5                |
| Charts      | Chart.js                      |
| Data Source | Notion API                    |
