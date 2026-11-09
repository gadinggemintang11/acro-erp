-- Migration: QA Approval Workflow
-- Tambah status 'Draft' & 'Rejected' di qa_header.status
-- Tambah 'Unapproved' di qa_approval_log.approval_status
-- Fix data row yang corrupt karena ENUM tidak menerima 'Draft'/'Rejected' sebelum migration

-- 1. ALTER qa_header.status
ALTER TABLE qa_header
    MODIFY COLUMN status ENUM('Pending','In Progress','Draft','Completed','Rejected')
    NULL DEFAULT 'Pending';

-- 2. ALTER qa_approval_log.approval_status
ALTER TABLE qa_approval_log
    MODIFY COLUMN approval_status ENUM('Approved','Rejected','Unapproved','Pending Review')
    NULL DEFAULT 'Pending Review';

-- 3. Fix data corrupt: row yang punya status '' (empty) akibat invalid ENUM write
-- Asumsi: row tersebut sebelumnya 'In Progress' lalu di-complete (jadi seharusnya 'Draft')
UPDATE qa_header SET status = 'Draft' WHERE status = '' OR status IS NULL;
