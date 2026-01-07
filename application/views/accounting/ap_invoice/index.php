<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('apinvoice/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Faktur Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Faktur Supplier (Utang Usaha)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tgl. Faktur</th>
                            <th>Supplier</th>
                            <th>Tipe</th>
                            <th class="text-right">Total Tagihan</th>
                            <th class="text-right">Total Terbayar</th>
                            <th>Status Bayar</th>
                            <th>Status Jurnal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('apinvoice/view/' . $inv->id); ?>">
                                    <strong><?= $inv->invoice_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($inv->invoice_date)); ?></td>
                            <td><?= $inv->nama_supplier; ?></td>
                            <td>
                                <?php 
                                    // Ini penanda PO / MIR / Subkon
                                    if ($inv->invoice_type == 'NON_GRN') {
                                        echo '<span class="badge badge-warning">Jasa/Subkon</span>';
                                    } else {
                                        // Gunakan data dari GRN yang sebenarnya
                                        $grn_type = isset($inv->grn_base_on) ? $inv->grn_base_on : '';
                                        if ($grn_type == 'PO') {
                                            echo '<span class="badge badge-primary">PO (Lokal)</span>';
                                        } elseif ($grn_type == 'MIR') {
                                            echo '<span class="badge badge-info">MIR (Impor)</span>';
                                        } else {
                                            // Fallback ke supplier type jika tidak ada data GRN
                                            if ($inv->supplier_type == 'Lokal') {
                                                echo '<span class="badge badge-primary">PO (Lokal)</span>';
                                            } else {
                                                echo '<span class="badge badge-info">MIR (Impor)</span>';
                                            }
                                        }
                                    }
                                ?>
                            </td>
                            <td class="text-right"><?= number_format($inv->total_invoice, 2); ?></td>
                            <td class="text-right"><?= number_format($inv->total_paid, 2); ?></td>
                            <td>
                                <?php
                                    // Ini status bayar
                                    $badge = 'danger';
                                    if ($inv->payment_status == 'Unpaid') $badge = 'danger';
                                    if ($inv->payment_status == 'Partial') $badge = 'warning';
                                    if ($inv->payment_status == 'Paid') $badge = 'success';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $inv->payment_status; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $inv->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                    <?= $inv->status; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('apinvoice/view/' . $inv->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($inv->status == 'Draft'): ?>
                                <a href="<?= base_url('apinvoice/delete/' . $inv->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus faktur DRAFT ini?');">
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