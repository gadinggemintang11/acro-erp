-- ============================================================
-- MIGRATION: Jejak PR per BARIS di po_details
--
-- Sebelumnya relasi PR→PO cuma 1 kolom di header (po_header.pr_header_id),
-- jadi 1 PO mustahil menampung item dari beberapa PR dan mustahil menarik
-- sebagian item saja. Dengan pr_header_id + pr_detail_id di level baris:
--   - 1 PO boleh menarik item dari BANYAK PR
--   - 1 PR boleh dipecah ke BANYAK PO (parsial)
--   - sisa qty PR dihitung on-the-fly dari SUM(po_details.qty), jadi PO yang
--     dibatalkan/dihapus otomatis melepas kembali qty-nya (tidak ada kolom
--     status yang bisa desync)
--
-- Pola sama persis dengan prf_details.po_header_id/po_detail_id
-- (lihat db/migration_prf_multi_po_pull.sql).
--
-- po_header.pr_header_id TETAP DIPAKAI dan diisi PR sumber utama (PR pertama
-- yang masuk) supaya Po_report_model, export PDF, dan traceability IRF/MR
-- yang membacanya tidak rusak.
--
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `po_details`
  ADD COLUMN IF NOT EXISTS `pr_header_id` INT(11) NULL
  COMMENT 'PR asal baris ini, jika ditarik dari PR',
  ADD COLUMN IF NOT EXISTS `pr_detail_id` INT(11) NULL
  COMMENT 'pr_details.id asal baris ini, jika ditarik dari PR';

CREATE INDEX IF NOT EXISTS idx_po_details_pr_detail_id ON po_details(pr_detail_id);
CREATE INDEX IF NOT EXISTS idx_po_details_pr_header_id ON po_details(pr_header_id);

-- ============================================================
-- BACKFILL data lama
--
-- PO lama hanya punya jejak di header. Kita turunkan ke baris:
--   1. pr_header_id  -> disalin apa adanya dari po_header
--   2. pr_detail_id  -> dicocokkan ke baris PR yang item_id + item_type-nya
--                       sama. Kalau satu PR punya beberapa baris dengan item
--                       identik, ambil yang id terkecil dan JANGAN dipakai
--                       ulang untuk baris PO lain (cegah dua baris PO
--                       menunjuk pr_detail yang sama sehingga sisa qty PR
--                       terhitung dobel).
-- Baris yang tidak ketemu pasangannya tetap NULL — tampil sebagai item
-- manual, tidak mengganggu perhitungan sisa.
-- ============================================================

UPDATE `po_details` d
JOIN `po_header` h ON h.id = d.po_header_id
SET d.pr_header_id = h.pr_header_id
WHERE d.pr_header_id IS NULL
  AND h.pr_header_id IS NOT NULL;

UPDATE `po_details` d
JOIN (
    SELECT
        pd.id            AS po_detail_id,
        MIN(prd.id)      AS matched_pr_detail_id
    FROM `po_details` pd
    JOIN `pr_details` prd
      ON prd.pr_header_id = pd.pr_header_id
     AND prd.item_id      = pd.item_id
     AND prd.item_type    = pd.item_type
    WHERE pd.pr_header_id IS NOT NULL
      AND pd.pr_detail_id IS NULL
    GROUP BY pd.id
) m ON m.po_detail_id = d.id
SET d.pr_detail_id = m.matched_pr_detail_id
WHERE d.pr_detail_id IS NULL;

-- Buang pasangan dobel: kalau beberapa baris PO menunjuk pr_detail yang sama,
-- sisakan baris PO tertua saja supaya sisa qty PR tidak terhitung dua kali.
UPDATE `po_details` d
JOIN (
    SELECT pr_detail_id, MIN(id) AS keep_id
    FROM `po_details`
    WHERE pr_detail_id IS NOT NULL
    GROUP BY pr_detail_id
    HAVING COUNT(*) > 1
) dup ON dup.pr_detail_id = d.pr_detail_id AND d.id <> dup.keep_id
SET d.pr_detail_id = NULL;

-- ============================================================
-- VERIFIKASI
-- ============================================================
SHOW COLUMNS FROM `po_details` LIKE 'pr_%';

SELECT
    COUNT(*)                                              AS total_baris_po,
    SUM(pr_header_id IS NOT NULL)                         AS punya_pr_header,
    SUM(pr_detail_id IS NOT NULL)                         AS punya_pr_detail
FROM `po_details`;
