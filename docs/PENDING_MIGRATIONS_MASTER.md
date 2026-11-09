# Master Checklist — Migration SQL Belum Di-deploy ke Production

**Kenapa dokumen ini ada:** semua file di bawah ini **ADA di folder `db/` tapi
BELUM di-commit ke git** (cek `git status` — semuanya muncul sebagai `??`
untracked), yang berarti kemungkinan besar **juga belum pernah dijalankan di
database production**. Dokumen ini daftar LENGKAP dari yang paling lama
sampai yang paling baru, supaya deploy ke production tidak ada yang
kelewat/kececer.

**Update dokumen ini** setiap kali ada migration SQL baru dibuat — tinggal
tambah baris baru di tabel, jangan bikin dokumen checklist terpisah lagi per
fitur (kecuali fitur itu butuh panduan detail, seperti bagian IRF Category di
bawah yang py dokumen sendiri).

**Cara cek current status:** jalankan `git status db/` — semua file `.sql`
yang muncul `??` (untracked) itulah yang BELUM di-commit, artinya kemungkinan
besar belum di-deploy. File yang tidak muncul di `git status` (sudah
tracked/committed) berarti sudah pernah di-deploy di sesi sebelumnya.

---

## Cara pakai dokumen ini saat deploy (2026-09-06: sekarang 1 perintah)

Dulu bagian ini isinya 36 baris `mysql ... < file.sql` yang harus di-copy
manual satu-satu. **Sekarang cukup 1 skrip**: [`db/deploy_migrations.sh`](../db/deploy_migrations.sh)
— sudah berisi urutan yang SAMA PERSIS dengan tabel "Daftar lengkap" di
bawah, jadi tidak perlu lagi baca/copy dari sini.

```bash
cd /path/to/nsi-erp

# Lihat dulu urutan yg akan dijalankan TANPA eksekusi apa pun:
DB_NAME=nsi-db DB_USER=<user> ./db/deploy_migrations.sh --dry-run

# Jalankan sungguhan (akan backup otomatis dulu via mysqldump, lalu minta
# konfirmasi "yes", baru eksekusi tiap file berurutan; berhenti otomatis
# begitu ada 1 file error, tanpa merusak yang sudah jalan):
DB_NAME=nsi-db DB_USER=<user> DB_PASSWORD=<password> ./db/deploy_migrations.sh
```

Ringkasan yang perlu diketahui (detail lengkap ada di komentar dalam skrip):
1. **Semua file MEMANG idempoten** (aman dijalankan ulang) — kalau skrip
   berhenti di tengah karena 1 file error, perbaiki masalahnya lalu jalankan
   ULANG SELURUH skrip dari awal, jangan comment-out file yang sudah sukses.
2. Backup otomatis (mysqldump) jalan duluan sebelum migration apa pun,
   kecuali dipanggil dengan flag `--skip-backup` (tidak disarankan di
   production).
3. Setelah SEMUA migration SQL sukses, skrip akan **menampilkan pengingat**
   langkah manual non-SQL (biasanya di halaman Role Access, ditandai ⚠️ di
   tabel bawah) — jangan dilewat.
4. Setelah kelar, **commit semua file `db/*.sql` yang baru** ke git (`git add
   db/*.sql docs/*.md && git commit`) — supaya lain kali `git status`
   menunjukkan dengan benar mana yang masih pending. Skrip juga mengingatkan
   ini di akhir.

**Kalau bikin migration SQL baru** setelah ini: tambahkan barisnya di array
`MIGRATIONS` dalam `db/deploy_migrations.sh` (di akhir, sesuai urutan
dibuat) **SEKALIGUS** baris baru di tabel "Daftar lengkap" di bawah — supaya
keduanya tidak saling berbeda (drift).

---

## Daftar lengkap (urut kronologis = urut jalan)

