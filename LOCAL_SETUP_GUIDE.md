# Panduan Setup ERP Mingbao - Local & cPanel

## 🔧 BAGIAN 1: Setup di Local (XAMPP)

### Step 1: Setup Database

1. **Buka phpMyAdmin** 
   - Akses: http://localhost/phpmyadmin
   
2. **Buat Database Baru**
   - Nama database: `erp_mingbao_local`
   - Collation: `utf8_general_ci`

3. **Import Database**
   ```bash
   # Pilih salah satu file SQL di folder db/ atau root
   # Rekomendasi: db/neokabersis_db.sql atau db_neokaber.sql
   ```
   - Klik database `erp_mingbao_local`
   - Tab "Import" → Pilih file SQL → Klik "Go"

### Step 2: Konfigurasi Database

Edit file: `application/config/database.php`

```php
$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',
    'username' => 'root',           // Username XAMPP default
    'password' => '',               // Password XAMPP default (kosong)
    'database' => 'erp_mingbao_local',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci'
);
```

### Step 3: Konfigurasi Base URL

Edit file: `application/config/config.php`

```php
$config['base_url'] = 'http://localhost/erp_mingbao/';
```

### Step 4: Setup .htaccess (untuk remove index.php)

Buat/Edit file `.htaccess` di root folder:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

### Step 5: Jalankan Aplikasi

1. **Start XAMPP**
   - Start Apache
   - Start MySQL

2. **Akses Aplikasi**
   - URL: http://localhost/erp_mingbao/
   
3. **Login Default** (sesuaikan dengan data di database)
   - Jika lupa password admin, gunakan: `tools/reset_admin_password.php`

---

## 🚀 BAGIAN 2: Push ke cPanel via Git

### Step 1: Inisialisasi Git Repository (Jika belum ada)

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/erp_mingbao

# Inisialisasi git
git init

