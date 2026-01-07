# Panduan Fix Error 403 Forbidden di cPanel

## Status Saat Ini:
- ✅ Domain: `mingbaolianchuangint.com` 
- ✅ DNS: Sudah resolve ke `103.142.21.130`
- ✅ Server: LiteSpeed running
- ❌ Status: **HTTP 403 Forbidden**

---

## Penyebab Error 403:

1. **File `index.php` tidak ada atau tidak readable**
2. **Permission folder salah**
3. **File `.htaccess` memblokir akses**
4. **Document root salah**
5. **File `.env` belum ada (menyebabkan database error)**
6. **Folder `vendor/` belum ter-upload**

---

## ✅ SOLUSI STEP-BY-STEP:

### **STEP 1: Login ke cPanel**
1. Buka: https://canmaxid.com:2083 atau https://cpanel.canmaxid.com
2. Login dengan kredensial hosting

---

### **STEP 2: Cek File Manager**

#### A. Periksa Document Root
1. Buka **File Manager**
2. Navigate ke: `/home/canmaxid/mingbaolianchuangint.com/`
3. **Pastikan file-file ini ada:**
   ```
   /home/canmaxid/mingbaolianchuangint.com/
   ├── index.php          ← HARUS ADA
   ├── .htaccess          ← HARUS ADA
   ├── .env               ← HARUS ADA (untuk database)
   ├── application/
   ├── system/
   ├── assets/
   └── vendor/            ← HARUS ADA (atau akan error composer)
   ```

#### B. Jika File Tidak Ada - Upload Ulang
```
Kemungkinan file belum ter-upload ke folder yang benar!
```

---

### **STEP 3: Cek & Fix Document Root di cPanel**

1. Di cPanel, buka **Domains** atau **Addon Domains**
2. Cari domain `mingbaolianchuangint.com`
3. **Cek Document Root** - harus pointing ke:
   ```
   /home/canmaxid/mingbaolianchuangint.com
   ```
   
4. **JIKA SALAH**, edit dan ubah ke path yang benar
5. **JANGAN** pointing ke subdirectory seperti `public_html/`

---

### **STEP 4: Fix Permission Files & Folders**

Via **File Manager** → Pilih folder root → **Permissions**:

```bash
# Folder permissions
application/     → 755
system/          → 755
assets/          → 755
vendor/          → 755
uploads/         → 777 (jika ada)

# File permissions
index.php        → 644
.htaccess        → 644
.env             → 644 (atau 600 untuk lebih aman)
```

**ATAU via SSH/Terminal cPanel:**
```bash
cd /home/canmaxid/mingbaolianchuangint.com
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 644 .env
chmod 644 .htaccess
```

---

### **STEP 5: Upload File yang Hilang**

#### A. Upload Folder `vendor/` (PENTING!)

**Cara 1: Via File Manager cPanel**
1. Di local, compress folder vendor:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/mingbao_erp
   zip -r vendor.zip vendor/
   ```
2. Upload `vendor.zip` ke cPanel File Manager
3. Extract di `/home/canmaxid/mingbaolianchuangint.com/`

**Cara 2: Via FTP (FileZilla/Cyberduck)**
1. Connect ke FTP: `ftp.canmaxid.com`
2. Upload folder `vendor/` ke `/home/canmaxid/mingbaolianchuangint.com/vendor/`

**Cara 3: Via rsync/SSH (tercepat - jika SSH enabled)**
```bash
rsync -avz --progress vendor/ username@server:/home/canmaxid/mingbaolianchuangint.com/vendor/
```

#### B. Upload & Setup File `.env`

1. Upload file `.env.production` dari local
2. Di cPanel File Manager, **rename** menjadi `.env`
3. **Edit file `.env`** dengan kredensial database cPanel yang benar

---

### **STEP 6: Setup Database di cPanel**

1. Buka **MySQL Databases** di cPanel
2. **Catat kredensial:**
   - Database name: (contoh: `canmaxid_mingbao`)
   - Database user: (contoh: `canmaxid_mingbao`)
   - Database password: (yang Anda set)

3. **Edit file `.env`** via File Manager:
   ```env
   DB_HOSTNAME=localhost
   DB_USERNAME=canmaxid_mingbao    ← Sesuaikan
   DB_PASSWORD=your_password        ← Sesuaikan
   DB_DATABASE=canmaxid_mingbao    ← Sesuaikan
   ```

4. **Import Database** (jika belum):
   - Buka **phpMyAdmin**
   - Pilih database `canmaxid_mingbao`
   - Import file `db_neokaber.sql` dari local

---

### **STEP 7: Cek File `.htaccess`**

Pastikan `.htaccess` di root berisi:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect all requests to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "(^\.|\.env|database\.php)">
    Require all denied
</FilesMatch>
```

