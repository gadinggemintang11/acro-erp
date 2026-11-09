-- ============================================================
-- Document Sharing (fitur baru, permintaan user 2026-09-04)
-- Menu upload dokumen dgn folder per departemen (reuse common_code
-- group_code='Department') + akses per-file: 'private' (cuma
-- uploader+Admin) atau 'roles' (dipilih role mana saja yg boleh lihat,
-- lintas departemen — lihat memory document_sharing_feature.md).
-- ============================================================

CREATE TABLE IF NOT EXISTS `doc_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_code` VARCHAR(50) NOT NULL COMMENT 'folder = common_code.code WHERE group_code=Department',
  `title` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(150) DEFAULT NULL,
  `file_size` INT DEFAULT NULL COMMENT 'bytes',
  `visibility` ENUM('private','roles') NOT NULL DEFAULT 'private',
  `uploaded_by` INT DEFAULT NULL,
  `uploaded_at` DATETIME DEFAULT NULL,
  KEY `idx_doc_dept` (`department_code`),
  KEY `idx_doc_uploader` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `doc_document_roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  UNIQUE KEY `uq_docrole` (`document_id`, `role_id`),
  KEY `idx_docrole_doc` (`document_id`),
  CONSTRAINT `fk_docrole_document` FOREIGN KEY (`document_id`) REFERENCES `doc_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu baru (bukan di bawah SYSTEM Setup sidebar — ini utility utk semua
-- karyawan, ditaruh sbg top-level link tersendiri; `department`='SYSTEM'
-- di sini cuma dipakai pengelompokan di layar Role Access, tidak menentukan
-- posisi sidebar).
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`)
VALUES ('Document Sharing', 'SYSTEM', 'document_sharing', 535);

-- Default permission: view+add dibuka utk SEMUA role (tool umum, semua
-- karyawan boleh upload+lihat dokumen yg mereka berhak lihat) — beda dari
-- Developer Mode yg Admin-only by default, krn sifat modul ini memang utk
-- dipakai luas. can_delete cuma Admin (role_id=1) by default; uploader
-- tetap bisa hapus dokumen MILIKNYA SENDIRI lepas dari can_delete ini
-- (dicek via kepemilikan di controller, bukan lewat permission menu).
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT r.id, (SELECT id FROM `menus` WHERE menu_key = 'document_sharing'), 1, 1, 0, (CASE WHEN r.id = 1 THEN 1 ELSE 0 END)
FROM `roles` r;
