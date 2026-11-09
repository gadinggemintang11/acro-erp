-- ============================================================
-- Perbaiki saldo berjalan (stock_ledger.balance)
-- ------------------------------------------------------------
-- User 2026-09-21: "PENERIMAAN 144 ITU NYATA SEMUA, MEMANG DATA INI NYATA
-- SEMUA BAIK DARI INITIAL STOCK ATAU PENERIMAAN".
--
-- Entri Stok Awal LAMA menulis `balance = qty` begitu saja, seolah selalu jadi
-- baris pertama barang itu — padahal stock_ledger memakai saldo berjalan.
-- Akibatnya penerimaan sebelum stok awal ikut terhapus dari saldo. Contoh
-- nyata PROD0004: terima 144 (5 Agu) lalu Stok Awal 29 (10 Agu) menimpa saldo
-- jadi 29, sehingga setelah keluar 29 saldonya 0 dan barangnya tidak muncul
-- di Stock Transfer — padahal fisiknya masih 144.
-- Penulisan stok awal sudah diperbaiki di Initial_stock_model (saldo terakhir
-- + qty), tapi baris lama belum ikut terbetulkan. Migrasi ini menghitung ulang
-- kolom balance untuk SEMUA baris.
--
-- Saldo dihitung kumulatif per (item_id, item_type, kategori gudang) urut id —
-- sama dengan cara semua penulis ledger (SGR, pengeluaran, transfer, stok awal)
-- membaca saldo terakhir.
--
-- Tidak mengubah qty_in/qty_out: nilai transaksinya tetap apa adanya.
-- Idempotent: dijalankan berulang hasilnya sama.
-- ============================================================

UPDATE `stock_ledger` sl
SET sl.balance = (
    SELECT COALESCE(SUM(x.qty_in - x.qty_out), 0)
    FROM (SELECT id, item_id, item_type, category, qty_in, qty_out FROM `stock_ledger`) x
    WHERE x.item_id   = sl.item_id
      AND x.item_type = sl.item_type
      AND COALESCE(x.category, '') = COALESCE(sl.category, '')
      AND x.id <= sl.id
);
