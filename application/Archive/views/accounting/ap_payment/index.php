<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('appayment/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Pembayaran Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pembayaran Utang</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Pembayaran</th>
                            <th>Tgl. Bayar</th>
                            <th>Supplier</th>
                            <th>Akun Bayar</th>
                            <th class="text-right">Total</th>
                            <th>Status Jurnal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('appayment/view/' . $p->id); ?>">
                                    <strong><?= $p->payment_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($p->payment_date)); ?></td>
                            <td><?= $p->nama_supplier; ?></td>
                            <td><?= $p->payment_account_name; ?></td>
                            <td class="text-right"><?= number_format($p->total_amount, 2); ?></td>
                            <td>
                                <span class="badge badge-<?= $p->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                    <?= $p->status; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('appayment/view/' . $p->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($p->status == 'Draft'): ?>
                                <a href="<?= base_url('appayment/delete/' . $p->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus pembayaran DRAFT ini?');">
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