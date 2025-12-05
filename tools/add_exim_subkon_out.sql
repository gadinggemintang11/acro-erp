-- Add EXIM Verification columns to subkon_out_header table
-- Run this script to enable EXIM verification for Subkon Out documents

USE neokabersis_db;

-- Add EXIM verification columns to subkon_out_header
ALTER TABLE subkon_out_header 
ADD COLUMN IF NOT EXISTS exim_verified TINYINT(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)' AFTER status,
ADD COLUMN IF NOT EXISTS exim_verified_by INT(11) NULL COMMENT 'User ID yang melakukan verifikasi EXIM' AFTER exim_verified,
ADD COLUMN IF NOT EXISTS exim_verified_at DATETIME NULL COMMENT 'Tanggal dan waktu verifikasi EXIM' AFTER exim_verified_by;

-- Add foreign key for exim_verified_by
ALTER TABLE subkon_out_header
ADD CONSTRAINT fk_subkon_out_exim_verifier 
FOREIGN KEY (exim_verified_by) REFERENCES users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Create index for better query performance
CREATE INDEX idx_subkon_out_exim_verified ON subkon_out_header(exim_verified);

-- Display success message
SELECT 'EXIM verification columns successfully added to subkon_out_header table' AS status;
