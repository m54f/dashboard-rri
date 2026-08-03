# Changelog: Halaman Petugas - Export Excel & Data Sources

## 📋 Ringkasan Perubahan

Tanggal: 2 Agustus 2026

### ✅ Fitur Export Excel dengan Foto Tertanam

Fitur export Excel sudah **terimplementasi lengkap** dengan kemampuan:

1. **Embed Foto ke Cell Excel**
   - Menggunakan library [ExcelJS](https://github.com/exceljs/exceljs) v4.4.0
   - Foto tertanam langsung di cell dengan ukuran 62x62 pixels
   - Support format: PNG, JPEG
   - Progress indicator menampilkan proses download foto (0-100%)

2. **Cara Kerja Export:**
   ```javascript
   // 1. Download semua foto menggunakan image proxy
   const proxyUrl = '../api/image-proxy.php?url=' + encodeURIComponent(url);
   
   // 2. Konversi ke buffer
   const buffer = await blob.arrayBuffer();
   
   // 3. Tambahkan ke workbook
   const imageId = workbook.addImage({ buffer, extension });
   
   // 4. Embed ke cell
   sheet.addImage(imageId, {
     tl: { col: 4.15, row: row.number - 1 + 0.1 },
     ext: { width: 62, height: 62 }
   });
   ```

3. **File Terkait:**
   - `pages/petugas.php` (line ~800-900): Fungsi `exportToExcel()`
   - `api/image-proxy.php`: Proxy untuk download foto dari Notion/AWS
   - CDN: `https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js`

### ✅ Penambahan Data Source Baru

Laporan petugas sekarang menarik data dari **6 sumber logbook**:

#### Sebelumnya (4 sumber):
1. ✓ Pemadaman Listrik
2. ✓ Troubleshoot
3. ✓ Checklist Perangkat
4. ✓ Jurnal Kegiatan

#### **BARU (ditambahkan 2 sumber):**
5. **✨ Rekap Youtube** (`api/youtube.php`)
   - Menampilkan video yang ditangani oleh petugas sebagai:
     - **PT Setting** (Petugas Teknik Setting)
     - **PT Produksi** (Petugas Produksi/PA)
   - Flyer video digunakan sebagai foto
   - Durasi: waktu video

6. **✨ Peminjaman Alat** (`api/peminjaman.php`)
   - Menampilkan peminjaman yang PIC-nya adalah petugas tersebut
   - Detail: nama kegiatan + alat yang dipinjam
   - Status: Dipinjam / Dikembalikan / Terlambat
   - Durasi: jam peminjaman

## 📝 File yang Dimodifikasi

### 1. `api/laporan-individu.php`

**Perubahan:**
```php
// A. Tambahkan API files
$apiFiles = [
    __DIR__.'/pemadaman.php',
    __DIR__.'/troubleshoot.php',
    __DIR__.'/checklist.php',
    __DIR__.'/jurnal.php',
    __DIR__.'/youtube.php',        // ← BARU
    __DIR__.'/peminjaman.php'       // ← BARU
];

// B. Update statistik
$statistik = [
    'Pemadaman Listrik' => 0,
    'Troubleshoot' => 0,
    'Checklist' => 0,
    'Jurnal Kegiatan' => 0,
    'Rekap Youtube' => 0,           // ← BARU
    'Peminjaman Alat' => 0          // ← BARU
];

// C. Tambahkan logic pengambilan data Youtube (baris ~150)
if (function_exists('getYoutubeVideos')) {
    $youtube = getYoutubeVideos();
    foreach ($youtube as $y) {
        $isTeknik = isPetugasIni($namaInput, $y['teknik'] ?? '');
        $isPA = isPetugasIni($namaInput, $y['pa'] ?? '');
        
        if ($isTeknik || $isPA) {
            $statistik['Rekap Youtube']++;
            // ... tambahkan ke riwayat
        }
    }
}

// D. Tambahkan logic pengambilan data Peminjaman (baris ~180)
if (function_exists('getPeminjaman')) {
    $peminjaman = getPeminjaman();
    foreach ($peminjaman as $p) {
        if (isPetugasIni($namaInput, $p['pic'] ?? '')) {
            $statistik['Peminjaman Alat']++;
            // ... tambahkan ke riwayat
        }
    }
}
```

### 2. `pages/petugas.php`

**Perubahan:**

A. **Update deskripsi header:**
```php
// Baris ~206
<p>Rekap aktivitas dari seluruh logbook (Listrik, Troubleshoot, Checklist, Jurnal, Youtube, Peminjaman).</p>
```

B. **Tambahkan filter tabs:**
```html
<!-- Baris ~252 -->
<div class="filter-tab" onclick="filterReport('Rekap Youtube', this)">Youtube</div>
<div class="filter-tab" onclick="filterReport('Peminjaman Alat', this)">Peminjaman</div>
```

## 🎯 Cara Penggunaan

### Export Excel dengan Foto:

1. Buka halaman **Manajemen Petugas**
2. Klik tombol **"Laporan Keseluruhan"** atau klik **ikon laporan** pada kartu petugas
3. Filter data sesuai kebutuhan (kategori, tahun, triwulan, search)
4. Klik tombol **"Export Excel"**
5. File Excel akan otomatis terdownload dengan:
   - Header laporan (nama, tanggal cetak, filter)
   - Tabel data dengan foto tertanam di kolom Foto
   - Progress indicator selama proses

### Contoh Output Excel:

```
| No | Tanggal       | Kategori         | Deskripsi              | Foto    | Durasi | Status   |
|----|---------------|------------------|------------------------|---------|--------|----------|
| 1  | 1 Aug 2026    | Rekap Youtube    | Berita Pagi (PT Set)   | [IMG]   | 30m    | Selesai  |
| 2  | 31 Jul 2026   | Peminjaman Alat  | Liputan - Mic, Cam     | -       | 5 Jam  | Dipinjam |
| 3  | 30 Jul 2026   | Pemadaman        | Pemancar FM            | [IMG]   | 2j 15m | Selesai  |
```

## 🔍 Testing

Jalankan file test:
```bash
php test-petugas-report.php
```

Output yang diharapkan:
- ✅ Total Tugas: [jumlah]
- ✅ Statistik mencakup 6 kategori
- ✅ Riwayat menampilkan data dari semua sumber

## 📌 Catatan Teknis

### Image Proxy Security:
File `api/image-proxy.php` hanya mengizinkan URL dari:
- `*.amazonaws.com` (AWS S3 - Notion storage)
- `*.notion.com`
- `*.notion.so`

### Kompatibilitas Browser:
- ExcelJS berjalan di browser modern (Chrome, Firefox, Edge, Safari)
- Memerlukan support: `async/await`, `Blob`, `ArrayBuffer`

### Performance:
- Download foto dilakukan parallel menggunakan `Promise.all()`
- Progress indicator real-time
- Timeout: default browser (biasanya 30-60 detik per request)

## 🐛 Troubleshooting

### Foto tidak muncul di Excel:
1. Cek console browser untuk error
2. Pastikan `api/image-proxy.php` accessible
3. Verifikasi URL foto valid (dari Notion)
4. Pastikan foto format PNG/JPEG

### Export lambat:
- Normal jika banyak foto (1 foto ≈ 0.5-2 detik)
- Progress bar menunjukkan status
- Browser mungkin menampilkan warning "Page unresponsive" - abaikan

### Data Youtube/Peminjaman tidak muncul:
1. Cek file `api/youtube.php` dan `api/peminjaman.php` ada
2. Verifikasi `DATABASE_ID_YOUTUBE` dan `DATABASE_ID_PEMINJAMAN` di `config/notion.php`
3. Cek field mapping (teknik, pa, pic) sesuai dengan struktur Notion

## ✅ Checklist Implementasi

- [x] Export Excel dengan ExcelJS
- [x] Embed foto ke cell Excel
- [x] Image proxy untuk CORS
- [x] Progress indicator
- [x] Integrasi API Youtube
- [x] Integrasi API Peminjaman
- [x] Update UI filter tabs
- [x] Update statistik
- [x] Update deskripsi header
- [x] Testing & dokumentasi

## 👨‍💻 Developer Notes

Jika perlu menambahkan sumber data baru:

1. **Backend** (`api/laporan-individu.php`):
   ```php
   // A. Tambahkan ke $apiFiles
   // B. Tambahkan kategori ke $statistik
   // C. Buat blok if (function_exists('getNamaAPI'))
   // D. Loop data dan tambahkan ke $riwayat[]
   ```

2. **Frontend** (`pages/petugas.php`):
   ```html
   <!-- Tambahkan filter tab -->
   <div class="filter-tab" onclick="filterReport('Nama Kategori', this)">Label</div>
   ```

3. **Update deskripsi** di header laporan

---

**Status:** ✅ Implementasi Lengkap & Siap Production

**Last Updated:** 2 Agustus 2026
