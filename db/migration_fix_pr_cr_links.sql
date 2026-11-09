-- ============================================================
-- Perbaiki jejak IRF -> PR yang putus (pr_detail_cr_links)
-- ------------------------------------------------------------
-- User 2026-09-17: IRF ADM00003 tampil "masih di IRF saja" padahal barangnya
-- sudah masuk PR ADM00084 dan PO POGNR-000069.
--
-- Sebabnya: Consumable_request_model::update() dulu MENGHAPUS seluruh baris
-- item IRF lalu menyisipkannya kembali, sehingga id barisnya berubah. Baris
-- `pr_detail_cr_links` masih menunjuk id lama yang sudah tidak ada, jadi:
--   - IRF Tracking tidak menemukan PR/PO-nya,
--   - hitungan "sudah ada di PR" saat membuat PR sisa ikut meleset.
-- Kode penyebabnya sudah diperbaiki (id baris kini dipertahankan saat edit);
-- migrasi ini membereskan data yang terlanjur putus.
--
-- Cara memasangkan ulang: dari baris PR -> PR header -> IRF yang namanya
-- disebut di `remarks` PR ("Sumber IRF: ADM00003", ditulis saat PR dibuat
-- otomatis dari outstanding IRF) -> baris IRF dengan barang yang sama.
-- HANYA diperbaiki kalau kandidatnya TEPAT SATU; kalau sebuah PR menggabung
-- beberapa IRF yang memuat barang sama, baris itu dilewati (lebih baik
-- dibiarkan kosong daripada salah sambung).
--
-- Idempotent: aman dijalankan berulang (yang sudah benar tidak disentuh).
-- ============================================================

DROP TEMPORARY TABLE IF EXISTS `tmp_link_fix`;
CREATE TEMPORARY TABLE `tmp_link_fix` (
    `link_id`      INT NOT NULL,
    `cr_detail_id` INT NOT NULL,
    PRIMARY KEY (`link_id`)
);

-- 1) Tautan yatim: cr_detail_id menunjuk baris yang sudah dihapus.
INSERT INTO `tmp_link_fix` (`link_id`, `cr_detail_id`)
SELECT x.link_id, x.cr_detail_id FROM (
    SELECT l.id AS link_id, MIN(crd.id) AS cr_detail_id, COUNT(DISTINCT crd.id) AS kandidat
    FROM `pr_detail_cr_links` l
    INNER JOIN `pr_details` pd ON pd.id = l.pr_detail_id
    INNER JOIN `pr_header` ph ON ph.id = pd.pr_header_id
    INNER JOIN `consumable_request_header` crh ON ph.remarks LIKE CONCAT('%', crh.request_number, '%')
    INNER JOIN `consumable_request_details` crd
            ON crd.header_id = crh.id AND crd.item_id = pd.item_id AND crd.item_type = pd.item_type
    WHERE NOT EXISTS (SELECT 1 FROM `consumable_request_details` cek WHERE cek.id = l.cr_detail_id)
    GROUP BY l.id
    HAVING kandidat = 1
) x;

UPDATE `pr_detail_cr_links` l
JOIN `tmp_link_fix` f ON f.link_id = l.id
SET l.cr_detail_id = f.cr_detail_id;

-- 2) Baris PR yang sama sekali belum punya tautan, padahal PR-nya memang
--    lahir dari outstanding IRF (ditandai di remarks). Terjadi pada baris
--    yang ditambahkan setelah PR dibuat, atau saat item IRF diubah.
INSERT INTO `pr_detail_cr_links` (`pr_detail_id`, `cr_detail_id`, `qty`, `created_at`)
SELECT y.pr_detail_id, y.cr_detail_id, y.qty, NOW() FROM (
    SELECT pd.id AS pr_detail_id, MIN(crd.id) AS cr_detail_id,
           LEAST(MIN(pd.qty), MIN(crd.qty_requested)) AS qty,
           COUNT(DISTINCT crd.id) AS kandidat
    FROM `pr_details` pd
    INNER JOIN `pr_header` ph ON ph.id = pd.pr_header_id
    INNER JOIN `consumable_request_header` crh ON ph.remarks LIKE CONCAT('%', crh.request_number, '%')
    INNER JOIN `consumable_request_details` crd
            ON crd.header_id = crh.id AND crd.item_id = pd.item_id AND crd.item_type = pd.item_type
    WHERE ph.remarks LIKE '%outstanding IRF%'
      AND NOT EXISTS (SELECT 1 FROM `pr_detail_cr_links` l2 WHERE l2.pr_detail_id = pd.id)
    GROUP BY pd.id
    HAVING kandidat = 1
) y;

-- 3) Tautan sampah: baris PR-nya sendiri sudah tidak ada (PR dihapus).
--    Tidak bisa dipakai siapa pun, jadi dibuang.
DELETE l FROM `pr_detail_cr_links` l
LEFT JOIN `pr_details` pd ON pd.id = l.pr_detail_id
WHERE pd.id IS NULL;

DROP TEMPORARY TABLE IF EXISTS `tmp_link_fix`;
