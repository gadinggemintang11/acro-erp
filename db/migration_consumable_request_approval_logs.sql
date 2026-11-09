-- Migration: IRF (Consumable Request) Approval Logs
-- Riwayat aksi approval IRF (chronological) — tahan walau IRF di-return & resubmit
-- (kolom audit di consumable_request_header di-reset saat return; log ini tidak).
--
-- Alur IRF efektif HOD-only:
--   submit        -> requestor submit
--   approve_hod   -> HOD approve (langsung Approved/final utk alur normal)
--   endorse       -> auto-endorse khusus direct final requester (user 37)
--   approve_final -> final approve (kasus direct final requester)
--   reject        -> HOD reject (+ alasan)
--   return        -> HOD return to draft (+ alasan)

CREATE TABLE IF NOT EXISTS `consumable_request_approval_logs` (
  `id`         INT(11)          NOT NULL AUTO_INCREMENT,
  `header_id`  INT(11) UNSIGNED NOT NULL,
  `action`     VARCHAR(30)      NOT NULL COMMENT 'submit, approve_hod, endorse, approve_final, reject, return',
  `actor_id`   INT(11)          NOT NULL,
  `actor_name` VARCHAR(100)     DEFAULT NULL,
  `notes`      TEXT             DEFAULT NULL,
  `created_at` DATETIME         NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_crl_header` (`header_id`),
  CONSTRAINT `fk_crl_header` FOREIGN KEY (`header_id`)
      REFERENCES `consumable_request_header` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IRF approval action log';

-- ------------------------------------------------------------------
-- Backfill one-time dari kolom audit yang sudah ada di header,
-- supaya IRF lama langsung punya riwayat. Aman dijalankan sekali.
-- (Skip otomatis kalau sudah ada log utk header tsb.)
-- ------------------------------------------------------------------
INSERT INTO `consumable_request_approval_logs` (`header_id`, `action`, `actor_id`, `actor_name`, `notes`, `created_at`)
SELECT h.id, 'submit', h.submitted_by, u.fullname, NULL, h.submitted_at
FROM consumable_request_header h
LEFT JOIN users u ON u.id = h.submitted_by
WHERE h.submitted_by IS NOT NULL AND h.submitted_at IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM consumable_request_approval_logs l WHERE l.header_id = h.id);

INSERT INTO `consumable_request_approval_logs` (`header_id`, `action`, `actor_id`, `actor_name`, `notes`, `created_at`)
SELECT h.id, 'approve_hod', h.hod_approved_by, u.fullname, NULL, h.hod_approved_at
FROM consumable_request_header h
LEFT JOIN users u ON u.id = h.hod_approved_by
WHERE h.hod_approved_by IS NOT NULL AND h.hod_approved_at IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM consumable_request_approval_logs l WHERE l.header_id = h.id AND l.action = 'approve_hod');

INSERT INTO `consumable_request_approval_logs` (`header_id`, `action`, `actor_id`, `actor_name`, `notes`, `created_at`)
SELECT h.id, 'endorse', h.endorsed_by, u.fullname, NULL, h.endorsed_at
FROM consumable_request_header h
LEFT JOIN users u ON u.id = h.endorsed_by
WHERE h.endorsed_by IS NOT NULL AND h.endorsed_at IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM consumable_request_approval_logs l WHERE l.header_id = h.id AND l.action = 'endorse');

INSERT INTO `consumable_request_approval_logs` (`header_id`, `action`, `actor_id`, `actor_name`, `notes`, `created_at`)
SELECT h.id, 'approve_final', h.final_approved_by, u.fullname, NULL, h.final_approved_at
FROM consumable_request_header h
LEFT JOIN users u ON u.id = h.final_approved_by
WHERE h.final_approved_by IS NOT NULL AND h.final_approved_at IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM consumable_request_approval_logs l WHERE l.header_id = h.id AND l.action = 'approve_final');

INSERT INTO `consumable_request_approval_logs` (`header_id`, `action`, `actor_id`, `actor_name`, `notes`, `created_at`)
SELECT h.id, 'reject', h.rejected_by, u.fullname, h.reject_reason, h.rejected_at
FROM consumable_request_header h
LEFT JOIN users u ON u.id = h.rejected_by
WHERE h.rejected_by IS NOT NULL AND h.rejected_at IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM consumable_request_approval_logs l WHERE l.header_id = h.id AND l.action = 'reject');
