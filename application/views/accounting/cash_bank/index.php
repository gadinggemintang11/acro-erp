<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('cashbank/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('cb_create_new', 'Buat Transaksi Baru') ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('cb_list_title', 'Daftar Transaksi') ?></h6>
            <div>
                <a href="<?= base_url('cashbank/export_excel'); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> <?= lang_line('cb_export_excel', 'Ekspor Excel') ?>
                </a>
                <a href="<?= base_url('cashbank/export_pdf'); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> <?= lang_line('cb_export_pdf', 'Ekspor PDF') ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('cb_th_doc_no', 'No. Dokumen') ?></th>
                            <th><?= lang_line('cb_th_date', 'Tanggal') ?></th>
                            <th><?= lang_line('cb_th_type', 'Tipe') ?></th>
                            <th><?= lang_line('cb_th_cashbank_account', 'Akun Kas/Bank') ?></th>
                            <th><?= lang_line('cb_th_description', 'Keterangan') ?></th>
                            <th class="text-right"><?= lang_line('cb_th_total', 'Total') ?></th>
                            <th><?= lang_line('cb_th_curr_short', 'CUR') ?></th>
                            <th><?= lang_line('cb_th_status', 'Status') ?></th>
                            <th><?= lang_line('cb_th_action', 'Aksi') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td>
                                    <a href="<?= base_url('cashbank/view/' . $t->id); ?>">
                                        <strong><?= htmlspecialchars($t->doc_no, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </a>
                                </td>
                                <td><?= date('d-m-Y', strtotime($t->doc_date)); ?></td>
                                <td>
                                    <?php if ($t->transaction_type == 'IN'): ?>
                                        <span class="badge badge-success"><?= lang_line('cb_type_in', 'Masuk') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= lang_line('cb_type_out', 'Keluar') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($t->payment_account_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($t->description, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right"><?= number_format((float)$t->total_amount, 2); ?></td>
                                <td><?= htmlspecialchars($t->currency, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge badge-<?= $t->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                        <?= htmlspecialchars($t->status, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('cashbank/view/' . $t->id); ?>"
                                       class="btn btn-info btn-circle btn-sm"
                                       title="<?= lang_line('cb_view_detail', 'Lihat Detail') ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($t->status == 'Draft'): ?>
                                    <a href="<?= base_url('cashbank/delete/' . $t->id); ?>"
                                       class="btn btn-danger btn-circle btn-sm"
                                       title="<?= lang_line('cb_btn_delete', 'Hapus') ?>"
                                       onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('cb_confirm_delete', 'Anda yakin ingin menghapus transaksi DRAFT ini?')), ENT_QUOTES) ?>);">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center"><em><?= lang_line('cb_no_transactions', 'Tidak ada data transaksi.') ?></em></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
