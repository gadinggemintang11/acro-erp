# 🔐 Environment Variables (.env) - Panduan Lengkap

## Kenapa Menggunakan .env?

### ✅ Keuntungan:
1. **Keamanan** - Kredensial tidak ter-commit ke Git
2. **Fleksibilitas** - Ganti environment tanpa edit code
3. **Portability** - Satu codebase untuk semua environment
4. **Best Practice** - Standard industry untuk manage config

### ❌ Tanpa .env (cara lama):
```php
// Hardcode di database.php - BURUK!
'username' => 'root',  // Ketahuan semua orang di Git
'password' => 'Forever2023',  // Password production ter-expose!
```

### ✅ Dengan .env (cara baru):
```php
// database.php
'username' => getenv('DB_USERNAME'),
'password' => getenv('DB_PASSWORD'),

// File .env (tidak di-commit ke Git)
DB_USERNAME=root
DB_PASSWORD=secret_password
```

---

## 📁 File Structure

```
erp_mingbao/
├── .env                  ← Your actual config (JANGAN commit!)
├── .env.example          ← Template (di-commit ke Git)
├── .env.production       ← Template untuk production
└── .gitignore            ← .env sudah di-exclude
```

---

## 🚀 Quick Start

### 1. Setup untuk Local Development

```bash
# File .env sudah dibuat otomatis dengan config local
# Cek isinya:
cat .env

# Jika belum ada, copy dari example:
cp .env.example .env

# Edit sesuai kebutuhan (optional)
nano .env
```

### 2. Setup untuk Production (cPanel)

```bash
# Di server production, copy template:
cp .env.production .env

# Edit dengan kredensial cPanel:
nano .env
```

Edit nilai-nilai ini:
```bash
CI_ENVIRONMENT=production
APP_BASE_URL=https://yourdomain.com/
DB_USERNAME=cpanel_db_user
DB_PASSWORD=cpanel_db_password
DB_DATABASE=cpanel_db_name
DB_DEBUG=false
DISPLAY_ERRORS=false
```

---

## 📝 Konfigurasi .env

### Environment Variables yang Tersedia:

#### 🌍 Environment
```bash
CI_ENVIRONMENT=development  # development | production | testing
```

#### 🌐 Application
```bash
APP_NAME="ERP Acropoly"
APP_BASE_URL=http://localhost/erp_mingbao/
APP_INDEX_PAGE=                    # Kosongkan untuk clean URL
APP_ENCRYPTION_KEY=                # Generate random key
APP_TIMEZONE=Asia/Jakarta
```

Generate encryption key:
```bash
php -r "echo bin2hex(random_bytes(16));"
```

#### 💾 Database
```bash
DB_DRIVER=mysqli
DB_HOSTNAME=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=erp_mingbao_local
DB_PREFIX=
DB_DEBUG=true                      # false untuk production
DB_CHARSET=utf8
DB_COLLATION=utf8_general_ci
```

#### 🔒 Security
```bash
DISPLAY_ERRORS=true                # false untuk production
LOG_THRESHOLD=4                    # 1 untuk production (error only)
```

#### 📤 File Upload
```bash
UPLOAD_MAX_SIZE=10240              # dalam KB
UPLOAD_PATH=./uploads/
ALLOWED_TYPES=gif|jpg|jpeg|png|pdf|doc|docx|xls|xlsx
```

#### 📧 Email (Optional)
```bash
MAIL_PROTOCOL=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_password
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=ERP Acropoly
```

---

## 🔧 Cara Menggunakan di Code

### Di CodeIgniter Config Files:

```php
// application/config/config.php
$config['base_url'] = getenv('APP_BASE_URL') ?: 'http://localhost/';

// application/config/database.php
$db['default'] = array(
    'hostname' => getenv('DB_HOSTNAME') ?: 'localhost',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_DATABASE') ?: 'database_name',
);
```

### Di Controllers/Models:

```php
// Akses environment variable
$appName = getenv('APP_NAME');
$uploadPath = getenv('UPLOAD_PATH');

// Atau pakai $_ENV
$dbName = $_ENV['DB_DATABASE'];

// Atau pakai $_SERVER
$environment = $_SERVER['CI_ENVIRONMENT'];
```

---

## 🎯 Environment-Specific Configs

### Local Development (.env)
```bash
CI_ENVIRONMENT=development
APP_BASE_URL=http://localhost/erp_mingbao/
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=erp_mingbao_local
DB_DEBUG=true
DISPLAY_ERRORS=true
LOG_THRESHOLD=4
```

### Production (.env di server)
```bash
CI_ENVIRONMENT=production
APP_BASE_URL=https://yourdomain.com/
DB_USERNAME=cpanel_user_erp
DB_PASSWORD=Strong_Password_123!
DB_DATABASE=cpanel_user_mingbao
DB_DEBUG=false
DISPLAY_ERRORS=false
LOG_THRESHOLD=1
```

