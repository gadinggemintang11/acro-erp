-- Migration: PO PDF letterhead (user 2026-09-05) — cetak PO harus mirip 100%
-- dengan contoh letterhead resmi PT New Specialife Industries (ISSUE TO/ATTN
-- TO/TEL, P.O. NO/DATE/REF/BUYER, kolom Unit Price USD+IDR, kotak Payment
-- Term/Delivery Term/Remarks, 2 blok tanda tangan).
--
-- "ATTN TO" (nama contact person di supplier) belum ada kolomnya sama sekali
-- di master supplier -- field ini per-SUPPLIER (diisi sekali, dipakai ulang
-- di semua PO ke supplier itu), beda dari field per-PO lain yang semua
-- sudah ada (po_number/po_date/remarks/shipping_mode_code/payment_mode_code).
-- "BUYER" pada letterhead diambil dari nama pembuat PO (po_header.created_by),
-- TIDAK perlu kolom baru. "REF." diisi dari nomor PR sumber (sudah dihitung
-- di export_pdf() via get_source_prs()), TIDAK perlu kolom baru juga.

ALTER TABLE suppliers
    ADD COLUMN contact_person VARCHAR(150) NULL AFTER nama_supplier;
