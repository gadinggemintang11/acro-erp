# 🚀 Panduan Lengkap - Setup & Deploy ERP Mingbao

## 📚 Daftar File Panduan

1. **QUICK_START.md** - Panduan cepat 5 menit ⚡
2. **ENV_GUIDE.md** - Panduan Environment Variables (.env) 🔐
3. **LOCAL_SETUP_GUIDE.md** - Panduan detail setup local 💻
4. **DEPLOYMENT_CHECKLIST.md** - Checklist deploy ke production ✅
5. **README_DEPLOYMENT.md** - File ini (overview lengkap) 📖

---

## 🎯 Ringkasan Proses

```
Local Development → Git Repository → cPanel Production
     (XAMPP)           (GitHub)         (Live Server)
       .env            .env.example       .env (production)
```

---

## 🆕 SEKARANG PAKAI .ENV FILE!

### ✅ Keuntungan:
- **Aman** - Password tidak ter-commit ke Git
- **Fleksibel** - Ganti environment tanpa edit code
- **Profesional** - Best practice industry standard

### 📁 File Structure:
```
.env                  ← Config actual (TIDAK di-commit)
.env.example          ← Template (di-commit)
.env.production       ← Template production
```

**Lihat panduan lengkap:** `ENV_GUIDE.md`

---

## 🎯 Ringkasan Proses

```
Local Development → Git Repository → cPanel Production
     (XAMPP)           (GitHub)         (Live Server)
```

---

## 📖 BAGIAN 1: Setup di Local (Pertama Kali)

### Step 1: Setup Database Local

```bash
# Otomatis (recommended):
./setup_local_db.sh

# Manual:
# 1. Buka http://localhost/phpmyadmin
# 2. Buat database: erp_mingbao_local
# 3. Import: db/neokabersis_db.sql
```

### Step 2: Konfigurasi Sudah Disesuaikan

**Sekarang pakai .env file!** 🎉

File `.env` sudah dibuat dengan konfigurasi local:
- Database: `erp_mingbao_local`
- User: `root`
- Password: (kosong)
- Base URL: `http://localhost/erp_mingbao/`

```bash
# Cek isi .env:
cat .env

# Edit jika perlu:
nano .env
```

**Cara lama (masih work tapi tidak recommended):**
Edit manual `application/config/database.php` dan `config.php`

### Step 3: Jalankan Aplikasi

```bash
# 1. Start XAMPP (Apache + MySQL)
# 2. Akses: http://localhost/erp_mingbao/
```

---

## 📖 BAGIAN 2: Setup Git Repository

### Inisialisasi Git (Pertama Kali)

```bash
# Otomatis:
./init_git.sh

# Manual:
git init
git add .
git commit -m "Initial commit - ERP Mingbao"
```

### Connect ke GitHub/GitLab

```bash
# 1. Buat repository baru di GitHub/GitLab
#    (jangan init dengan README, .gitignore, dll)

# 2. Tambahkan remote:
git remote add origin https://github.com/USERNAME/erp-mingbao.git

# 3. Push:
git branch -M main
git push -u origin main
```

---

## 📖 BAGIAN 3: Deploy ke cPanel

### Metode 1: Git via cPanel (Recommended)

#### A. Persiapan cPanel

1. **Buat Database**
   - Login cPanel
   - MySQL Database Wizard
   - Buat database & user
   - **CATAT kredensial!**

2. **Import Database**
   - phpMyAdmin
   - Pilih database
   - Import file SQL dari local

#### B. Clone Repository

3. **Edit Konfigurasi**
   - Via File Manager, buat/upload file `.env`
   - Copy dari `.env.production` atau buat baru:
   
   ```bash
   # .env di production
   CI_ENVIRONMENT=production
   APP_BASE_URL=https://yourdomain.com/
   
   DB_HOSTNAME=localhost
   DB_USERNAME=cpanel_user_erp
   DB_PASSWORD=Strong_Password_Here
   DB_DATABASE=cpanel_user_mingbao
   DB_DEBUG=false
   
   DISPLAY_ERRORS=false
   LOG_THRESHOLD=1
   ```
   
   **PENTING:** File `.env` TIDAK ada di Git, harus upload manual!

