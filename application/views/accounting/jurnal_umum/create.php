<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('jurnalumum/store'); ?>" method="POST" id="journal-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ju_header_section', 'Header Jurnal') ?></h6>
                <button type="button" class="btn btn-success btn-sm" id="btn-import" disabled>
                    <i class="fas fa-file-excel"></i> <?= lang_line('ju_btn_import_idr_only', 'Impor Detail (Hanya IDR)') ?>
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_journal_no', 'No. Jurnal') ?></label>
                            <input type="text" id="journal_no" name="journal_no" class="form-control"
                                   value="<?= set_value('journal_no', $this->input->get('journal_no') ?? $journal_no); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_post_date', 'Tgl Posting *') ?></label>
                            <input type="date" id="journal_date" name="journal_date" class="form-control"
                                   value="<?= set_value('journal_date', $this->input->get('journal_date') ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_currency', 'Mata Uang *') ?></label>
                            <select name="currency" id="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $curr->code == 'IDR'); ?>>
                                        <?= $curr->code; ?> (<?= $curr->description; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_rate', 'Kurs (Rate) *') ?></label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control"
                                   value="<?= set_value('exchange_rate', '1.0000'); ?>"
                                   min="0.0001" step="0.0001" readonly required>
                            <small id="kurs_info" class="form-text text-muted"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_doc_date', 'Tgl Dokumen') ?> <span id="document_date_req" class="text-danger" style="display:none;">*</span></label>
                            <input type="date" id="document_date" name="document_date" class="form-control"
                                   value="<?= set_value('document_date'); ?>">
                            <small class="form-text text-muted"><?= lang_line('ju_lbl_doc_date_hint', 'Tgl invoice/kwitansi sumber') ?></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_due_date', 'Tgl Pembayaran') ?> <small class="text-muted"><?= lang_line('ju_label_due_date_hint', '(Due Date)') ?></small></label>
                            <input type="date" id="due_date" name="due_date" class="form-control"
                                   value="<?= set_value('due_date'); ?>">
                            <small class="form-text text-muted"><?= lang_line('ju_lbl_due_date_hint', 'Tgl jatuh tempo') ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_tax_invoice_no', 'No. Faktur Pajak') ?> <span id="tax_invoice_req" class="text-danger" style="display:none;">*</span></label>
                            <input type="text" id="tax_invoice_no" name="tax_invoice_no" class="form-control"
                                   value="<?= set_value('tax_invoice_no'); ?>"
                                   placeholder="010.000-25.12345678" maxlength="30">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_description', 'Keterangan *') ?></label>
                            <input type="text" id="description" name="description" class="form-control"
                                   value="<?= set_value('description', $this->input->get('description') ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_pull_prf', 'Tarik dari Payment Request (opsional)') ?></label>
                            <select id="prf_id" name="prf_id" class="form-control select2-prf" style="width:100%">
                                <option value=""><?= lang_line('ju_prf_none', '-- Tanpa Payment Request --') ?></option>
                                <?php foreach (($prf_list ?? []) as $p): ?>
                                    <option value="<?= (int) $p->id ?>"
                                        data-supplier-id="<?= (int) $p->supplier_id ?>"
                                        data-subtotal="<?= (float) $p->subtotal ?>"
                                        data-desc="<?= htmlspecialchars('Payment Request ' . $p->prf_number . ' - ' . ($p->item_descriptions ?? '')) ?>"
                                        data-due="<?= htmlspecialchars($p->due_date ?? '') ?>">
                                        <?= htmlspecialchars($p->prf_number) ?> &mdash; <?= htmlspecialchars($p->supplier_name) ?> &mdash; Rp <?= number_format((float) $p->subtotal, 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('ju_prf_hint', 'Pilih Payment Request yang sudah Approved & belum dibayar untuk auto-isi vendor, nominal, deskripsi & due date.') ?></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_vendor', 'Vendor / Supplier') ?></label>
                            <select name="supplier_id" id="supplier_id" class="form-control select2-vendor" style="width:100%">
                                <option value=""><?= lang_line('ju_vendor_none', '-- Tanpa Vendor --') ?></option>
                                <?php foreach (($vendors ?? []) as $v): ?>
                                    <option value="<?= (int)$v->id ?>"
                                        data-bank="<?= htmlspecialchars($v->bank_name ?? '') ?>"
                                        data-account="<?= htmlspecialchars($v->bank_account ?? '') ?>"
                                        data-pph23="<?= (int)$v->pph23 ?>"
                                        data-rate="<?= (float)$v->pph23_rate ?>"
                                        <?= set_select('supplier_id', $v->id) ?>>
                                        <?= htmlspecialchars($v->nama_supplier) ?><?= $v->pph23 ? ' [PPh23 '.rtrim(rtrim(number_format($v->pph23_rate,2),'0'),'.').'%]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted" id="vendorInfo"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="ppn_enabled" name="ppn_enabled" value="1"
                                   <?= set_checkbox('ppn_enabled', '1'); ?>>
                            <label class="custom-control-label" for="ppn_enabled"><?= lang_line('ju_lbl_ppn_enabled', '<strong>Kena PPN</strong> (otomatis tambah baris PPN)') ?></label>
                        </div>
                    </div>
                </div>
                <div id="ppn_section" class="row bg-light p-3 rounded mx-1 mb-2" style="display:none;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_ppn_type', 'Jenis PPN *') ?></label>
                            <select name="ppn_type" id="ppn_type" class="form-control">
                                <option value="IN"  <?= set_select('ppn_type', 'IN', true); ?>><?= lang_line('ju_opt_ppn_in', 'PPN Masukan (Debit)') ?></option>
                                <option value="OUT" <?= set_select('ppn_type', 'OUT'); ?>><?= lang_line('ju_opt_ppn_out', 'PPN Keluaran (Kredit)') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_dpp', 'DPP *') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="text" id="dpp_amount" name="dpp_amount" class="form-control text-right"
                                       value="<?= set_value('dpp_amount', '0'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_ppn_pct', 'PPN % *') ?></label>
                            <div class="input-group">
                                <input type="number" id="ppn_percent" name="ppn_percent" class="form-control text-right"
                                       value="<?= set_value('ppn_percent', '11'); ?>" min="0" max="100" step="0.01">
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('ju_lbl_ppn_amount', 'Nilai PPN') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="text" id="ppn_amount_display" class="form-control text-right font-weight-bold" value="0" readonly>
                            </div>
                            <small class="form-text text-muted"><?= sprintf(lang_line('ju_ppn_account_hint_tpl', 'Akun: <span id="ppn_account_label" class="font-weight-bold">%s</span> (dari Setting Akun)'), lang_line('ju_ppn_in_label', 'PPN Masukan')) ?></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="wht_enabled" name="wht_enabled" value="1" <?= set_checkbox('wht_enabled', '1'); ?>>
                            <label class="custom-control-label" for="wht_enabled"><?= lang_line('ju_lbl_wht_enabled', '<strong>Kena WHT / PPh Potong</strong> (otomatis tambah baris Hutang PPh + Bank jadi net)') ?></label>
                        </div>
                    </div>
                </div>
                <div id="wht_section" class="row bg-light p-3 rounded mx-1 mb-2" style="display:none;">
                    <div class="col-md-6">
                        <div class="border rounded p-2 mb-2 bg-white">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="wht23_enabled" name="wht23_enabled" value="1" <?= set_checkbox('wht23_enabled', '1'); ?>>
                                <label class="custom-control-label font-weight-bold" for="wht23_enabled"><?= lang_line('ju_lbl_wht23', 'WHT PPh 23') ?></label>
                            </div>
                            <div class="form-row">
                                <div class="col-7">
                                    <label class="small mb-1"><?= lang_line('ju_lbl_wht_dpp', 'DPP (input sendiri)') ?></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="text" id="wht23_dpp" name="wht23_dpp" class="form-control text-right" value="<?= set_value('wht23_dpp', '0'); ?>">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <label class="small mb-1"><?= lang_line('ju_lbl_wht_rate', 'Rate %') ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" id="wht23_rate" name="wht23_rate" class="form-control text-right" value="<?= set_value('wht23_rate', '2'); ?>" min="0" max="100" step="0.01">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted"><?= lang_line('ju_wht23_amount_hint', 'Nilai WHT 23:') ?> Rp <span id="wht23_amount_display" class="font-weight-bold">0</span></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2 mb-2 bg-white">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="wht42_enabled" name="wht42_enabled" value="1" <?= set_checkbox('wht42_enabled', '1'); ?>>
                                <label class="custom-control-label font-weight-bold" for="wht42_enabled"><?= lang_line('ju_lbl_wht42', 'WHT PPh 4(2)') ?></label>
                            </div>
                            <div class="form-row">
                                <div class="col-7">
                                    <label class="small mb-1"><?= lang_line('ju_lbl_wht_dpp', 'DPP (input sendiri)') ?></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="text" id="wht42_dpp" name="wht42_dpp" class="form-control text-right" value="<?= set_value('wht42_dpp', '0'); ?>">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <label class="small mb-1"><?= lang_line('ju_lbl_wht_rate', 'Rate %') ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" id="wht42_rate" name="wht42_rate" class="form-control text-right" value="<?= set_value('wht42_rate', '10'); ?>" min="0" max="100" step="0.01">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted"><?= lang_line('ju_wht42_amount_hint', 'Nilai WHT 4(2):') ?> Rp <span id="wht42_amount_display" class="font-weight-bold">0</span></small>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <small class="text-info"><i class="fas fa-info-circle"></i> <?= lang_line('ju_wht_net_hint', 'Total WHT') ?> Rp <span id="wht_total_display" class="font-weight-bold">0</span> <?= lang_line('ju_wht_net_hint2', 'akan mengurangi baris pembayaran (Bank/Kas) jadi net & ditambahkan sebagai baris Hutang PPh (akun dari Setting Akun).') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= sprintf(lang_line('ju_section_detail_curr_only_tpl', 'Detail Jurnal (Hanya akun dengan mata uang <span class="currency-symbol-text font-weight-bold">%s</span> yang tampil)'), 'IDR') ?></h6>
                <button type="button" id="add-row" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= lang_line('ju_btn_add_row', 'Tambah Baris') ?></button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;"><?= lang_line('ju_th_account_required', 'Akun *') ?></th>
                                <th style="width: 20%;"><?= lang_line('ju_th_debit_required', 'Debit *') ?></th>
                                <th style="width: 20%;"><?= lang_line('ju_th_kredit_required', 'Kredit *') ?></th>
                                <th><?= lang_line('ju_th_desc', 'Deskripsi') ?></th>
                                <th style="width: 5%;"><?= lang_line('ju_th_action', 'Aksi') ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            <?php
                            $imported_details = $this->session->flashdata('imported_details');
                            $details = set_value('details', $imported_details ?? []);
                            if (empty($details)) { $details = [[]]; }

                            foreach ($details as $i => $detail):
                            ?>
                            <tr>
                                <td>
                                    <select name="details[<?= $i; ?>][account_id]" class="form-control account-select" required>
                                        <option value=""><?= lang_line('ju_opt_select_account', '-- Pilih Akun --') ?></option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id; ?>"
                                                    data-currency="<?= $acc->currency; ?>"
                                                    <?= set_select("details[{$i}][account_id]", $acc->id, ($detail['account_id'] ?? '') == $acc->id); ?>>
                                                <?= $acc->account_code; ?> - <?= $acc->account_name; ?> (<?= $acc->currency; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text currency-symbol">Rp</span>
                                        </div>
                                        <input type="text" name="details[<?= $i; ?>][debit]" class="form-control debit-input input-currency text-right"
                                               value="<?= set_value("details[{$i}][debit]", $detail['debit'] ?? 0); ?>"
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text currency-symbol">Rp</span>
                                        </div>
                                        <input type="text" name="details[<?= $i; ?>][kredit]" class="form-control kredit-input input-currency text-right"
                                               value="<?= set_value("details[{$i}][kredit]", $detail['kredit'] ?? 0); ?>"
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="details[<?= $i; ?>][description]" class="form-control"
                                           value="<?= set_value("details[{$i}][description]", $detail['description'] ?? ''); ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th class="text-right"><?= sprintf(lang_line('ju_total_curr_tpl', 'TOTAL (%s)'), '<span class="currency-symbol-text">IDR</span>') ?></th>
                                <th><input type="text" id="total-debit" class="form-control text-right font-weight-bold" readonly></th>
                                <th><input type="text" id="total-kredit" class="form-control text-right font-weight-bold" readonly></th>
                                <th colspan="2" id="balance-status" class="text-center align-middle"></th>
                            </tr>
                            <tr class="bg-gray-200">
                                <th class="text-right"><?= lang_line('ju_total_idr_label', 'TOTAL (dalam IDR)') ?></th>
                                <th><input type="text" id="total-debit-local" class="form-control text-right font-weight-bold" readonly></th>
                                <th><input type="text" id="total-kredit-local" class="form-control text-right font-weight-bold" readonly></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary"><?= lang_line('ju_btn_cancel', 'Batal') ?></a>
            <button type="submit" class="btn btn-primary"><?= lang_line('ju_btn_save_draft', 'Simpan Jurnal (Draft)') ?></button>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td>
            <select name="details[0][account_id]" class="form-control account-select" required>
                <option value=""><?= lang_line('ju_opt_select_account', '-- Pilih Akun --') ?></option>
                <?php foreach($accounts as $acc): ?>
                    <option value="<?= $acc->id; ?>" data-currency="<?= $acc->currency; ?>">
                        <?= $acc->account_code; ?> - <?= $acc->account_name; ?> (<?= $acc->currency; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text currency-symbol">Rp</span>
                </div>
                <input type="text" name="details[0][debit]" class="form-control debit-input input-currency text-right" value="0" required>
            </div>
        </td>
        <td>
             <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text currency-symbol">Rp</span>
                </div>
                <input type="text" name="details[0][kredit]" class="form-control kredit-input input-currency text-right" value="0" required>
            </div>
        </td>
        <td><input type="text" name="details[0][description]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<form id="form-impor" action="<?= base_url('jurnalumum/import'); ?>" method="GET" style="display: none;">
    <input type="hidden" name="journal_no" id="import_journal_no">
    <input type="hidden" name="journal_date" id="import_journal_date">
    <input type="hidden" name="description" id="import_description">
</form>

<script>
const I18N_JUC = {
    select_account:        <?= json_encode(lang_line('ju_opt_select_account', '-- Pilih Akun --')) ?>,
    ppn_in_label:          <?= json_encode(lang_line('ju_ppn_in_label', 'PPN Masukan')) ?>,
    ppn_out_label:         <?= json_encode(lang_line('ju_ppn_out_label', 'PPN Keluaran')) ?>,
    ppn_idr_only:          <?= json_encode(lang_line('ju_js_ppn_idr_only', 'PPN hanya berlaku untuk mata uang IDR. Ubah mata uang ke IDR terlebih dahulu.')) ?>,
    fetching_kurs:         <?= json_encode(lang_line('ju_js_fetching_kurs', 'Mengambil kurs pajak terbaru...')) ?>,
    kurs_auto_tpl:         <?= json_encode(lang_line('ju_js_kurs_auto_tpl', 'Auto dari Kurs Pajak (%s s/d %s). Boleh diubah manual.')) ?>,
    kurs_unavailable:      <?= json_encode(lang_line('ju_js_kurs_unavailable', 'Kurs tidak tersedia, isi manual.')) ?>,
    kurs_failed:           <?= json_encode(lang_line('ju_js_kurs_failed', 'Gagal mengambil kurs, isi manual.')) ?>,
    detail_filter_tpl:     <?= json_encode(lang_line('ju_js_detail_section_filter_tpl', 'Detail Jurnal (Hanya akun dengan mata uang <span class="font-weight-bold">%s</span> yang tampil)')) ?>,
    balanced:              <?= json_encode(lang_line('ju_balanced', 'BALANCE')) ?>,
    unbalanced:            <?= json_encode(lang_line('ju_unbalanced', 'UNBALANCE')) ?>
};

document.addEventListener("DOMContentLoaded", function() {
    let rowIndex = <?= count($details); ?>;
    const detailBody = document.getElementById("detail-body");
    const template = document.getElementById("row-template");
    const currencySelect = document.getElementById("currency");
    const rateInput = document.getElementById("exchange_rate");
    const importButton = document.getElementById("btn-import");
    const detailHeader = document.querySelector('.card-header h6.text-primary');

    // ===== PPN State =====
    const ppnEnabledCb  = document.getElementById('ppn_enabled');
    const ppnSection    = document.getElementById('ppn_section');
    const ppnTypeSelect = document.getElementById('ppn_type');
    const dppInput      = document.getElementById('dpp_amount');
    const ppnPercentInp = document.getElementById('ppn_percent');
    const ppnAmountDsp  = document.getElementById('ppn_amount_display');
    const ppnAcctLabel  = document.getElementById('ppn_account_label');
    const docDateReq    = document.getElementById('document_date_req');
    const taxInvReq     = document.getElementById('tax_invoice_req');
    const PPN_IN_ACCT   = <?= (int) ($ppn_in_account_id ?? 0); ?>;
    const PPN_OUT_ACCT  = <?= (int) ($ppn_out_account_id ?? 0); ?>;

    let autoNumericOptions = {
        decimalCharacter: ',',
        digitGroupSeparator: '.',
        decimalPlaces: 2,
        minimumValue: "0"
    };

    function initializeAutoNumeric(elements) {
        $(elements).each(function() {
            if (AutoNumeric.getAutoNumericElement(this)) {
                AutoNumeric.getAutoNumericElement(this).remove();
            }
            new AutoNumeric(this, autoNumericOptions);
        });
    }

    function updateAutoNumericOptions(currencyCode) {
        if (currencyCode === 'IDR') {
            autoNumericOptions.decimalCharacter = ',';
            autoNumericOptions.digitGroupSeparator = '.';
            autoNumericOptions.decimalPlaces = 2;
        } else {
            autoNumericOptions.decimalCharacter = '.';
            autoNumericOptions.digitGroupSeparator = ',';
            autoNumericOptions.decimalPlaces = 2;
        }
        initializeAutoNumeric(detailBody.querySelectorAll('.input-currency'));
    }

    const masterAccountList = Array.from(template.content.querySelector(".account-select").options);

    function addRow() {
        const newRow = template.content.cloneNode(true);
        newRow.querySelectorAll("input, select").forEach(el => {
            el.name = el.name.replace(/details\[0\]/g, `details[${rowIndex}]`);
        });
        const symbol = getCurrencySymbol(currencySelect.value);
        newRow.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        detailBody.appendChild(newRow);

        filterAccountDropdowns(currencySelect.value);

        const lastRow = detailBody.querySelector('tr:last-child');
        if (lastRow) {
            initializeAutoNumeric(lastRow.querySelectorAll('.input-currency'));
        }

        rowIndex++;
    }

    document.getElementById("add-row").addEventListener("click", addRow);

    detailBody.addEventListener("click", function(e) {
        if (e.target.closest(".remove-row")) {
            e.target.closest("tr").remove();
            calculateTotals();
        }
    });

    detailBody.addEventListener("input", function(e) {
        const row = e.target.closest("tr");
        if (e.target.classList.contains("input-currency")) {
            if (e.target.classList.contains("debit-input")) {
                if (AutoNumeric.getAutoNumericElement(e.target).getNumber() > 0) {
                    AutoNumeric.getAutoNumericElement(row.querySelector(".kredit-input")).set(0);
                }
            } else if (e.target.classList.contains("kredit-input")) {
                if (AutoNumeric.getAutoNumericElement(e.target).getNumber() > 0) {
                    AutoNumeric.getAutoNumericElement(row.querySelector(".debit-input")).set(0);
                }
            }
            calculateTotals();
        }
    });

    rateInput.addEventListener("input", calculateTotals);

    function getPPNAmount() {
        if (!ppnEnabledCb.checked) return 0;
        const dpp = AutoNumeric.getAutoNumericElement(dppInput)
            ? AutoNumeric.getAutoNumericElement(dppInput).getNumber()
            : (parseFloat(dppInput.value) || 0);
        const pct = parseFloat(ppnPercentInp.value) || 0;
        // VAT/PPN: bulat ke rupiah, rounding normal (>=0.5 ke atas, <0.5 ke bawah).
        return Math.round(dpp * pct / 100);
    }

    function refreshPPNDisplay() {
        const amount = getPPNAmount();
        ppnAmountDsp.value = AutoNumeric.format(amount, autoNumericOptions);
        ppnAcctLabel.textContent = (ppnTypeSelect.value === 'OUT') ? I18N_JUC.ppn_out_label : I18N_JUC.ppn_in_label;
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalKredit = 0;
        let rate = parseFloat(rateInput.value) || 0;

        detailBody.querySelectorAll("tr").forEach(row => {
            const debitEl = row.querySelector(".debit-input");
            const kreditEl = row.querySelector(".kredit-input");
            if (!debitEl || !kreditEl) return;
            totalDebit += AutoNumeric.getAutoNumericElement(debitEl).getNumber();
            totalKredit += AutoNumeric.getAutoNumericElement(kreditEl).getNumber();
        });

        const ppnAmt = getPPNAmount();
        if (ppnAmt > 0) {
            if (ppnTypeSelect.value === 'OUT') {
                totalKredit += ppnAmt;
            } else {
                totalDebit += ppnAmt;
            }
        }

        document.getElementById("total-debit").value = AutoNumeric.format(totalDebit, autoNumericOptions);
        document.getElementById("total-kredit").value = AutoNumeric.format(totalKredit, autoNumericOptions);
        document.getElementById("total-debit-local").value = AutoNumeric.format(totalDebit * rate, autoNumericOptions);
        document.getElementById("total-kredit-local").value = AutoNumeric.format(totalKredit * rate, autoNumericOptions);

        const statusEl = document.getElementById("balance-status");
        if (Math.abs(totalDebit - totalKredit) < 0.01 && totalDebit > 0) {
            statusEl.innerHTML = '<span class="badge badge-success">' + I18N_JUC.balanced + '</span>';
        } else {
            statusEl.innerHTML = '<span class="badge badge-danger">' + I18N_JUC.unbalanced + '</span>';
        }
    }

    function togglePPNSection() {
        const on = ppnEnabledCb.checked;
        ppnSection.style.display = on ? '' : 'none';
        docDateReq.style.display = on ? '' : 'none';
        taxInvReq.style.display = on ? '' : 'none';
        document.getElementById('document_date').required  = on;
        document.getElementById('tax_invoice_no').required = on;
        if (on && currencySelect.value !== 'IDR') {
            alert(I18N_JUC.ppn_idr_only);
            ppnEnabledCb.checked = false;
            togglePPNSection();
            return;
        }
        refreshPPNDisplay();
        calculateTotals();
    }

    ppnEnabledCb.addEventListener('change', togglePPNSection);
    ppnTypeSelect.addEventListener('change', function() {
        refreshPPNDisplay();
        calculateTotals();
    });
    ppnPercentInp.addEventListener('input', function() {
        refreshPPNDisplay();
        calculateTotals();
    });
    dppInput.addEventListener('input', function() {
        refreshPPNDisplay();
        calculateTotals();
    });

    // ===== WHT State (PPh 23 & 4(2)) =====
    const whtEnabledCb = document.getElementById('wht_enabled');
    const whtSection   = document.getElementById('wht_section');
    const wht23Cb      = document.getElementById('wht23_enabled');
    const wht23Dpp     = document.getElementById('wht23_dpp');
    const wht23Rate    = document.getElementById('wht23_rate');
    const wht23Dsp     = document.getElementById('wht23_amount_display');
    const wht42Cb      = document.getElementById('wht42_enabled');
    const wht42Dpp     = document.getElementById('wht42_dpp');
    const wht42Rate    = document.getElementById('wht42_rate');
    const wht42Dsp     = document.getElementById('wht42_amount_display');
    const whtTotalDsp  = document.getElementById('wht_total_display');

    function _anNum(el) {
        const a = AutoNumeric.getAutoNumericElement(el);
        return a ? a.getNumber() : (parseFloat(el.value) || 0);
    }
    function getWHTValue(cb, dppEl, rateEl) {
        if (!cb.checked) return 0;
        const dpp = _anNum(dppEl);
        const r = parseFloat(rateEl.value) || 0;
        // WHT: bulat KE BAWAH (floor) ke rupiah bulat.
        return Math.floor(dpp * r / 100);
    }
    function refreshWHTDisplay() {
        const w23 = getWHTValue(wht23Cb, wht23Dpp, wht23Rate);
        const w42 = getWHTValue(wht42Cb, wht42Dpp, wht42Rate);
        wht23Dsp.textContent  = AutoNumeric.format(w23, autoNumericOptions);
        wht42Dsp.textContent  = AutoNumeric.format(w42, autoNumericOptions);
        whtTotalDsp.textContent = AutoNumeric.format(w23 + w42, autoNumericOptions);
    }
    function toggleWHTSection() {
        const on = whtEnabledCb.checked;
        whtSection.style.display = on ? '' : 'none';
        if (on && currencySelect.value !== 'IDR') {
            alert(I18N_JUC.ppn_idr_only);
            whtEnabledCb.checked = false;
            whtSection.style.display = 'none';
            return;
        }
        refreshWHTDisplay();
    }
    whtEnabledCb.addEventListener('change', toggleWHTSection);
    [wht23Cb, wht42Cb].forEach(el => el.addEventListener('change', refreshWHTDisplay));
    [wht23Rate, wht42Rate].forEach(el => el.addEventListener('input', refreshWHTDisplay));
    [wht23Dpp, wht42Dpp].forEach(el => el.addEventListener('input', refreshWHTDisplay));

    function getCurrencySymbol(currencyCode) {
        switch(currencyCode) {
            case 'USD': return '$';
            case 'RMB': return '¥';
            case 'IDR':
            default:
                return 'Rp';
        }
    }

    function filterAccountDropdowns(selectedCurrency) {
        const allSelects = detailBody.querySelectorAll(".account-select");
        allSelects.forEach(select => {
            const selectedValue = select.value;
            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).select2('destroy');
            }
            select.innerHTML = '';
            select.appendChild(new Option(I18N_JUC.select_account, ''));

            masterAccountList.forEach(optionNode => {
                if (optionNode.value === '') return;
                const accountCurrency = optionNode.dataset.currency;
                if (accountCurrency === selectedCurrency) {
                    select.appendChild(optionNode.cloneNode(true));
                }
            });

            select.value = selectedValue || '';

            $(select).select2({ dropdownParent: $(select).closest('td') });
        });
    }

    currencySelect.addEventListener("change", function() {
        const selectedCurrency = this.value;
        const symbol = getCurrencySymbol(selectedCurrency);

        document.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        document.querySelectorAll('.currency-symbol-text').forEach(el => el.textContent = selectedCurrency);

        const detailCardHeader = document.querySelector('#detail-table').closest('.card').querySelector('.card-header h6');
        if(detailCardHeader) {
            detailCardHeader.innerHTML = I18N_JUC.detail_filter_tpl.replace('%s', selectedCurrency);
        }

        if (selectedCurrency !== 'IDR' && ppnEnabledCb.checked) {
            ppnEnabledCb.checked = false;
            togglePPNSection();
        }
        ppnEnabledCb.disabled = (selectedCurrency !== 'IDR');

        if (selectedCurrency !== 'IDR' && whtEnabledCb.checked) {
            whtEnabledCb.checked = false;
            toggleWHTSection();
        }
        whtEnabledCb.disabled = (selectedCurrency !== 'IDR');

        if (selectedCurrency === 'IDR') {
            rateInput.value = '1.0000';
            rateInput.readOnly = true;
            importButton.disabled = false;
            $('#kurs_info').html('');
        } else {
            rateInput.value = '';
            rateInput.readOnly = false;
            importButton.disabled = true;
            $('#kurs_info').removeClass('text-success text-danger').addClass('text-muted')
                .html('<i class="fas fa-spinner fa-spin"></i> ' + I18N_JUC.fetching_kurs);
            $.getJSON('<?= base_url('kurs_pajak/ajax_get_rate'); ?>', { currency: selectedCurrency })
                .done(function(res) {
                    if (res.ok && res.rate) {
                        rateInput.value = res.rate;
                        const msg = I18N_JUC.kurs_auto_tpl
                            .replace('%s', res.effective_date_start)
                            .replace('%s', res.effective_date_end);
                        $('#kurs_info').removeClass('text-muted text-danger').addClass('text-success')
                            .html('<i class="fas fa-check-circle"></i> ' + msg);
                        calculateTotals();
                    } else {
                        $('#kurs_info').removeClass('text-muted text-success').addClass('text-danger')
                            .html('<i class="fas fa-exclamation-triangle"></i> ' + (res.message || I18N_JUC.kurs_unavailable));
                    }
                })
                .fail(function() {
                    $('#kurs_info').removeClass('text-muted text-success').addClass('text-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> ' + I18N_JUC.kurs_failed);
                });
        }

        updateAutoNumericOptions(selectedCurrency);
        filterAccountDropdowns(selectedCurrency);
        calculateTotals();
    });

    document.getElementById("btn-import").addEventListener("click", function() {
        document.getElementById("import_journal_no").value = document.getElementById("journal_no").value;
        document.getElementById("import_journal_date").value = document.getElementById("journal_date").value;
        document.getElementById("import_description").value = document.getElementById("description").value;
        document.getElementById("form-impor").submit();
    });

    $('#journal-form').on('submit', function() {
        $('#detail-table .input-currency').each(function() {
            const anElement = AutoNumeric.getAutoNumericElement(this);
            if (anElement) {
                $(this).val(anElement.getNumber());
            }
        });
        const dppAn = AutoNumeric.getAutoNumericElement(dppInput);
        if (dppAn) {
            $(dppInput).val(dppAn.getNumber());
        }
        [wht23Dpp, wht42Dpp].forEach(function(el) {
            const a = AutoNumeric.getAutoNumericElement(el);
            if (a) { $(el).val(a.getNumber()); }
        });
        return true;
    });

    initializeAutoNumeric(detailBody.querySelectorAll('.input-currency'));
    new AutoNumeric(dppInput, autoNumericOptions);
    new AutoNumeric(wht23Dpp, autoNumericOptions);
    new AutoNumeric(wht42Dpp, autoNumericOptions);
    refreshPPNDisplay();
    togglePPNSection();
    refreshWHTDisplay();
    toggleWHTSection();
    calculateTotals();
    currencySelect.dispatchEvent(new Event('change'));

    // Diekspos supaya bisa dipakai handler Payment Request picker (script block terpisah di bawah).
    window.__ju = { addRow: addRow, calculateTotals: calculateTotals, detailBody: detailBody };
});
</script>

<script>
// Vendor dropdown (Select2 searchable) + info bank/PPh23
$(function () {
    var $v = $('#supplier_id');
    if ($v.length && $.fn.select2) {
        $v.select2({ width: '100%', placeholder: <?= json_encode(lang_line('ju_vendor_ph', 'Cari vendor (nama)...')); ?>, allowClear: true });
    }
    function showVendorInfo() {
        var o = $v.find(':selected');
        var bank = o.data('bank') || '', acc = o.data('account') || '',
            pph = parseInt(o.data('pph23') || 0, 10), rate = o.data('rate') || 0;
        var bits = [];
        if (bank) bits.push('Bank: ' + bank);
        if (acc) bits.push('Rek: ' + acc);
        if (pph === 1) bits.push('Kena PPh 23 ' + rate + '% → WHT otomatis dari DPP');
        $('#vendorInfo').text(bits.join('  •  '));
    }
    $v.on('change', showVendorInfo);
    showVendorInfo();
});
</script>

<script>
// Payment Request picker (Select2 searchable) — tarik vendor, nominal, deskripsi & due date dari Payment Request terpilih.
$(function () {
    var $p = $('#prf_id');
    if ($p.length && $.fn.select2) {
        $p.select2({ width: '100%', placeholder: <?= json_encode(lang_line('ju_prf_ph', 'Cari Payment Request (nomor/vendor)...')); ?>, allowClear: true });
    }
    $p.on('change', function () {
        var prfId = $p.val();
        if (!prfId) return;

        var o = $p.find(':selected');
        var supplierId = o.data('supplier-id') || '';
        var subtotal   = parseFloat(o.data('subtotal')) || 0;
        var desc       = o.data('desc') || '';
        var due        = o.data('due') || '';

        if (supplierId) {
            var $v = $('#supplier_id');
            if ($v.find('option[value="' + supplierId + '"]').length) {
                $v.val(supplierId).trigger('change');
            }
        }
        if (desc) { $('#description').val(desc); }
        if (due)  { $('#due_date').val(due); }

        var ju = window.__ju;
        if (ju && subtotal > 0) {
            ju.detailBody.innerHTML = '';
            ju.addRow();
            ju.addRow();
            var rows = ju.detailBody.querySelectorAll('tr');
            var debitEl  = AutoNumeric.getAutoNumericElement(rows[0].querySelector('.debit-input'));
            var kreditEl = AutoNumeric.getAutoNumericElement(rows[1].querySelector('.kredit-input'));
            if (debitEl)  debitEl.set(subtotal);
            if (kreditEl) kreditEl.set(subtotal);
            ju.calculateTotals();
        }
    });
});
</script>
