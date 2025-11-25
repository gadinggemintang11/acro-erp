<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('arreceipt/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Catat Penerimaan</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Penerimaan Piutang</h6>
            <div>
                <a href="<?= base_url('arreceipt/export_excel'); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Ekspor Excel
                </a>
                <a href="<?= base_url('arreceipt/export_pdf'); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Ekspor PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Penerimaan</th>
                            <th>Tgl. Terima</th>
                            <th>Customer</th>
                            <th>Akun Terima</th>
                            <th class="text-right">Total (CUR)</th>
                            <th>CUR</th>
                            <th>Status Jurnal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('arreceipt/view/' . $r->id); ?>">
                                    <strong><?= $r->receipt_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($r->receipt_date)); ?></td>
                            <td><?= $r->nama_customer; ?></td>
                            <td><?= $r->payment_account_name; ?></td>
                            <td class="text-right"><?= number_format($r->total_amount, 2); ?></td>
                            <td><?= $r->currency; ?></td>
                            <td>
                                <span class="badge badge-<?= $r->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                    <?= $r->status; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('arreceipt/view/' . $r->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($r->status == 'Draft'): ?>
                                <a href="<?= base_url('arreceipt/delete/' . $r->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus penerimaan DRAFT ini?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>