<?php
$is_draft = ($header->status == 'Draft');
// Controller mengirim izin per kategori (_can_sgr). Dulu view hanya cek menu
// blanket, jadi user yang cuma punya izin SGR General/Produksi tidak melihat
// tombol apa pun padahal controller mengizinkan.
$can_edit = $can_edit ?? check_access('supplier_general_item_receipt', 'edit');
// Approve/unapprove pakai izin Confirm, sama dengan controller (dulu Add, jadi
// pembuat dokumen otomatis bisa approve — tidak sesuai PDF ROLE LEVEL).
$can_approve = $can_approve ?? check_access('supplier_general_item_receipt', 'confirm');
$grand_total = 0;
foreach ($details as $d) { $grand_total += (float)($d->total_value ?? 0); }
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check text-primary mr-2"></i>
            <?= lang_line('sgr_detail_title', 'Detail SGR'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->sgr_number); ?></span>
            <?php if ($header->status === 'Completed'): ?>
                <span class="badge badge-success ml-1"><?= lang_line('sgr_badge_approved'); ?></span>
            <?php else: ?>
                <span class="badge badge-secondary ml-1"><?= lang_line('sgr_badge_draft'); ?></span>
            <?php endif; ?>
        </h3>
        <div>
            <?php if ($is_draft && $can_edit): ?>
                <button type="button" class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#poAutoLoadModalSgr">
                    <i class="fas fa-file-import mr-1"></i> <?= lang_line('sgr_btn_pick_load_po'); ?>
                </button>
                <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editSgrHeaderModal">
                    <i class="fas fa-edit"></i> <?= lang_line('sgr_btn_edit_header'); ?>
                </button>
            <?php endif; ?>

            <?php if ($is_draft && $can_approve && !empty($details)): ?>
                <a href="<?= base_url('supplier_general_item_receipt/approve/' . $header->id); ?>"
                   class="btn btn-primary btn-sm mr-1"
                   onclick="return confirm('<?= lang_line('sgr_confirm_approve', 'Yakin approve SGR? Barang akan masuk stock.'); ?>')">
                    <i class="fas fa-check-circle"></i> <?= lang_line('sgr_btn_approve'); ?>
                </a>
            <?php elseif (!$is_draft && $can_approve): ?>
                <a href="<?= base_url('supplier_general_item_receipt/unapprove/' . $header->id); ?>"
                   class="btn btn-danger btn-sm mr-1"
                   onclick="return confirm('<?= lang_line('sgr_confirm_unapprove', 'Yakin batalkan approval? Stok akan dikembalikan.'); ?>')">
                    <i class="fas fa-times-circle"></i> <?= lang_line('sgr_btn_unapprove'); ?>
                </a>
            <?php endif; ?>

            <a href="<?= base_url('supplier_general_item_receipt/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf"></i> <?= lang_line('sgr_btn_pdf'); ?>
            </a>
            <a href="<?= base_url('supplier_general_item_receipt'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('sgr_btn_back'); ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
        <div class="alert alert-warning"><?= $this->session->flashdata('warning'); ?></div>
    <?php endif; ?>

    <!-- Header info -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong><?= lang_line('sgr_label_sgr_date'); ?></strong> <?= date('d M Y', strtotime($header->sgr_date)); ?></div>
                <div class="col-md-3"><strong><?= lang_line('sgr_label_warehouse_type'); ?></strong> <?= htmlspecialchars(ucfirst($header->warehouse_type)); ?></div>
                <div class="col-md-3"><strong><?= lang_line('sgr_category', 'Kategori'); ?></strong>
                    <?php $hc = $header->category ?? 'Lainnya'; ?>
                    <span class="badge badge-<?= $hc === 'Produksi' ? 'dark' : ($hc === 'General' ? 'secondary' : 'light text-muted') ?>"><?= htmlspecialchars($hc) ?></span>
                </div>
                <div class="col-md-3"><strong><?= lang_line('sgr_label_supplier'); ?></strong> <?= htmlspecialchars($header->nama_supplier ?? '-'); ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4"><strong><?= lang_line('sgr_label_delivery_note'); ?></strong> <?= htmlspecialchars($header->delivery_note_number ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('sgr_label_bc'); ?></strong> <?= htmlspecialchars(($header->bc_type_desc ?? $header->bc_type_code) ?: '-'); ?> <?= !empty($header->bc_number) ? ' / ' . htmlspecialchars($header->bc_number) : ''; ?></div>
                <div class="col-md-4"><strong><?= lang_line('sgr_label_bc_date'); ?></strong> <?= !empty($header->bc_date) ? date('d M Y', strtotime($header->bc_date)) : '-'; ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12"><strong><?= lang_line('sgr_label_remarks'); ?></strong> <?= htmlspecialchars($header->remarks ?? '-'); ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><strong><?= lang_line('sgr_label_created_by'); ?></strong> <?= htmlspecialchars($header->creator_name ?? '-'); ?>
                    <small class="text-muted">(<?= !empty($header->created_at) ? date('d M Y H:i', strtotime($header->created_at)) : '-'; ?>)</small>
                </div>
                <?php if (!empty($header->approved_at)): ?>
                <div class="col-md-6"><strong><?= lang_line('sgr_label_approved_by'); ?></strong> <?= htmlspecialchars($header->approver_name ?? '-'); ?>
                    <small class="text-muted">(<?= date('d M Y H:i', strtotime($header->approved_at)); ?>)</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('sgr_card_items'); ?></h6>
            <?php if ($is_draft && $can_edit): ?>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#poAutoLoadModalSgr">
                    <i class="fas fa-plus mr-1"></i> <?= lang_line('sgr_btn_add_item'); ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('sgr_th_no'); ?></th>
                            <th><?= lang_line('sgr_th_po_no'); ?></th>
                            <th><?= lang_line('sgr_th_item_code'); ?></th>
                            <th><?= lang_line('sgr_th_item_name'); ?></th>
                            <th><?= lang_line('sgr_th_type'); ?></th>
                            <th><?= lang_line('cr_category', 'Kategori'); ?></th>
                            <th><?= lang_line('sgr_th_qty'); ?></th>
                            <th><?= lang_line('sgr_th_unit'); ?></th>
                            <th><?= lang_line('sgr_th_unit_value'); ?></th>
                            <th><?= lang_line('sgr_th_total'); ?></th>
                            <?php if ($is_draft && $can_edit): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($details)): $no = 1; foreach ($details as $d): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->source_doc_number ?? '-'); ?></td>
                            <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                            <td class="text-left"><?= htmlspecialchars($d->item_name ?? '-'); ?>
                                <?php if (!empty($d->bc_type_code) || !empty($d->bc_number)): ?>
                                    <div class="small text-muted">BC: <?= htmlspecialchars(trim(($d->bc_type_code ?? '') . ' ' . ($d->bc_number ?? ''))); ?><?= !empty($d->bc_date) ? ' · ' . date('d M Y', strtotime($d->bc_date)) : ''; ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(ucfirst($d->item_type)); ?></td>
                            <td>
                                <?php if (!empty($d->category)): ?>
                                    <span class="badge badge-<?= $d->category === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($d->category) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((float)$d->receive_qty, 2); ?></td>
                            <td><?= htmlspecialchars($d->satuan ?? '-'); ?></td>
                            <td class="text-right"><?= number_format((float)$d->unit_value, 2); ?></td>
                            <td class="text-right"><?= number_format((float)$d->total_value, 2); ?></td>
                            <?php if ($is_draft && $can_edit): ?>
                            <td class="text-nowrap">
                                <?php if ($d->source_doc_type === 'PO'): ?>
                                <button type="button" class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#editSgrItemModal" data-detail-id="<?= $d->id; ?>" title="Edit qty & BC">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <a href="<?= base_url('supplier_general_item_receipt/delete_item/' . $header->id . '/' . $d->id); ?>"
                                   class="btn btn-sm btn-danger btn-delete"
                                   data-message="Yakin hapus item ini?">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="<?= ($is_draft && $can_edit) ? 11 : 10; ?>" class="text-center text-muted py-3"><?= lang_line('sgr_no_items'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($details)): ?>
                    <tfoot>
                        <tr>
                            <th colspan="9" class="text-right"><?= lang_line('sgr_grand_total'); ?></th>
                            <th class="text-right"><?= number_format($grand_total, 2); ?></th>
                            <?php if ($is_draft && $can_edit): ?><th></th><?php endif; ?>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($is_draft && $can_edit): ?>
