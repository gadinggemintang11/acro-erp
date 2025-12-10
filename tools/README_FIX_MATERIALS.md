# Fix Materials Table - Kategori Column

## 🔴 Problem
Database error ketika menambah material dengan kategori baru:
```
Error Number: 1265
Data truncated for column 'kategori' at row 1
```

## 🎯 Root Cause
Kolom `kategori` dan `asal` di tabel `materials` menggunakan **ENUM** dengan nilai terbatas:
- `kategori`: `'Kain','Outsole','Insole','Benang','Lem','Lainnya'`
- `asal`: `'Lokal','Impor'`

Sistem sudah diupgrade menggunakan **dynamic categories** dari `common_code` table, tapi struktur database belum diupdate.

## ✅ Solution
Ubah kolom dari **ENUM** menjadi **VARCHAR** untuk mendukung kategori dinamis.

## 📋 Migration Steps

### Option 1: Via phpMyAdmin (Recommended for beginners)
1. Buka **phpMyAdmin** di browser: http://localhost/phpmyadmin
2. Pilih database `neokaber_mingbao`
3. Klik tab **SQL**
4. Copy-paste isi file `fix_materials_kategori.sql`
5. Klik **Go** / **Jalankan**
6. Cek hasilnya, seharusnya muncul: "Materials table structure updated successfully!"

### Option 2: Via Terminal/Command Line
```bash
# Masuk ke XAMPP MySQL
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p

# Atau langsung execute file SQL
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p neokaber_mingbao < tools/fix_materials_kategori.sql
```

## 🧪 Testing
Setelah migration berhasil, coba tambah material dengan kategori baru:
1. Buka Master Material
2. Klik "Tambah Material Baru"
3. Pilih kategori: **SIZE LABEL** atau kategori lain dari common_code
4. Isi form dan simpan
5. ✅ Seharusnya berhasil tanpa error!

## 📊 Verifikasi Struktur
Cek struktur tabel setelah migration:
```sql
DESCRIBE materials;
```

Expected result:
```
kategori | varchar(50) | YES | NULL | Lainnya | Material category from common_code.MATERIAL_CATEGORY
asal     | varchar(20) | YES | NULL | Lokal   | Origin: Lokal/Impor/Import from common_code.ITEM_ORIGIN
```

## 🔧 Files Modified
1. ✅ `tools/fix_materials_kategori.sql` - SQL migration file
2. ✅ `application/models/Material_model.php` - Added `get_by_id()` method
3. ✅ Database table `materials` - Changed ENUM to VARCHAR

## 💡 Benefits
- ✅ Support dynamic categories from `common_code`
- ✅ No need to ALTER table every time adding new category
- ✅ More flexible and maintainable
- ✅ Consistent with system architecture

---
**Date:** December 7, 2025
**Affected Module:** Master Material
**Migration File:** `tools/fix_materials_kategori.sql`
