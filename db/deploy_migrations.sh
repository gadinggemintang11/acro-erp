#!/usr/bin/env bash
#
# db/deploy_migrations.sh — jalankan SEMUA migration SQL yang masih pending
# (lihat docs/PENDING_MIGRATIONS_MASTER.md) ke database, BERURUTAN, dalam
# SATU perintah. Dibuat 2026-09-06 supaya deploy tidak perlu lagi copy-paste
# satu-satu daftar mysql di dokumen tsb secara manual.
#
# SUMBER KEBENARAN URUTAN: array MIGRATIONS di bawah ini HARUS selalu sama
# persis dgn tabel "Daftar lengkap" di docs/PENDING_MIGRATIONS_MASTER.md.
# Kalau bikin migration SQL baru: tambahkan barisnya DI SINI (di akhir
# array, sesuai urutan dibuat) SEKALIGUS di baris baru tabel dokumen itu --
# supaya keduanya tidak drift.
#
# CARA PAKAI:
#   cd nsi-erp
#   DB_NAME=nsi-db DB_USER=root DB_PASSWORD=xxx ./db/deploy_migrations.sh
#
#   Kalau DB_PASSWORD tidak di-set sama sekali, mysql akan minta password
#   interaktif (lebih aman -- tidak ketinggalan di shell history / process
#   list). Kalau root LOKAL memang tanpa password (mis. XAMPP default),
#   set eksplisit DB_PASSWORD="" (bukan tidak di-set) supaya tidak ditanya.
#
#   Opsi:
#     --dry-run       cuma tampilkan urutan yg akan dijalankan, TIDAK eksekusi apa pun
#     --skip-backup   lewati mysqldump backup otomatis (TIDAK disarankan di production)
#
# SEMUA migration SQL yang terdaftar SUDAH ditulis idempoten (aman
# dijalankan ulang) -- kalau skrip ini berhenti di tengah krn 1 file error,
# perbaiki masalahnya lalu jalankan ULANG SELURUH skrip ini dari awal, TIDAK
# perlu comment-out file yang sudah sukses.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/deploy_migrations_$(date +%Y%m%d_%H%M%S).log"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-nsi-db}"
SKIP_BACKUP="${SKIP_BACKUP:-0}"
DRY_RUN=0

for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=1 ;;
    --skip-backup) SKIP_BACKUP=1 ;;
    -h|--help)
      echo "Usage: DB_NAME=nsi-db DB_USER=root DB_PASSWORD=xxx $0 [--dry-run] [--skip-backup]"
      exit 0
      ;;
    *)
      echo "Argumen tidak dikenal: $arg (pakai --help utk bantuan)" >&2
      exit 1
      ;;
  esac
done

# Urutan = urutan kronologis dibuat = urutan aman secara dependency (tiap
# migration dibangun di atas skema yang berlaku saat itu). SAMA PERSIS dgn
# blok bash & tabel "Daftar lengkap" di docs/PENDING_MIGRATIONS_MASTER.md.
MIGRATIONS=(
  migration_po_jasa.sql
  migration_pr_jasa.sql
  migration_supplier_kategori_jasa.sql
  migration_prf_journal_link.sql
  migration_prf_multi_po_pull.sql
  migration_irf_status_menu.sql
  migration_asset.sql
  migration_asset_fase2_pr.sql
  migration_asset_fase3_po.sql
  migration_irf_machine.sql
  migration_irf_category.sql
  migration_irf_category_chain.sql
  migration_consumable_out_category_menu.sql
  migration_stock_category_split.sql
  migration_stock_transfer.sql
  migration_irf_outstanding_status_category_menu.sql
  migration_item_code_v2.sql
  migration_item_code_v2_commoncode.sql
  migration_item_release_attachments.sql
  migration_irf_receipt_attachments.sql
  migration_sgr_category_split.sql
  migration_po_approval_logs.sql
  migration_back_to_draft_reply.sql
  migration_irf_jasa_category.sql
  migration_pr_detail_cr_links.sql
  migration_pr_po_details_jasa_category.sql
  migration_pr_po_monitoring.sql
  migration_notifications.sql
  migration_po_back_to_draft_reply.sql
  migration_lang_overrides.sql
  migration_site_assets.sql
  migration_ui_columns.sql
  migration_prf_unit.sql
  migration_document_sharing.sql
  migration_user_type_approval.sql
  migration_po_letterhead_pdf.sql
  migration_sidebar_order.sql
  migration_pr_approval_logs.sql
  migration_sidebar_hidden.sql
  migration_inventory_replacement.sql
  migration_po_price_4dec.sql
  migration_pdf_layout.sql
  migration_irf_value_approval.sql
  migration_sidebar_item_parent.sql
  migration_sidebar_lang_key.sql
  migration_inventory_traceability_menu.sql
  migration_fix_pr_cr_links.sql
  migration_pr_detail_mr_links.sql
  migration_scrap_source_mr_link.sql
  migration_fix_stock_ledger_balance.sql
  migration_department_codes_v2.sql
  migration_role_position.sql
  migration_role_dept_scope.sql
  migration_role_lock.sql
  migration_admin_full_access.sql
  migration_material_ng_detail.sql
  migration_fg_delivery_weight.sql
  migration_supplier_account_name.sql
  migration_po_ppn_inclusive.sql
  migration_inventory_usage_category.sql
  migration_site_settings.sql
  migration_rebrand_acropoly.sql
  migration_rebrand_istilah_acropoly.sql
  migration_sidebar_show_pr.sql
  migration_remove_user_approval_executive.sql
  migration_so_single_approval.sql
  migration_prf_hutang_jurnal_otomatis.sql
  migration_coa_acropoly.sql
  migration_setting_akun_acropoly.sql
  migration_tahap_produksi_acropoly.sql
  migration_sidebar_mr_list.sql
  migration_sidebar_accounting_split.sql
)