| # | File | Dibuat | Fitur | Apa yang dilakukan |
|---|---|---|---|---|
| 1 | `migration_po_jasa.sql` | 9 Jul | PO Jasa + Master Jasa | Bikin tabel `jasa` (master service) + daftarkan menu PO Jasa. PO Jasa reuse `po_header/po_details` (`type='jasa'`). Tombol "Selesaikan Jasa" nulis ke `grn_header/grn_details` (`warehouse_type='jasa'`) biar AP Accounting otomatis detect. |
| 2 | `migration_pr_jasa.sql` | 10 Jul | PR Jasa | Tambah value `'jasa'` ke enum `pr_header.type`. PR Jasa reuse `pr_header/pr_details` + `Purchase_request_model`, approval 3-stage spt PR General, baru "Buat PO" jadi PO Jasa. |
| 3 | `migration_supplier_kategori_jasa.sql` | 10 Jul | Master Supplier | Tambah value `'jasa'` ke enum `suppliers.kategori` (sebelumnya cuma general/produksi) + daftarkan pilihan dropdown-nya di `common_code` (group `Kategori_supplier`). |
| 4 | `migration_prf_journal_link.sql` | 15 Jul | PRF ↔ Jurnal Umum | Tambah kolom `prf_header.journal_header_id` — dicatat saat PRF ditarik jadi Jurnal Umum ("Tarik dari PRF"), cegah PRF yang sama ditarik/dijurnal 2x. NULL lagi kalau draft jurnalnya dihapus. |
| 5 | `migration_prf_multi_po_pull.sql` | 29 Jul | PRF multi-PO | Tambah kolom `prf_details.po_header_id` & `po_detail_id` — supaya 1 PRF bisa tarik item dari BEBERAPA PO sekaligus (per-baris tracking), bukan cuma 1 PO per PRF (`prf_header.po_id` lama). |
| 6 | `migration_irf_status_menu.sql` | 4 Aug | Menu "IRF Status" | Daftarkan menu baru `irf_status` (read-only, status per NOMOR IRF: diminta/di-release/outstanding). Menu lama `consumable_outstanding` diganti nama jadi "IRF Outstanding" (isinya agregasi per ITEM, beda konsep). |
| 7 | `migration_asset.sql` | 6 Aug | Asset FASE 0 | Pondasi modul Master Asset. Tabel `assets`, `asset_register`. TIDAK ada seed COA (akun aktiva tetap dipilih manual dari COA yang sudah ada). Jalur akuntansi asset TIDAK lewat GRN/AP Invoice (pola sama spt PRF — pull manual ke Jurnal Umum). |
| 8 | `migration_asset_fase2_pr.sql` | 6 Aug | Asset FASE 2 (PR) | Daftarkan menu PR Asset + PR Asset Approval. Reuse `pr_header/pr_details` (`type='asset'`, enum sudah ditambah di Fase 0). Tidak ada perubahan struktur tabel. |
| 9 | `migration_asset_fase3_po.sql` | 6 Aug | Asset FASE 3 (PO) | Daftarkan menu PO Asset + PO Asset Approval. Reuse `po_header/po_details` (`type='asset'`). Nomor PO prefix baru `POAST-`. Beda dari PO Jasa: TIDAK ada "Selesaikan Jasa"/GRN — penerimaan aset ditangani terpisah di Fase 4 (nulis ke `asset_register` per unit fisik). |
| 10 | `migration_irf_machine.sql` | 20 Aug | IRF item Mesin | IRF (Consumable Request) & Consumable Out sebelumnya cuma bisa item dari `inventories`. Sekarang bisa juga dari `machines` (Stock Machine). Kolom `item_type` ditambah ke tabel detail terkait supaya join ke `stock_ledger`/`stock_on_hand` tidak salah item (id inventories & machines bisa bentrok). |
| 11 | `migration_irf_category.sql` | 31 Aug | IRF General/Produksi (1/6) | **Lihat panduan detail:** [`docs/IRF_CATEGORY_SEPARATION_DEPLOYMENT.md`](./IRF_CATEGORY_SEPARATION_DEPLOYMENT.md) bagian 1a. Tambah kolom `category` di `consumable_request_header` & `consumable_out_header`. |
| 12 | `migration_irf_category_chain.sql` | 31 Aug | IRF General/Produksi (2/6) | Lihat doc di atas bagian 1b. Tambah kolom `category` (nullable) di `pr_details`, `po_details`, `sgr_details` — rantai traceability kategori sampai penerimaan barang. |
| 13 | `migration_consumable_out_category_menu.sql` | 31 Aug | IRF General/Produksi (3/6) | Lihat doc di atas bagian 1c. Pecah menu "Stock Item Release" jadi 2 pintu sidebar (`consumable_out_general`/`_produksi`), salin role_permissions lama sbg starting point. **⚠️ Setelah ini WAJIB masuk Role Access, persempit akses per role** (lihat doc bagian 2). |
| 14 | `migration_stock_category_split.sql` | 31 Aug | IRF General/Produksi (5/6, revisi arsitektur) | Lihat doc di atas bagian 7a. `stock_ledger` utk item_type inventory/machine BENAR-BENAR dipecah 2 saldo (General/Produksi), bukan cuma label lagi. **Backfill: semua saldo lama jadi General, Produksi mulai 0** — kalau ada barang fisik yang sebenarnya di gudang Produksi, pindahkan manual pakai Stock Transfer (baris berikutnya) setelah migration ini. |
| 15 | `migration_stock_transfer.sql` | 31 Aug | IRF General/Produksi (6/6) | Lihat doc di atas bagian 7c. Modul baru **Stock Transfer** — cara resmi pindah saldo General↔Produksi utk item yang sama (dipakai jugauntuk koreksi cutover baris di atas). |
| 16 | `migration_irf_outstanding_status_category_menu.sql` | 31 Aug | IRF General/Produksi (tambahan bagian 8) | Lihat doc di atas bagian 8a. Pecah menu "IRF Outstanding" & "IRF Status" jadi 2 pintu per kategori juga (pola sama baris 13). **⚠️ Ulangi langkah Role Access utk 4 menu baru ini.** |
| 17 | `migration_item_code_v2.sql` | 2 Sep | Kode Master Barang v2 | Kolom baru `inventories.item_type` (Stock/Non-Stock) & `materials.sub_class` (RAW/SUP/CON/WIP) — dasar format kode baru `MAIN-SUB-NUMBER`. Insert common_code group `INVENTORY_SUB_CATEGORY` (STA/CVL/MEC/ELC/APL/FUR/COM/ENT). Lihat memory Claude `erp_revision_code_system` utk detail lengkap. |
| 18 | `migration_item_code_v2_commoncode.sql` | 2 Sep | Kode Master Barang v2 (follow-up) | Follow-up baris 17: ubah `item_type`/`sub_class` dari ENUM hardcode jadi VARCHAR + common_code (group `INVENTORY_ITEM_TYPE`, `MATERIAL_SUB_CLASS`) — konsisten dgn pola semua kategori lain di app ini. **Jalankan SETELAH baris 17.** |
| 19 | `migration_item_release_attachments.sql` | 2 Sep | Item Release — Bukti Pengeluaran | Tabel baru `consumable_out_attachments` & `material_issuance_attachments` — attachment bukti pengeluaran oleh gudang, wajib diupload SETELAH barang keluar (bukan syarat sebelum). |
| 20 | `migration_irf_receipt_attachments.sql` | 2 Sep | IRF — Bukti Penerimaan | Tabel baru `consumable_request_attachments` — attachment bukti penerimaan oleh REQUESTOR (beda dari baris 19 yang punya gudang), muncul di IRF begitu status `Released`. |
| 21 | `migration_sgr_category_split.sql` | 2 Sep | SGR General/Produksi/Lainnya | Kolom baru `sgr_header.category` (General/Produksi/Lainnya, backfill baris lama ke 'Lainnya'). 2 menu baru `supplier_general_item_receipt_general/_produksi`, salin role_permissions. Prasyarat chooser "Pemasukan" (belum dibangun). **⚠️ Setelah ini disarankan masuk Role Access, persempit akses per role** (pola sama baris 13). |
| 22 | `migration_po_approval_logs.sql` | 2 Sep | History PO | Tabel baru `po_approval_logs` (pola sama `erf_approval_logs`/`prf_approval_logs`) — replikasi History universal ke PO (submit/approve HOD/endorse/approve final/return-to-draft/reject), berlaku ke semua varian PO (base/general/jasa/asset) sekaligus krn share `Po_model`+`po_header`. |
| 23 | `migration_back_to_draft_reply.sql` | 2 Sep | Back to Draft 2-arah | Lebarkan `erf_approval_logs.action` ENUM tambah `'Reply'`. IRF (`consumable_request_approval_logs.action`) & PRF (`prf_approval_logs.approval_level`/`status`) kolomnya VARCHAR bebas — TIDAK butuh migration, cukup value baru dari kode PHP. PO belum ikut (di luar scope permintaan awal). |
| 24 | `migration_irf_jasa_category.sql` | 3 Sep | IRF kategori Jasa | Lebarkan `consumable_request_header.category` ENUM tambah `'Jasa'` (skrg General/Produksi/Jasa). Prasyarat item picker IRF diperluas ke Master Asset+Jasa — lihat [[revision_irf_auto_pr_at_issuance]]. |
| 25 | `migration_pr_detail_cr_links.sql` | 3 Sep | Penyelesaian Jasa balik ke IRF | Tabel baru `pr_detail_cr_links` (pr_detail_id, cr_detail_id, qty) — link structural yang sebelumnya tidak ada antara `pr_details` dan `consumable_request_details`. Rantai: cr_detail → pr_detail → `po_details.pr_detail_id` (sudah ada) → `grn_details.source_doc_detail_id` (sudah ada, `warehouse_type='jasa'`). Dipakai baca-saja oleh `Consumable_outstanding_model` & `Consumable_request_model::check_outstanding_by_item()` supaya outstanding Jasa resolve ke 0 begitu PO Jasa-nya "Selesaikan Jasa" — sebelum ini outstanding Jasa permanen 100% krn item Jasa tidak pernah lewat `consumable_out_details`. Lihat [[revision_irf_auto_pr_at_issuance]]. |
| 26 | `migration_pr_po_details_jasa_category.sql` | 3 Sep | Bugfix kategori Jasa di PR/PO | **BUG DITEMUKAN** saat menguji baris 25: `pr_details.category` & `po_details.category` masih ENUM('General','Produksi') dari sebelum kategori Jasa ada — `Consumable_outstanding::create_pr()` GAGAL total (SQL error, strict mode) tiap kali baris Jasa dijadikan PR. Lebarkan kedua ENUM tambah `'Jasa'`. **WAJIB dijalankan bareng/sebelum baris 24 dipakai serius** (tanpa ini fitur "PR dari Outstanding" utk kategori Jasa error). |
| 27 | `migration_pr_po_monitoring.sql` | 3 Sep | PR List & PO List (monitoring) | 2 menu baru `po_monitoring`/`pr_monitoring` (dept PURCHASING MATERIAL) — view GABUNGAN lintas-tipe (produksi/non_produksi/jasa/asset), read-only. Copy permission dari `po_report`/`purchase_request` sbg starting point (`can_delete` sengaja 0 semua kecuali Admin). Lihat [[erp_revision_monitoring_lists]]. |
| 28 | `migration_notifications.sql` | 3 Sep | Notification system (topbar bell) | Tabel baru `notifications` (user_id, title, message, type, icon, link, source_module, source_id, is_read, read_at). Menggantikan sumber data bell topbar yang lama (`user_logs`, feed generik) — `Notification_model` ditulis ulang total utk baca/tulis tabel ini. Tidak ada menu/permission baru (bell sudah ada dari sebelumnya, cuma isinya yang diganti). Lihat [[erp_revision_cross_cutting]]. |
| 29 | `migration_po_back_to_draft_reply.sql` | 3 Sep | Back to Draft 2-arah untuk PO | `po_approval_logs.action` ENUM dilebarkan tambah `'Reply'` — replikasi fitur reply/chat yang sudah ada di IRF/PRF/ERF (2 Sep) ke PO. Lihat [[erp_revision_cross_cutting]]. |
| 30 | `migration_lang_overrides.sql` | 3 Sep | Developer Mode (editor teks/terjemahan) | Tabel baru `lang_overrides` (lang_key, language, custom_value, default_value) — dipakai `lang_line()` sbg lapisan override sebelum fallback ke app_lang.php. Menu baru `developer_mode` (dept SYSTEM), akses diatur lewat Role Access biasa (bukan dikunci Super Admin). **BELUM diuji manual di browser** — lihat [[developer_mode_feature]]. |
| 31 | `migration_site_assets.sql` | 3 Sep | Developer Mode — Kelola Logo/Gambar | Tabel baru `site_assets` (asset_key UNIQUE, file_path nullable=pakai default, original_name, uploaded_by/at) — override manual (BUKAN auto-discovery spt lang_overrides) utk slot logo terbatas (`logo_sidebar_full`/`logo_sidebar_mini`). Lihat [[developer_mode_feature]]. |
| 32 | `migration_ui_columns.sql` | 3 Sep | Developer Mode — Atur Kolom List | Tabel baru `ui_columns` (UNIQUE(page_key,column_key), column_label, is_hidden, updated_by/at) — auto-discovery spt lang_overrides (INSERT IGNORE tiap kolom baru ketemu). Dipakai 6 halaman list (IRF/PRF/ERF List, PR/PO Monitoring, IRF Approval Queue). Lihat [[developer_mode_feature]]. |
| 33 | `migration_prf_unit.sql` | 4 Sep | PRF Unit | Kolom baru `prf_details.unit` (nullable, reuse dropdown master `common_code` group SATUAN yg sudah dipakai modul lain) — PRF sebelumnya tidak punya field satuan per item sama sekali. Sekalian tambah 1 opsi baru ke SATUAN: `TRX` (Transaction), via INSERT...WHERE NOT EXISTS krn `common_code` tidak punya UNIQUE(group_code,code). Lihat [[erp_revision_prf_erf]]. |
| 34 | `migration_document_sharing.sql` | 4 Sep | Document Sharing (menu baru) | 2 tabel baru: `doc_documents` (folder=`common_code` group Department, title/file_path/visibility private-or-roles/uploaded_by) & `doc_document_roles` (junction, dipakai kalau visibility='roles', cascade delete). Menu baru `document_sharing` (dept SYSTEM utk Role Access grouping, TAPI sidebar-nya top-level tersendiri bukan di bawah System Setup). Default permission: view+add ke SEMUA role (tool umum), can_delete cuma Admin (uploader tetap bisa hapus punya sendiri lepas dari ini, dicek kepemilikan di controller). Lihat [[document_sharing_feature]]. |
| 35 | `migration_user_type_approval.sql` | 4 Sep | Tipe User & Approval Akun | Kolom baru `users.user_type` (super_admin/executive/accounting/user, default 'user') & `users.approval_status` (approved/pending/rejected, default 'approved') — 4 akun riil ditandai sesuai dokumen asli (martinus=super_admin, henry+johanes=executive, adin=accounting), akun lain TIDAK berubah/ter-lock. Tabel baru `user_pending_changes` (queue approval create/edit akun, required_approver director-vs-accounting tergantung target user_type). Menu baru `user_approval` (dept SYSTEM), akses cuma di-grant ke role_id 1/27/22 (Admin/Direktur/Accounting Officer) — beda dari pola "grant semua role" krn approver-nya memang spesifik. Lihat [[user_type_approval_feature]]. |
| 36 | `migration_po_letterhead_pdf.sql` | 5 Sep | Cetak PO — Letterhead resmi | Kolom baru `suppliers.contact_person` (nullable) — field "ATTN TO" di cetakan PO letterhead baru (`pdf_po_letterhead.php`), tidak ada sebelumnya sama sekali di master supplier. Tidak ada tabel/menu baru — permission PO existing (`purchase_order*`) tetap dipakai apa adanya. Lihat [[po_letterhead_pdf]]. |
| 37 | `migration_sidebar_order.sql` | 6 Sep | Developer Mode — Atur Urutan Sidebar | 2 tabel baru: `sidebar_order` (urutan 15 menu utama, di-seed langsung sesuai urutan kode saat ini) & `sidebar_item_order` (urutan submenu per dropdown, auto-discovery murni — TIDAK di-seed). Tidak ada menu/permission baru (reuse `developer_mode` existing). Heading "Accounting & Finance"/"Configuration" DIHAPUS dari `templates/sidebar.php` (bukan migration, tapi ikut ter-deploy bareng kode). Lihat memory `migration_deploy_runner` & catatan lengkap di file migration-nya sendiri. |
| 38 | `migration_pr_approval_logs.sql` | 6 Sep | Riwayat Approval + Back to Draft 2-arah untuk PR | Tabel baru `pr_approval_logs` (replikasi persis struktur `po_approval_logs`, action ENUM sudah termasuk `'Reply'` dari awal). PR sebelumnya SATU-SATUNYA modul ber-approval yang tidak punya tabel riwayat sama sekali — alasan Back to Draft cuma tersimpan di `pr_header.return_reason` (1 arah, ketimpa tiap return berikutnya). Tidak ada menu/permission baru. Kode aman jalan tanpa migration ini (model cek `table_exists()` dulu, fiturnya sekadar tidak aktif). Lihat [[erp_revision_cross_cutting]]. |
| 39 | `migration_sidebar_hidden.sql` | 7 Sep | Copot & pasang menu sidebar | Kolom baru `is_hidden` (TINYINT, default 0 = tampil) di `sidebar_order` & `sidebar_item_order`. Melengkapi fitur Atur Urutan Sidebar: menu bisa disembunyikan/ditampilkan lagi lewat Developer Mode. **Murni tampilan — URL tetap bisa diakses & izin Role Access tidak berubah.** Kode aman jalan tanpa migrasi ini (dicek `db_has_column()`, fitur sekadar tidak aktif). |
| 40 | `migration_inventory_replacement.sql` | 9 Sep | Item pengganti di Master General Item | Kolom baru `replaced_by_id` (INT NULL) + `replaced_at` (DATETIME NULL) + index di `inventories`. Dipakai saat item dinonaktifkan karena diganti item berkode baru: penggantinya dipilih di modal Edit, lalu muncul sebagai tooltip di kolom Status. Tidak ada FOREIGN KEY (master di-soft delete, validasi di controller). Kode aman jalan tanpa migrasi ini (dicek `db_has_column()`, fitur sekadar tidak muncul). |
| 41 | `migration_po_price_4dec.sql` | 10 Sep | Harga satuan PO 4 desimal | `po_details.base_price` & `local_price` dari DECIMAL(15,2) jadi **DECIMAL(19,4)**. `total_local` & `total` SENGAJA tetap 2 desimal (user: "untuk total nya 2 digit belakang koma saja"). Dipakai untuk harga satuan pecahan (mis. per gram/meter) yang selama ini dibulatkan ke 2 desimal saat disimpan. Lebar 19 dipilih supaya digit di DEPAN koma tetap 15 — kalau langsung (15,4), plafon harga turun dari ~9,9 triliun ke ~99 miliar. Nilai lama otomatis jadi x.xx00, tidak ada yang berubah/terpotong. Idempoten (aman diulang). |
| 42 | `migration_pdf_layout.sql` | 10 Sep | Atur tata letak PDF (Developer Mode) | Tabel baru `pdf_layout_overrides` (template_key + setting_key unik). Menyimpan setelan ukuran font, padding tabel, tinggi kotak ttd, margin & baris pengisi untuk template PDF PRF & ERF, diatur dari `/developer_mode/pdf_layout`. Kode AMAN jalan tanpa migrasi ini (`pdf_layout_ready()` cek `table_exists()`; kalau tabel belum ada semua nilai balik ke bawaan dan PDF tampil persis seperti sebelumnya). |
| 43 | `migration_department_codes_v2.sql` | 15 Sep | Kode Departemen baru (sesuai Common Code production) | Pastikan 10 kode ada: ACC, ADM, GWH, LOG, MGM, PIE, PRO, PUR, PWH, QAD (deskripsi klien tidak ditimpa). Konversi nilai lama → kode baru di users.departemen, roles.department(_code), header IRF/pengeluaran/PR/PO/PRF/ERF/MRF, doc_documents, machines.pic_departemen, asset: FIN ACT/FAT→ACC, HRGA/MTC→ADM, EXIM→LOG, MANAGEMENT/FACTORY/OFFICE/DIR→MGM, I E→PIE, PROD/Produksi→PRO, PCH→PUR, Q A→QAD. Baris kode lama dihapus dari master. **Wajib sebelum migrasi Role Level (#44–#46)** supaya backfill departemennya memakai kode baru. |
| 44 | `migration_role_position.sql` | 15 Sep | Role Level: Departemen × Posisi, 1 user banyak role | Master posisi baru di `common_code` group `POSITION_LEVEL` (Director, Assistant of Director, HOD, Officer, Supervisor, Admin/Clerk) + departemen baru `GWH` General Warehouse & `LOG` Logistic. Kolom baru `roles.department_code` & `roles.position_code` (nullable; departemen role lama diisi otomatis bila cocok dgn master Department, posisi TIDAK ditebak). Tabel baru `user_roles` (user↔role many-to-many) di-backfill dari `users.role_id`, jadi hak akses semua user sama persis sebelum & sesudah migrasi. `users.role_id` tetap sbg role utama. Kode aman di DB yg belum dimigrasi (`db_table_exists`). Acuan klien: `assets/NSI - ERP ROLE LEVEL.pdf`. |
| 45 | `migration_role_submit_permission.sql` | 15 Sep | Role Level tahap 2: izin Submit terpisah | Kolom baru `role_permissions.can_submit` (kolom "Submit" di Kelola Hak Akses). Diisi dari izin yang SELAMA INI dipakai tombol Submit per modul (IRF/ERF/MRF/PO 4 tipe/Return to Supplier = Edit, PRF = Confirm, IPO = Add) jadi siapa yang bisa submit tidak berubah setelah migrasi. Sekalian `can_confirm` di 3 menu Penerimaan General (SGR) diisi dari `can_add` karena approve SGR kini memakai Confirm (dulu Add). Backfill hanya di run pertama. Wajib bareng deploy kode yang sama (check_access 'submit' kembali ke can_confirm kalau kolom belum ada). |
| 46 | `migration_role_dept_scope.sql` | 15 Sep | Role Level tahap 3: Own Dept vs All Dept | Kolom baru `role_permissions.all_dept_actions` (aksi yang berlaku semua departemen; `*`, daftar kolom, atau kosong = departemen role). Semua baris lama diisi `legacy` = aksi tetap berlaku semua departemen & daftar dokumen tidak melebar, jadi perilaku user lama tidak berubah sampai role diatur ulang. Kolom baru `prf_header.department_code` & `erf_header.department_code` (dulu kode departemen dimasukkan ke `department_id` INT → semua jadi 0) di-backfill dari departemen pemohon. `consumable_request_header.department` yang berisi nama ("HRGA") diseragamkan jadi kode ("ADM"). Wajib bareng kode `dept_scope_helper`. |
| 47 | `migration_irf_value_approval.sql` | 15 Sep | IRF: nilai dari PO terakhir & approval 1/3 tahap | Kolom baru `consumable_request_details.unit_price` + `price_po_detail_id` (harga satuan dari PO Confirmed terakhir, NULL = belum pernah di-PO) dan `consumable_request_header.total_value`, `approval_route` (1_stage/3_stage), `approval_route_reason`. Aturan: ada barang belum pernah di-PO atau total ≥ Rp 1.000.000 → 3 tahap; selain itu 1 tahap (HOD langsung final). Dihitung & dikunci saat submit. IRF lama (route NULL) tetap 3 tahap. |
| 48 | `migration_sidebar_item_parent.sql` | 16 Sep | Sidebar: pindah menu antar segmen | Tabel baru `sidebar_item_parent` (dropdown_key, item_key, parent_key). Menyimpan "menu ini sebenarnya milik segmen mana", dipakai Developer Mode > Atur Urutan Sidebar untuk memindahkan mis. "Daftar PRF" dari segmen LAPORAN: ke KEUANGAN. `parent_key` kosong = keluar dari segmen mana pun. Tanpa tabel ini fitur pindah segmen tidak muncul dan sidebar tampil seperti aslinya. |
| 49 | `migration_sidebar_lang_key.sql` | 16 Sep | Sidebar: simpan kunci teks tiap menu | Kolom baru `sidebar_item_order.lang_key` + backfill kunci segmen dari `item_key`. Kunci menu biasa diisi otomatis saat sidebar dirender (atribut `data-lk` di sidebar.php). Dipakai tombol pensil "ganti tulisan menu" di halaman Atur Urutan Sidebar. |
| 50 | `migration_inventory_traceability_menu.sql` | 16 Sep | Menu Penelusuran Barang General | Mendaftarkan menu `inventory_traceability` (modul baru: telusuri 1 barang general lintas dokumen IRF → PR → PO → Penerimaan → Pengeluaran → Kartu Stok). TIDAK ada tabel/kolom baru — semua data dibaca dari dokumen yang sudah ada. Role yang sudah punya View di Material Traceability otomatis dapat View di menu ini; sisanya diatur manual di Role Access. |
| 51 | `migration_role_lock.sql` | 17 Sep | Kunci Role Access (satu kunci untuk semua role) | Tabel baru `role_access_lock` (1 baris, id=1: `is_locked`, `locked_by`, `locked_at`). Awalnya TIDAK terkunci — tidak ada perubahan perilaku sampai Admin menekan tombol kunci. |
| 52 | `migration_pr_detail_mr_links.sql` | 21 Sep | Tautan baris PR ke baris MRF | Tabel baru `pr_detail_mr_links` (pr_detail_id + mr_detail_id + qty), padanan `pr_detail_cr_links` utk jalur material. Dipakai supaya qty PR dari MRF dikurangi stok & PR aktif (anti PR ganda) dan baris outstanding bisa menampilkan nomor PR-nya. Data lama tidak ikut tertaut — PR material yang dibuat sebelum migrasi ini tidak terhitung sebagai "sudah di PR". |
| 53 | `migration_scrap_source_mr_link.sql` | 21 Sep | Tautan asal scrap ke baris MRF (**migrasi lama yang terlewat**) | Kolom baru `production_scrap_sources.mr_detail_id` + indeks. File-nya sudah lama ada di repo tapi TIDAK pernah terdaftar di sini maupun di deploy_migrations.sh, jadi belum pernah jalan di production — akibatnya modal "Detail Asal Scrap" di Proses Produksi selalu HTTP 500. **Prioritas: jalankan secepatnya.** |
| 54 | `migration_fix_stock_ledger_balance.sql` | 21 Sep | Perbaiki saldo berjalan stock_ledger | Menghitung ulang kolom `balance` kumulatif per (item, tipe, kategori gudang) urut id. Entri Stok Awal lama menimpa saldo (balance = qty) sehingga penerimaan sebelumnya hilang — contoh PROD0004: terima 144 lalu Stok Awal 29 bikin saldo 29, setelah keluar 29 jadi 0 & barang tak muncul di Stock Transfer. qty_in/qty_out TIDAK diubah. Idempotent. |
| 55 | `migration_fix_pr_cr_links.sql` | 17 Sep | Perbaiki jejak IRF -> PR yang putus | Menyambung ulang `pr_detail_cr_links` yang menunjuk baris IRF sudah terhapus (akibat edit IRF versi lama yang menghapus & menyisipkan ulang seluruh baris item), membuat tautan untuk baris PR dari outstanding IRF yang belum punya, dan membuang tautan yang baris PR-nya sudah tidak ada. Hanya diperbaiki bila kandidatnya tepat satu. Gejala yang diperbaiki: IRF Tracking menulis "Belum ada PR" padahal PO sudah jalan. **Baris ini sempat hilang saat tabel dinomori ulang (80395cd) padahal migrasinya tetap terdaftar di deploy_migrations.sh.** |
| 56 | `migration_admin_full_access.sql` | 22 Sep | Role Admin: buka semua akses | Melengkapi baris `role_permissions` milik role 1 yang belum ada (ketemu 4 menu tanpa baris sama sekali: `purchase_request_asset`, `purchase_request_approval_asset`, `document_sharing`, `user_approval`), lalu menyalakan SELURUH kolom izin untuk role 1. Juga membetulkan `developer_mode` yang cuma punya View padahal kodenya memeriksa Edit. Idempotent — aman diulang, dan tidak menyentuh role lain. |
| 57 | `migration_material_ng_detail.sql` | 30 Sep | Rincian NG material: 4 kategori + kode NG | Kolom baru `ng_human`, `ng_material`, `ng_mechanic`, `ng_others` (DECIMAL) + `ng_code` di DUA tabel: `production_scrap_sources` (material jadi scrap saat proses produksi) dan `issuance_qc_results` (material reject saat QC penerimaan). Master 43 kode NG masuk `common_code` group `NG_CODE` (bisa ditambah lewat menu Common Code). Mendaftarkan menu `material_ng_report` (QUALITY) + memberi View ke role yang sudah punya View di QA Report. Angkanya murni catatan — tidak menyentuh stok, ledger, maupun jurnal. Acuan: `assets/DAILY PRODUCTION REPORT (PQC & MATERIAL) (1).xlsx`. Idempotent. |
| 58 | `migration_fg_delivery_weight.sql` | 1 Okt | Delivery Note: berat bersih & kotor | Kolom baru `fg_delivery_details.nw` & `.gw` (DECIMAL(15,4), NULL = belum ditimbang). Diisi per baris barang saat membuat/mengubah pengiriman barang jadi, dicetak di kolom NW/GW Delivery Note. Ditaruh di baris pengiriman (bukan master barang jadi) karena yang dicatat surat jalan adalah berat kiriman itu — GW sudah termasuk kemasan. Sengaja tanpa default 0 supaya "belum ditimbang" bisa dibedakan dari "beratnya nol". Idempotent. |
| 59 | `migration_supplier_account_name.sql` | 2 Okt | Master Supplier: Nama Akun (pemilik rekening) | Kolom baru `suppliers.bank_account_name` (diisi di Master Supplier, di samping Nama Bank & No. Rekening) + `prf_header.bank_account_name` sebagai SALINAN saat PRF dibuat -- sejalan dgn bank_name & bank_account yang memang sudah disalin, supaya dokumen yang sudah terbit tidak ikut berubah kalau master suppliernya diedit. Cetak PRF dulu menebak pemilik rekening dari NAMA VENDOR; sering keliru karena rekening bisa atas nama pemilik usaha, bukan nama badan usahanya. Kosong = cetakan jatuh kembali ke nama vendor seperti semula, jadi PRF lama tetap wajar. ERF sengaja tidak ikut (reimburse ke karyawan, pemilik rekening memang karyawan itu). Idempotent. |
| 60 | `migration_po_ppn_inclusive.sql` | 2 Okt | PO: PPN include/exclude & persen bebas | Kolom baru `po_header.ppn_inclusive` (0 = exclude: PPN dihitung dari subtotal lalu ditambahkan; 1 = include: PPN diambil dari total karena harga sudah mengandungnya). Default 0 supaya SELURUH PO lama dihitung persis seperti sebelumnya. Sekalian PO lama yang `has_ppn=1` tapi `ppn_rate` masih 0 diisi 11, supaya tidak jadi "ada PPN tapi 0%" setelah persentasenya benar-benar dipakai -- sebelumnya nilai kolom itu diabaikan dan beberapa tempat mematok 11% (0.11/1.11). Idempotent. |
| 61 | `migration_inventory_usage_category.sql` | 6 Okt | Master Barang: Jenis Pemakaian (General/Produksi) | Kolom baru `inventories.usage_category` ('General' | 'Produksi'), BEDA dengan `kategori` yang sudah ada (itu sub-kategori kode barang: OFC, MTC, PROD, ...). Dipakai menyaring pencarian item di IRF: kategori kebutuhan General cuma memunculkan barang General, Produksi cuma memunculkan barang Produksi. Sebelum ini kategori IRF hanya menentukan EMBER STOCK mana yang dibaca (stock_ledger.category), bukan barang mana yang boleh diminta. Backfill dari pemakaian NYATA di IRF (barang lebih sering diminta lewat IRF Produksi daripada General -> Produksi; seri & belum pernah diminta -> General): 14 dari 333 barang jadi Produksi di DB lokal. Backfill SENGAJA cuma jalan saat kolomnya baru dibuat, jadi mengulang migrasi tidak menimpa betulan manual user. Idempotent. |
| 62 | `migration_site_settings.sql` | 8 Okt | Developer Mode: identitas perusahaan bisa diubah dari UI | Tabel baru `site_settings` (key/value), pola sama dengan `site_assets` & `lang_overrides`: hanya menyimpan YANG DIUBAH user, nilai bawaan tetap hidup di kode (`company_info()` di site_asset_helper.php). Tidak ada baris seed, jadi DB yang belum dimigrasi tetap menampilkan nama & alamat yang benar. Dipakai nama/alamat/telepon perusahaan yang sebelumnya ditulis langsung di 27 berkas (kop PDF, export Excel, login, footer). Slot gambar baru (logo login, logo kop PDF, favicon) TIDAK butuh migrasi -- tabel site_assets sudah ada & key-nya didaftarkan di kode. Idempotent. |
| 63 | `migration_rebrand_acropoly.sql` | 5 Nov | Rebrand NSI -> PT Acropoly International | Bersihkan sisa NSI di DB: hapus override `lang_overrides` yang masih berisi teks NSI (contoh nomor PO `PO/NSI/...`, copyright login, `po_letterhead_company_name` yang sudah tak dipakai) & ganti nama di override lain; hapus baris `site_settings` yang masih bernilai NSI supaya kembali ke bawaan Acropoly di `company_info()`. Tidak menyentuh data transaksi maupun `site_assets` (logo unggahan -- cek manual di Developer Mode). Idempotent. |
| 64 | `migration_rebrand_istilah_acropoly.sql` | 5 Nov | Istilah NSI -> Acropoly | Menyamakan data DB dgn label baru: `menus.menu_name` (30 menu, ikut label sidebar Indonesia) & `lang_overrides` (custom_value + default_value). Istilah: IRF->Permintaan Barang, PMR/MRF->Material Request, IPO->Sales Order, PMPO->PO Material, GMPO->PO General, PRF->Payment Request, ERF->Reimburse Request. Tiap UPDATE mencocokkan persis nilai lama (baris yang sudah beda tidak tersentuh). `menu_key` & kode internal tidak diubah. Idempotent. |
| 65 | `migration_sidebar_show_pr.sql` | 5 Nov | Munculkan menu PR di sidebar | `sidebar_item_order.is_hidden = 0` utk Persetujuan PR General (dulu "Persetujuan IPR") & Daftar PR (Semua Tipe). item_key = md5(path link), sama di semua server. Idempotent. |
| 66 | `migration_remove_user_approval_executive.sql` | 5 Nov | Hapus approval user baru & tipe Executive | User pending -> approved; antrian user_pending_changes yang pending ditutup; user Executive -> user lalu `executive` dibuang dari ENUM users.user_type; menu `user_approval` dihapus (role_permissions ikut, FK CASCADE). Tabel user_pending_changes tetap (riwayat). Idempotent. |
| 67 | `migration_so_single_approval.sql` | 6 Nov | Sales Order approval 1 tahap | Tambah nilai `Submitted` ke ENUM sales_contracts.status; SO yang masih Pending Endorsement/Endorsed dipindah ke Submitted (antrian approval 1 tahap). Idempotent. |
| 68 | `migration_prf_hutang_jurnal_otomatis.sql` | 6 Nov | Payment Request -> Hutang -> Jurnal otomatis | Payment Request yang menarik PO / AP Invoice (`source_type='HUTANG'`): saat di-approve otomatis membuat AP Invoice + jurnal hutang, saat dibayar membuat AP Payment + jurnal pembayaran (PPh 23 & biaya admin bank opsional). Tabel pemetaan akun debit `item_account_map` (per barang -> kategori -> jenis barang; belum dipetakan = approval ditolak), kolom penghubung PRF di AP, setting akun baru. Payment Request manual tetap dijurnal lewat Jurnal Umum. Idempotent. |
| 69 | `migration_coa_acropoly.sql` | 7 Nov | Chart of Accounts Acropoly | 114 akun detail dari `COA_2026-11-07_22-41-18.xlsx` + 30 akun header (nama header usulan, boleh diganti di menu COA); kode "1,101,001.00" disimpan "1.101.001". Upsert per `account_code`. Kelas 7 sengaja tanpa header "7" (7.300 Biaya, 7.400 Pendapatan). 1.105.050 & 2.102.01 dibuat nonaktif. Idempotent. |
| 70 | `migration_setting_akun_acropoly.sql` | 7 Nov | Setting Akun diisi COA Acropoly | Prasyarat baris 69. Hanya mengisi setting yang masih kosong (pilihan Accounting tidak tertimpa); akun dicari lewat kode. `akun_wht42` & `akun_pendapatan_foc` dibiarkan kosong (belum ada akun cocok); laba & rugi kurs sama-sama ke 8.100.001. Idempotent. |
| 71 | `migration_tahap_produksi_acropoly.sql` | 8 Nov | Tahap produksi Acropoly + waktu per tahap | Tahap jadi Cutting -> Sewing -> Assembling -> Finishing: data tahap lama `grinding` dipindah ke `cutting`. Tabel baru `production_stage_times` (Mulai/Selesai per tahap per dokumen produksi); tanggal stok WIP mengikuti waktu Mulai tahap. Override teks Grinding yang kosong dibersihkan. Idempotent. |
| 72 | `migration_sidebar_mr_list.sql` | 8 Nov | Munculkan "Daftar Material Request" di sidebar | Menu Produksi > Daftar MR sempat disembunyikan di era NSI (`sidebar_item_order.is_hidden=1`); ditampilkan lagi. Idempotent. |
| 73 | `migration_sidebar_accounting_split.sql` | 9 Nov | Sidebar: Finance & Accounting dipecah jadi 6 menu | Pengajuan Dana, Kas Bank & Jurnal (key lama `accounting`), Hutang & Piutang, Laporan Keuangan, Stock Opname, Pengaturan Akuntansi. Menata urutan di posisi menu lama & membawa status "dicopot" dari menu lama (sekali saja; upsert). Tanpa migrasi ini sidebar tetap jalan, hanya urutannya belum rapi. Idempotent. |

> **Catatan penomoran.** Nomor pada tabel ini adalah urutan DOKUMENTASI, dan sejak
> penomoran ulang di `80395cd` sudah TIDAK lagi sama dengan urutan eksekusi di
> `db/deploy_migrations.sh`. Yang menentukan urutan jalan adalah array `MIGRATIONS`
> di skrip itu; tabel ini untuk penjelasan isinya.


---

## Langkah manual non-SQL yang WAJIB setelah migration di atas

- **Setelah baris 13** (`migration_consumable_out_category_menu.sql`): masuk
  Role Access, persempit akses "Pengeluaran Barang - General/Produksi" per
  role (staff GA vs staff gudang produksi). Detail:
  [`IRF_CATEGORY_SEPARATION_DEPLOYMENT.md`](./IRF_CATEGORY_SEPARATION_DEPLOYMENT.md) bagian 2.
- **Setelah baris 16** (`migration_irf_outstanding_status_category_menu.sql`):
  ulangi langkah Role Access yang sama utk 4 menu baru (`consumable_outstanding_general/produksi`,
  `irf_status_general/produksi`). Detail: doc yang sama bagian 8a.
- **Setelah baris 14** (`migration_stock_category_split.sql`): cek apakah ada
  barang yang FISIKNYA sudah di gudang Produksi tapi ke-backfill sebagai
  General — kalau ada, koreksi pakai modul Stock Transfer (baris 15).
- **Setelah baris 21** (`migration_sgr_category_split.sql`): masuk Role
  Access, persempit akses "Penerimaan Barang - General/Produksi" per role
  (pola sama baris 13). Semua SGR lama otomatis ke-backfill kategori
  'Lainnya' — tetap muncul di menu blanket lama, tidak perlu tindakan.
- Baris lain (1-12, 17-20) murni tambah kolom/tabel/menu baru — tidak ada
  langkah manual wajib, tapi tetap disarankan smoke-test fitur terkait
  setelah deploy (lihat checklist di `IRF_CATEGORY_SEPARATION_DEPLOYMENT.md`
  bagian 4 & 8c utk baris 11-16; fitur lain belum py checklist tertulis,
  cukup coba pakai fiturnya langsung).

## Rollback

Semua kolom yang ditambahkan nullable atau punya default — **tidak perlu
rollback DB** kalau mau nonaktifkan sementara, cukup revert kode ke commit
sebelumnya (logic ada di kode, kolom baru yang menganggur di DB tidak
mengganggu kode lama). Rollback SQL manual (drop kolom/tabel) cuma perlu
kalau benar-benar mau bersihkan skema — lihat masing-masing file migration
atau `IRF_CATEGORY_SEPARATION_DEPLOYMENT.md` bagian 5 untuk contoh.

## Setelah migration ini semua kelar dijalankan di production

Commit file-file ini ke git supaya `git status` bersih dan dokumen ini akurat
utk generasi berikutnya:

```bash
git add db/*.sql docs/PENDING_MIGRATIONS_MASTER.md
git commit -m "chore: track pending migrations (po/pr jasa, asset, irf category split, item code v2, item release attachments)"
```
