# 📘 PANDUAN SETUP SISTEM ACCOUNTING

## 🎯 TUJUAN REVISI

Sistem accounting telah direvisi agar **KONSISTEN**, **AMAN**, dan **MUDAH DIKELOLA** dengan prinsip:
- ✅ **Semua akun jurnal otomatis diambil dari Setting Akun**
- ✅ **Tidak ada pemilihan manual akun saat transaksi** (mengurangi human error)
- ✅ **Validasi lengkap** di setiap tahap transaksi
- ✅ **Multi-currency support** dengan proper exchange rate handling

---

## 📋 DAFTAR PERUBAHAN

### 1. **Tabel `setting_accounting`** - Ditambahkan Setting Baru

| Setting Key | Nama Setting | Currency | Penjelasan |
|------------|--------------|----------|------------|
| `akun_penerimaan_piutang` | Akun Penerimaan Piutang (Kas/Bank) | IDR, USD, RMB | Akun kas/bank untuk **AR Receipt** |
| `akun_pembayaran_utang` | Akun Pembayaran Utang (Kas/Bank) | IDR, USD, RMB | Akun kas/bank untuk **AP Payment** |
| `akun_beban_umum` | Akun Beban Umum | IDR, USD, RMB | Akun beban untuk invoice non-GRN |
| `akun_selisih_kurs` | Akun Selisih Kurs | IDR | Akun selisih kurs transaksi multi-currency |
| `akun_kas_kecil` | Akun Kas Kecil (Petty Cash) | IDR | Akun kas kecil (opsional) |

### 2. **Model AR Receipt** (`ArReceipt_model.php`)

**PERUBAHAN:**
- ❌ **DIHAPUS**: Dropdown pemilihan akun kas/bank di form
- ✅ **DITAMBAH**: Akun penerimaan otomatis dari setting
- ✅ **DITAMBAH**: Validasi lengkap (overpayment, akun aktif, currency match)
- ✅ **DITAMBAH**: Error handling yang lebih baik

**CARA KERJA BARU:**
```
User pilih: Customer + Currency + Invoice yang dibayar
System otomatis: Ambil akun dari setting_accounting.akun_penerimaan_piutang
Jurnal:
  Debit: Kas/Bank (dari setting)
  Kredit: Piutang (dari setting)
```

### 3. **Model AP Payment** (`ApPayment_model.php`)

**PERUBAHAN:**
- ❌ **DIHAPUS**: Dropdown pemilihan akun kas/bank di form
- ✅ **DITAMBAH**: Akun pembayaran otomatis dari setting
- ✅ **DITAMBAH**: Validasi lengkap (overpayment, akun aktif)
- ✅ **DITAMBAH**: Error handling yang lebih baik

**CARA KERJA BARU:**
```
User pilih: Supplier + Invoice yang dibayar
System otomatis: Ambil akun dari setting_accounting.akun_pembayaran_utang
Jurnal:
  Debit: Utang (dari setting)
  Kredit: Kas/Bank (dari setting)
```

### 4. **Model AR Invoice & AP Invoice**

**TETAP SAMA** - Sudah menggunakan setting akun sejak awal.

---

## 🚀 LANGKAH-LANGKAH SETUP

