-- ============================================================
-- Tautan baris PR -> baris MRF (padanan pr_detail_cr_links utk IRF)
-- ------------------------------------------------------------
-- User 2026-09-21: "ketika stock tidak tercukupi kan juga dibuat PR,
-- logikanya harusnya sama dengan general kan?" — jalur material (MRF ->
-- Material Release) belum punya jejak struktural PR mana yang sudah dibuat
-- untuk baris MRF mana, sehingga qty PR tidak bisa dikurangi "yang sudah ada
-- di PR aktif" seperti jalur IRF, dan PR ganda tidak bisa dicegah.
--
-- Isi: 1 baris = 1 baris PR yang lahir dari 1 baris MRF, beserta qty-nya.
-- Dipakai Material_outstanding_model::get_pr_need_map() & get_pr_docs_map().
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS `pr_detail_mr_links` (
    `id`           INT(11)        NOT NULL AUTO_INCREMENT,
    `pr_detail_id` INT(11)        NOT NULL,
    `mr_detail_id` INT(11)        NOT NULL,
    `qty`          DECIMAL(15,4)  NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME       DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_prmr_pr_detail_id` (`pr_detail_id`),
    KEY `idx_prmr_mr_detail_id` (`mr_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
