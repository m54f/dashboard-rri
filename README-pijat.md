# 📦 Fitur Peminjaman Alat — Logbook RRI

Modul CRUD untuk mencatat peminjaman dan pengembalian alat siaran, terhubung langsung ke **Notion** sebagai database.

---

## 1. Prasyarat

- Integration token Notion sudah aktif (dipakai bersama fitur Troubleshoot)
- Integration sudah punya akses **Connections** ke database yang akan dibuat di langkah 2
- PHP dengan ekstensi `curl` aktif

---

## 2. Setup Database Notion

Buat database baru di Notion, beri nama **"Peminjaman Alat"**, dengan properti **persis** seperti tabel berikut (nama & tipe **case-sensitive**, harus sama persis):

| Nama Property       | Tipe          | Opsi (jika Select/Multi-select)                                                                                                       |
|----------------------|---------------|------------------------------------------------------------------------------------------------------------------------------------------|
| `Nama Kegiatan`      | Title         | —                                                                                                                                           |
| `Nama PIC PT`        | Select        | Alfian, Alif, Saiful, Yhanuar, Yayang, Yudha, Dicky, Yoyok, Anggi, Ludfi, Shofirin, Syukur, Dedy, Deny, Dayat, Winsa, Selvy                |
| `Lokasi`             | Text          | —                                                                                                                                           |
| `Tanggal Kegiatan`   | Date          | —                                                                                                                                           |
| `Tanggal Pinjam`     | Date          | —                                                                                                                                           |
| `Tanggal Kembali`    | Date          | dikosongkan sampai alat dikembalikan                                                                                                       |
| `Alat Dipinjam`      | Multi-select  | Mixer, Mic, Laptop, Tripod, Kamera, Kabel, Soundcard, Speaker, HT, Headphone, Modem, Video Switcher, Video Sender, Converter, Baterai, Lampu, Overlength, Earpik, Lainnya |
| `Detail Alat`        | Text          | diisi otomatis oleh sistem (format: `Mixer (Yamaha MG10) x2, Mic x1`)                                                                     |
| `Durasi (jam)`       | Number        | —                                                                                                                                           |
| `Keterangan`         | Text          | —                                                                                                                                           |
| `Status`             | Select        | Dipinjam, Dikembalikan                                                                                                                     |

> 💡 **Tips membuat opsi Select/Multi-select lebih cepat:** ketik satu nilai dulu di kolom lalu tekan Enter untuk membuat opsi baru — tidak perlu bikin semua opsi manual dari pengaturan property.

### Share database ke Integration

1. Buka database yang baru dibuat
2. Klik `•••` (pojok kanan atas) → **Connections**
3. Cari nama integration Notion kamu → klik untuk konfirmasi

Tanpa langkah ini, semua API call akan gagal dengan error `403 Insufficient permissions`.

### Ambil Database ID

Buka database di browser, lihat URL:
```
https://www.notion.so/workspace/NAMA-DATABASE-<DATABASE_ID>?v=...
```
`<DATABASE_ID>` adalah string 32 karakter (huruf+angka, dengan/tanpa strip) tepat sebelum `?v=`.

---

## 3. Struktur File

```
logbook-rri/
├── config/
│   └── notion.php                  ← tambahkan DATABASE_ID_PEMINJAMAN di sini
├── api/
│   ├── peminjaman.php              ← getPeminjaman() untuk ambil data (READ)
│   ├── create-peminjaman.php       ← endpoint CREATE
│   ├── update-peminjaman.php       ← endpoint UPDATE
│   ├── mark-returned.php           ← endpoint aksi cepat "Tandai Dikembalikan"
│   └── delete-peminjaman.php       ← endpoint DELETE (archive)
└── pages/
    └── peminjaman.php              ← halaman UI (tabel + modal CRUD)
```

---

## 4. Langkah Instalasi

1. Copy 7 file yang sudah dibuat ke lokasi masing-masing sesuai tabel di atas (replace file lama untuk `config/notion.php`, `api/peminjaman.php`, `pages/peminjaman.php`)
2. Buka `config/notion.php`, ganti baris:
   ```php
   define('DATABASE_ID_PEMINJAMAN', 'ISI_DENGAN_ID_DATABASE_PEMINJAMAN_ALAT');
   ```
   dengan ID database asli dari langkah 2
3. Buka halaman `pages/peminjaman.php` di browser, coba klik **"Catat Peminjaman"** dan submit satu data uji coba
4. Kalau berhasil, data akan langsung muncul di tabel Notion kamu

---

## 5. Cara Kerja Fitur

| Aksi | Tombol di UI | Endpoint yang dipanggil | Efek di Notion |
|---|---|---|---|
| Tambah data | "Catat Peminjaman" | `create-peminjaman.php` | Page baru dibuat |
| Edit data | Ikon pensil ✏️ | `update-peminjaman.php` | Page di-update (PATCH) |
| Tandai kembali | Ikon centang ✅ | `mark-returned.php` | `Status` → Dikembalikan, `Tanggal Kembali` → hari ini |
| Hapus data | Ikon tempat sampah 🗑️ | `delete-peminjaman.php` | Page di-*archive* (`archived: true`) — masuk Trash Notion, bukan hilang permanen |

**Status "Terlambat"** tidak disimpan sebagai nilai di Notion — dihitung otomatis di PHP (`api/peminjaman.php`): kalau status masih "Dipinjam" tapi `Tanggal Pinjam` sudah lewat dari hari ini, otomatis ditampilkan sebagai "Terlambat" di UI.

**Daftar alat** dicatat dalam 2 bentuk sekaligus:
- `Alat Dipinjam` (Multi-select) → untuk filter/tag ringkas
- `Detail Alat` (Text) → detail lengkap Jenis/Tipe & Qty per alat, digabung dalam satu teks

---

## 6. Troubleshooting

| Gejala | Kemungkinan Penyebab |
|---|---|
| HTTP 403 "Insufficient permissions" | Database belum di-share ke integration (lihat langkah 2) |
| HTTP 400 "X is expected to be Y" | Nama atau tipe property di Notion tidak persis sama dengan yang dipakai kode — cek ulang tabel skema di atas |
| Data tidak muncul di tabel | `DATABASE_ID_PEMINJAMAN` belum diisi / salah copy |
| Tombol Edit membuka form kosong | Cek console browser (F12) — biasanya field `data-payload` gagal di-parse karena karakter aneh di judul kegiatan |

---

## 7. Catatan Keamanan

- `NOTION_TOKEN` di `config/notion.php` adalah kredensial rahasia — **jangan commit ke repository publik** (tambahkan `config/notion.php` ke `.gitignore` jika pakai Git)
- Semua endpoint di folder `api/` menerima POST tanpa autentikasi tambahan — kalau server ini nanti diakses dari luar jaringan lokal, pertimbangkan menambahkan lapisan otentikasi (misal session login) sebelum go-live