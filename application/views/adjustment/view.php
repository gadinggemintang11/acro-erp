<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('adjustment'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Dokumen</h6>
                    <?php if($header->status == 'Draft'): ?>
                    <div>
                        <a href="<?= base_url('adjustment/edit/' . $header->id); ?>" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        <a href="<?= base_url('adjustment/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus dokumen DRAFT ini?');">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            No. Dokumen: <strong><?= $header->doc_no; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tgl. Dokumen: <strong><?= date('d M Y', strtotime($header->doc_date)); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dibuat Oleh: <strong><?= $header->creator_name; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Status:
                            <?php
                                $badge = 'secondary';
                                if ($header->status == 'Draft') $badge = 'warning';
                                if ($header->status == 'Confirmed') $badge = 'success';
                                if ($header->status == 'Cancelled') $badge = 'danger';
                            ?>
                            <strong class="badge badge-<?= $badge; ?> p-2"><?= $header->status; ?></strong>
                        </li>
                        <?php if($header->status == 'Confirmed'): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dikonfirmasi Oleh: <strong><?= $header->confimer_name; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tgl. Konfirmasi: <strong><?= date('d M Y H:i', strtotime($header->updated_at)); ?></strong>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item">
                            Catatan:
                            <p class="mb-0"><strong><?= $header->notes; ?></strong></p>
                        </li>
                    </ul>

                    <?php if($header->status == 'Draft'): ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="<?= base_url('adjustment/cancel/' . $header->id); ?>" class="btn btn-danger btn-icon-split" onclick="return confirm('Anda yakin ingin membatalkan dokumen ini?');">
                                <span class="icon text-white-50"><i class="fas fa-times"></i></span>
                                <span class="text">Batalkan</span>
                            </a>
                            <a href="<?= base_url('adjustment/confirm/' . $header->id); ?>" class="btn btn-success btn-icon-split" onclick="return confirm('ANDA YAKIN INGIN KONFIRMASI?\nStok akan diperbarui dan tidak bisa diubah lagi.');">
                                <span class="icon text-white-50"><i class="fas fa-check"></i></span>
                                <span class="text">Konfirmasi Stok</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detail Item Penyesuaian</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Tipe</th>
                                    <th>Qty</th>
                                    <th>Nilai (Rp)</th>
                                    <th>Batch (Material Out)</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                    <tr class="<?= $d->adjustment_type == 'IN' ? 'table-success' : 'table-danger'; ?>">
                                        <td><?= $d->item_name; ?></td>
                                        <td><strong><?= $d->adjustment_type; ?></strong></td>
                                        <td class="text-right"><?= number_format($d->qty, 4); ?> <?= $d->item_unit; ?></td>
                                        <td class="text-right">
                                            <?php if($d->adjustment_type == 'IN'): ?>
                                                <?= number_format($d->unit_value, 2); ?>
                                            <?php else: ?>
                                                <small>(Dari Batch)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $d->stock_on_hand_id ? 'Batch ID: ' . $d->stock_on_hand_id : '-'; ?>
                                        </td>
                                        <td><?= $d->remarks; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>