# SOLUSI: Neraca Tidak Balance - Year-End Closing

## Masalah
Neraca tahun 2026 tidak balance karena:
- **Laba/Rugi tahun 2025** (Revenue & Expense) belum ditutup
- Selisih laba ditahan belum masuk ke **Retained Earnings** 

## Solusi

### 1. Jalankan SQL untuk Membuat Tabel
Jalankan file: `tools/create_year_end_closing_table.sql`

```sql
-- Ini akan membuat:
-- 1. Tabel year_end_closings
-- 2. Akun Laba Ditahan (3200) jika belum ada
```

### 2. Akses Menu Year-End Closing
URL: `http://localhost/neokaber_mingbao/yearendclosing`

### 3. Proses Tutup Buku Tahun 2025

**Step-by-step:**

1. Klik **"Buat Year-End Closing Baru"**
2. Pilih **Tahun: 2025**
3. Pilih **Akun Retained Earnings** (misal: 3200 - Laba Ditahan)
4. Klik **"Preview"** untuk melihat summary:
   - Total Revenue 2025
   - Total Expense 2025
   - Net Income (Laba Bersih)
5. Jika sudah benar, klik **"Proses Tutup Buku"**

**Sistem akan otomatis:**
- ✅ Membuat jurnal penutup (YEC/2025/0001)
- ✅ Debit semua akun Revenue (4xxx) → Tutup jadi 0
- ✅ Kredit semua akun Expense (5xxx) → Tutup jadi 0
- ✅ Transfer Net Income ke Retained Earnings (3200)
- ✅ Auto-posting jurnal penutup

### 4. Cek Neraca Lagi

Setelah tutup buku 2025:
- Akun Revenue & Expense 2025 = **Rp 0** (sudah ditutup)
- Akun Retained Earnings bertambah sesuai laba 2025
- **Neraca 2026 akan BALANCE** ✅

## Contoh Jurnal Penutup

```
YEC/2025/0001 | 31 Des 2025
--------------------------------
Revenue (4xxx)           | Debit  XXX
  Expense (5xxx)         |        Kredit XXX
  Retained Earnings 3200 |        Kredit YYY (Net Income)
--------------------------------
(Semua Revenue & Expense jadi 0, laba masuk Retained Earnings)
```

## Catatan Penting

1. **Tutup buku harus dilakukan setiap akhir tahun**
2. **Satu tahun hanya bisa ditutup 1x** (ada validasi)
3. **Jurnal penutup otomatis Posted** (tidak bisa diedit/delete)
4. **Lakukan sebelum mulai transaksi tahun baru**

## Troubleshooting

**Q: Neraca masih tidak balance setelah tutup buku?**
A: Jalankan query `tools/check_unbalanced_journals.sql` untuk cek jurnal yang tidak seimbang

**Q: Mau tutup buku ulang?**
A: Hapus dulu record di tabel `year_end_closings` dan jurnal terkait (hati-hati!)

**Q: Akun Retained Earnings tidak ada?**
A: Buat manual di Chart of Accounts:
- Kode: 3200
- Nama: Laba Ditahan
- Type: Equity
- Normal Balance: Kredit
