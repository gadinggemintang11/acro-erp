# Panduan Perbaikan Error 403 & Database Production

## Error yang Terjadi:

### 1. ❌ Error 403 Forbidden
**Penyebab:** Konfigurasi server atau file tidak ter-upload dengan benar

### 2. ❌ Database Connection Error
```
Access denied for user 'root'@'localhost' (using password: NO)
```
**Penyebab:** File `.env` tidak ada di production, sehingga menggunakan default `root` tanpa password

### 3. ❌ Composer Autoload Missing
```
Could not find the specified $config['composer_autoload'] path
```
**Penyebab:** Folder `vendor/` tidak ter-upload ke cPanel

---

## ✅ Solusi Step-by-Step:

### Step 1: Upload Folder vendor/
```bash
# Dari local, compress folder vendor
cd /Applications/XAMPP/xamppfiles/htdocs/mingbao_erp
zip -r vendor.zip vendor/

# Upload vendor.zip ke cPanel via File Manager
# Extract di folder: /home/canmaxid/mingbaolianchuangint.com/
```

**ATAU gunakan FTP/rsync:**
```bash
# Via rsync (lebih cepat)
rsync -avz vendor/ username@server:/home/canmaxid/mingbaolianchuangint.com/vendor/
```

### Step 2: Buat File .env di Production

1. **Upload file `.env.production` ke cPanel**
2. **Rename menjadi `.env`** di folder root: `/home/canmaxid/mingbaolianchuangint.com/.env`
3. **Edit kredensial database** sesuai dengan database cPanel:

```env
DB_HOSTNAME=localhost
DB_USERNAME=canmaxid_mingbao    # Sesuaikan dengan user database cPanel
DB_PASSWORD=password_database    # Password database cPanel
DB_DATABASE=canmaxid_mingbao    # Nama database cPanel
```

### Step 3: Cek Kredensial Database di cPanel

1. Login ke **cPanel**
2. Buka **MySQL Databases**
3. Catat:
   - Database Name (biasanya: `canmaxid_namadb`)
   - Database User (biasanya: `canmaxid_namauser`)
   - Database Password
4. Masukkan ke file `.env`

### Step 4: Set Permission File .env

```bash
# Via SSH atau Terminal File Manager cPanel
chmod 644 .env
```

### Step 5: Clear Cache (Optional)

```bash
# Hapus cache CodeIgniter
rm -rf application/cache/*
```

### Step 6: Test Koneksi Database

Buat file `test_db.php` di root:

```php
<?php
// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value, '"\''));
    }
}

// Test koneksi
$host = getenv('DB_HOSTNAME') ?: 'localhost';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_DATABASE') ?: '';

echo "Connecting to:<br>";
echo "Host: $host<br>";
echo "User: $user<br>";
echo "Database: $db<br><br>";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully!";
$conn->close();
?>
```

Akses: `https://mingbaolianchuangint.com/test_db.php`

---

## Checklist Upload ke Production:

- [ ] Upload folder `vendor/` ke cPanel
- [ ] Upload file `.env.production` dan rename ke `.env`
- [ ] Edit `.env` dengan kredensial database cPanel yang benar
- [ ] Set permission `.env` menjadi 644
- [ ] Test koneksi database dengan `test_db.php`
- [ ] Hapus `test_db.php` setelah berhasil
- [ ] Clear cache: `application/cache/*`
- [ ] Test akses website

---

## Troubleshooting:

### Jika masih error 403:
1. Cek file `.htaccess` di root
2. Pastikan `index.php` ada dan readable
3. Cek permission folder: `chmod 755 /home/canmaxid/mingbaolianchuangint.com/`

### Jika masih error database:
1. Pastikan database user sudah ditambahkan ke database di cPanel
2. Cek apakah database sudah di-import (jika fresh install)
3. Test koneksi langsung dengan `mysqli_connect()`

### Error composer_autoload:
```bash
# Jika tidak bisa upload vendor/, disable composer autoload sementara
# Sudah di-fix di config.php dengan file_exists() check
```

---

## File yang Sudah Diupdate:

✅ `application/config/config.php` - Composer autoload dengan file_exists check  
✅ `.env.production` - Template environment production

## Yang Harus Dilakukan:

1. Upload `vendor/` ke production
2. Setup `.env` dengan kredensial database cPanel
3. Test & verify
