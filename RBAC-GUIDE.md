# 🔐 Role-Based Access Control (RBAC) - Implementation Guide

## 📋 Overview

Sistem RBAC Logbook RRI mendukung 2 role utama:
- **Open Mode** (Default): Akses publik untuk melihat data dan input data baru
- **Admin Mode**: Akses penuh untuk edit, delete, dan manajemen sistem

## 🎯 Fitur Implementasi

### ✅ Yang Sudah Diimplementasikan

1. **Config Management & Security** ✓
   - Credential terpisah di `config/auth.php` (tidak masuk git)
   - Password menggunakan bcrypt hashing
   - Template tersedia di `config/auth.php.example`

2. **Role Management** ✓
   - Session-based role tracking
   - Helper functions di `includes/auth.php`
   - Auto-detection role di setiap page

3. **UI Indicators** ✓
   - Role badge di topbar (hijau = Open, biru = Admin)
   - Login/Logout button conditional
   - Responsive design untuk mobile

4. **Button Visibility Control** ✓
   - Edit/Delete buttons hanya muncul untuk Admin
   - View buttons tetap tersedia untuk semua role
   - Implementasi di 7 pages utama

5. **API Protection** ✓
   - Semua update/delete API endpoints dilindungi
   - Auto-reject dengan HTTP 403 jika bukan admin
   - 15 API files ter-protect

6. **Activity Tracking** ✓
   - "Created By" field otomatis di semua create operations
   - Tracking admin name atau "Open Mode"
   - Integrasi dengan Notion database

## 🚀 Setup & Konfigurasi

### 1. Setup Credentials

```bash
# Copy template
cp config/auth.php.example config/auth.php

# Edit config/auth.php dan set password Anda
# Password akan di-hash otomatis saat login
```

**File: config/auth.php**
```php
<?php
// Admin Credentials Configuration
define('ADMIN_CREDENTIALS', [
    'username' => 'admin',
    // Password hash (bcrypt) - ganti dengan password Anda
    'password' => '$2y$10$abcdefghijklmnopqrstuvwxyz...'
]);
```

### 2. Generate Password Hash

Gunakan PHP untuk generate hash:

```php
<?php
// generate-hash.php
$password = 'password_anda_disini';
echo password_hash($password, PASSWORD_BCRYPT);
```

### 3. Verifikasi Setup

1. Buka aplikasi di browser → akan otomatis masuk Open Mode
2. Klik icon Login di topbar → masuk halaman login
3. Login dengan credentials yang sudah di-set
4. Pastikan badge berubah jadi biru (Admin)
5. Edit/Delete buttons sekarang muncul

## 🔧 Technical Architecture

### File Structure

```
logbook-rri/
├── config/
│   ├── auth.php              # Credentials (NOT in git)
│   └── auth.php.example      # Template
├── includes/
│   ├── auth.php              # Role management functions
│   └── api-auth.php          # API protection helper
├── pages/auth/
│   ├── login.php             # Login page
│   └── logout.php            # Logout handler
└── api/
    ├── create-*.php          # Protected + tracking
    ├── update-*.php          # Admin-only
    └── delete-*.php          # Admin-only
```

### Key Functions

**includes/auth.php:**
```php
getRole()        // Returns 'admin' or 'open'
getUserLabel()   // Returns formatted label for UI
isOpen()         // Check if in open mode
```

**includes/api-auth.php:**
```php
requireAdmin()      // Blocks non-admin API calls
getApiRole()        // Get role in API context
getApiAdminName()   // Get admin name for tracking
```

## 📝 Usage Examples

### Frontend - Hide Buttons

```php
<?php if (getRole() === 'admin'): ?>
  <button onclick="editEntry()">Edit</button>
  <button onclick="deleteEntry()">Delete</button>
<?php endif; ?>
```

### Frontend - JavaScript Role Check

```javascript
const userRole = '<?= getRole() ?>';

if (userRole === 'admin') {
  // Show admin features
  renderEditButton();
}
```

### API - Protect Endpoint

```php
<?php
require_once __DIR__ . '/../includes/api-auth.php';
requireAdmin(); // Will exit with 403 if not admin

// Your API logic here
```

### API - Track Creator

```php
<?php
require_once __DIR__ . '/../includes/api-auth.php';

$createdBy = getApiAdminName() ?? 'Open Mode';

$properties = [
  'Title' => ['title' => [['text' => ['content' => $title]]]],
  'Created By' => ['rich_text' => [['text' => ['content' => $createdBy]]]]
];
```

## 🛡️ Security Features