### **STEP 1: Jalankan SQL Update**

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/neokaber_mingbao/tools
mysql -u root -p neokabersis_db < update_setting_accounting_complete.sql
```

Atau via phpMyAdmin:
1. Buka phpMyAdmin
2. Pilih database `neokabersis_db`
3. Import file `tools/update_setting_accounting_complete.sql`

### **STEP 2: Setting Akun di Menu**

Login sebagai admin, buka menu **Setting Akun**, lalu isi:

#### **A. Akun Penerimaan Piutang (AR Receipt)**

| Currency | Pilih Akun |
|----------|------------|
| IDR | Bank Mandiri - 1350052013669 (IDR) atau Kas Besar |
| USD | Bank Mandiri - 1350052016886 (USD) |
| RMB | (Kosongkan jika tidak ada) |

#### **B. Akun Pembayaran Utang (AP Payment)**

| Currency | Pilih Akun |
|----------|------------|
| IDR | Bank Mandiri - 1350052013669 (IDR) atau Kas Besar |
| USD | Bank Mandiri - 1350052016886 (USD) |
| RMB | (Kosongkan jika tidak ada) |

#### **C. Akun Lainnya (Pastikan sudah terisi)**

- ✅ Akun Piutang Usaha (IDR, USD)
- ✅ Akun Utang Usaha (IDR, USD)
- ✅ Akun PPN Masukan (IDR)
- ✅ Akun PPN Keluaran (IDR)
- ✅ Akun Penjualan FG (IDR, USD)
- ✅ Akun HPP FG (IDR)
- ✅ Akun Persediaan FG (IDR)
- ✅ Akun Beban Umum (IDR, USD)
- ✅ Akun Selisih Kurs (IDR)

### **STEP 3: Testing**

#### **Test AR Receipt:**
1. Buat faktur customer (AR Invoice) → Post
2. Buat penerimaan (AR Receipt):
   - Pilih customer
   - Pilih currency (misal IDR)
   - Pilih invoice yang belum lunas
   - Input jumlah pembayaran
   - **TIDAK PERLU** pilih akun kas/bank (otomatis dari setting)
3. Save Draft → Post
4. Cek jurnal: Debit Kas/Bank, Kredit Piutang

#### **Test AP Payment:**
1. Buat faktur supplier (AP Invoice) → Post
2. Buat pembayaran (AP Payment):
   - Pilih supplier
   - Pilih invoice yang belum lunas
   - Input jumlah pembayaran
   - **TIDAK PERLU** pilih akun kas/bank (otomatis dari setting)
3. Save Draft → Post
4. Cek jurnal: Debit Utang, Kredit Kas/Bank

---

## ⚠️ VALIDASI & ERROR HANDLING

### **Validasi yang Ditambahkan:**

1. **Akun Belum Di-Setting**
   ```
   Error: "Akun Penerimaan Piutang (IDR) belum di-setting. 
          Silakan setting terlebih dahulu di menu Setting Akun."
   ```
   **Solusi**: Setting akun di menu Setting Akun

2. **Overpayment**
   ```
   Error: "Pembayaran untuk invoice INV-001 melebihi sisa piutang (Max: 1000000)."
   ```
   **Solusi**: Kurangi jumlah pembayaran

3. **Currency Mismatch**
   ```
   Error: "Currency akun penerimaan (IDR) tidak sesuai dengan currency penerimaan (USD)."
   ```
   **Solusi**: Pastikan setting akun untuk USD sudah benar

4. **Akun Tidak Aktif**
   ```
   Error: "Akun penerimaan tidak valid atau tidak aktif."
   ```
   **Solusi**: Aktifkan akun di Chart of Accounts atau ganti setting

5. **Kurs Tidak Valid**
   ```
   Error: "Kurs harus lebih besar dari 0 untuk mata uang USD."
   ```
   **Solusi**: Input kurs yang valid (> 0)

---

## 🔒 KEAMANAN & BEST PRACTICES

### ✅ **DO's**

1. **Selalu setting semua akun** sebelum mulai transaksi
2. **Review setting akun** secara berkala
3. **Backup database** sebelum update sistem
4. **Test di development** sebelum production
5. **Gunakan akun yang sesuai** dengan mata uang transaksi

### ❌ **DON'Ts**

1. **Jangan hapus** akun yang sudah dipakai di transaksi
2. **Jangan ubah setting** saat ada transaksi draft
3. **Jangan nonaktifkan** akun yang masih di-setting
4. **Jangan skip** validasi error message
5. **Jangan abaikan** warning saat posting jurnal

---

## 📊 FLOW DIAGRAM

### **AR Receipt Flow:**
```
Customer → Invoice → Payment
                       ↓
              [Cek Setting Akun]
                       ↓
              akun_penerimaan_piutang
                       ↓
                  [Validasi]
                       ↓
                  Save Draft
                       ↓
                [Post Jurnal]
                       ↓
        Debit: Kas/Bank (setting)
        Kredit: Piutang (setting)
```

### **AP Payment Flow:**
```
Supplier → Invoice → Payment
                       ↓
              [Cek Setting Akun]
                       ↓
              akun_pembayaran_utang
                       ↓
                  [Validasi]
                       ↓
                  Save Draft
                       ↓
                [Post Jurnal]
                       ↓
        Debit: Utang (setting)
        Kredit: Kas/Bank (setting)
```

---

## 📞 SUPPORT & TROUBLESHOOTING

### **Problem: Setting akun tidak muncul**
**Solusi**: 
1. Cek apakah SQL sudah dijalankan
2. Refresh cache browser (Ctrl + F5)
3. Logout dan login kembali

### **Problem: Error saat posting**
**Solusi**:
1. Cek error message dengan teliti
2. Pastikan semua akun di-setting
3. Cek status akun (harus active)
4. Cek currency akun sesuai transaksi

### **Problem: Jurnal tidak balance**
**Solusi**:
1. Cek setting akun untuk modul terkait
2. Cek exchange rate (untuk USD/RMB)
3. Cek pembulatan (max 2 decimal)

---

## 📈 BENEFITS SISTEM BARU

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| **Konsistensi** | ❌ User bisa salah pilih akun | ✅ Akun otomatis dari setting |
| **Keamanan** | ❌ No validation | ✅ Validasi lengkap |
| **Audit Trail** | ❌ Sulit trace kesalahan | ✅ Clear error messages |
| **Maintenance** | ❌ Hard to change logic | ✅ Easy to update setting |
| **User Experience** | ❌ Bingung pilih akun | ✅ Simple, no confusion |

---

## 📝 CHANGELOG

**Version 2.0 - December 2025**
- ✅ Added comprehensive setting accounts
- ✅ Removed manual account selection in AR Receipt
- ✅ Removed manual account selection in AP Payment
- ✅ Added validation for overpayment
- ✅ Added validation for currency mismatch
- ✅ Added validation for inactive accounts
- ✅ Improved error handling and messages
- ✅ Added exchange rate validation
- ✅ Updated documentation

---

**Dibuat oleh: AI Assistant**  
**Tanggal: 11 Desember 2025**  
**Status: ✅ Production Ready**