2. **Konfigurasi Clone**
   ```
   Clone URL:     https://github.com/USERNAME/erp-mingbao.git
   Repository Path: /home/username/public_html
   Repository Name: erp_mingbao
   ```
   - Klik "Create"

3. **Edit Konfigurasi**
   - Via File Manager, edit:
     - `application/config/database.php` (kredensial production)
     - `application/config/config.php` (base_url domain)

4. **Set Permissions**
   ```bash
   # Via Terminal SSH atau File Manager:
   chmod -R 755 /home/username/public_html
   chmod -R 777 /home/username/public_html/application/cache
   chmod -R 777 /home/username/public_html/application/logs
   ```

5. **Test**
   - Akses: https://yourdomain.com
   - Login & test fitur

### Metode 2: Upload Manual + Git

```bash
# 1. Upload semua file via File Manager/FTP

# 2. Via SSH/Terminal cPanel:
cd ~/public_html
git init
git remote add origin https://github.com/USERNAME/erp-mingbao.git
git fetch
git reset --hard origin/main

# 3. Lanjut ke langkah Edit Konfigurasi & Set Permissions
```

---

## 📖 BAGIAN 4: Workflow Update Code

### Development → Production

```bash
# === DI LOCAL ===
# 1. Buat perubahan code
# 2. Test di local (http://localhost/erp_mingbao/)
# 3. Commit & push

git add .
git commit -m "Menambahkan fitur XYZ"
git push origin main


# === DI CPANEL ===
# Opsi A: Via cPanel Interface
#   → Git Version Control
#   → Manage
#   → Pull or Deploy
#   → Update from Remote

# Opsi B: Via SSH
ssh username@yourdomain.com
cd ~/public_html
git pull origin main
```

---

## 📖 BAGIAN 5: File-file Penting

### File Konfigurasi

| File | Keterangan | Edit untuk Production? |
|------|------------|----------------------|
| `application/config/database.php` | Koneksi database | ✅ Ya |
| `application/config/config.php` | Base URL, settings | ✅ Ya |
| `.htaccess` | URL rewriting | Biasanya tidak |
| `.gitignore` | Exclude files dari Git | Tidak |
| `index.php` | Entry point | Optional (ENVIRONMENT) |

### File Helper

| File | Keterangan |
|------|------------|
| `setup_local_db.sh` | Script otomatis setup DB local |
| `init_git.sh` | Script otomatis init Git |
| `database_production_template.php` | Template config DB production |
| `config_production_template.php` | Template config production |
| `DEPLOYMENT_CHECKLIST.md` | Checklist deploy |

### Folder Penting

| Folder | Permissions | Keterangan |
|--------|-------------|------------|
| `application/cache/` | 777 | Cache CodeIgniter |
| `application/logs/` | 777 | Error logs |
| `uploads/` | 777 | User uploads (jika ada) |
| `vendor/` | 755 | Composer packages |

---

## 📖 BAGIAN 6: Troubleshooting

### Problem: Tidak bisa akses http://localhost/erp_mingbao/

**Solusi:**
```bash
# 1. Cek XAMPP running:
#    - Apache: ✓
#    - MySQL: ✓

# 2. Cek folder location:
#    Harus di: /Applications/XAMPP/xamppfiles/htdocs/erp_mingbao/

# 3. Cek base_url di config.php
#    $config['base_url'] = 'http://localhost/erp_mingbao/';
```

### Problem: Database connection error di local

**Solusi:**
```bash
# Cek application/config/database.php:
'hostname' => 'localhost',
'username' => 'root',
'password' => '',
'database' => 'erp_mingbao_local',

# Cek database exists:
# http://localhost/phpmyadmin → cek database erp_mingbao_local
```