**PENTING:** Ubah `RewriteBase /mingbao_erp/` menjadi `RewriteBase /`

---

### **STEP 8: Clear Cache & Test**

1. **Clear cache CodeIgniter:**
   ```bash
   # Via File Manager, hapus semua file di:
   /home/canmaxid/mingbaolianchuangint.com/application/cache/*
   ```

2. **Test akses:**
   ```
   https://mingbaolianchuangint.com/
   ```

---

### **STEP 9: Debug Jika Masih Error**

#### Test 1: Buat file `info.php` di root
```php
<?php phpinfo(); ?>
```
Akses: `https://mingbaolianchuangint.com/info.php`

Jika **bisa diakses** → berarti PHP running, masalah di CodeIgniter  
Jika **403** → masalah di server/permission

#### Test 2: Buat file `test.html` di root
```html
<!DOCTYPE html>
<html>
<body><h1>Test OK</h1></body>
</html>
```
Akses: `https://mingbaolianchuangint.com/test.html`

Jika **bisa diakses** → berarti server OK, masalah di index.php  
Jika **403** → masalah permission/document root

#### Test 3: Cek Error Log
Di cPanel → **Errors** atau **Error Log**  
Atau via File Manager: `/home/canmaxid/logs/error_log`

---

## 🎯 CHECKLIST SEBELUM TEST:

- [ ] File `index.php` ada di `/home/canmaxid/mingbaolianchuangint.com/`
- [ ] File `.htaccess` ada dan sudah di-edit (RewriteBase /)
- [ ] File `.env` ada dan berisi kredensial database yang benar
- [ ] Folder `vendor/` sudah ter-upload lengkap
- [ ] Database sudah di-import via phpMyAdmin
- [ ] Database user sudah ditambahkan ke database
- [ ] Permission folder: 755, file: 644
- [ ] Document Root sudah benar di cPanel Domains
- [ ] Cache sudah di-clear

---

## 🚨 KEMUNGKINAN MASALAH PALING SERING:

### 1. **File belum ter-upload ke folder yang benar**
   → Upload ulang semua file ke `/home/canmaxid/mingbaolianchuangint.com/`

### 2. **Folder vendor/ belum ada**
   → Upload vendor.zip dan extract

### 3. **File .env belum ada**
   → Upload dan edit dengan kredensial database cPanel

### 4. **RewriteBase masih /mingbao_erp/**
   → Edit .htaccess, ubah menjadi `RewriteBase /`

### 5. **Document Root salah**
   → Cek di cPanel Domains, pastikan pointing ke folder yang benar

---

## 📞 Jika Masih Bermasalah:

1. Screenshot error di browser
2. Screenshot File Manager (list file di root)
3. Screenshot cPanel Domains (Document Root setting)
4. Copy error dari log file
5. Test dengan file `info.php` dan `test.html`

---

## File yang Perlu Di-Upload:

1. ✅ Semua file CodeIgniter (application/, system/, assets/)
2. ✅ File `index.php`
3. ✅ File `.htaccess` (edit RewriteBase)
4. ✅ File `.env` (dari `.env.production`)
5. ✅ Folder `vendor/` (compress → upload → extract)
6. ✅ File database SQL (import via phpMyAdmin)

---

**NEXT ACTION:** Login ke cPanel dan ikuti step 1-9 di atas! 🚀