# Buat .gitignore
cat > .gitignore << 'EOF'
# CodeIgniter files
application/cache/*
!application/cache/index.html
!application/cache/.htaccess
application/logs/*
!application/logs/index.html
!application/logs/.htaccess

# User uploaded files (optional)
uploads/*
!uploads/index.html

# Composer
vendor/
composer.lock

# Environment files
.env

# OS files
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.sublime-project
*.sublime-workspace
EOF

# Add semua file
git add .

# Commit pertama
git commit -m "Initial commit - ERP Mingbao"
```

### Step 2: Setup Remote Repository

**Opsi A: GitHub/GitLab/Bitbucket**

```bash
# Buat repository baru di GitHub/GitLab
# Lalu connect:
git remote add origin https://github.com/username/erp-mingbao.git
git branch -M main
git push -u origin main
```

**Opsi B: Git di cPanel (Direct)**

```bash
# Jika cPanel support Git, tambahkan remote:
git remote add cpanel ssh://username@your-domain.com/~/repositories/erp-mingbao.git
```

### Step 3: Konfigurasi untuk Production (cPanel)

Buat file konfigurasi terpisah untuk production:

**File: `application/config/database_production.php`** (akan di-copy manual di cPanel)

```php
<?php
$db['default'] = array(
    'dsn'   => '',
    'hostname' => 'localhost',
    'username' => 'cpanel_db_user',      // Ganti dengan user DB cPanel
    'password' => 'cpanel_db_password',  // Ganti dengan password DB cPanel
    'database' => 'cpanel_db_name',      // Ganti dengan nama DB cPanel
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,  // IMPORTANT: FALSE untuk production
    'cache_on' => FALSE,
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci'
);
```

### Step 4: Deploy ke cPanel

#### **Metode 1: Git via cPanel Interface (Recommended)**

1. **Login ke cPanel**

2. **Buat Database & User**
   - MySQL Database Wizard
   - Catat: database name, username, password

3. **Import Database**
   - phpMyAdmin → Import file SQL

4. **Setup Git Deployment**
   - Cari "Git Version Control"
   - Klik "Create"
   - Pilih "Clone a Repository"
   - Masukkan repository URL (GitHub/GitLab)
   - Repository Path: `/home/username/public_html` atau `/home/username/repositories/erp-mingbao`
   - Klik "Create"

5. **Pull/Deploy**
   - Klik "Manage" pada repository
   - Klik "Pull or Deploy" → "Update from Remote"

#### **Metode 2: Manual Upload + Git Pull**

1. **Upload via File Manager/FTP**
   - Upload semua file ke `public_html/` atau subfolder

2. **Setup Git di cPanel Terminal**
   ```bash
   cd ~/public_html
   git init
   git remote add origin https://github.com/username/erp-mingbao.git
   git fetch
   git checkout main
   ```

#### **Metode 3: SSH/Terminal (Paling Fleksibel)**

```bash
# SSH ke cPanel
ssh username@your-domain.com

# Masuk ke folder
cd ~/public_html

# Clone repository
git clone https://github.com/username/erp-mingbao.git .

# Atau jika sudah ada, pull:
git pull origin main
```

### Step 5: Konfigurasi di cPanel (Setelah Deploy)

1. **Edit Database Config**
   ```bash
   # Via File Manager atau SSH
   nano ~/public_html/application/config/database.php
   ```
   Update dengan kredensial database cPanel

2. **Edit Base URL**
   ```bash
   nano ~/public_html/application/config/config.php
   ```
   ```php
   $config['base_url'] = 'https://yourdomain.com/';
   ```

3. **Set Permissions**
   ```bash
   chmod -R 755 ~/public_html
   chmod -R 777 ~/public_html/application/cache
   chmod -R 777 ~/public_html/application/logs
   chmod -R 777 ~/public_html/uploads  # jika ada
   ```

4. **Setup .htaccess**
   Pastikan file `.htaccess` ada di root dengan konfigurasi yang benar

---

## 🔄 Workflow Update/Push Perubahan

### Di Local (Development):

```bash
# 1. Buat perubahan di code
# 2. Test di local

# 3. Commit perubahan
git add .
git commit -m "Deskripsi perubahan"

# 4. Push ke repository
git push origin main
```

### Di cPanel (Production):

**Via cPanel Git Interface:**
- Masuk ke "Git Version Control"
- Klik "Manage" pada repository
- Klik "Pull or Deploy" → "Update from Remote"

**Via SSH:**
```bash
ssh username@your-domain.com
cd ~/public_html
git pull origin main
```

---

## ⚠️ PENTING - Checklist Sebelum Deploy

- [ ] Database production sudah dibuat & diimport
- [ ] `database.php` sudah disesuaikan untuk production
- [ ] `config.php` base_url sudah benar
- [ ] `db_debug` di database.php = FALSE (production)
- [ ] File `.gitignore` sudah benar (jangan push file sensitif)
- [ ] Permissions folder cache/ dan logs/ sudah 777
- [ ] .htaccess sudah di-upload
- [ ] Test login & fitur utama

---

## 🔐 Tips Keamanan

1. **Jangan commit password production ke Git**
   ```bash
   # Gunakan environment variables atau
   # Edit manual database.php di server
   ```

2. **Backup Database Reguler**
   - Setup cron job backup di cPanel

3. **Enable HTTPS**
   - Gunakan SSL certificate (Let's Encrypt gratis via cPanel)

4. **Set Environment Production**
   ```php
   // index.php
   define('ENVIRONMENT', 'production');
   ```

---

## 📞 Troubleshooting

### Error "500 Internal Server Error"
- Cek file `.htaccess`
- Cek permissions folder
- Cek error log: `application/logs/`

### Database Connection Error
- Cek kredensial di `database.php`
- Cek apakah user DB punya akses ke database

### "404 Page Not Found" untuk semua page
- Cek `config.php` → `$config['index_page'] = '';`
- Cek `.htaccess` ada dan benar
- Cek Apache mod_rewrite enabled

### Git Push Ditolak
```bash
git pull origin main --rebase
git push origin main
```

---

## 📚 Referensi

- CodeIgniter 3 Docs: https://codeigniter.com/userguide3/
- cPanel Git: https://docs.cpanel.net/cpanel/files/git-version-control/

