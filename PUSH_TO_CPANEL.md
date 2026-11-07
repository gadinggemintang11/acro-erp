# 🚀 Cara Push ke cPanel - Step by Step

## 📋 Overview Proses

```
Local (Mac) → Git (GitHub/GitLab) → cPanel (Server Production)
```

Ada **2 metode** yang bisa dipilih:
1. **Git via cPanel** (Recommended) ⭐
2. **Manual FTP/File Manager** (Alternatif)

---

## ⭐ METODE 1: Git via cPanel (RECOMMENDED)

Metode ini paling efisien dan profesional.

### TAHAP A: Setup Git di Local (Pertama Kali)

#### 1. Inisialisasi Git Repository

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/erp_mingbao

# Jalankan script otomatis
./init_git.sh
```

**Atau manual:**

```bash
# 1. Init git
git init

# 2. Add semua file
git add .

# 3. Commit
git commit -m "Initial commit - ERP Acropoly dengan .env support"

# 4. Lihat status
git status
```

#### 2. Buat Repository di GitHub/GitLab

**Pilih salah satu:**

##### Option A: GitHub
1. Buka https://github.com
2. Login
3. Klik tombol "+" → "New repository"
4. Nama: `erp-mingbao` (atau sesuai keinginan)
5. **JANGAN** centang "Initialize with README"
6. Klik "Create repository"
7. Copy URL repository (contoh: `https://github.com/username/erp-mingbao.git`)

##### Option B: GitLab
1. Buka https://gitlab.com
2. Login
3. Klik "New project" → "Create blank project"
4. Project name: `erp-mingbao`
5. **JANGAN** centang "Initialize repository with a README"
6. Klik "Create project"
7. Copy URL repository

#### 3. Connect Local ke Remote Repository

```bash
# Tambahkan remote (ganti URL dengan URL Anda!)
git remote add origin https://github.com/username/erp-mingbao.git

# Rename branch ke main
git branch -M main

# Push ke GitHub/GitLab
git push -u origin main
```

**Jika diminta username/password:**
- GitHub: Gunakan Personal Access Token (bukan password)
  - Settings → Developer settings → Personal access tokens → Generate new token
- GitLab: Gunakan username & password biasa

**✅ Sekarang code sudah di GitHub/GitLab!**

---

### TAHAP B: Setup di cPanel

#### 1. Setup Database Production

**Login cPanel → MySQL Database Wizard:**

```
Step 1: Create Database
  - Database Name: mingbao (akan jadi username_mingbao)
  - Klik "Next Step"

Step 2: Create User
  - Username: admin_erp (akan jadi username_admin_erp)
  - Password: [Generate Strong Password] → COPY PASSWORD INI!
  - Klik "Create User"

Step 3: Add User to Database
  - Centang: ALL PRIVILEGES
  - Klik "Next Step"

✅ Complete!
```

**CATAT KREDENSIAL:**
```
Database Name: username_mingbao
Database User: username_admin_erp
Database Pass: [password yang di-copy]
```

#### 2. Import Database

**cPanel → phpMyAdmin:**

1. Pilih database `username_mingbao`
2. Tab "Import"
3. Klik "Choose File"
4. **Upload dari local**: Pilih file `db/neokabersis_db.sql`
5. Klik "Go"
6. Tunggu sampai selesai (mungkin 1-5 menit tergantung ukuran)

**✅ Database production siap!**

#### 3. Clone Repository dari Git

**cPanel → Git™ Version Control:**

1. Klik **"Create"**

2. **Clone a Repository:**
   ```
   Clone URL: https://github.com/username/erp-mingbao.git
   
   Repository Path: /home/username/public_html
   (Atau jika ingin di subfolder: /home/username/public_html/erp)
   
   Repository Name: erp-mingbao
   ```

3. Klik **"Create"**

4. Tunggu proses clone (1-3 menit)

**✅ Code sudah di server!**

#### 4. Buat File .env di Production

**cPanel → File Manager:**