### Problem: 500 Error di cPanel

**Solusi:**
```bash
# 1. Cek .htaccess - pastikan benar
# 2. Cek permissions:
chmod -R 755 ~/public_html
chmod -R 777 ~/public_html/application/cache
chmod -R 777 ~/public_html/application/logs

# 3. Cek error log:
#    cPanel → Errors → Error Log
#    Atau di: application/logs/log-YYYY-MM-DD.php
```

### Problem: Git push ditolak

**Solusi:**
```bash
# Pull dulu, lalu push:
git pull origin main --rebase
git push origin main

# Atau force push (hati-hati!):
git push origin main --force
```

### Problem: Blank page setelah deploy

**Solusi:**
```php
// 1. Edit index.php sementara, tambahkan:
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Refresh page, lihat error
// 3. Fix error
// 4. Kembalikan display_errors ke 0
```

---

## 📖 BAGIAN 7: Tips & Best Practices

### Security

1. **Jangan commit password production ke Git**
   ```bash
   # File .gitignore sudah exclude:
   # - .env
   # - application/config/database_production.php
   ```

2. **Set ENVIRONMENT di production**
   ```php
   // index.php
   define('ENVIRONMENT', 'production');
   ```

3. **Disable db_debug di production**
   ```php
   // database.php
   'db_debug' => FALSE,
   ```

### Performance

1. **Enable OPcache** (cPanel → Select PHP Version → Extensions → opcache)
2. **Use PHP 7.4 or 8.0+**
3. **Enable Gzip compression** (di .htaccess atau cPanel)

### Backup

1. **Database** - Setup cron job backup harian
2. **Files** - Backup via cPanel Backup Wizard weekly
3. **Git** - Repository adalah backup code

### Development

1. **Branch Strategy**
   ```bash
   # Main branch: production
   # Development: dev branch
   
   git checkout -b dev
   # ... develop ...
   git push origin dev
   
   # Merge ke main saat siap deploy:
   git checkout main
   git merge dev
   git push origin main
   ```

2. **Commit Message** - Gunakan format jelas:
   ```
   feat: Menambahkan modul laporan keuangan
   fix: Memperbaiki bug pada form input
   docs: Update dokumentasi API
   ```

---

## 📖 BAGIAN 8: Checklist Cepat

### ✅ First Time Setup (Sekali saja)

- [ ] Setup database local
- [ ] Konfigurasi `database.php` dan `config.php` untuk local
- [ ] Test aplikasi di local
- [ ] Init Git repository
- [ ] Push ke GitHub/GitLab
- [ ] Setup database di cPanel
- [ ] Clone/upload code ke cPanel
- [ ] Konfigurasi `database.php` dan `config.php` untuk production
- [ ] Set permissions
- [ ] Test aplikasi di production

### ✅ Update Code (Setiap ada perubahan)

- [ ] Develop di local
- [ ] Test di local
- [ ] `git add .`
- [ ] `git commit -m "message"`
- [ ] `git push origin main`
- [ ] Pull di cPanel (via interface atau SSH)
- [ ] Test di production

---

## 📞 Bantuan Lebih Lanjut

### Dokumentasi
- CodeIgniter 3: https://codeigniter.com/userguide3/
- Git: https://git-scm.com/doc
- cPanel Git: https://docs.cpanel.net/cpanel/files/git-version-control/

### File yang Bisa Dibaca
```bash
QUICK_START.md              # Quick start 5 menit
LOCAL_SETUP_GUIDE.md        # Detail setup local
DEPLOYMENT_CHECKLIST.md     # Checklist deployment
```

### Script Helper
```bash
./setup_local_db.sh         # Auto setup database local
./init_git.sh               # Auto init Git repository
```

---

**Happy Coding! 🚀**