### Staging (Optional)
```bash
CI_ENVIRONMENT=testing
APP_BASE_URL=https://staging.yourdomain.com/
DB_DATABASE=staging_database
DB_DEBUG=true
```

---

## 🔐 Security Best Practices

### 1. Jangan Commit .env ke Git
```bash
# .gitignore sudah include:
.env
.env.local
.env.production
```

### 2. Generate Strong Encryption Key
```bash
# Jangan pakai key yang sama di semua environment!
php -r "echo bin2hex(random_bytes(16));"

# Atau online: https://randomkeygen.com/
```

### 3. Use Strong Database Passwords
```bash
# ❌ BURUK
DB_PASSWORD=123456

# ✅ BAIK
DB_PASSWORD=X9$mK#pL2@qR8wN5
```

### 4. Different Passwords for Each Environment
```bash
# Local: bisa simple
DB_PASSWORD=

# Production: HARUS kuat dan berbeda!
DB_PASSWORD=Pr0d_S3cur3_P@ssw0rd!2026
```

---

## 📋 Checklist Deployment

### ✅ Sebelum Push ke Git:
- [ ] `.env` sudah masuk `.gitignore`
- [ ] `.env.example` sudah di-update
- [ ] Tidak ada hardcoded password di code
- [ ] Test di local dengan `.env`

### ✅ Di Production Server:
- [ ] Copy `.env.production` ke `.env`
- [ ] Edit semua credentials dengan nilai production
- [ ] Set `CI_ENVIRONMENT=production`
- [ ] Set `DB_DEBUG=false`
- [ ] Set `DISPLAY_ERRORS=false`
- [ ] Generate unique `APP_ENCRYPTION_KEY`
- [ ] Test koneksi database
- [ ] Test aplikasi

---

## 🐛 Troubleshooting

### Problem: Variable .env tidak terbaca

**Solusi:**
```bash
# 1. Cek .env ada di root folder
ls -la .env

# 2. Cek permissions
chmod 644 .env

# 3. Cek format file (tidak ada karakter aneh)
cat -A .env

# 4. Pastikan tidak ada spasi sebelum/sesudah =
# ❌ SALAH
DB_USERNAME = root     # ada spasi

# ✅ BENAR
DB_USERNAME=root       # tidak ada spasi
```

### Problem: .env di production tidak work

**Solusi:**
```bash
# 1. Pastikan .env ada di root bersama index.php
cd ~/public_html
ls -la .env

# 2. Jangan lupa upload .env (karena tidak di Git)
# Upload manual via File Manager atau FTP

# 3. Cek syntax tidak ada error
php -l index.php
```

### Problem: Database connection error setelah pakai .env

**Solusi:**
```php
// Temporary debug - tambahkan di index.php:
echo "DB Host: " . getenv('DB_HOSTNAME') . "<br>";
echo "DB User: " . getenv('DB_USERNAME') . "<br>";
echo "DB Name: " . getenv('DB_DATABASE') . "<br>";
exit;

// Cek apakah values terbaca dengan benar
```

---

## 📚 Advanced Usage

### Multiple Environment Files

```bash
# Gunakan env berbeda berdasarkan hostname
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $envFile = '.env.local';
} elseif (strpos($_SERVER['HTTP_HOST'], 'staging') !== false) {
    $envFile = '.env.staging';
} else {
    $envFile = '.env.production';
}

// Load di index.php
```

### Load .env di Custom Location

```php
// index.php - modify path jika .env di folder lain
if (file_exists(__DIR__ . '/config/.env')) {
    // Load dari folder config/
}
```

---

## 🔄 Migration dari Config Lama

### Dari config hardcoded ke .env:

**Before (config.php):**
```php
$config['base_url'] = 'http://localhost/erp/';
```

**After:**
```php
// config.php
$config['base_url'] = getenv('APP_BASE_URL') ?: 'http://localhost/erp/';

// .env
APP_BASE_URL=http://localhost/erp/
```

**Before (database.php):**
```php
'username' => 'root',
'password' => '123456',
```

**After:**
```php
// database.php
'username' => getenv('DB_USERNAME') ?: 'root',
'password' => getenv('DB_PASSWORD') ?: '',

// .env
DB_USERNAME=root
DB_PASSWORD=123456
```

---

## 📖 References

- [The Twelve-Factor App - Config](https://12factor.net/config)
- [PHP dotenv (Vance Lucas)](https://github.com/vlucas/phpdotenv)
- [Laravel Environment Configuration](https://laravel.com/docs/configuration#environment-configuration)

---

**✅ Sekarang aplikasi Anda lebih aman dan profesional!**

