<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <a href="<?= base_url('cashbank'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> <?= lang_line('cb_btn_back_to_list', 'Kembali ke Daftar') ?>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <!-- Header Info -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('cb_info_title', 'Informasi Transaksi') ?></h6>
                    <div>
                        <a href="<?= base_url('cashbank/export_pdf_detail/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf"></i> <?= lang_line('cb_export_pdf', 'Ekspor PDF') ?>
                        </a>
                        <?php if ($header->status == 'Draft'): ?>
                            <?php if (!empty($can_edit)): ?>
                            <a href="<?= base_url('cashbank/edit/' . $header->id); ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> <?= lang_line('cb_btn_edit', 'Edit') ?>
                            </a>
                            <?php endif; ?>
                            <a href="<?= base_url('cashbank/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('cb_confirm_delete', 'Anda yakin ingin menghapus transaksi DRAFT ini?')), ENT_QUOTES) ?>);">
                                <i class="fas fa-trash"></i> <?= lang_line('cb_btn_delete', 'Hapus') ?>
                            </a>
                            <a href="<?= base_url('cashbank/post/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('cb_confirm_post', "ANDA YAKIN INGIN POSTING TRANSAKSI INI?\nJurnal otomatis akan dibuat dan tidak bisa diubah.")), ENT_QUOTES) ?>);">
                                <i class="fas fa-check-circle"></i> <?= lang_line('cb_btn_post', 'Posting Transaksi') ?>
                            </a>
                        <?php elseif ($header->status == 'Posted' && !empty($can_edit)): ?>
                            <?php if (empty($redraft_block)): ?>
                            <form method="post" action="<?= base_url('cashbank/redraft/' . $header->id); ?>" class="d-inline form-redraft">
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-undo"></i> Redraft</button>
                            </form>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="<?= htmlspecialchars($redraft_block); ?>"><i class="fas fa-undo"></i> Redraft</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($header->status == 'Posted' && !empty($can_edit) && !empty($redraft_block)): ?>
                        <div class="small text-muted mb-2"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($redraft_block); ?></div>
                    <?php endif; ?>
                    <?php if ($header->status == 'Draft' && !empty($journal_header)): ?>
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-undo mr-1"></i> Transaksi hasil <strong>redraft</strong>. Jurnal <strong><?= htmlspecialchars($journal_header->journal_no); ?></strong>
                            berstatus Draft (tidak masuk laporan). Edit bila perlu, lalu klik <strong>Posting Transaksi</strong> — jurnal yang sama diperbarui &amp; diposting ulang.
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('cb_label_doc_no', 'No. Dokumen:') ?></strong><br><?= htmlspecialchars($header->doc_no, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('cb_label_date', 'Tanggal:') ?></strong><br><?= date('d M Y', strtotime($header->doc_date)); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('cb_label_type', 'Tipe:') ?></strong><br>
                            <?php if ($header->transaction_type == 'IN'): ?>
                                <span class="badge badge-success"><?= lang_line('cb_type_in', 'Masuk') ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?= lang_line('cb_type_out', 'Keluar') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3"><strong><?= lang_line('cb_label_status', 'Status:') ?></strong><br>
                            <span class="badge badge-<?= $header->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                <?= htmlspecialchars($header->status, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('cb_label_cashbank_account', 'Akun Kas/Bank:') ?></strong><br><?= htmlspecialchars($header->payment_account_name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('cb_label_currency', 'Mata Uang:') ?></strong><br><?= htmlspecialchars($header->currency, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('cb_label_rate', 'Kurs (Rate):') ?></strong><br><?= number_format((float)$header->exchange_rate, 4); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('cb_label_total', 'Total:') ?></strong><br>
                            <h5 class="font-weight-bold">
                                <?= htmlspecialchars($header->currency, ENT_QUOTES, 'UTF-8'); ?> <?= number_format((float)$header->total_amount, 2); ?>
                            </h5>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12"><strong><?= lang_line('cb_label_description', 'Keterangan:') ?></strong><br><?= nl2br(htmlspecialchars($header->description, ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Akun Lawan -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('cb_section_details', 'Detail Akun Lawan') ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('cb_th_account_code', 'Kode Akun') ?></th>
                                    <th><?= lang_line('cb_th_account_name', 'Nama Akun') ?></th>
                                    <th class="text-right"><?= sprintf(lang_line('cb_th_amount_curr', 'Jumlah (%s)'), htmlspecialchars($header->currency, ENT_QUOTES, 'UTF-8')) ?></th>
                                    <th class="text-right"><?= lang_line('cb_th_amount_idr', 'Jumlah (IDR)') ?></th>
                                    <th><?= lang_line('cb_th_desc', 'Deskripsi') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($details)): ?>
                                    <?php foreach ($details as $d): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d->account_code, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($d->account_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-right"><?= number_format((float)$d->amount, 2); ?></td>
                                            <td class="text-right bg-gray-100"><?= number_format((float)$d->amount_local, 2); ?></td>
                                            <td><?= htmlspecialchars($d->description, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center"><em><?= lang_line('cb_no_details', 'Tidak ada detail akun lawan.') ?></em></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jurnal Entri -->
        <?php if (in_array($header->status, ['Posted', 'Draft'], true) && isset($journal_details) && !empty($journal_header)): ?>
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <?php if ($journal_header->status === 'Posted'): ?>
                        <h6 class="m-0 font-weight-bold text-success">
                            <?= sprintf(lang_line('cb_section_journal', 'Jurnal Entri (Posted) - %s'), htmlspecialchars($journal_header->journal_no, ENT_QUOTES, 'UTF-8')) ?>
                        </h6>
                        <?php else: ?>
                        <h6 class="m-0 font-weight-bold text-warning">
                            Jurnal <?= htmlspecialchars($journal_header->journal_no, ENT_QUOTES, 'UTF-8'); ?> (Draft &mdash; isi lama, diperbarui saat Posting)
                        </h6>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th><?= lang_line('cb_th_account_code', 'Kode Akun') ?></th>
                                        <th><?= lang_line('cb_th_account_name', 'Nama Akun') ?></th>
                                        <th class="text-right"><?= sprintf(lang_line('cb_th_debit_curr', 'Debit (%s)'), htmlspecialchars($header->currency, ENT_QUOTES, 'UTF-8')) ?></th>
                                        <th class="text-right"><?= sprintf(lang_line('cb_th_credit_curr', 'Kredit (%s)'), htmlspecialchars($header->currency, ENT_QUOTES, 'UTF-8')) ?></th>
                                        <th class="text-right"><?= lang_line('cb_th_debit_idr', 'Debit (IDR)') ?></th>
                                        <th class="text-right"><?= lang_line('cb_th_credit_idr', 'Kredit (IDR)') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($journal_details as $jd): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($jd->account_code, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($jd->account_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-right"><?= $jd->debit > 0 ? number_format($jd->debit, 2) : '-'; ?></td>
                                            <td class="text-right"><?= $jd->kredit > 0 ? number_format($jd->kredit, 2) : '-'; ?></td>
                                            <td class="text-right bg-gray-100"><?= $jd->debit_local > 0 ? number_format($jd->debit_local, 2) : '-'; ?></td>
                                            <td class="text-right bg-gray-100"><?= $jd->kredit_local > 0 ? number_format($jd->kredit_local, 2) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.form-redraft').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        var r = prompt('Transaksi ini akan dikembalikan ke DRAFT supaya bisa diedit.\nJurnalnya ikut menjadi Draft (keluar dari laporan) sampai transaksi diposting ulang.\n\nAlasan redraft (wajib):');
        if (r === null) { e.preventDefault(); return; }
        if (r.trim().length < 5) { e.preventDefault(); alert('Alasan redraft minimal 5 karakter.'); return; }
        f.querySelector('input[name=reason]').value = r.trim();
    });
});
</script>
