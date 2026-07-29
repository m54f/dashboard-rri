# 🚀 RBAC Deployment Checklist

Gunakan checklist ini untuk memastikan sistem RBAC ter-deploy dengan benar.

## 📦 Pre-Deployment

### 1. File Configuration
- [ ] `config/auth.php.example` sudah ada di repository
- [ ] `config/auth.php` sudah di-exclude di `.gitignore`
- [ ] `config/notion.php` sudah di-exclude di `.gitignore` (jika ada credentials)

### 2. Code Review
- [ ] Semua create-*.php files punya tracking "Created By"
- [ ] Semua update-*.php files punya `requireAdmin()` check
- [ ] Semua delete-*.php files punya `requireAdmin()` check
- [ ] Special API files (add-video, edit-video, mark-returned, delete-video) ter-protect
- [ ] Semua 7 pages punya button visibility control

### 3. Security Check
- [ ] Tidak ada hardcoded passwords di code
- [ ] Password menggunakan bcrypt hash
- [ ] Session management sudah proper
- [ ] API endpoints return 403 untuk unauthorized access

## 🔧 Deployment Steps

### Step 1: Setup Config Files

```bash
# Di server production
cd /path/to/logbook-rri

# Copy auth template
cp config/auth.php.example config/auth.php

# Generate password hash
php -r "echo password_hash('YOUR_PASSWORD_HERE', PASSWORD_BCRYPT);"

# Edit config/auth.php dengan hash hasil generate
nano config/auth.php
```

### Step 2: Set File Permissions

```bash
# Pastikan config files tidak readable oleh public
chmod 600 config/auth.php
chmod 600 config/notion.php

# Set proper ownership
chown www-data:www-data config/auth.php
chown www-data:www-data config/notion.php
```

### Step 3: Verify .gitignore

```bash
# Pastikan ini ada di .gitignore
cat .gitignore | grep "config/auth.php"
cat .gitignore | grep "config/notion.php"
```

## ✅ Post-Deployment Testing

### 1. Open Mode Testing
- [ ] Buka aplikasi tanpa login → masuk Open Mode
- [ ] Badge hijau dengan text "Open" muncul di topbar
- [ ] Login button visible di topbar
- [ ] View data berfungsi normal
- [ ] Add new entry berfungsi (dengan Created By = "Open Mode")
- [ ] Edit buttons TIDAK muncul di semua pages
- [ ] Delete buttons TIDAK muncul di semua pages

### 2. Login Flow Testing
- [ ] Klik Login button → redirect ke login page
- [ ] Login dengan credentials salah → error message muncul
- [ ] Login dengan credentials benar → redirect ke dashboard
- [ ] Badge berubah jadi biru dengan text "Admin"
- [ ] Logout button muncul di topbar (ganti Login button)

### 3. Admin Mode Testing
- [ ] Badge biru dengan text "Admin: [nama]" muncul
- [ ] Logout button visible di topbar
- [ ] Edit buttons muncul di semua pages
- [ ] Delete buttons muncul di semua pages
- [ ] Create entry dengan Created By = "[Admin Name]"

### 4. API Protection Testing

Test dengan curl atau Postman:

```bash
# Test tanpa login (should return 403)
curl -X POST http://localhost/logbook-rri/api/update-troubleshoot.php \
  -H "Content-Type: application/json" \
  -d '{"page_id":"test123","kendala":"Test"}'

# Expected: {"success":false,"error":"Akses ditolak...","code":"FORBIDDEN"}
```

### 5. Page-by-Page Testing

Test di setiap page untuk Open dan Admin mode:

**Troubleshoot:**
- [ ] Open: View ✓, Add ✓, Edit ✗, Delete ✗
- [ ] Admin: View ✓, Add ✓, Edit ✓, Delete ✓

**Checklist:**
- [ ] Open: View ✓, Add ✓, Edit ✗, Delete ✗
- [ ] Admin: View ✓, Add ✓, Edit ✓, Delete ✓

**Pemadaman:**
- [ ] Open: View ✓, Add ✓, Edit ✗, Delete ✗
- [ ] Admin: View ✓, Add ✓, Edit ✓, Delete ✓

**Peminjaman:**
- [ ] Open: View ✓, Add ✓, Return ✗, Edit ✗, Delete ✗
- [ ] Admin: View ✓, Add ✓, Return ✓, Edit ✓, Delete ✓

**Jurnal:**
- [ ] Open: View ✓, Add ✓, Edit ✗, Delete ✗
- [ ] Admin: View ✓, Add ✓, Edit ✓, Delete ✓

**Gallery:**
- [ ] Open: View ✓, Add ✓, Edit ✗, Delete ✗
- [ ] Admin: View ✓, Add ✓, Edit ✓, Delete ✓

**Petugas:**
- [ ] Open: View ✓, View Laporan ✓, Add ✗, Edit ✗, Delete ✗
- [ ] Admin: View ✓, View Laporan ✓, Add ✓, Edit ✓, Delete ✓

### 6. Notion Integration Testing
- [ ] Create entry di Open mode → cek "Created By" di Notion = "Open Mode"
- [ ] Create entry di Admin mode → cek "Created By" di Notion = "[Admin Name]"
- [ ] Update entry di Admin mode → data berubah di Notion
- [ ] Delete entry di Admin mode → page archived di Notion

## 🐛 Troubleshooting

### Issue: Config file not found
```bash
# Check file exists
ls -la config/auth.php

# If not, copy from template
cp config/auth.php.example config/auth.php
```

### Issue: Permission denied
```bash
# Fix permissions
chmod 600 config/auth.php
chown www-data:www-data config/auth.php
```

### Issue: Session not working
```php
// Check session in PHP
<?php
session_start();
var_dump($_SESSION);
?>
```

### Issue: Password hash not matching
```php
// Verify hash
<?php
$password = 'your_password';
$hash = '$2y$10$...'; // from config
var_dump(password_verify($password, $hash));
?>
```

## 📊 Performance Check

- [ ] Page load time < 2 seconds
- [ ] API response time < 1 second
- [ ] No PHP errors in error log
- [ ] No JavaScript console errors
- [ ] Mobile responsive working

## 🔒 Security Audit

- [ ] No sensitive data in git history
- [ ] Config files not accessible via browser
- [ ] Session timeout configured
- [ ] HTTPS enabled (production only)
- [ ] SQL injection protection active
- [ ] XSS protection active

## 📝 Documentation

- [ ] README.md updated dengan info RBAC
- [ ] RBAC-GUIDE.md tersedia dan lengkap
- [ ] Deployment checklist ini complete
- [ ] Tim sudah trained untuk use RBAC
- [ ] Admin credentials di-share ke authorized person only

## 🎉 Go Live Checklist

Final checks sebelum production:

- [ ] All tests passed
- [ ] Documentation complete
- [ ] Team trained
- [ ] Backup created
- [ ] Rollback plan ready
- [ ] Monitoring setup
- [ ] Alert system configured

## 📞 Support Contacts

**Technical Issues:**
- System Admin: [contact]
- Developer: [contact]

**Access Issues:**
- Admin credentials: [authorized person]
- Password reset: [process]

---

**Deployment Date**: _________  
**Deployed By**: _________  
**Verified By**: _________  
**Status**: ⬜ Pending | ⬜ In Progress | ⬜ Complete
