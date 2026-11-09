-- Migration: Status 'Released' untuk IRF (consumable request)
-- IRF yang seluruh itemnya sudah di-issue penuh via Consumable Out (Confirmed)
-- berubah status dari 'Approved' menjadi 'Released'.
-- Ke depan status di-sync otomatis oleh Consumable_out_model::_sync_request_status().
-- Kolom status sudah varchar(50), tidak perlu perubahan struktur.
-- Tanggal: 2026-06-12

UPDATE consumable_request_header cr_h
SET cr_h.status = 'Released'
WHERE cr_h.status = 'Approved'
  AND EXISTS (
      SELECT 1 FROM consumable_request_details d WHERE d.header_id = cr_h.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM consumable_request_details cr_d
      LEFT JOIN (
          SELECT co_h.request_id, co_d.item_id, SUM(co_d.qty_issued) AS qty_issued_sum
          FROM consumable_out_details co_d
          INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
          WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
          GROUP BY co_h.request_id, co_d.item_id
      ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id
      WHERE cr_d.header_id = cr_h.id
        AND (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0)) > 0
  );
