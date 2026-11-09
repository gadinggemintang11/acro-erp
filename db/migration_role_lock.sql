-- ============================================================
-- Kunci Role Access (satu kunci untuk semua role)
-- ------------------------------------------------------------
-- User 2026-09-17: "buat di role acces ada button kunci role, khusus admin,
-- jadi ketika sudah dikunci button edit akses dan edit posisi dll itu ga ada",
-- lalu "saya mau button lock ini 1 aja dan meng lock semua".
--
-- Tabel satu baris (id = 1). Saat is_locked = 1: tambah role, kelola hak
-- akses, edit role (nama/departemen/posisi/status), hapus role, serta
-- buat/terapkan Template PDF ditolak — tombolnya disembunyikan dan endpoint
-- ikut menolak. Hanya Admin yang bisa mengunci & membuka kunci.
-- Pemberian role ke user (User Management) TIDAK ikut terkunci.
--
-- Idempotent: aman dijalankan berulang. Awalnya TIDAK terkunci.
-- ============================================================

CREATE TABLE IF NOT EXISTS `role_access_lock` (
    `id`        TINYINT      NOT NULL,
    `is_locked` TINYINT(1)   NOT NULL DEFAULT 0,
    `locked_by` INT          NULL DEFAULT NULL,
    `locked_at` DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `role_access_lock` (`id`, `is_locked`) VALUES (1, 0);
