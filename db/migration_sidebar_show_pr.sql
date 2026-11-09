-- ============================================================
-- Munculkan lagi menu Purchase Request di sidebar (user 2026-11-05:
-- "menu PR ini di hide, sekarang harus di munculkan").
-- Disembunyikan lewat Developer Mode > Atur Urutan Sidebar (is_hidden=1):
--   item_934e9aa69eac = Gudang General > Persetujuan PR General (dulu "Persetujuan IPR")
--   item_843adc070801 = Pembelian > Laporan > Daftar PR (Semua Tipe)
-- item_key = md5(path link) -> sama di semua server. Idempotent.
-- ============================================================
UPDATE `sidebar_item_order` SET `is_hidden` = 0 WHERE `item_key` IN ('item_934e9aa69eac', 'item_843adc070801');
