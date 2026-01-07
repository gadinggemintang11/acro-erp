# Deployment Checklist - ERP Mingbao ke cPanel

## 📋 Checklist Sebelum Deploy

### A. Persiapan Local
- [ ] Aplikasi sudah berjalan sempurna di local
- [ ] Semua fitur sudah di-test
- [ ] Database sudah final dan clean
- [ ] File sensitif sudah masuk .gitignore
- [ ] Git repository sudah di-push ke GitHub/GitLab

### B. Persiapan cPanel

#### 1. Database
- [ ] Login ke cPanel
- [ ] Buka "MySQL Database Wizard" atau "MySQL Databases"
- [ ] Buat database baru
  - Nama: `username_mingbao` (atau nama lain)
  - Catat nama lengkapnya!
- [ ] Buat user database
  - Username: `username_admin` (atau nama lain)
  - Password: (buat password kuat)
  - Catat username dan password!
- [ ] Tambahkan user ke database dengan ALL PRIVILEGES
- [ ] Buka phpMyAdmin
- [ ] Pilih database yang baru dibuat
- [ ] Import file SQL:
  - Tab "Import"
  - Choose file: `db/neokabersis_db.sql` (upload dari local)
  - Klik "Go"
  - Tunggu sampai selesai

#### 2. Deploy Files

**Opsi A: Via Git (Recommended)**
- [ ] Di cPanel, buka "Git Version Control"
- [ ] Klik "Create"
- [ ] Clone Repository
  - Repository URL: (paste URL GitHub/GitLab)
  - Repository Path: `/home/username/public_html` atau `/home/username/public_html/erp`
  - Repository Name: `erp_mingbao`
- [ ] Klik "Create"
- [ ] Tunggu sampai clone selesai

**Opsi B: Via File Manager/FTP**
- [ ] Download repository dari GitHub sebagai ZIP
- [ ] Upload ke cPanel via File Manager atau FTP
- [ ] Extract di folder yang diinginkan

#### 3. Konfigurasi Files

- [ ] **Buat file `.env` di production** (PENTING!)
  ```bash
  # Via File Manager atau SSH, buat file .env
  # Copy dari .env.production atau buat manual
  ```

- [ ] **Edit `.env` dengan kredensial production**
  ```bash
  CI_ENVIRONMENT=production
  APP_BASE_URL=https://yourdomain.com/
  
  DB_HOSTNAME=localhost
  DB_USERNAME=cpanel_user_erp        # Sesuaikan
  DB_PASSWORD=Strong_Password_Here   # Sesuaikan
  DB_DATABASE=cpanel_user_mingbao    # Sesuaikan
  DB_DEBUG=false
  
  DISPLAY_ERRORS=false
  LOG_THRESHOLD=1
  ```
  
  **📖 Lihat panduan lengkap:** `ENV_GUIDE.md`

- [ ] Cek file `.htaccess` ada di root folder

- [ ] ~~Edit `application/config/database.php`~~ (Tidak perlu! Sudah pakai .env)

- [ ] ~~Edit `application/config/config.php`~~ (Tidak perlu! Sudah pakai .env)

#### 4. Set Permissions
Via File Manager atau SSH:
- [ ] Root folder: 755
- [ ] `application/cache/`: 777
- [ ] `application/logs/`: 777
- [ ] `uploads/` (jika ada): 777

```bash
# Via SSH:
cd ~/public_html
chmod -R 755 .
chmod -R 777 application/cache
chmod -R 777 application/logs
chmod -R 777 uploads
```

#### 5. SSL Certificate (Recommended)
- [ ] Di cPanel, buka "SSL/TLS Status" atau "Let's Encrypt"
- [ ] Aktifkan SSL untuk domain
- [ ] Tunggu sampai certificate issued

### C. Testing Production

- [ ] Akses domain: `https://yourdomain.com`
- [ ] Test login page muncul
- [ ] Test login dengan akun admin
- [ ] Test fitur-fitur utama:
  - [ ] Dashboard
  - [ ] Master data
  - [ ] Transaksi
  - [ ] Laporan
  - [ ] Upload file (jika ada)
- [ ] Cek console browser (F12) untuk error JavaScript
- [ ] Cek `application/logs/` untuk error PHP

### D. Security

- [ ] `.env` file sudah dibuat di production dengan config yang benar
- [ ] `CI_ENVIRONMENT=production` di `.env`
- [ ] `DB_DEBUG=false` di `.env`
- [ ] `DISPLAY_ERRORS=false` di `.env`
- [ ] Password database production BERBEDA dari local
- [ ] Password admin aplikasi sudah diubah
- [ ] `.htaccess` sudah aktif
- [ ] Directory browsing disabled
- [ ] File `.env` TIDAK ter-commit ke Git (cek .gitignore)

### E. Performance

- [ ] Enable OPcache di cPanel PHP Selector (jika tersedia)
- [ ] Set PHP version ke 7.4 atau 8.0+
- [ ] Enable compression (gzip)
- [ ] Test kecepatan loading page

### F. Backup

- [ ] Setup cron job untuk backup database otomatis
- [ ] Backup manual database pertama kali
- [ ] Backup files via cPanel Backup Wizard

---

## 🔄 Update/Push Changes (Workflow)

### Di Local:
```bash
# 1. Buat perubahan
# 2. Test di local
# 3. Commit & push

git add .
git commit -m "Deskripsi perubahan"
git push origin main
```

### Di cPanel:

**Via Git Interface:**
```
cPanel → Git Version Control → Manage → Pull or Deploy → Update from Remote
```

**Via SSH:**
```bash
ssh username@yourdomain.com
cd ~/public_html
git pull origin main
```

---

### 500 Internal Server Error
1. Cek `.htaccess` - pastikan syntax benar
2. Cek `.env` file ada dan readable
3. Cek `application/logs/log-YYYY-MM-DD.php`
4. Cek error_log di cPanel
5. Cek permissions folder (755 untuk folder, 644 untuk file)
### Database Connection Error
1. Cek kredensial di `.env` file
2. Pastikan `.env` file ada di root folder (sama level dengan index.php)
3. Cek di cPanel apakah user sudah ditambahkan ke database
4. Test koneksi via phpMyAdmin
5. Debug: tampilkan nilai env sementara
   ```php
   // Tambahkan di index.php sementara:
   echo "DB: " . getenv('DB_DATABASE') . "<br>";
   echo "User: " . getenv('DB_USERNAME');
   exit;
   ```
1. Cek kredensial di `database.php`
2. Cek di cPanel apakah user sudah ditambahkan ke database
3. Test koneksi via phpMyAdmin

### 404 Error untuk semua halaman
1. Cek `.htaccess` ada dan benar
2. Cek `config.php` → `index_page = ''`
3. Cek Apache mod_rewrite enabled (biasanya sudah default di cPanel)

### Blank Page
1. Enable error display sementara di `index.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Cek `application/logs/`
3. Cek cPanel error_log

### Upload File Error
1. Cek permissions folder upload (777)
2. Cek PHP max_upload_size di cPanel
3. Cek folder path benar di config

---

## 📞 Support

Jika ada masalah:
1. Cek `application/logs/log-YYYY-MM-DD.php`
2. Cek cPanel Error Log
3. Cek browser console (F12)
4. Contact hosting support jika issue di server

---

## ✅ Post-Deployment

Setelah deploy sukses:
- [ ] Dokumentasikan kredensial di tempat aman
- [ ] Setup monitoring/uptime checker
- [ ] Setup backup rutin
- [ ] Informasikan ke tim/client
- [ ] Monitor 24 jam pertama untuk error

