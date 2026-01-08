# Fix 404 Error untuk batch_import di Production

## Masalah
URL `https://mingbaolianchuangint.com/jurnalumum/batch_import` menampilkan **404 Page Not Found**

## Penyebab Kemungkinan

### 1. ❌ .htaccess Salah (Paling Sering)
File `.htaccess` di production mungkin tidak benar atau tidak ada.

### 2. ❌ base_url Config Salah
Config `base_url` di `application/config/config.php` tidak sesuai domain production.

### 3. ❌ mod_rewrite Tidak Aktif
Apache module `mod_rewrite` tidak diaktifkan di cPanel.

---

## ✅ SOLUSI - Ikuti Step by Step

### Step 1: Cek & Update .htaccess di Root Production

**Login ke cPanel** → **File Manager** → Masuk ke folder root aplikasi

**Pastikan file `.htaccess` ada di root**, jika tidak ada atau salah, copy isi berikut:

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

**PENTING:** 
- `RewriteBase /` → Karena domain langsung (bukan subfolder)
- Jika aplikasi di subfolder misal `/erp/`, ubah jadi `RewriteBase /erp/`

---

### Step 2: Update Config Production

Edit file: `application/config/config.php`

```php
// Line ~26-30
$config['base_url'] = 'https://mingbaolianchuangint.com/';

// Line ~39
$config['index_page'] = '';  // HARUS KOSONG untuk clean URL

// Line ~328
$config['uri_protocol'] = 'REQUEST_URI';  // Atau coba 'AUTO'
```

---

### Step 3: Cek mod_rewrite di cPanel

**Cara 1: Via PHP Info**
1. Buat file `info.php` di root:
```php
<?php phpinfo(); ?>
```
2. Akses: `https://mingbaolianchuangint.com/info.php`
3. Cari `mod_rewrite` → Harus ada di **Loaded Modules**
4. **HAPUS file info.php** setelah cek (keamanan!)

**Cara 2: Kontak Hosting**
Minta hosting provider untuk aktifkan `mod_rewrite`

---

### Step 4: Test URL Langsung

Coba akses dengan `index.php` di URL:
```
https://mingbaolianchuangint.com/index.php/jurnalumum/batch_import
```

- ✅ **Jika bisa**: Masalah di `.htaccess` atau `mod_rewrite`
- ❌ **Jika tetap 404**: Masalah di controller atau routing

---

### Step 5: Cek File Controller Ada di Server

Via **cPanel File Manager**, pastikan file ada:
```
application/controllers/JurnalUmum.php
```

Buka file tersebut, pastikan ada method:
```php
public function batch_import()
{
    // ... code
}
```

---

### Step 6: Clear Cache (Jika Pakai OpCache)

Tambahkan di root, buat file `clear_cache.php`:
```php
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache cleared!";
} else {
    echo "OpCache not enabled";
}
```

Akses: `https://mingbaolianchuangint.com/clear_cache.php`
Lalu **HAPUS file ini** setelah selesai.

---

## 🔍 Debug Mode

Untuk debug lebih detail, buka file:
`application/config/config.php`

Ubah sementara:
```php
// Line ~219
$config['log_threshold'] = 2;  // Enable logging
```

Lalu cek log error di:
`application/logs/log-YYYY-MM-DD.php`

---

## ✅ Checklist Verifikasi

- [ ] File `.htaccess` ada di root dengan RewriteBase yang benar
- [ ] `base_url` di config.php sesuai domain production
- [ ] `index_page` kosong (`''`)
- [ ] `mod_rewrite` aktif di Apache
- [ ] File `JurnalUmum.php` ada di server
- [ ] Method `batch_import()` adalah `public`
- [ ] Sudah clear OpCache (jika ada)

---

## 🚨 Jika Masih 404

### Test Routing Dasar
Coba akses URL lain dulu:
- `https://mingbaolianchuangint.com/jurnalumum` → Harus tampil index
- `https://mingbaolianchuangint.com/jurnalumum/create` → Harus bisa

Jika semua 404, masalah di **routing utama**.
Jika hanya `batch_import` yang 404, cek nama method (case-sensitive).

### Alternatif Quick Fix
Tambahkan routing explicit di `application/config/routes.php`:
```php
$route['jurnalumum/batch_import'] = 'jurnalumum/batch_import';
$route['jurnalumum/process_batch_import'] = 'jurnalumum/process_batch_import';
$route['jurnalumum/download_batch_template'] = 'jurnalumum/download_batch_template';
```

---

## 📞 Butuh Bantuan?

Jika masih error, kirimkan info berikut:
1. Screenshot error 404
2. Isi file `.htaccess` production
3. Config `base_url` dan `index_page`
4. Hasil phpinfo (screenshot bagian Loaded Modules)
5. Isi log file terbaru

---

**Created:** 2026-01-08
**For:** mingbaolianchuangint.com Production Issue