log() { echo "$@" | tee -a "$LOG_FILE"; }

log "==================================================================="
log " nsi-erp — Migration Runner"
log " Target DB : $DB_NAME @ $DB_HOST:$DB_PORT (user: $DB_USER)"
log " Jumlah file: ${#MIGRATIONS[@]}"
if [ "$DRY_RUN" = "1" ]; then
  log " Mode      : DRY RUN (tidak eksekusi apa pun)"
else
  log " Mode      : EKSEKUSI SUNGGUHAN"
fi
log "==================================================================="

# Fail fast kalau ada nama file yang typo/hilang -- jangan sampai baru
# ketahuan di tengah jalan setelah beberapa migration lain sudah jalan.
missing=0
for f in "${MIGRATIONS[@]}"; do
  if [ ! -f "$SCRIPT_DIR/$f" ]; then
    log "!! File tidak ditemukan: db/$f"
    missing=1
  fi
done
if [ "$missing" = "1" ]; then
  log "Berhenti -- ada file migration yang hilang di folder db/. Cek nama filenya."
  exit 1
fi

if [ "$DRY_RUN" = "1" ]; then
  log ""
  log "Urutan yang AKAN dijalankan (dry-run, belum ada yang dieksekusi):"
  i=1
  for f in "${MIGRATIONS[@]}"; do
    log "  $i. db/$f"
    i=$((i+1))
  done
  exit 0
fi

read -r -p "Lanjut jalankan ${#MIGRATIONS[@]} migration ke database '$DB_NAME'? Ketik 'yes' untuk lanjut: " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
  log "Dibatalkan oleh user."
  exit 1
fi

MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
if [ -n "${DB_PASSWORD:-}" ]; then
  # DB_PASSWORD di-set & tidak kosong -> pakai langsung.
  MYSQL_ARGS+=(-p"$DB_PASSWORD")
elif [ "${DB_PASSWORD+set}" = "set" ]; then
  # DB_PASSWORD di-set TAPI sengaja kosong (mis. DB_PASSWORD="" utk root lokal
  # XAMPP tanpa password) -> jangan pakai -p sama sekali, kalau tidak mysql
  # akan minta password interaktif padahal user sudah bilang tidak ada.
  :
else
  # DB_PASSWORD sama sekali tidak di-set -> minta interaktif (default paling
  # aman utk production, password tidak ketinggalan di shell history).
  MYSQL_ARGS+=(-p)
fi

if [ "$SKIP_BACKUP" != "1" ]; then
  BACKUP_FILE="$SCRIPT_DIR/backup_before_migrate_$(date +%Y%m%d_%H%M%S).sql"
  log "Backup database ke $BACKUP_FILE ..."
  mysqldump "${MYSQL_ARGS[@]}" "$DB_NAME" > "$BACKUP_FILE"
  log "Backup selesai ($(du -h "$BACKUP_FILE" | cut -f1))."
else
  log "SKIP_BACKUP=1 -- backup dilewati (TIDAK disarankan untuk production)."
fi

i=1
for f in "${MIGRATIONS[@]}"; do
  log ""
  log "[$i/${#MIGRATIONS[@]}] Menjalankan db/$f ..."
  if mysql "${MYSQL_ARGS[@]}" "$DB_NAME" < "$SCRIPT_DIR/$f" 2>>"$LOG_FILE"; then
    log "    OK"
  else
    log ""
    log "!! GAGAL di file: db/$f -- lihat detail error di $LOG_FILE"
    log "!! Semua file SEBELUM ini sudah berhasil jalan. Setelah masalah"
    log "!! diperbaiki, aman jalankan ULANG SELURUH skrip ini dari awal"
    log "!! (semua migration ditulis idempoten, tidak akan duplikat)."
    exit 1
  fi
  i=$((i+1))
done

log ""
log "==================================================================="
log " SEMUA ${#MIGRATIONS[@]} migration selesai dijalankan tanpa error."
log "==================================================================="
log ""
log "LANGKAH SELANJUTNYA (manual, WAJIB dicek):"
log "  1. Buka menu Role Access -- cek & sesuaikan permission menu-menu"
log "     baru (lihat bagian 'Langkah manual non-SQL' di"
log "     docs/PENDING_MIGRATIONS_MASTER.md)."
log "  2. Smoke-test fitur-fitur terkait."
log "  3. Commit file migration ke git supaya tidak lagi 'pending':"
log "       git add db/*.sql docs/PENDING_MIGRATIONS_MASTER.md"
log "       git commit -m 'chore: deploy pending migrations to production'"