### 1. Password Security
- Bcrypt hashing (cost factor 10)
- No plaintext passwords in code
- Config file excluded from git

### 2. API Protection
- Session-based authentication
- HTTP 403 for unauthorized access
- All write operations protected

### 3. Input Validation
- Username/password validation
- CSRF protection via session
- SQL injection prevention (parameterized)

### 4. Audit Trail
- "Created By" tracking di semua create operations
- Session tracking untuk admin actions
- Notion timestamp otomatis

## 📱 User Interface

### Role Badge
- **Open Mode**: 🌐 Badge hijau dengan icon globe
- **Admin Mode**: 🛡️ Badge biru dengan icon shield

### Login/Logout Flow
1. **Open Mode**: Click Login button → Login page
2. **Enter credentials** → Success → Dashboard (Admin mode)
3. **Admin Mode**: Click Logout button → Back to Open mode

### Button Visibility
| Feature | Open Mode | Admin Mode |
|---------|-----------|------------|
| View data | ✅ | ✅ |
| Add new | ✅ | ✅ |
| Edit existing | ❌ | ✅ |
| Delete | ❌ | ✅ |
| Open in Notion | ✅ | ✅ |

## 🧪 Testing Checklist

### Setup Testing
- [ ] Copy auth.php.example to auth.php
- [ ] Set admin credentials
- [ ] Verify .gitignore excludes config/auth.php
- [ ] Test password hash generation

### Login Flow Testing
- [ ] Access app → default Open Mode
- [ ] Login with correct credentials → Admin mode
- [ ] Login with wrong credentials → error message
- [ ] Logout → back to Open Mode

### UI Testing
- [ ] Badge shows correct color (green/blue)
- [ ] Badge text correct (Open/Admin)
- [ ] Login button visible in Open Mode
- [ ] Logout button visible in Admin Mode
- [ ] Edit buttons hidden in Open Mode
- [ ] Edit buttons visible in Admin Mode

### API Testing
- [ ] Create entry in Open Mode → "Created By: Open Mode"
- [ ] Create entry in Admin Mode → "Created By: [Admin Name]"
- [ ] Update API in Open Mode → 403 Forbidden
- [ ] Update API in Admin Mode → Success
- [ ] Delete API in Open Mode → 403 Forbidden
- [ ] Delete API in Admin Mode → Success

### Page-Specific Testing
Test button visibility di 7 pages:
- [ ] Troubleshoot page
- [ ] Checklist page
- [ ] Pemadaman page
- [ ] Peminjaman page
- [ ] Jurnal page
- [ ] Gallery page
- [ ] Petugas page

## 🐛 Troubleshooting

### Issue: Login tidak berhasil
**Solution:**
1. Cek password hash di config/auth.php
2. Generate ulang hash dengan script PHP
3. Pastikan username match (case-sensitive)

### Issue: Edit button tidak muncul padahal sudah login
**Solution:**
1. Clear browser cookies
2. Logout dan login ulang
3. Cek session dengan `var_dump($_SESSION)`

### Issue: API return 403 padahal sudah admin
**Solution:**
1. Cek api-auth.php sudah included
2. Cek session masih aktif
3. Verifikasi requireAdmin() dipanggil setelah session_start()

### Issue: "Created By" tidak terisi
**Solution:**
1. Pastikan api-auth.php sudah di-require di create-*.php
2. Cek fungsi getApiAdminName() return value
3. Verifikasi property "Created By" ada di Notion database

## 📚 Best Practices

### 1. Password Management
- Gunakan password yang kuat (min 12 karakter)
- Jangan share credentials via chat/email
- Rotate password secara berkala

### 2. Session Security
- Set session timeout yang reasonable
- Gunakan HTTPS di production
- Regenerate session ID setelah login

### 3. Code Maintenance
- Jangan hardcode credentials
- Review .gitignore sebelum commit
- Test RBAC di staging sebelum production

### 4. User Training
- Train admin untuk logout setelah selesai
- Dokumentasikan workflow untuk tim
- Setup backup admin account

## 🔄 Upgrade Path

### Future Enhancements
1. **Multiple Admin Accounts**: Database-based user management
2. **Custom Roles**: Tambah role seperti "Editor", "Viewer"
3. **Permission Granularity**: Per-page atau per-feature permissions
4. **Activity Logs**: Comprehensive audit trail
5. **2FA Support**: Two-factor authentication
6. **API Keys**: Token-based authentication untuk integrations

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check documentation ini dulu
2. Review troubleshooting section
3. Test di development environment
4. Contact system administrator

---

**Last Updated**: 2026-01-29  
**Version**: 1.0.0  
**Status**: ✅ Production Ready
