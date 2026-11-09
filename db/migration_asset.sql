-- =====================================================================
-- Migration: Master Asset (FASE 0)
--
-- Pondasi modul pembelian aset tetap. Mengikuti pola Master Jasa
-- (migration_po_jasa.sql), dengan dua perbedaan penting:
--
--   1. TIDAK ada seed Chart of Account. COA aktiva tetap di DB ini sudah
--      lengkap (1000.01.01-.07 aset, 1000.02.01-.07 akumulasi penyusutan,
--      6200.04.01-.08 beban penyusutan). Akun dipilih MANUAL lewat dropdown
--      di Master Asset, tidak di-hardcode di sini.
--
--   2. Jalur akuntansi aset TIDAK lewat GRN / AP Invoice. Kedua modul itu
--      kosong (0 baris) di DB produksi; semua jurnal riil dibuat manual oleh
--      Finance lewat Jurnal Umum. Karena itu `asset_register` memakai pola
--      yang sama dengan PRF (lihat migration_prf_journal_link.sql):
--      kolom `journal_header_id` diisi saat baris ditarik ke Jurnal Umum,
--      supaya satu aset tidak bisa dijurnal dua kali.
--
-- Cakupan Fase 0: struktur tabel + enum + kolom department di PO + common_code
-- + menu Master Asset. Menu PR/PO Asset, Asset Register, dan Penyusutan
-- didaftarkan menyusul pada fase masing-masing supaya tidak ada link mati.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db  (MariaDB 10.4)
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) Enum additive: tipe 'asset' di PR & PO (tidak mengubah data lama)
-- ---------------------------------------------------------------------
ALTER TABLE `pr_header` MODIFY `type` ENUM('produksi','non_produksi','jasa','asset') NOT NULL DEFAULT 'produksi';
ALTER TABLE `po_header` MODIFY `type` ENUM('produksi','non_produksi','jasa','asset') NOT NULL DEFAULT 'produksi';


-- ---------------------------------------------------------------------
-- 2) Departemen di PO  (prasyarat Laporan Spend per Departemen)
--
--    Sebelumnya department hanya ada di `pr_header`. Dari 57 PO, 19 dibuat
--    tanpa PR -- kalau laporan cuma join ke PR, sepertiga belanja hilang.
--    Kolom di-backfill dari PR untuk PO yang punya PR; sisanya NULL dan
--    akan tampil sebagai "(Tidak Ditentukan)" sampai diisi.
-- ---------------------------------------------------------------------
ALTER TABLE `po_header`
  ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Departemen peminta. Turun dari pr_header saat PO dibuat dari PR; diisi manual untuk PO tanpa PR.'
    AFTER `supplier_id`;

UPDATE `po_header` po
  JOIN `pr_header` pr ON pr.id = po.pr_header_id
   SET po.department = pr.department
 WHERE po.department IS NULL
   AND pr.department IS NOT NULL;


