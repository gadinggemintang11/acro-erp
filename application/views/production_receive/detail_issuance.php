<?php
/*
 * Master kode NG untuk dropdown "Rincian NG" pada form QC. Dibaca sekali di
 * sini supaya tidak query berulang di dalam loop item. Array kosong kalau
 * db/migration_material_ng_detail.sql belum dijalankan -- dropdown tinggal
 * berisi "-" dan sisanya tetap jalan normal.
 */
$ng_code_options = function_exists('ng_code_options') ? ng_code_options() : [];
?>
<div class="container-fluid">

    <?php /* ── Flash Messages ── */ ?>
    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i><?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i><?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <?php /* ── Header Bar ── */ ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-check-circle text-primary mr-2"></i><?= lang_line('pr_production_receive_label', 'Penerimaan Produksi'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
            <span class="badge badge-info ml-1"><?= htmlspecialchars($issuance->issuance_number); ?></span>
            <?php
            $is_received  = $issuance->status === 'Received';
            $is_qc_done   = ($issuance->qc_status ?? '') === 'QC Done';
            ?>
            <?php if ($is_received && $is_qc_done): ?>
                <span class="badge badge-success ml-1"><i class="fas fa-microscope"></i> <?= lang_line('pr_badge_qc_done', 'QC Done'); ?></span>
            <?php elseif ($is_received): ?>
                <span class="badge badge-warning ml-1"><i class="fas fa-clock"></i> <?= lang_line('pr_badge_qc_need', 'Perlu QC'); ?></span>
            <?php endif; ?>
        </h3>
        <div>
            <?php if ($issuance->status === 'Issued' && check_access('production_receive', 'edit')): ?>
                <a href="<?= base_url('production_receive/confirm_receive/' . $issuance->id); ?>"
                   class="btn btn-primary btn-sm mr-1"
                   onclick="return confirm('<?= lang_line('pr_confirm_receive_simple', 'Konfirmasi penerimaan material ini?'); ?>')">
                    <i class="fas fa-check mr-1"></i><?= lang_line('pr_btn_confirm_receive_simple', 'Konfirmasi Terima'); ?>
                </a>
            <?php endif; ?>
            <?php if ($is_received && !$is_qc_done && check_access('production_receive', 'edit')): ?>
                <button class="btn btn-warning btn-sm mr-1" id="btnShowQcForm">
                    <i class="fas fa-microscope mr-1"></i><?= lang_line('pr_btn_input_qc', 'Input QC'); ?>
                </button>
            <?php endif; ?>
            <?php if ($is_received && $is_qc_done && check_access('production_receive', 'edit')): ?>
                <button class="btn btn-outline-warning btn-sm mr-1" id="btnShowQcForm">
                    <i class="fas fa-redo mr-1"></i><?= lang_line('pr_btn_re_qc', 'Re-QC'); ?>
                </button>
            <?php endif; ?>
            <?php if ($is_received && !$is_qc_done && check_access('production_receive', 'edit')): ?>
                <a href="<?= base_url('production_receive/unconfirm_receive/' . $issuance->id); ?>"
                   class="btn btn-danger btn-sm mr-1"
                   onclick="return confirm('<?= lang_line('pr_confirm_cancel_receive', 'Batalkan penerimaan ini?'); ?>')">
                    <i class="fas fa-undo mr-1"></i><?= lang_line('pr_btn_cancel_receive', 'Batalkan Terima'); ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('production_receive'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i><?= lang_line('btn_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?php /* ── Info Card ── */ ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('pr_info_mr_number', 'MR Number'); ?></small>
                    <strong><?= htmlspecialchars($header->mr_number); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('pr_info_issuance_number', 'Issuance Number'); ?></small>
                    <strong><?= htmlspecialchars($issuance->issuance_number); ?></strong>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('pr_info_receive_status', 'Status Terima'); ?></small>
                    <span class="badge badge-<?= $is_received ? 'success' : 'warning'; ?>">
                        <?= $issuance->status; ?>
                    </span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('pr_info_qc_status', 'Status QC'); ?></small>
                    <span class="badge badge-<?= $is_qc_done ? 'success' : 'secondary'; ?>">
                        <?= $is_qc_done ? lang_line('pr_badge_qc_done', 'QC Done') : lang_line('pr_status_not_qc_yet', 'Belum QC'); ?>
                    </span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('pr_info_issued_received_date', 'Tgl Issued / Received'); ?></small>
                    <span class="small">
                        <?= date('d M Y H:i', strtotime($issuance->issued_at)); ?>
                        <?php if ($issuance->received_at): ?>
                        <br><?= date('d M Y H:i', strtotime($issuance->received_at)); ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php /* ── Item Diterima (read-only jika sudah QC done, atau sebelum receive) ── */ ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-boxes mr-2"></i><?= lang_line('pr_items_received_title', 'Item Dikeluarkan dari Gudang'); ?>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('pr_th_code', 'Kode'); ?></th>
                            <th><?= lang_line('pr_th_item_name', 'Nama Item'); ?></th>
                            <th><?= lang_line('pr_th_unit', 'Satuan'); ?></th>
                            <th class="text-right"><?= lang_line('pr_th_issued_qty', 'Issued Qty'); ?></th>
                            <?php if ($is_received): ?>
                            <th class="text-right text-success"><?= lang_line('pr_th_accept_qty', 'Accept Qty'); ?></th>
                            <th class="text-right text-danger"><?= lang_line('pr_th_reject_qty', 'Reject Qty'); ?></th>
                            <th><?= lang_line('pr_th_ng_detail', 'Rincian NG'); ?></th>
                            <th class="text-center"><?= lang_line('pr_th_qc_status', 'QC Status'); ?></th>
                            <th><?= lang_line('pr_th_qc_notes', 'Catatan QC'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item):
                                $qk  = $item->item_id . '_' . $item->item_type;
                                $qr  = $qc_results_map[$qk] ?? null;
                                $qst = $qr->qc_status ?? null;
                                $qst_color = ['Accepted' => 'success', 'Partial' => 'warning', 'Rejected' => 'danger'][$qst] ?? 'secondary';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($item->item_code); ?></td>
                                <td><?= htmlspecialchars($item->item_name); ?></td>
                                <td class="text-center"><?= htmlspecialchars($item->item_unit); ?></td>
                                <td class="text-right font-weight-bold"><?= number_format($item->issued_qty, 2); ?></td>
                                <?php if ($is_received): ?>
                                <td class="text-right text-success">
                                    <?= $qr ? number_format($qr->accept_qty, 2) : '<span class="text-muted">-</span>'; ?>
                                </td>
                                <td class="text-right <?= ($qr && $qr->reject_qty > 0) ? 'text-danger font-weight-bold' : 'text-muted'; ?>">
                                    <?= $qr ? number_format($qr->reject_qty, 2) : '<span class="text-muted">-</span>'; ?>
                                </td>
                                <td><small class="text-muted">
                                    <?= $qr ? (ng_summary_text($qr) ?: '-') : '-'; ?>
                                </small></td>
                                <td class="text-center">
                                    <?php if ($qr): ?>
                                        <span class="badge badge-<?= $qst_color; ?>"><?= $qst; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= lang_line('pr_status_not_qc_yet', 'Belum QC'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= htmlspecialchars($qr->qc_notes ?? '-'); ?></small></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('pr_no_items', 'Tidak ada item'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php /* ── QC Form (tampil jika sudah Received dan belum/sudah QC) ── */ ?>
    <?php if ($is_received && check_access('production_receive', 'edit')): ?>
    <div class="card shadow mb-4 border-warning" id="qcFormCard" style="display:none;">
        <div class="card-header bg-warning text-dark py-3">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-microscope mr-2"></i>
                <?= $is_qc_done ? lang_line('pr_qc_form_title_re', 'Re-Process QC') : lang_line('pr_qc_form_title_new', 'Input Hasil QC'); ?>
                <small class="ml-2 font-weight-normal">
                    — <?= lang_line('pr_qc_form_subtitle', 'Accept Qty + Reject Qty tidak boleh melebihi Issued Qty'); ?>
                </small>
            </h6>
        </div>
        <form method="post" action="<?= base_url('production_receive/process_qc/' . $issuance->id); ?>">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light text-center">
                            <tr>
                                <th><?= lang_line('pr_th_code', 'Kode'); ?></th>
                                <th><?= lang_line('pr_th_item_name', 'Nama Item'); ?></th>
                                <th><?= lang_line('pr_th_unit', 'Satuan'); ?></th>
                                <th class="text-right"><?= lang_line('pr_th_issued_qty', 'Issued Qty'); ?></th>
                                <th class="text-center" style="width:130px;"><?= lang_line('pr_th_accept_qty', 'Accept Qty'); ?> <span class="text-success">*</span></th>
                                <th class="text-center" style="width:130px;"><?= lang_line('pr_th_reject_qty', 'Reject Qty'); ?></th>
                                <th style="width:220px;"><?= lang_line('pr_th_qc_notes', 'Catatan QC'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item):
                                $qk = $item->item_id . '_' . $item->item_type;
                                $qr = $qc_results_map[$qk] ?? null;
                                $prev_accept = $qr ? $qr->accept_qty : $item->issued_qty;
                                $prev_reject = $qr ? $qr->reject_qty : 0;
                                $prev_notes  = $qr ? $qr->qc_notes  : '';
                                $fk = htmlspecialchars($qk);
                            ?>
                            <tr class="qc-row" data-issued="<?= (float)$item->issued_qty; ?>">
                                <td><?= htmlspecialchars($item->item_code); ?></td>
                                <td><?= htmlspecialchars($item->item_name); ?></td>
                                <td class="text-center"><?= htmlspecialchars($item->item_unit); ?></td>
                                <td class="text-right font-weight-bold">
                                    <?= number_format($item->issued_qty, 2); ?>
                                    <input type="hidden" name="items[<?= $fk; ?>][issued_qty]" value="<?= (float)$item->issued_qty; ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="<?= (float)$item->issued_qty; ?>"
                                           class="form-control form-control-sm text-right qc-accept"
                                           name="items[<?= $fk; ?>][accept_qty]"
                                           value="<?= number_format($prev_accept, 2, '.', ''); ?>"
                                           required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="<?= (float)$item->issued_qty; ?>"
                                           class="form-control form-control-sm text-right qc-reject"
                                           name="items[<?= $fk; ?>][reject_qty]"
                                           value="<?= number_format($prev_reject, 2, '.', ''); ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           name="items[<?= $fk; ?>][qc_notes]"
                                           placeholder="<?= lang_line('pr_qc_reject_notes_placeholder', 'Keterangan reject...'); ?>"
                                           value="<?= htmlspecialchars($prev_notes); ?>">
                                </td>
                            </tr>
                            <!--
                                Rincian NG: hanya muncul kalau Reject Qty > 0, karena kalau
                                tidak ada yang ditolak tidak ada yang perlu dijelaskan.
                                Angka-angkanya murni catatan untuk Daily Material NG Report --
                                tidak menyentuh stok. Lihat application/helpers/ng_helper.php.
                            -->
                            <tr class="ng-row bg-light" data-for="<?= $fk; ?>" style="<?= ((float)$prev_reject > 0) ? '' : 'display:none;'; ?>">
                                <td colspan="7" class="py-2">
                                    <div class="form-row align-items-end">
                                        <div class="col-auto pr-3">
                                            <small class="font-weight-bold text-danger">
                                                <?= lang_line('pr_ng_detail_label', 'Rincian NG'); ?>
                                            </small>
                                        </div>
                                        <?php foreach (ng_categories() as $ng_col => $ng_label):
                                            $ng_val = $qr ? (float)($qr->$ng_col ?? 0) : 0; ?>
                                        <div class="col-6 col-md-2 form-group mb-0">
                                            <label class="small mb-0"><?= htmlspecialchars($ng_label); ?></label>
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control form-control-sm text-right ng-cat"
                                                   name="items[<?= $fk; ?>][<?= $ng_col; ?>]"
                                                   value="<?= number_format($ng_val, 2, '.', ''); ?>">
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="col-md-3 form-group mb-0">
                                            <label class="small mb-0"><?= lang_line('pr_ng_code', 'Kode NG'); ?></label>
                                            <select class="form-control form-control-sm ng-code" name="items[<?= $fk; ?>][ng_code]">
                                                <option value="">-</option>
                                                <?php foreach ($ng_code_options as $ngc): ?>
                                                <option value="<?= htmlspecialchars($ngc->code); ?>"
                                                    <?= ($qr && ($qr->ng_code ?? '') === $ngc->code) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($ngc->code . ' — ' . $ngc->description); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <small class="ng-balance-note"></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div id="qcValidationMsg" class="text-danger small"></div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm mr-2" id="btnHideQcForm">
                            <i class="fas fa-times mr-1"></i><?= lang_line('btn_cancel', 'Batal'); ?>
                        </button>
                        <button type="submit" class="btn btn-warning btn-sm" id="btnSubmitQc">
                            <i class="fas fa-save mr-1"></i><?= lang_line('pr_btn_save_qc', 'Simpan Hasil QC'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php /* ── Info reject → gudang ── */ ?>
    <?php if ($is_qc_done): ?>
        <?php
        $has_reject = false;
        foreach ($qc_results as $qr) {
            if ($qr->reject_qty > 0) { $has_reject = true; break; }
        }
        ?>
        <?php if ($has_reject): ?>
        <div class="alert alert-info border-left-info">
            <i class="fas fa-info-circle mr-2"></i>
            <?= lang_line('pr_reject_alert_html', '<strong>Item Reject</strong> sudah dikembalikan ke stok gudang via transaksi <code>IN_RETURN_WIP</code>. Jika item reject merupakan cacat dari supplier, Anda dapat membuat <strong>Retur Supplier</strong> di menu Warehouse Out → NG Return.'); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<script>
const I18N_PR = {
    validation_exceed: <?= json_encode(lang_line('pr_js_validation_exceed', 'Accept Qty + Reject Qty tidak boleh melebihi Issued Qty')); ?>,
    validation_fix: <?= json_encode(lang_line('pr_js_validation_fix', 'Perbaiki error sebelum menyimpan.')); ?>
};

(function(){
    var btnShow = document.getElementById('btnShowQcForm');
    var btnHide = document.getElementById('btnHideQcForm');
    var card    = document.getElementById('qcFormCard');

    if (btnShow && card) {
        btnShow.addEventListener('click', function(){ card.style.display = ''; card.scrollIntoView({behavior:'smooth'}); });
    }
    if (btnHide && card) {
        btnHide.addEventListener('click', function(){ card.style.display = 'none'; });
    }

    // Real-time validation: accept + reject <= issued
    document.querySelectorAll('.qc-row').forEach(function(row){
        var issued  = parseFloat(row.dataset.issued) || 0;
        var acc     = row.querySelector('.qc-accept');
        var rej     = row.querySelector('.qc-reject');
        var msgEl   = document.getElementById('qcValidationMsg');

        function validate() {
            var a = parseFloat(acc.value) || 0;
            var r = parseFloat(rej.value) || 0;
            if (a + r > issued + 0.001) {
                acc.classList.add('is-invalid');
                rej.classList.add('is-invalid');
                if (msgEl) msgEl.textContent = I18N_PR.validation_exceed + ' (' + issued.toFixed(2) + ')';
            } else {
                acc.classList.remove('is-invalid');
                rej.classList.remove('is-invalid');
                if (msgEl) msgEl.textContent = '';
            }
        }

        /* ----------------------------------------------------------
         * Rincian NG -- baris di bawah tiap item.
         *
         * Muncul hanya kalau ada yang ditolak. Qty reject yang diketik
         * langsung dituangkan ke satu kategori default supaya total rincian
         * selalu sama dengan reject qty tanpa user mengetik dua kali; begitu
         * user menyentuh salah satu kategori, pengisian otomatis berhenti.
         *
         * Tidak memblokir simpan kalau totalnya tidak pas -- sifatnya catatan
         * (lihat application/helpers/ng_helper.php), bukan angka stok.
         * ---------------------------------------------------------- */
        var ngRow    = row.nextElementSibling;
        var isNgRow  = ngRow && ngRow.classList.contains('ng-row');
        var ngCats   = isNgRow ? Array.prototype.slice.call(ngRow.querySelectorAll('.ng-cat')) : [];
        var ngNote   = isNgRow ? ngRow.querySelector('.ng-balance-note') : null;
        var ngAuto   = true;   // false begitu user mengetik sendiri

        function ngTotal() {
            return ngCats.reduce(function(s, el){ return s + (parseFloat(el.value) || 0); }, 0);
        }

        function ngRefresh() {
            if (!ngNote) return;
            var r = parseFloat(rej.value) || 0;
            var total = Math.round(ngTotal() * 100) / 100;
            if (r <= 0 && total <= 0) { ngNote.textContent = ''; ngNote.className = 'ng-balance-note'; return; }
            var pas = Math.abs(total - r) < 0.01;
            ngNote.textContent = 'Total rincian ' + total.toFixed(2) + ' dari reject ' + r.toFixed(2) + (pas ? '' : ' — belum pas');
            ngNote.className = 'ng-balance-note small ' + (pas ? 'text-success' : 'text-danger');
        }

        function ngSync() {
            if (!isNgRow) return;
            var r = parseFloat(rej.value) || 0;
            ngRow.style.display = r > 0 ? '' : 'none';
            if (r <= 0) {
                ngCats.forEach(function(el){ el.value = '0.00'; });
                ngAuto = true;
            } else if (ngAuto) {
                ngCats.forEach(function(el){ el.value = '0.00'; });
                var def = ngRow.querySelector('[name$="[ng_material]"]');
                if (def) def.value = r.toFixed(2);
            }
            ngRefresh();
        }

        ngCats.forEach(function(el){
            el.addEventListener('input', function(){ ngAuto = false; ngRefresh(); });
        });

        // Auto-fill: ketika reject diisi, accept = issued - reject
        rej.addEventListener('input', function(){
            var r = parseFloat(this.value) || 0;
            var remaining = Math.max(0, issued - r);
            acc.value = remaining.toFixed(2);
            validate();
            ngSync();
        });
        acc.addEventListener('input', validate);

        // Saat halaman dibuka: kalau QC sebelumnya sudah punya rincian, jangan
        // ditimpa -- anggap sudah diisi manual.
        if (isNgRow && ngTotal() > 0) ngAuto = false;
        ngRefresh();
    });

    // Block submit jika ada invalid
    var form = document.querySelector('#qcFormCard form');
    if (form) {
        form.addEventListener('submit', function(e){
            var invalid = document.querySelectorAll('#qcFormCard .is-invalid');
            if (invalid.length > 0) {
                e.preventDefault();
                document.getElementById('qcValidationMsg').textContent = I18N_PR.validation_fix;
            }
        });
    }
})();
</script>

<?php $this->load->view('templates/footer'); ?>
