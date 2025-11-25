<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('cashbank/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Transaksi Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Transaksi</h6>
            <div>
                <a href="<?= base_url('cashbank/export_excel'); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Ekspor Excel
                </a>
                <a href="<?= base_url('cashbank/export_pdf'); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Ekspor PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Dokumen</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Akun Kas/Bank</th>
                            <th>Keterangan</th>
                            <th class="text-right">Total</th>
                            <th>CUR</th>
                            <th>Status</th>
                            <th>Aksi</th>
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
                                        <span class="badge badge-success">Masuk</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Keluar</span>
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
                                       title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($t->status == 'Draft'): ?>
                                    <a href="<?= base_url('cashbank/delete/' . $t->id); ?>" 
                                       class="btn btn-danger btn-circle btn-sm" 
                                       title="Hapus" 
                                       onclick="return confirm('Anda yakin ingin menghapus transaksi DRAFT ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center"><em>Tidak ada data transaksi.</em></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
