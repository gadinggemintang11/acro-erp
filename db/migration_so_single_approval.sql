-- ============================================================
-- Sales Order: approval 1 tahap (user 2026-11-06: "untuk sales contract
-- approval itu 1 stage aja approve nya").
-- ------------------------------------------------------------
-- Alur baru: Draft -> Submitted -> (Approve) -> Open. Dulu: Draft ->
-- Pending Endorsement -> Endorsed -> Open.
--   1. Tambah nilai 'Submitted' ke ENUM sales_contracts.status (nilai lama
--      tetap ada utk riwayat).
--   2. SO yang masih di tengah alur lama dipindah ke 'Submitted' supaya
--      masuk antrian approval yang sama.
-- Idempotent.
-- ============================================================
ALTER TABLE `sales_contracts` MODIFY `status` ENUM('Draft','Submitted','Pending Endorsement','Endorsed','Open','Closed','Cancelled','Finish','Rejected') NOT NULL DEFAULT 'Draft';
UPDATE `sales_contracts` SET `status` = 'Submitted' WHERE `status` IN ('Pending Endorsement', 'Endorsed');