1. Navigate ke folder: `public_html/` (atau `public_html/erp/`)

2. Klik **"+ File"** → Nama: `.env`

3. Edit file `.env` yang baru dibuat

4. **Copy template ini dan sesuaikan:**

```bash
#--------------------------------------------------------------------
# PRODUCTION CONFIG
#--------------------------------------------------------------------

CI_ENVIRONMENT=production

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
APP_NAME="ERP Acropoly"
APP_BASE_URL=https://yourdomain.com/
APP_INDEX_PAGE=
APP_ENCRYPTION_KEY=

#--------------------------------------------------------------------
# DATABASE - SESUAIKAN DENGAN KREDENSIAL CPANEL!
#--------------------------------------------------------------------
DB_DRIVER=mysqli
DB_HOSTNAME=localhost
DB_USERNAME=username_admin_erp
DB_PASSWORD=password_yang_dicopy_tadi
DB_DATABASE=username_mingbao
DB_PREFIX=
DB_DEBUG=false
DB_CHARSET=utf8
DB_COLLATION=utf8_general_ci

#--------------------------------------------------------------------
# SECURITY - PRODUCTION
#--------------------------------------------------------------------
DISPLAY_ERRORS=false
LOG_THRESHOLD=1

#--------------------------------------------------------------------
# TIMEZONE
#--------------------------------------------------------------------
APP_TIMEZONE=Asia/Jakarta
```

5. **Save**

**Generate Encryption Key:**
```bash
# Via Terminal SSH atau online tool
# https://randomkeygen.com/
# Copy ke APP_ENCRYPTION_KEY
```

#### 5. Set Permissions

**Via File Manager:**

1. Select folder `application/cache` → Permissions → `777`
2. Select folder `application/logs` → Permissions → `777`
3. Select folder `uploads` (jika ada) → Permissions → `777`

**Atau via Terminal SSH:**

```bash
cd ~/public_html
chmod -R 755 .
chmod -R 777 application/cache
chmod -R 777 application/logs
chmod -R 777 uploads
```

#### 6. Test Aplikasi

**Akses di browser:**
```
https://yourdomain.com
```

**Jika error, cek:**
- cPanel → Errors → Error Log
- File Manager → `application/logs/log-2026-01-07.php`

**✅ Deploy selesai!**

---

### TAHAP C: Update Code (Workflow Harian)

Setiap kali ada perubahan di local:

#### Di Local (Mac):

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/erp_mingbao

# 1. Buat perubahan code
# 2. Test di local

# 3. Add & commit
git add .
git commit -m "Menambahkan fitur laporan penjualan"

# 4. Push ke GitHub/GitLab
git push origin main
```

#### Di cPanel:

**Metode A: Via cPanel Interface (Mudah)**

1. Login cPanel
2. **Git™ Version Control**
3. Klik **"Manage"** pada repository erp-mingbao
4. Klik **"Pull or Deploy"** tab
5. Klik **"Update from Remote"**
6. Tunggu selesai
7. **✅ Code updated!**

**Metode B: Via SSH (Advanced)**

```bash
# SSH ke server
ssh username@yourdomain.com

# Masuk ke folder
cd ~/public_html

# Pull changes
git pull origin main

# ✅ Done!
```

---

## 🔄 METODE 2: Manual FTP/File Manager (Alternatif)

Jika cPanel tidak support Git, gunakan cara manual.

### Setup Awal:

#### 1. Setup Database (sama seperti metode 1)

Lihat **TAHAP B.1** di atas

#### 2. Upload Files

**Via File Manager:**

1. Login cPanel → File Manager
2. Navigate ke `public_html/`
3. Upload semua file (kecuali yang ada di .gitignore)
4. Extract jika di-zip

**Via FTP:**

```bash
# Gunakan FTP client (FileZilla, Cyberduck, dll)
Host: ftp.yourdomain.com
Username: cpanel_username
Password: cpanel_password
Port: 21

