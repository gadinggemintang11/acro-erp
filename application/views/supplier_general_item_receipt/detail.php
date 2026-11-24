<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Receiving Item Form (SGR) -- detail. Redesign 2026-11-09, gaya .pp2.
 * Logika modal "Pilih Item PO" & "Edit Item" (JS di bawah) tidak berubah;
 * hanya tampilan. Izin per kategori dari controller ($can_edit/$can_approve).
 */
$this->load->view('production_process/_pp_style');

$is_draft = ($header->status == 'Draft');
// Controller mengirim izin per kategori (_can_sgr). Dulu view hanya cek menu
// blanket, jadi user yang cuma punya izin SGR General/Produksi tidak melihat
// tombol apa pun padahal controller mengizinkan.
$can_edit = $can_edit ?? check_access('supplier_general_item_receipt', 'edit');
// Approve/unapprove pakai izin Confirm, sama dengan controller.
$can_approve = $can_approve ?? check_access('supplier_general_item_receipt', 'confirm');
$grand_total = 0; $total_qty = 0;
foreach ($details as $d) { $grand_total += (float) ($d->total_value ?? 0); $total_qty += (float) $d->receive_qty; }
$cat      = $header->category ?: 'Lainnya';
$cat_tone = ['General' => 'tone-blue', 'Produksi' => 'tone-slate', 'Lainnya' => 'tone-amber'][$cat] ?? 'tone-slate';
$back     = $cat === 'General' ? 'supplier_general_item_receipt/index/general' : ($cat === 'Produksi' ? 'supplier_general_item_receipt/index/produksi' : 'supplier_general_item_receipt');
$wh_label = ['machine' => lang_line('sgr2_wh_machine', 'Mesin'), 'inventory' => lang_line('sgr2_wh_inventory', 'Inventaris')];
$fmt_dt   = function ($v, $time = true) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($time ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$qty      = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$n_items  = count($details);
$pos      = []; foreach ($details as $d) { if (!empty($d->source_doc_number)) $pos[$d->source_doc_header_id] = $d->source_doc_number; }
$steps = [
    ['cls' => 'is-done', 'icon' => 'fa-check', 'label' => lang_line('pod2_step_created', 'Dibuat'), 'meta' => trim(($header->creator_name ?? '-') . ($fmt_dt($header->created_at ?? null) ? ' · ' . $fmt_dt($header->created_at) : ''))],
    ['cls' => $n_items ? 'is-done' : 'is-current', 'icon' => $n_items ? 'fa-check' : 'fa-file-import', 'label' => lang_line('sgr2_step_items', 'Item dari PO'),
     'meta' => $n_items ? sprintf(lang_line('sgr2_items_meta', '%d item · %d PO'), $n_items, count($pos)) : lang_line('sgr2_wait_items', 'Pilih item PO yang datang')],
    ['cls' => !$is_draft ? 'is-done' : ($n_items ? 'is-current' : ''), 'icon' => !$is_draft ? 'fa-check' : 'fa-warehouse', 'label' => lang_line('sgr2_step_approved', 'Disetujui · stok masuk'),
     'meta' => !$is_draft ? trim(($header->approver_name ?? '-') . ($fmt_dt($header->approved_at ?? null) ? ' · ' . $fmt_dt($header->approved_at) : '')) : ($n_items ? lang_line('pod2_wait_approval', 'Menunggu approval') : '—')],
];
$bc_text = trim((($header->bc_type_desc ?? '') ?: ($header->bc_type_code ?? '')) . (!empty($header->bc_number) ? ' · ' . $header->bc_number : ''));
?>
<style>
.sgrd .pod-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-bottom: 1.25rem; }
.sgrd .pod-step { display: flex; gap: .65rem; padding: .8rem .9rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); min-width: 0; }
.sgrd .pod-step-dot { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; font-size: .82rem; background: var(--pp-soft); color: var(--pp-muted); border: 1px solid var(--pp-line); }
.sgrd .pod-step.is-done .pod-step-dot { background: #10b981; color: #fff; border-color: transparent; }
.sgrd .pod-step.is-current { border-color: var(--brand-1); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12), var(--pp-shadow); }
.sgrd .pod-step.is-current .pod-step-dot { background: var(--brand-1); color: #fff; border-color: transparent; }
.sgrd .pod-step-name { font-weight: 700; font-size: .84rem; }
.sgrd .pod-step-meta { font-size: .72rem; color: var(--pp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 767.98px) { .sgrd .pod-steps { grid-template-columns: 1fr; } }
.sgrd .sgr-empty { text-align: center; padding: 2.2rem 1rem; }
.sgrd .sgr-empty i.big { font-size: 2rem; color: var(--pp-muted); opacity: .4; display: block; margin-bottom: .6rem; }
.sgrd .sgr-note { display: flex; gap: .7rem; align-items: flex-start; padding: .8rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .85rem; background: var(--pp-card); border: 1px solid var(--pp-line); }
.sgrd td .pp-chip { margin-top: .25rem; }
/* modal lama (Bootstrap) -> gaya pp-modal */
.pp-modal .table thead th { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; background: #f8fafc; }
.pp-modal .card { border-radius: 12px; border-color: #e2e8f0; }
.pp-modal .card-header { background: #f8fafc; }
</style>

<div class="container-fluid pp2 sgrd">
    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-dolly-flatbed"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker"><?= lang_line('sgr2_title', 'Receiving Item Form'); ?> · <?= htmlspecialchars($cat); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($header->sgr_number); ?> <span class="pp-status <?= $is_draft ? 'pp-status--draft' : 'pp-status--completed'; ?>"><?= $is_draft ? 'Draft' : lang_line('sgr2_approved', 'Disetujui'); ?></span></h1>
                    <div class="pp-hero-subline"><i class="far fa-calendar mr-1"></i><?= $fmt_dt($header->sgr_date, false); ?><span class="mx-1">·</span><i class="fas fa-store mr-1"></i><?= htmlspecialchars($header->nama_supplier ?: '-'); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if ($is_draft && $can_edit): ?>
                    <button type="button" class="btn pp-btn-light" data-toggle="modal" data-target="#poAutoLoadModalSgr"><i class="fas fa-file-import mr-1"></i><?= lang_line('sgr2_pick_po', 'Pilih Item PO'); ?></button>
                <?php endif; ?>
                <?php if ($is_draft && $can_approve && $n_items): ?>
                    <a href="<?= base_url('supplier_general_item_receipt/approve/' . $header->id); ?>" class="btn pp-btn-light btn-confirm"
                       data-message="<?= htmlspecialchars(sprintf(lang_line('sgr2_approve_q', 'Setujui %s?'), $header->sgr_number)); ?>"
                       data-text="<?= htmlspecialchars(lang_line('sgr2_approve_text', 'Barang langsung masuk stok gudang.')); ?>" data-icon="question" data-confirm-text="<?= htmlspecialchars(lang_line('poa_yes_approve', 'Ya, Setujui')); ?>"><i class="fas fa-check-double mr-1"></i><?= lang_line('sgr_btn_approve', 'Approve'); ?></a>
                <?php elseif (!$is_draft && $can_approve): ?>
                    <a href="<?= base_url('supplier_general_item_receipt/unapprove/' . $header->id); ?>" class="btn pp-btn-glass btn-confirm"
                       data-message="<?= htmlspecialchars(lang_line('sgr2_unapprove_q', 'Batalkan approval dokumen ini?')); ?>"
                       data-text="<?= htmlspecialchars(lang_line('sgr2_unapprove_text', 'Stok dari dokumen ini ditarik kembali dan dokumen kembali ke Draft. Ditolak kalau stoknya sudah terpakai.')); ?>" data-icon="warning" data-confirm-text="<?= htmlspecialchars(lang_line('sgr2_yes_unapprove', 'Ya, Batalkan')); ?>"><i class="fas fa-undo mr-1"></i><?= lang_line('sgr_btn_unapprove', 'Unapprove'); ?></a>
                <?php endif; ?>
                <?php if ($is_draft && $can_edit): ?>
                    <button type="button" class="btn pp-btn-glass" data-toggle="modal" data-target="#editSgrHeaderModal"><i class="fas fa-pen mr-1"></i><?= lang_line('sgr_btn_edit_header', 'Edit Header'); ?></button>
                <?php endif; ?>
                <a href="<?= base_url('supplier_general_item_receipt/export_pdf/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass no-loader"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <a href="<?= base_url($back); ?>" class="btn pp-btn-glass btn-load"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('pod2_back', 'Kembali'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('sgr_category', 'Kategori'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($cat); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('sgr_warehouse_type', 'Tipe Gudang'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($wh_label[$header->warehouse_type] ?? ucfirst($header->warehouse_type)); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('sgr2_dn', 'Surat jalan'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->delivery_note_number ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('sgr2_bc', 'Dokumen BC'); ?></div><div class="pp-meta-value" title="<?= htmlspecialchars($bc_text); ?>"><?= htmlspecialchars($bc_text ?: '-'); ?><?= !empty($header->bc_date) ? ' <small>' . date('d M Y', strtotime($header->bc_date)) . '</small>' : ''; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('sgr2_th_items', 'Item'); ?></div><div class="pp-meta-value"><?= $n_items; ?> <small><?= lang_line('prm2_lines', 'baris'); ?> · qty <?= $qty($total_qty); ?></small></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('sgr2_th_value', 'Nilai'); ?></div><div class="pp-meta-value"><?= number_format($grand_total, 2, '.', ','); ?></div></div>
        </div>
    </section>

    <?php foreach (['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <?php if (trim((string) $header->remarks) !== ''): ?>
        <div class="sgr-note"><i class="fas fa-sticky-note mt-1 text-muted"></i><div><b><?= lang_line('sgr_remarks', 'Keterangan'); ?></b><br><?= nl2br(htmlspecialchars($header->remarks)); ?></div></div>
    <?php endif; ?>

    <nav class="pod-steps" aria-label="<?= htmlspecialchars(lang_line('sgr2_flow', 'Alur penerimaan')); ?>">
        <?php foreach ($steps as $st): ?>
        <div class="pod-step <?= $st['cls']; ?>">
            <span class="pod-step-dot"><i class="fas <?= $st['icon']; ?>"></i></span>
            <span style="min-width:0"><span class="pod-step-name d-block"><?= htmlspecialchars($st['label']); ?></span><span class="pod-step-meta d-block" title="<?= htmlspecialchars($st['meta']); ?>"><?= htmlspecialchars($st['meta']); ?></span></span>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="pp-card">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-list-ul"></i></span><span><?= lang_line('sgr2_items', 'Item Diterima'); ?> (<?= $n_items; ?>)
                <span class="d-block pp-sub"><?= lang_line('sgr2_items_sub', 'Nilai satuan mengikuti harga di PO.'); ?></span></span></h2>
            <?php if ($is_draft && $can_edit && $n_items): ?>
                <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#poAutoLoadModalSgr"><i class="fas fa-plus mr-1"></i><?= lang_line('sgr2_add_items', 'Tambah dari PO'); ?></button>
            <?php endif; ?>
        </div>
        <?php if (!$n_items): ?>
            <div class="sgr-empty">
                <i class="fas fa-dolly-flatbed big"></i>
                <div class="font-weight-bold mb-1"><?= lang_line('sgr2_empty', 'Belum ada item.'); ?></div>
                <div class="pp-item-code mb-3"><?= lang_line('sgr2_empty_hint', 'Pilih PO lalu centang item yang benar-benar datang beserta qty-nya.'); ?></div>
                <?php if ($is_draft && $can_edit): ?><button type="button" class="btn btn-pp" data-toggle="modal" data-target="#poAutoLoadModalSgr"><i class="fas fa-file-import mr-1"></i><?= lang_line('sgr2_pick_po', 'Pilih Item PO'); ?></button><?php endif; ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th style="width:42px">#</th>
                    <th><?= lang_line('pod2_th_item', 'Item'); ?></th>
                    <th><?= lang_line('sgr2_th_type_cat', 'Tipe / Kategori'); ?></th>
                    <th class="num"><?= lang_line('po_qty', 'Qty'); ?></th>
                    <th class="num"><?= lang_line('sgr_th_unit_value', 'Nilai Satuan'); ?></th>
                    <th class="num"><?= lang_line('po_total', 'Total'); ?></th>
                    <?php if ($is_draft && $can_edit): ?><th class="text-right" style="width:90px"></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php $no = 1; foreach ($details as $d): ?>
                    <tr>
                        <td class="text-muted font-weight-bold" style="font-size:.76rem"><?= $no++; ?></td>
                        <td>
                            <div class="pp-item-name"><?= htmlspecialchars($d->item_name ?: '-'); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($d->item_code ?: '-'); ?></div>
                            <?php if (!empty($d->source_doc_number)): ?><span class="pp-chip"><i class="fas fa-file-invoice"></i><?= htmlspecialchars($d->source_doc_number); ?></span><?php endif; ?>
                            <?php if (!empty($d->bc_type_code) || !empty($d->bc_number)): ?><div class="pp-item-code mt-1">BC: <?= htmlspecialchars(trim(($d->bc_type_code ?? '') . ' ' . ($d->bc_number ?? ''))); ?><?= !empty($d->bc_date) ? ' · ' . date('d M Y', strtotime($d->bc_date)) : ''; ?></div><?php endif; ?>
                        </td>
                        <td><span class="pp-tag tone-slate"><?= htmlspecialchars($wh_label[$d->item_type] ?? ucfirst($d->item_type)); ?></span>
                            <?php if (!empty($d->category)): ?> <span class="pp-tag <?= $d->category === 'Produksi' ? 'tone-slate' : 'tone-blue'; ?>"><?= htmlspecialchars($d->category); ?></span><?php endif; ?></td>
                        <td class="num"><b><?= $qty($d->receive_qty); ?></b> <small class="text-muted"><?= htmlspecialchars($d->satuan ?? ''); ?></small></td>
                        <td class="num"><?= number_format((float) $d->unit_value, 2, '.', ','); ?></td>
                        <td class="num font-weight-bold"><?= number_format((float) $d->total_value, 2, '.', ','); ?></td>
                        <?php if ($is_draft && $can_edit): ?>
                        <td class="text-right"><div class="pp-actions">
                            <?php if ($d->source_doc_type === 'PO'): ?><button type="button" class="btn btn-light" data-toggle="modal" data-target="#editSgrItemModal" data-detail-id="<?= (int) $d->id; ?>" title="<?= htmlspecialchars(lang_line('sgr2_edit_item', 'Edit qty & BC')); ?>"><i class="fas fa-pen"></i></button><?php endif; ?>
                            <a href="<?= base_url('supplier_general_item_receipt/delete_item/' . $header->id . '/' . $d->id); ?>" class="btn btn-light text-danger btn-delete" data-message="<?= htmlspecialchars(sprintf(lang_line('pod2_delete_item_q', 'Hapus item %s?'), $d->item_name ?? '')); ?>"><i class="fas fa-trash-alt"></i></a>
                        </div></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="5" class="text-right font-weight-bold" style="background:var(--pp-soft)"><?= lang_line('sgr_grand_total', 'Grand Total'); ?></td><td class="num font-weight-bold" style="background:var(--pp-soft);font-size:1rem"><?= number_format($grand_total, 2, '.', ','); ?></td><?php if ($is_draft && $can_edit): ?><td style="background:var(--pp-soft)"></td><?php endif; ?></tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_draft && $can_edit): ?>
<!-- Modal Edit Header -->
<div class="modal fade pp-modal" id="editSgrHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('supplier_general_item_receipt/update_header/' . $header->id); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen mr-2"></i><?= lang_line('sgr_modal_edit_header_title'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('sgr_label_sgr_date_input'); ?></label>
                            <input type="date" name="sgr_date" class="form-control" value="<?= $header->sgr_date; ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('sgr_label_warehouse_type_input'); ?></label>
                            <input type="text" class="form-control" value="<?= ucfirst($header->warehouse_type); ?>" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('sgr_label_delivery_note_input'); ?></label>
                            <input type="text" name="delivery_note_number" class="form-control" value="<?= htmlspecialchars($header->delivery_note_number); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('sgr_label_supplier_input'); ?></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header->nama_supplier ?? '-'); ?>" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_label_bc_type'); ?></label>
                            <select name="bc_type_code" class="form-control">
                                <option value="">-- Tanpa BC --</option>
                                <?php foreach ($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= $header->bc_type_code == $bc->code ? 'selected' : ''; ?>><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_label_bc_date_input'); ?></label>
                            <input type="date" name="bc_date" class="form-control" value="<?= $header->bc_date; ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= lang_line('sgr_label_bc_number'); ?></label>
                            <input type="text" name="bc_number" class="form-control" value="<?= htmlspecialchars($header->bc_number); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('sgr_label_remarks_input'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header->remarks); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('sgr_btn_cancel'); ?></button>
                    <button type="submit" class="btn btn-pp"><i class="fas fa-save mr-1"></i><?= lang_line('sgr_btn_save'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pilih Item PO. User 2026-09-14: sama seperti Warehouse In --
     pilih item & qty tertentu (penerimaan parsial), tiap baris menampilkan
     status penerimaannya. Menggantikan modal "Tambah Item" 1-per-1 dan
     "Load semua item PO". -->
<div class="modal fade pp-modal" id="poAutoLoadModalSgr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i><?= lang_line('sgr2_pick_po_title', 'Pilih Item PO yang Diterima'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem;">
                    <div class="input-group" style="max-width:360px;">
                        <input type="text" id="sgrPoSearch" class="form-control form-control-sm" placeholder="<?= htmlspecialchars(lang_line('sgr_po_search_placeholder', 'Cari nomor PO atau supplier...')); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="sgrPoSearchBtn"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="custom-control custom-switch ml-2">
                        <input type="checkbox" class="custom-control-input" id="sgrPoShowDone">
                        <label class="custom-control-label small" for="sgrPoShowDone">Tampilkan PO yang sudah diterima penuh</label>
                    </div>
                </div>
                <p class="small text-muted mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Dokumen ini untuk <strong><?= $header->warehouse_type === 'machine' ? 'Mesin' : 'Inventory'; ?></strong>,
                    kategori <strong><?= htmlspecialchars($header->category ?? 'Lainnya'); ?></strong>.
                    Centang item yang benar-benar datang, lalu sesuaikan qty-nya. Qty otomatis diisi sisa yang belum diterima dan tidak bisa melebihinya.
                    Baris dengan tipe atau kategori lain ditampilkan sebagai informasi saja.
                </p>
                <div id="sgrPoError" class="alert alert-danger py-2 small d-none"></div>
                <div id="sgrPoListContainer">
                    <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data PO...</div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="small text-muted" id="sgrPoSummary">Belum ada item dipilih.</div>
                <div>
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('sgr_btn_cancel', 'Batal'); ?></button>
                    <button type="button" id="sgrAutoLoadBtn" class="btn btn-pp" disabled>
                        <i class="fas fa-check mr-1"></i> Muat Item Terpilih
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Item. Qty dibatasi sisa PO (dihitung server), BC per baris,
     nilai satuan tetap mengikuti harga PO. -->
<div class="modal fade pp-modal" id="editSgrItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="editSgrItemForm" novalidate>
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen mr-2"></i><?= lang_line('sgr2_edit_item_title', 'Edit Item Penerimaan'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="esDetailId">
                    <div id="esLoading" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data item...</div>
                    <div id="esBody" class="d-none">
                        <div id="esError" class="alert alert-danger py-2 small d-none"></div>
                        <div class="mb-3">
                            <div class="small text-muted">PO <span id="esPo" class="font-weight-bold text-dark"></span> · <span id="esType"></span></div>
                            <div class="h6 mb-0"><span id="esCode" class="text-primary"></span> <span id="esName"></span></div>
                        </div>
                        <div class="form-row text-center mb-3">
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2"><div class="small text-muted">Qty PO</div><div class="font-weight-bold" id="esStatPo">-</div></div></div>
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2"><div class="small text-muted">Sudah diterima</div><div class="font-weight-bold" id="esStatReceived">-</div></div></div>
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2" id="esStatDraftBox"><div class="small text-muted">Di draft lain</div><div class="font-weight-bold" id="esStatDraft">-</div></div></div>
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2 border-success"><div class="small text-muted">Maksimum baris ini</div><div class="font-weight-bold text-success" id="esStatMax">-</div></div></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="esQty" class="font-weight-bold">Qty Diterima <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0.01" id="esQty" class="form-control text-right" required>
                                    <div class="input-group-append"><span class="input-group-text" id="esUnit"></span></div>
                                    <div class="invalid-feedback" id="esQtyFeedback"></div>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">Nilai Satuan</label>
                                <input type="text" id="esUnitValue" class="form-control text-right" disabled>
                                <small class="text-muted">Mengikuti harga di PO.</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">Total Nilai</label>
                                <input type="text" id="esTotal" class="form-control text-right" disabled>
                                <small class="text-muted">Dihitung otomatis.</small>
                            </div>
                        </div>
                        <div class="small font-weight-bold text-muted mb-2"><i class="fas fa-file-contract mr-1"></i> Data Kepabeanan (BC) baris ini</div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="esBcType">Tipe BC</label>
                                <select id="esBcType" class="form-control">
                                    <option value="">-- Tanpa BC --</option>
                                    <?php foreach (($bc_types ?? []) as $bc): ?>
                                        <option value="<?= htmlspecialchars($bc->code); ?>"><?= htmlspecialchars($bc->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="esBcNumber">Nomor BC</label>
                                <input type="text" id="esBcNumber" class="form-control" maxlength="50">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="esBcDate">Tanggal BC</label>
                                <input type="date" id="esBcDate" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('sgr_btn_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-pp" id="esSave" disabled><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// jQuery & Bootstrap dimuat di footer (setelah view ini), jadi semua binding
// ditunda sampai DOMContentLoaded.
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?= base_url(); ?>';
    const sgrId = <?= (int) $header->id; ?>;

    const esc = v => $('<div>').text(v == null ? '' : String(v)).html();
    const num = v => Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    const money = v => Number(v || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const TYPE = { machine: 'Mesin', inventory: 'Inventory', material: 'Material', semifinished: 'Semi-finished' };

    // ====== PILIH ITEM PO ======
    const STATUS = {
        not_received:    ['secondary',    'Belum diterima'],
        partial:         ['info',         'Diterima sebagian'],
        done:            ['success',      'Sudah diterima penuh'],
        in_this_doc:     ['primary',      'Sudah di SGR ini'],
        held_in_draft:   ['warning',      'Tertahan di draft lain'],
        other_module:    ['light border', 'Lewat Receiving Material Form'],
        other_warehouse: ['light border', 'Beda tipe gudang'],
        other_category:  ['light border', 'Beda kategori']
    };
    let poData = [];
    const picked = {};   // po_detail_id -> qty

    function loadPoList(q) {
        $('#sgrPoError').addClass('d-none').empty();
        $('#sgrPoListContainer').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data PO...</div>');
        $.getJSON(baseUrl + 'supplier_general_item_receipt/get_available_pos', { sgr_id: sgrId, q: q || '' }).done(function (r) {
            if (r.status !== 'success') { $('#sgrPoListContainer').html('<div class="text-danger text-center py-3">' + esc(r.message) + '</div>'); return; }
            poData = r.data || [];
            render();
        }).fail(function () {
            $('#sgrPoListContainer').html('<div class="text-danger text-center py-3">Gagal memuat data PO.</div>');
        });
    }

    function render() {
        const showDone = $('#sgrPoShowDone').is(':checked');
        const list = poData.filter(po => showDone || !po.fully_received);
        if (!list.length) {
            $('#sgrPoListContainer').html('<div class="text-center text-muted py-4">' +
                (poData.length ? 'Semua PO sudah diterima penuh. Aktifkan tombol di atas untuk melihatnya.' : 'Tidak ada PO yang siap diterima untuk SGR ini.') + '</div>');
            updateSummary(); return;
        }
        let html = '';
        list.forEach(function (po) {
            const canPick = po.receivable_lines > 0;
            html += '<div class="card mb-2 po-card" data-po="' + po.po_id + '">'
                  + '<div class="card-header py-2 d-flex flex-wrap align-items-center" style="gap:.5rem;cursor:pointer" data-toggle="collapse" data-target="#sgrPoBody' + po.po_id + '">'
                  + '<input type="checkbox" class="po-all" ' + (canPick ? '' : 'disabled') + ' title="Pilih semua item yang bisa diterima di PO ini" onclick="event.stopPropagation()">'
                  + '<strong>' + esc(po.po_number) + '</strong>'
                  + '<span class="text-muted small">' + esc(po.supplier_name || '-') + ' · ' + esc(po.po_date || '-') + (po.currency ? ' · ' + esc(po.currency) : '') + '</span>'
                  + '<span class="ml-auto"></span>'
                  + (canPick ? '<span class="badge badge-success">' + po.receivable_lines + ' item siap diterima</span>'
                             : '<span class="badge badge-secondary">Tidak ada item tersisa</span>')
                  + (po.other_lines ? ' <span class="badge badge-light border" title="Tipe/kategori lain, tidak diterima di SGR ini">' + po.other_lines + ' item lain</span>' : '')
                  + '<span class="small text-muted" style="min-width:120px">Diterima ' + po.progress + '%'
                  + '<div class="progress" style="height:5px"><div class="progress-bar bg-success" style="width:' + po.progress + '%"></div></div></span>'
                  + '</div>'
                  + '<div id="sgrPoBody' + po.po_id + '" class="collapse ' + (canPick ? 'show' : '') + '">'
                  + '<div class="table-responsive"><table class="table table-sm mb-0 small">'
                  + '<thead class="thead-light"><tr>'
                  + '<th width="30"></th><th>Barang</th><th>Tipe / Kategori</th>'
                  + '<th class="text-right">Qty PO</th><th class="text-right">Sudah diterima</th>'
                  + '<th class="text-right">Di draft lain</th><th class="text-right">Di SGR ini</th>'
                  + '<th class="text-right">Sisa</th><th>Status</th><th width="120" class="text-right">Qty diterima sekarang</th>'
                  + '</tr></thead><tbody>';
            po.lines.forEach(function (l) {
                const st = STATUS[l.status_key] || ['secondary', l.status_key];
                const on = picked[l.po_detail_id] !== undefined;
                const draftTip = l.draft_other_numbers ? ' title="' + esc('Tertahan di: ' + l.draft_other_numbers) + '"' : '';
                html += '<tr class="' + (l.receivable ? '' : 'text-muted') + '">'
                      + '<td><input type="checkbox" class="po-line" data-id="' + l.po_detail_id + '" data-max="' + l.qty_remaining + '" ' + (l.receivable ? '' : 'disabled') + (on ? ' checked' : '') + '></td>'
                      + '<td><div class="font-weight-bold">' + esc(l.item_code || '-') + '</div>' + esc(l.item_name || '-') + '</td>'
                      + '<td><span class="badge badge-light border">' + esc(TYPE[l.item_type] || l.item_type) + '</span>'
                      + (l.category ? ' <span class="badge badge-' + (l.category === 'Produksi' ? 'dark' : 'secondary') + '">' + esc(l.category) + '</span>' : '') + '</td>'
                      + '<td class="text-right">' + num(l.qty_po) + ' ' + esc(l.unit || '') + '</td>'
                      + '<td class="text-right">' + num(l.qty_received) + '</td>'
                      + '<td class="text-right"' + draftTip + '>' + num(l.qty_draft_other) + (l.qty_draft_other > 0 ? ' <i class="fas fa-info-circle text-warning"></i>' : '') + '</td>'
                      + '<td class="text-right">' + num(l.qty_this_doc) + '</td>'
                      + '<td class="text-right font-weight-bold">' + num(l.qty_remaining) + '</td>'
                      + '<td><span class="badge badge-' + st[0] + '">' + st[1] + '</span></td>'
                      + '<td class="text-right">' + (l.receivable
                            ? '<input type="number" class="form-control form-control-sm text-right po-qty" data-id="' + l.po_detail_id + '" min="0.01" step="any" max="' + l.qty_remaining + '" value="' + (on ? picked[l.po_detail_id] : l.qty_remaining) + '" ' + (on ? '' : 'disabled') + '>'
                            : '<span class="text-muted">-</span>')
                      + '</td></tr>';
            });
            html += '</tbody></table></div></div></div>';
        });
        $('#sgrPoListContainer').html(html);
        syncPoChecks();
        updateSummary();
    }

    function syncPoChecks() {
        $('#sgrPoListContainer .po-card').each(function () {
            const lines = $(this).find('.po-line:not(:disabled)');
            $(this).find('.po-all').prop('checked', lines.length > 0 && lines.filter(':checked').length === lines.length);
        });
    }

    function readQty($input) {
        const max = parseFloat($input.attr('max')) || 0;
        const v = parseFloat($input.val());
        const bad = isNaN(v) || v <= 0 || v > max + 0.0001;
        $input.toggleClass('is-invalid', bad);
        return bad ? null : v;
    }

    function updateSummary() {
        const ids = Object.keys(picked);
        let invalid = 0, total = 0;
        const poSet = {};
        ids.forEach(function (id) {
            const $i = $('#sgrPoListContainer .po-qty[data-id="' + id + '"]');
            const v = $i.length ? readQty($i) : null;
            if (v === null) invalid++; else total += v;
            const card = $i.closest('.po-card').data('po');
            if (card) poSet[card] = true;
        });
        $('#sgrPoSummary').html(ids.length
            ? '<strong>' + ids.length + '</strong> item dari <strong>' + Object.keys(poSet).length + '</strong> PO · total qty <strong>' + num(total) + '</strong>'
              + (invalid ? ' · <span class="text-danger">' + invalid + ' qty tidak valid</span>' : '')
            : 'Belum ada item dipilih.');
        $('#sgrAutoLoadBtn').prop('disabled', !ids.length || invalid > 0);
    }

    $(document).on('change', '#sgrPoListContainer .po-line', function () {
        const id = $(this).data('id');
        const $q = $('#sgrPoListContainer .po-qty[data-id="' + id + '"]');
        if (this.checked) { picked[id] = parseFloat($q.val()) || 0; $q.prop('disabled', false).trigger('focus'); }
        else { delete picked[id]; $q.prop('disabled', true).removeClass('is-invalid').val($(this).data('max')); }
        syncPoChecks(); updateSummary();
    });
    $(document).on('change', '#sgrPoListContainer .po-all', function () {
        const on = this.checked;
        $(this).closest('.po-card').find('.po-line:not(:disabled)').each(function () {
            if (this.checked !== on) { this.checked = on; $(this).trigger('change'); }
        });
    });
    $(document).on('input', '#sgrPoListContainer .po-qty', function () {
        const id = $(this).data('id');
        if (picked[id] !== undefined) picked[id] = parseFloat($(this).val()) || 0;
        updateSummary();
    });
    $('#sgrPoShowDone').on('change', render);
    $('#sgrPoSearchBtn').on('click', () => loadPoList($('#sgrPoSearch').val()));
    $('#sgrPoSearch').on('keypress', e => { if (e.which === 13) { e.preventDefault(); loadPoList($('#sgrPoSearch').val()); } });

    $('#sgrAutoLoadBtn').on('click', function () {
        const items = Object.keys(picked).map(id => ({ po_detail_id: parseInt(id, 10), qty: picked[id] }));
        if (!items.length) return;
        const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat...');
        $('#sgrPoError').addClass('d-none').empty();
        $.post(baseUrl + 'supplier_general_item_receipt/load_selected_po_items', { sgr_id: sgrId, items: JSON.stringify(items) }, null, 'json')
            .done(function (r) {
                if (r.status === 'success') { location.reload(); return; }
                let msg = '<strong>' + esc(r.message || 'Gagal memuat item.') + '</strong>';
                if (r.errors && r.errors.length) msg += '<ul class="mb-0 pl-3">' + r.errors.map(e => '<li>' + esc(e) + '</li>').join('') + '</ul>';
                $('#sgrPoError').html(msg).removeClass('d-none');
                $('#poAutoLoadModalSgr .modal-body').scrollTop(0);
            })
            .fail(function () { $('#sgrPoError').html('Gagal menghubungi server.').removeClass('d-none'); })
            .always(function () { $btn.html('<i class="fas fa-check mr-1"></i> Muat Item Terpilih'); updateSummary(); });
    });

    // Selalu ambil data segar: status penerimaan bisa berubah karena SGR lain
    // di-approve sementara halaman ini terbuka.
    $('#poAutoLoadModalSgr').on('show.bs.modal', function (e) {
        if (e.target !== this) return;   // abaikan event collapse di dalam modal
        Object.keys(picked).forEach(k => delete picked[k]);
        loadPoList($('#sgrPoSearch').val());
    });

    // ====== EDIT ITEM ======
    let unitValue = 0, maxQty = 0;
    function recalc() {
        const v = parseFloat($('#esQty').val());
        let msg = '';
        if (isNaN(v) || v <= 0) msg = 'Qty harus lebih dari 0.';
        else if (v > maxQty + 0.0001) msg = 'Melebihi maksimum ' + num(maxQty) + '.';
        $('#esQty').toggleClass('is-invalid', !!msg);
        $('#esQtyFeedback').text(msg);
        $('#esTotal').val(money((isNaN(v) ? 0 : v) * unitValue));
        $('#esSave').prop('disabled', !!msg);
    }
    function showEsError(text) { $('#esError').text(text).removeClass('d-none'); }

    $('#editSgrItemModal').on('show.bs.modal', function (event) {
        const id = $(event.relatedTarget).data('detail-id');
        $('#esDetailId').val(id);
        $('#esBody, #esError').addClass('d-none');
        $('#esLoading').removeClass('d-none');
        $('#esSave').prop('disabled', true);
        $.getJSON(baseUrl + 'supplier_general_item_receipt/get_po_item_detail', { detail_id: id }).done(function (r) {
            $('#esLoading').addClass('d-none');
            $('#esBody').removeClass('d-none');
            if (r.status !== 'success') { showEsError(r.message || 'Gagal memuat item.'); return; }
            const d = r.data;
            unitValue = Number(d.unit_value) || 0;
            maxQty    = Number(d.max_qty) || 0;
            $('#esPo').text(d.po_number || '-');
            $('#esType').text(TYPE[d.item_type] || d.item_type);
            $('#esCode').text(d.item_code || '');
            $('#esName').text(d.item_name || '');
            $('#esUnit').text(d.unit || '');
            $('#esStatPo').text(num(d.qty_po));
            $('#esStatReceived').text(num(d.qty_received));
            $('#esStatDraft').text(num(d.qty_draft_other));
            $('#esStatDraftBox').attr('title', d.draft_other_numbers ? 'Tertahan di: ' + d.draft_other_numbers : '').toggleClass('border-warning', d.qty_draft_other > 0);
            $('#esStatMax').text(num(maxQty));
            $('#esQty').attr('max', maxQty).val(d.receive_qty);
            $('#esUnitValue').val(money(unitValue));
            $('#esBcType').val(d.bc_type_code || '');
            $('#esBcNumber').val(d.bc_number || '');
            $('#esBcDate').val(d.bc_date || '');
            recalc();
        }).fail(function () {
            $('#esLoading').addClass('d-none');
            $('#esBody').removeClass('d-none');
            showEsError('Gagal menghubungi server.');
        });
    });
    $('#esQty').on('input', recalc);
    $('#editSgrItemForm').on('submit', function (e) {
        e.preventDefault();
        recalc();
        if ($('#esSave').prop('disabled')) return;
        const $btn = $('#esSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        $.post(baseUrl + 'supplier_general_item_receipt/update_po_item', {
            detail_id: $('#esDetailId').val(), receive_qty: $('#esQty').val(),
            bc_type_code: $('#esBcType').val(), bc_number: $('#esBcNumber').val(), bc_date: $('#esBcDate').val()
        }, null, 'json').done(function (r) {
            if (r.status === 'success') { location.reload(); return; }
            showEsError(r.message || 'Gagal menyimpan.');
            if (r.max !== undefined) { maxQty = Number(r.max); $('#esStatMax').text(num(maxQty)); $('#esQty').attr('max', maxQty); }
        }).fail(function () { showEsError('Gagal menghubungi server.'); })
          .always(function () { $btn.html('<i class="fas fa-save mr-1"></i> Simpan'); recalc(); });
    });
});
</script>
<?php endif; ?>

<?php $this->load->view('templates/footer'); ?>
