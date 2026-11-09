-- ============================================================
-- Harga satuan PO jadi 4 angka di belakang koma
-- ------------------------------------------------------------
-- User 2026-09-10: "di purchase order ini buat agar bisa nyimpan nilai
-- satuan dan total nya dalam 4 digit dibelakang koma" -> disusul
-- "untuk total nya 2 digit belakang koma saja".
--
-- Jadi:
--   base_price  (harga satuan mata uang asal) -> 4 desimal
--   local_price (harga satuan dalam IDR)      -> 4 desimal
--   total_local & total                       -> TETAP 2 desimal
--
-- Kenapa decimal(19,4) dan bukan (15,4):
--   (15,2) punya 13 digit di depan koma. Kalau langsung jadi (15,4),
--   digit depannya menyusut jadi 11 — plafon harga turun dari ~9,9 triliun
--   ke ~99 miliar. Dengan (19,4) bagian depannya tetap 15 digit, jadi
--   TIDAK ADA data lama yang berisiko terpotong.
--
-- Nilai lama otomatis jadi x.xx00 (MySQL menambah nol, bukan membulatkan),
-- jadi migrasi ini aman diulang & tidak mengubah angka mana pun.
-- ============================================================

ALTER TABLE `po_details`
    MODIFY COLUMN `base_price`  DECIMAL(19,4) NULL DEFAULT 0.0000,
    MODIFY COLUMN `local_price` DECIMAL(19,4) NULL DEFAULT 0.0000;