# Upload semua folder & file
```

#### 3. Buat .env (sama seperti metode 1)

Lihat **TAHAP B.4** di atas

#### 4. Set Permissions (sama seperti metode 1)

Lihat **TAHAP B.5** di atas

### Update Code:

Setiap ada perubahan:
1. Edit di local
2. Upload file yang berubah via FTP/File Manager
3. **❌ Tidak efisien!** (Makanya lebih baik pakai Git)

---

## 📊 Perbandingan Metode

| Aspek | Git via cPanel | Manual FTP |
|-------|----------------|------------|
| **Setup awal** | Agak ribet | Simple |
| **Update code** | 1 klik | Upload manual |
| **Rollback** | Mudah (git) | Susah |
| **Team collaboration** | Mudah | Susah |
| **Best practice** | ✅ Ya | ❌ Tidak |
| **Recommended** | ⭐⭐⭐⭐⭐ | ⭐⭐ |

---

## 🎯 Rekomendasi Workflow

### Pertama Kali (One-time Setup):

```bash
1. Init Git local          → ./init_git.sh
2. Buat repo GitHub        → 5 menit
3. Push ke GitHub          → git push origin main
4. Setup DB cPanel         → 5 menit
5. Clone di cPanel         → 3 menit
6. Buat .env production    → 3 menit
7. Set permissions         → 2 menit
8. Test                    → ✅

Total: ~30 menit
```

### Setiap Update (Daily):

```bash
# Local
git add .
git commit -m "Update feature X"
git push origin main

# cPanel
Git Version Control → Manage → Update from Remote

Total: ~2 menit
```

---

## 🐛 Troubleshooting

### Problem: Git push ditolak

```bash
# Pull dulu
git pull origin main --rebase

# Lalu push
git push origin main
```

### Problem: Clone di cPanel gagal

**Penyebab:**
- Repository private tapi tidak ada SSH key
- URL salah

**Solusi:**
1. Pastikan repository **Public** (untuk kemudahan)
2. Atau setup SSH key di cPanel:
   - cPanel → SSH Access → Manage SSH Keys
   - Generate Key → Authorize → Copy public key
   - GitHub → Settings → SSH Keys → Add
   - Clone pakai SSH URL: `git@github.com:user/repo.git`

### Problem: Update from Remote tidak jalan

**Solusi:**

```bash
# Via SSH
cd ~/public_html
git status

# Jika ada conflict
git stash
git pull origin main
git stash pop
```

### Problem: .env tidak terbaca di production

**Solusi:**

```bash
# Cek .env ada
ls -la .env

# Cek permissions
chmod 644 .env

# Cek isi
cat .env

# Debug - tambahkan di index.php sementara:
echo "ENV loaded: " . getenv('CI_ENVIRONMENT');
exit;
```

---

## ✅ Checklist Deploy

### Before First Deploy:
- [ ] Git repository created
- [ ] Code pushed ke GitHub/GitLab
- [ ] Database cPanel created
- [ ] Database imported
- [ ] Repository cloned di cPanel
- [ ] File .env created & configured
- [ ] Permissions set (777 untuk cache/logs)
- [ ] SSL/HTTPS active
- [ ] Test aplikasi

### Before Every Update:
- [ ] Test di local
- [ ] Commit dengan message jelas
- [ ] Push ke GitHub
- [ ] Pull di cPanel
- [ ] Test di production
- [ ] Monitor error logs

---

## 📚 Resources

**Panduan Terkait:**
- `ENV_GUIDE.md` - Panduan .env lengkap
- `DEPLOYMENT_CHECKLIST.md` - Checklist detail
- `README_DEPLOYMENT.md` - Overview lengkap

**Video Tutorial (search di YouTube):**
- "How to use Git in cPanel"
- "Deploy CodeIgniter to cPanel"

**cPanel Documentation:**
- https://docs.cpanel.net/cpanel/files/git-version-control/

---

**✅ Sekarang Anda sudah tahu cara push ke cPanel!** 🚀

**Rekomendasi: Pakai Metode 1 (Git via cPanel)** untuk workflow yang profesional dan efisien.

