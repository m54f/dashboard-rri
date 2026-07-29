# 📝 Setup "Created By" Property in Notion

## ⚠️ Important Note

Property "Created By" tracking sudah diimplementasikan di code, tapi **di-comment out secara default** karena property ini harus dibuat dulu di Notion database sebelum bisa digunakan.

## 🔍 Check Current Status

Jalankan script checker untuk melihat database mana yang sudah/belum punya property:

```bash
php check-created-by-property.php
```

Script ini akan menampilkan:
- ✓ Database yang sudah punya property "Created By"
- ✗ Database yang belum punya property
- Link langsung ke setiap database Notion

## 📋 Databases yang Perlu Property

1. **Checklist** - `DATABASE_ID_CHECKLIST`
2. **Troubleshoot** - `DATABASE_ID_TROUBLESHOOT`
3. **Pemadaman** - `DATABASE_ID_PADAM_PLN`
4. **Peminjaman** - `DATABASE_ID_PEMINJAMAN`
5. **Petugas** - `DATABASE_ID_PETUGAS`
6. **Jurnal/Kegiatan** - `DATABASE_ID_KEGIATAN`
7. **YouTube/Video** - `DATABASE_ID_YOUTUBE`

## 🛠️ Cara Menambahkan Property di Notion

### Step 1: Buka Database di Notion

Klik database yang ingin ditambahkan property (gunakan URL dari script checker).

### Step 2: Tambah Property

1. Di view tabel database, scroll ke kolom paling kanan
2. Klik icon **"+"** (Add a property)
3. Di dialog yang muncul:
   - **Property name**: `Created By` (harus EXACT sama, case-sensitive!)
   - **Property type**: Pilih **"Text"** atau **"Rich text"**
4. Klik **Create**

### Step 3: Verifikasi

Property baru akan muncul sebagai kolom di tabel. Anda bisa:
- Drag untuk reorder posisinya
- Hide jika tidak ingin ditampilkan di view (data tetap tersimpan)
- Leave it visible untuk tracking manual

## ✅ Activate Tracking di Code

Setelah property ditambahkan di Notion, aktifkan tracking di file yang sesuai:

### create-checklist.php
```php
// Uncomment baris ini (hapus //)
$properties["Created By"] = ["rich_text" => [["text" => ["content" => $createdBy]]]];
```

### create-troubleshoot.php
```php
// Uncomment baris ini
$properties["Created By"] = ["rich_text" => [["text" => ["content" => $createdBy]]]];
```

### create-pemadaman.php
```php
// Uncomment baris ini
$body['properties']['Created By'] = ['rich_text' => [['text' => ['content' => $createdBy]]]];
```

### create-peminjaman.php
```php
// Uncomment baris ini
$properties['Created By'] = ['rich_text' => [['text' => ['content' => $createdBy]]]];
```

### create-petugas.php
```php
// Uncomment baris ini
$body['properties']['Created By'] = ['rich_text' => [['text' => ['content' => $createdBy]]]];
```

### create-jurnal.php
```php
// Uncomment baris ini
$properties['Created By'] = ['rich_text' => [['text' => ['content' => $createdBy]]]];
```

### add-video.php
```php
// Uncomment baris ini
$properties['Created By'] = ['rich_text' => richTextPayload($createdBy)];
```

## 🧪 Testing

Setelah mengaktifkan tracking:

1. **Open Mode**: Buat entry baru
   - Check di Notion → "Created By" = "Open Mode"

2. **Admin Mode**: Login, buat entry baru
   - Check di Notion → "Created By" = "[Admin Name]"

## 🎯 Quick Setup (All at Once)

Jika ingin setup semua database sekaligus:

### Option 1: Manual (Recommended)
1. Buka satu database di Notion
2. Tambahkan property "Created By" (Text)
3. **Duplicate property** ke database lain:
   - Right-click property header → Copy
   - Buka database lain → Right-click space → Paste
4. Repeat untuk semua 7 databases

### Option 2: Via Notion Template
1. Buat property di satu database
2. Create database template yang include property ini
3. Use template untuk database baru

## 📊 Monitoring

Setelah aktif, Anda bisa:
- Filter entries by "Created By" di Notion
- Buat view terpisah untuk "Open Mode" vs "Admin"
- Export laporan dengan creator info
- Audit trail lengkap

## ⚙️ Configuration Reference

File locations:
```
api/create-checklist.php     → Line ~85 (approx)
api/create-troubleshoot.php  → Line ~110 (approx)
api/create-pemadaman.php     → Line ~50 (approx)
api/create-peminjaman.php    → Line ~65 (approx)
api/create-petugas.php       → Line ~35 (approx)
api/create-jurnal.php        → Line ~30 (approx)
api/add-video.php            → Line ~140 (approx)
```

Cari comment:
```php
// Add Created By tracking (optional - uncomment after adding property to Notion)
```

## 🔄 Rollback

Jika ada masalah, bisa disable tracking dengan:
1. Comment kembali baris `$properties["Created By"]`
2. Property di Notion bisa di-hide atau di-delete
3. Data yang sudah ada tidak terpengaruh

## 💡 Tips

1. **Konsistensi naming**: Pastikan property name EXACT "Created By" di semua database
2. **Property type**: Text atau Rich Text sama saja untuk use case ini
3. **Visibility**: Bisa hide property di view tapi data tetap tracked
4. **Migration**: Entries lama akan kosong, entries baru otomatis terisi
5. **Bulk update**: Tidak perlu update entries lama, tracking mulai dari sekarang

## 📞 Troubleshooting

### Error: "Created By is not a property that exists"
**Solution**: Property belum dibuat di Notion. Follow steps di atas.

### Error: "Invalid value for Created By"
**Solution**: 
- Check property type (harus Text atau Rich Text)
- Check property name (case-sensitive: "Created By")

### Tracking tidak jalan
**Solution**:
- Verify baris sudah di-uncomment di create-*.php
- Check `getApiAdminName()` return value
- Clear PHP session dan test lagi

### Property tidak muncul di Notion
**Solution**:
- Refresh Notion page
- Check di "Properties" menu (mungkin di-hide)
- Verify di Database settings

---

**Last Updated**: 2026-01-29  
**Status**: Optional Feature (Disabled by Default)  
**Action Required**: Follow setup steps to enable