<!-- Modal Edit Header -->
<div class="modal fade" id="editSgrHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('supplier_general_item_receipt/update_header/' . $header->id); ?>">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><?= lang_line('sgr_modal_edit_header_title'); ?></h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
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
                    <button type="submit" class="btn btn-warning"><?= lang_line('sgr_btn_save'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('sgr_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pilih Item PO. User 2026-09-14: sama seperti Warehouse In --
     pilih item & qty tertentu (penerimaan parsial), tiap baris menampilkan
     status penerimaannya. Menggantikan modal "Tambah Item" 1-per-1 dan
     "Load semua item PO". -->
<div class="modal fade" id="poAutoLoadModalSgr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i>Pilih Item PO yang Diterima</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('sgr_btn_cancel', 'Batal'); ?></button>
                    <button type="button" id="sgrAutoLoadBtn" class="btn btn-success" disabled>
                        <i class="fas fa-check mr-1"></i> Muat Item Terpilih
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Item. Qty dibatasi sisa PO (dihitung server), BC per baris,
     nilai satuan tetap mengikuti harga PO. -->
<div class="modal fade" id="editSgrItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="editSgrItemForm" novalidate>
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Item Penerimaan</h5>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('sgr_btn_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-warning" id="esSave" disabled><i class="fas fa-save mr-1"></i> Simpan</button>
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
