-- ============================================================
-- PHASE 1A: Rename menu_name labels (sync with sidebar)
-- Database: nsi-db
-- Date: 2026-05-18
-- Aman dijalankan berulang (idempotent). Tidak mengubah menu_key.
-- Tujuan: sync nama menu di tabel `menus` supaya Role Access page
-- menampilkan label yang konsisten dengan sidebar yang baru.
-- ============================================================

-- ---- MASTER DATABASE items ----
UPDATE `menus` SET `menu_name` = 'Customer'                WHERE `menu_key` = 'master_customer';
UPDATE `menus` SET `menu_name` = 'Production Material'     WHERE `menu_key` = 'master_material';
UPDATE `menus` SET `menu_name` = 'Semi-Finished Material'  WHERE `menu_key` = 'master_semifinished';
UPDATE `menus` SET `menu_name` = 'Finished Goods'          WHERE `menu_key` = 'master_fg';
UPDATE `menus` SET `menu_name` = 'General Stock Material'  WHERE `menu_key` = 'master_inventory';
UPDATE `menus` SET `menu_name` = 'Equipment & Tools'       WHERE `menu_key` = 'master_machine';

-- ---- IT INVENTORY items (rename only) ----
UPDATE `menus` SET `menu_name` = 'Good Incoming Report'   WHERE `menu_key` IN ('Lap_Pemasukan_BB', 'lap_pemasukan_bb', 'lappemasukanbb');
UPDATE `menus` SET `menu_name` = 'Good Outgoing Report'   WHERE `menu_key` = 'lap_pengeluaran_barang';
UPDATE `menus` SET `menu_name` = 'Mutation Report - Scrap'         WHERE `menu_key` = 'lap_mutasi_scrap';
UPDATE `menus` SET `menu_name` = 'Mutation Report - Material'      WHERE `menu_key` = 'lapmutasimaterial';
UPDATE `menus` SET `menu_name` = 'Mutation Report - Finish Good'   WHERE `menu_key` = 'lapmutasifinished';
UPDATE `menus` SET `menu_name` = 'Mutation Report - WIP'           WHERE `menu_key` = 'lapmutasiwip';
UPDATE `menus` SET `menu_name` = 'Mutation Report - Equipment & Tools' WHERE `menu_key` = 'lapmutasimesin';

-- ---- Verifikasi ----
SELECT id, menu_key, menu_name, department
FROM `menus`
WHERE `menu_key` IN (
  'master_supplier','master_customer','master_material','master_semifinished',
  'master_fg','master_scrap','master_inventory','master_machine',
  'Lap_Pemasukan_BB','lap_pemasukan_bb','lappemasukanbb','lap_pengeluaran_barang',
  'lap_mutasi_scrap','lapmutasimaterial','lapmutasifinished','lapmutasiwip','lapmutasimesin'
)
ORDER BY menu_key;
