-- Migration: tambah kolom due_date pada prf_header
-- Permintaan Accounting: input tanggal jatuh tempo (due date) per PRF dari halaman List PRF.
-- Tanggal: 2026-06-02

ALTER TABLE prf_header
    ADD COLUMN due_date DATE NULL AFTER prf_date;
    