<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div>
            <a href="<?= base_url('jurnalumum/export_excel/' . $header->id); ?>"
               class="btn btn-success btn-sm mr-2"
               title="<?= lang_line('ju_export_excel_tooltip', 'Ekspor ke Excel') ?>">
                <i class="fas fa-file-excel"></i> <?= lang_line('ju_export_excel', 'Excel') ?>
            </a>
            <a href="<?= base_url('jurnalumum/export_pdf/' . $header->id); ?>"
               class="btn btn-danger btn-sm mr-2"
               title="<?= lang_line('ju_export_pdf_tooltip', 'Ekspor ke PDF') ?>">
                <i class="fas fa-file-pdf"></i> <?= lang_line('ju_export_pdf', 'PDF') ?>
            </a>
            <a href="<?= base_url('summary_ledger/voucher_pdf/' . $header->id); ?>"
               target="_blank"
               class="btn btn-outline-danger btn-sm mr-2"
               title="<?= lang_line('ju_voucher_pdf_tooltip', 'Cetak Voucher Disbursement (PDF)') ?>">
                <i class="fas fa-file-invoice-dollar"></i> <?= lang_line('ju_voucher_pdf', 'Voucher PDF') ?>
            </a>
            <a href="<?= base_url('summary_ledger/voucher_excel/' . $header->id); ?>"
               class="btn btn-outline-success btn-sm mr-2"
               title="<?= lang_line('ju_voucher_excel_tooltip', 'Export Voucher Disbursement (Excel)') ?>">
                <i class="fas fa-file-invoice-dollar"></i> <?= lang_line('ju_voucher_excel', 'Voucher Excel') ?>
            </a>
            <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> <?= lang_line('ju_btn_back_to_list', 'Kembali ke Daftar') ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ju_info_title', 'Informasi Jurnal') ?></h6>
                    <?php if($header->status == 'Draft'): ?>
                    <div>
                        <a href="<?= base_url('jurnalumum/edit/' . $header->id); ?>" class="btn btn-warning btn-sm mr-2">
                            <i class="fas fa-edit"></i> <?= lang_line('ju_btn_edit', 'Edit') ?>
                        </a>
                        <a href="<?= base_url('jurnalumum/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('ju_confirm_delete_doc', 'Anda yakin ingin menghapus dokumen DRAFT ini?')), ENT_QUOTES) ?>);">
                            <i class="fas fa-trash"></i> <?= lang_line('ju_btn_delete', 'Hapus') ?>
                        </a>
                        <a href="<?= base_url('jurnalumum/post/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('ju_confirm_post', "ANDA YAKIN INGIN POSTING JURNAL INI?\nJurnal yang sudah di-posting tidak bisa diubah atau dihapus.")), ENT_QUOTES) ?>);">
                            <i class="fas fa-check-circle"></i> <?= lang_line('ju_btn_post', 'Posting Jurnal') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_journal_no', 'No. Jurnal:') ?></strong><br>
                            <?= $header->journal_no; ?>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_post_date', 'Tgl Posting:') ?></strong><br>
                            <?= date('d M Y', strtotime($header->journal_date)); ?>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_status', 'Status:') ?></strong><br>
                            <?php
                                $badge = 'secondary';
                                if ($header->status == 'Draft') $badge = 'warning';
                                if ($header->status == 'Posted') $badge = 'success';
                                if ($header->status == 'Cancelled') $badge = 'danger';
                            ?>
                            <span class="badge badge-<?= $badge; ?> p-2"><?= $header->status; ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_source', 'Sumber:') ?></strong><br>
                            <?= $header->source_module; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong><?= lang_line('ju_label_description', 'Keterangan:') ?></strong><br>
                            <?= $header->description; ?>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_currency', 'Mata Uang:') ?></strong><br>
                            <?= $header->currency; ?>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_rate', 'Kurs (Rate):') ?></strong><br>
                            <?= number_format((float)$header->exchange_rate, 4); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_doc_date', 'Tgl Dokumen:') ?></strong><br>
                            <?= !empty($header->document_date) ? date('d M Y', strtotime($header->document_date)) : '<em class="text-muted">-</em>'; ?>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('ju_label_due_date', 'Tgl Pembayaran') ?> <small class="text-muted"><?= lang_line('ju_label_due_date_hint', '(Due Date)') ?></small>:</strong><br>
                            <?= !empty($header->due_date) ? date('d M Y', strtotime($header->due_date)) : '<em class="text-muted">-</em>'; ?>
                        </div>
                        <div class="col-md-6">
                            <strong><?= lang_line('ju_label_tax_invoice_no', 'No. Faktur Pajak:') ?></strong><br>
                            <?= !empty($header->tax_invoice_no) ? htmlspecialchars($header->tax_invoice_no) : '<em class="text-muted">-</em>'; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <strong><?= lang_line('ju_label_ppn', 'PPN:') ?></strong><br>
                            <?php if (!empty($header->ppn_enabled)): ?>
                                <span class="badge badge-info p-2">
                                    <?= ($header->ppn_type === 'OUT') ? lang_line('ju_ppn_out_label', 'PPN Keluaran') : lang_line('ju_ppn_in_label', 'PPN Masukan'); ?>
                                    (<?= rtrim(rtrim(number_format((float)$header->ppn_percent, 2, '.', ''), '0'), '.'); ?>%)
                                </span>
                                &nbsp; <?= lang_line('ju_ppn_dpp_label', 'DPP:') ?> <strong>Rp <?= number_format((float)$header->dpp_amount, 2); ?></strong>
                                &nbsp;|&nbsp; <?= lang_line('ju_ppn_amount_label', 'Nilai PPN:') ?> <strong>Rp <?= number_format((float)$header->ppn_amount, 2); ?></strong>
                            <?php else: ?>
                                <em class="text-muted"><?= lang_line('ju_no_ppn', 'Tidak kena PPN') ?></em>
                            <?php endif; ?>
                        </div>
                    </div>
                     <?php if($header->status == 'Posted'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong><?= lang_line('ju_label_created_by', 'Dibuat Oleh:') ?></strong><br>
                            <?= $header->creator_name; ?>
                        </div>
                        <div class="col-md-6">
                            <strong><?= lang_line('ju_label_posted_by', 'Diposting Oleh:') ?></strong><br>
                            <?= sprintf(lang_line('ju_posted_at_tpl', '%s pada %s'), $header->poster_name, date('d M Y H:i', strtotime($header->posted_at))) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ju_section_detail', 'Detail Jurnal') ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2" class="align-middle"><?= lang_line('ju_th_account_code', 'Kode Akun') ?></th>
                                    <th rowspan="2" class="align-middle"><?= lang_line('ju_th_account_name', 'Nama Akun') ?></th>
                                    <th colspan="2" class="text-center"><?= sprintf(lang_line('ju_th_trx_curr', 'Transaksi (%s)'), $header->currency) ?></th>
                                    <th colspan="2" class="text-center"><?= lang_line('ju_th_local_idr', 'Lokal (IDR)') ?></th>
                                    <th rowspan="2" class="align-middle"><?= lang_line('ju_th_desc', 'Deskripsi') ?></th>
                                </tr>
                                <tr>
                                    <th class="text-right"><?= lang_line('ju_th_debit', 'Debit') ?></th>
                                    <th class="text-right"><?= lang_line('ju_th_kredit', 'Kredit') ?></th>
                                    <th class="text-right"><?= lang_line('ju_th_debit_idr', 'Debit (IDR)') ?></th>
                                    <th class="text-right"><?= lang_line('ju_th_kredit_idr', 'Kredit (IDR)') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                <tr <?= !empty($d->is_ppn_line) ? 'class="table-info"' : ''; ?>>
                                    <td>
                                        <?= $d->account_code; ?>
                                        <?php if (!empty($d->is_ppn_line)): ?>
                                            <span class="badge badge-info ml-1" title="<?= lang_line('ju_ppn_line_tooltip', 'Baris PPN otomatis') ?>"><?= lang_line('ju_ppn_line_badge', 'PPN') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $d->account_name; ?></td>
                                    <td class="text-right"><?= $d->debit > 0 ? number_format($d->debit, 2) : '-'; ?></td>
                                    <td class="text-right"><?= $d->kredit > 0 ? number_format($d->kredit, 2) : '-'; ?></td>
                                    <td class="text-right bg-gray-100"><?= $d->debit_local > 0 ? number_format($d->debit_local, 2) : '-'; ?></td>
                                    <td class="text-right bg-gray-100"><?= $d->kredit_local > 0 ? number_format($d->kredit_local, 2) : '-'; ?></td>
                                    <td><?= $d->description; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-200">
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right"><?= sprintf(lang_line('ju_total_curr_tpl', 'TOTAL (%s)'), $header->currency) ?></td>

                                    <td class="text-right"><?= number_format($header->total_debit, 2); ?></td>
                                    <td class="text-right"><?= number_format($header->total_kredit, 2); ?></td>
                                    <td class="text-right"><?= number_format($header->total_debit_local, 2); ?></td>
                                    <td class="text-right"><?= number_format($header->total_kredit_local, 2); ?></td>
                                    <td class="text-center">
                                        <?php if($header->total_debit == $header->total_kredit): ?>
                                            <span class="badge badge-success"><?= lang_line('ju_balanced', 'BALANCE') ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><?= lang_line('ju_unbalanced', 'UNBALANCE') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