-- ---------------------------------------------------------------------
-- 3) Master Asset
--
--    Tiga kolom coa_* sengaja NULLABLE: modul bisa dipakai penuh
--    (master + PR + PO) tanpa menyentuh accounting sama sekali. Mapping
--    diisi belakangan saat Finance siap. Yang mewajibkannya cuma modul
--    Penyusutan, dan itu menolak dengan pesan spesifik kalau masih kosong.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assets` (
  `id`                   int(11)        NOT NULL AUTO_INCREMENT,
  `kode_asset`           varchar(20)    NOT NULL,
  `nama_asset`           varchar(150)   NOT NULL,
  `kategori`             varchar(50)    DEFAULT 'LAINNYA' COMMENT 'from common_code.ASSET_CATEGORY',
  `satuan`               varchar(20)    DEFAULT 'UNIT'    COMMENT 'from common_code.SATUAN',
  `umur_manfaat_bulan`   int(11)        DEFAULT 48        COMMENT 'Masa manfaat dalam bulan. 48=4th (Kel.I), 96=8th (Kel.II), 192=16th (Kel.III), 240=20th (Bangunan)',
  `metode_penyusutan`    varchar(30)    DEFAULT 'GARIS_LURUS' COMMENT 'from common_code.DEPRECIATION_METHOD',
  `nilai_residu_persen`  decimal(5,2)   DEFAULT 0.00      COMMENT 'Persen nilai sisa di akhir masa manfaat',
  `coa_asset_id`         int(11)        DEFAULT NULL      COMMENT 'FK chart_of_accounts -- akun Aktiva Tetap (debit saat perolehan)',
  `coa_akum_id`          int(11)        DEFAULT NULL      COMMENT 'FK chart_of_accounts -- akun Akumulasi Penyusutan (kredit saat penyusutan)',
  `coa_beban_id`         int(11)        DEFAULT NULL      COMMENT 'FK chart_of_accounts -- akun Beban Penyusutan (debit saat penyusutan)',
  `keterangan`           text           DEFAULT NULL,
  `status`               enum('active','inactive') DEFAULT 'active',
  `created_at`           datetime       DEFAULT current_timestamp(),
  `updated_at`           datetime       DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_asset` (`kode_asset`),
  KEY `idx_assets_kategori` (`kategori`),
  KEY `idx_assets_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------
-- 4) Asset Register -- satu baris per UNIT FISIK
--
--    Beli 5 laptop = 1 baris di po_details, tapi 5 baris di sini, karena
--    tiap unit disusutkan, dipindah, dan dilepas secara terpisah.
--
--    `journal_header_id` NULL  = belum masuk pembukuan (siap ditarik Finance)
--    `journal_header_id` terisi = sudah dijurnal, tidak bisa ditarik lagi
--    Di-NULL-kan lagi kalau draft jurnalnya dihapus (pola PRF).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_register` (
  `id`                    int(11)      NOT NULL AUTO_INCREMENT,
  `no_asset`              varchar(50)  NOT NULL COMMENT 'Nomor aset unik per unit fisik, mis. AST-2026-0001',
  `asset_id`              int(11)      NOT NULL COMMENT 'FK assets.id',
  `po_header_id`          int(11)      DEFAULT NULL COMMENT 'FK po_header.id -- NULL kalau aset lama / input manual',
  `po_detail_id`          int(11)      DEFAULT NULL COMMENT 'FK po_details.id',
  `po_number`             varchar(50)  DEFAULT NULL COMMENT 'Disalin supaya laporan tidak perlu join saat PO diarsip',
  `supplier_id`           int(11)      DEFAULT NULL,
  `tanggal_perolehan`     date         NOT NULL COMMENT 'Tanggal terima fisik -- jadi titik mulai penyusutan',
  `nilai_perolehan`       decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga per unit',
  `department`            varchar(100) DEFAULT NULL COMMENT 'Departemen pemakai (from common_code.Department)',
  `lokasi`                varchar(100) DEFAULT NULL,
  `pic`                   varchar(100) DEFAULT NULL COMMENT 'Penanggung jawab / pemegang aset',
  `umur_manfaat_bulan`    int(11)      NOT NULL DEFAULT 48 COMMENT 'Disalin dari master saat register dibuat; bisa dioverride per unit',
  `metode_penyusutan`     varchar(30)  DEFAULT 'GARIS_LURUS',
  `nilai_residu`          decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai sisa dalam rupiah (bukan persen)',
  `akumulasi_penyusutan`  decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Akumulasi s/d periode terakhir yang sudah diposting',
  `nilai_buku`            decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'nilai_perolehan - akumulasi_penyusutan',
  `mulai_penyusutan`      date         DEFAULT NULL COMMENT 'Bulan pertama disusutkan. Default = bulan berikutnya setelah perolehan.',
  `journal_header_id`     int(11)      DEFAULT NULL COMMENT 'FK journal_header.id kalau perolehan aset ini sudah ditarik ke Jurnal Umum (cegah double-journal)',
  `status`                enum('Aktif','Disposed','Habis Disusutkan') NOT NULL DEFAULT 'Aktif',
  `disposed_at`           date         DEFAULT NULL,
  `disposed_notes`        text         DEFAULT NULL,
  `keterangan`            text         DEFAULT NULL,
  `created_by`            int(11)      DEFAULT NULL,
  `created_at`            datetime     DEFAULT current_timestamp(),
  `updated_at`            datetime     DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_asset` (`no_asset`),
  KEY `idx_ar_asset` (`asset_id`),
  KEY `idx_ar_po` (`po_header_id`),
  KEY `idx_ar_dept` (`department`),
  KEY `idx_ar_journal` (`journal_header_id`),
  KEY `idx_ar_status` (`status`),
  KEY `idx_ar_tanggal` (`tanggal_perolehan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------
-- 5) Penyusutan bulanan (batch)
--
--    UNIQUE (periode_year, periode_month) mencegah satu periode disusutkan
--    dua kali -- guard paling penting di modul ini.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_depreciation_header` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `batch_number`      varchar(50)  NOT NULL COMMENT 'mis. DEP-2026-08',
  `periode_year`      int(4)       NOT NULL,
  `periode_month`     int(2)       NOT NULL,
  `total_amount`      decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_asset`       int(11)      NOT NULL DEFAULT 0,
  `journal_header_id` int(11)      DEFAULT NULL COMMENT 'FK journal_header.id setelah diposting',
  `status`            enum('Draft','Posted') NOT NULL DEFAULT 'Draft',
  `notes`             text         DEFAULT NULL,
  `created_by`        int(11)      DEFAULT NULL,
  `created_at`        datetime     DEFAULT current_timestamp(),
  `posted_by`         int(11)      DEFAULT NULL,
  `posted_at`         datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  UNIQUE KEY `uniq_periode` (`periode_year`, `periode_month`),
  KEY `idx_dep_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `asset_depreciation_details` (
  `id`                        int(11)      NOT NULL AUTO_INCREMENT,
  `depreciation_header_id`    int(11)      NOT NULL,
  `asset_register_id`         int(11)      NOT NULL,
  `nilai_perolehan`           decimal(20,2) NOT NULL DEFAULT 0.00,
  `akumulasi_sebelum`         decimal(20,2) NOT NULL DEFAULT 0.00,
  `penyusutan_bulan_ini`      decimal(20,2) NOT NULL DEFAULT 0.00,
  `akumulasi_sesudah`         decimal(20,2) NOT NULL DEFAULT 0.00,
  `nilai_buku_sesudah`        decimal(20,2) NOT NULL DEFAULT 0.00,
  `coa_beban_id`              int(11)      DEFAULT NULL COMMENT 'Disalin saat batch dibuat -- jurnal tetap benar walau master diubah kemudian',
  `coa_akum_id`               int(11)      DEFAULT NULL,
  `department`                varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dd_header` (`depreciation_header_id`),
  KEY `idx_dd_register` (`asset_register_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------
-- 6) common_code: kategori aset
--
--    Kode sengaja dibuat sejajar dengan struktur COA yang sudah ada supaya
--    pemetaan akun di Master Asset jelas. CATATAN: penomoran beban penyusutan
--    LOMPAT (6200.04.06 tidak ada) -- Motor Vehicle = .07, Computer = .08.
--    Jadi mapping harus dipilih berdasarkan NAMA akun, bukan urutan nomor.
--
--      Kategori                     Aset        Akum.       Beban
--      Leasehold Improvement        1000.01.01  1000.02.01  6200.04.01
--      Plant and Machinery          1000.01.02  1000.02.02  6200.04.02
--      Mould and Tooling            1000.01.03  1000.02.03  6200.04.03
--      Fixture and Fitting          1000.01.04  1000.02.04  6200.04.04
--      Furniture and Equipment      1000.01.05  1000.02.05  6200.04.05
--      Motor Vehicle                1000.01.06  1000.02.06  6200.04.07  <-- lompat
--      Computer Equipment/Software  1000.01.07  1000.02.07  6200.04.08  <-- lompat
-- ---------------------------------------------------------------------
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT v.group_code, v.group_description, v.code, v.description, 'active'
FROM (
    SELECT 'ASSET_CATEGORY' AS group_code, NULL AS group_description, 'LEASEHOLD'        AS code, 'Leasehold Improvement'         AS description
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'PLANT_MACHINERY',  'Plant and Machinery'
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'MOULD_TOOLING',    'Mould and Tooling'
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'FIXTURE_FITTING',  'Fixture and Fitting'
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'FURNITURE_EQUIP',  'Furniture and Equipment'
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'MOTOR_VEHICLE',    'Motor Vehicle'
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'COMPUTER_SOFT',    'Computer Equipment / Software'
    UNION ALL SELECT 'ASSET_CATEGORY', NULL, 'LAINNYA',          'Lainnya'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` cc WHERE cc.group_code = v.group_code AND cc.code = v.code
);

-- Metode penyusutan. Saat ini modul hanya mengimplementasi garis lurus;
-- saldo menurun didaftarkan supaya tidak perlu migrasi lagi kalau nanti dipakai.
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT v.group_code, v.group_description, v.code, v.description, 'active'
FROM (
    SELECT 'DEPRECIATION_METHOD' AS group_code, NULL AS group_description, 'GARIS_LURUS' AS code, 'Garis Lurus (Straight Line)' AS description
    UNION ALL SELECT 'DEPRECIATION_METHOD', NULL, 'SALDO_MENURUN', 'Saldo Menurun (Declining Balance)'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` cc WHERE cc.group_code = v.group_code AND cc.code = v.code
);


-- ---------------------------------------------------------------------
-- 7) Menu Master Asset
--
--    Hanya menu Fase 1 yang didaftarkan di sini. Menu PR Asset, PO Asset,
--    Asset Register, dan Penyusutan menyusul di migrasi fase masing-masing
--    supaya sidebar tidak menampilkan link ke controller yang belum ada.
-- ---------------------------------------------------------------------
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Master Asset', 'MASTER DATA', 'master_asset', 520)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- Hak akses: ikuti master_inventory (master barang general yang paling mirip)
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'master_inventory'
JOIN `menus` m_new ON m_new.menu_key = 'master_asset'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- Admin (role_id = 1) selalu akses penuh
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 1, 1, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key = 'master_asset'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );


-- ---------------------------------------------------------------------
-- 8) Verifikasi
-- ---------------------------------------------------------------------
SELECT 'assets'                     AS tabel, COUNT(*) AS baris FROM `assets`
UNION ALL SELECT 'asset_register',            COUNT(*) FROM `asset_register`
UNION ALL SELECT 'asset_depreciation_header', COUNT(*) FROM `asset_depreciation_header`
UNION ALL SELECT 'asset_depreciation_details',COUNT(*) FROM `asset_depreciation_details`;

SELECT `code`, `description` FROM `common_code` WHERE `group_code` = 'ASSET_CATEGORY' ORDER BY `code`;

SELECT COUNT(*) AS po_total,
       SUM(`department` IS NOT NULL) AS po_ada_dept,
       SUM(`department` IS NULL)     AS po_belum_dept
  FROM `po_header`;
