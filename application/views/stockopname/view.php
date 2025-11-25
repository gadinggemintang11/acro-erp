<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('stockopname'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong class="text-primary">No. Dokumen:</strong><br>
                    <?= $header->opname_no; ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary">Tanggal:</strong><br>
                    <?= date('d M Y', strtotime($header->opname_date)); ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary">Status:</strong><br>
                    <span class="badge badge-success" style="font-size: 1rem;"><?= $header->status; ?></span>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary">Dok. Penyesuaian:</strong><br>
                    <?php if($header->adjustment_doc): ?>
                        <a href="<?= base_url('adjustment/view/' . $header->adjustment_header_id); ?>" target="_blank" class="btn btn-success btn-sm">
                            <i class="fas fa-file-alt"></i> <?= $header->adjustment_doc; ?>
                        </a>
                    <?php else: ?>
                        <span class="badge badge-secondary">Tidak ada selisih</span>
                    <?php endif; ?>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <strong class="text-primary">Catatan:</strong><br>
                    <?= $header->notes; ?>
                </div>
                <?php if($header->status == 'Completed'): ?>
                <div class="col-md-6">
                    <strong class="text-primary">Dikonfirmasi Oleh:</strong><br>
                    <?= $header->confimer_name; ?> pada <?= date('d M Y H:i', strtotime($header->confirmed_at)); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Hasil Akhir Stock Opname</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th>Nama Item</th>
                            <th>Satuan</th>
                            <th>Qty Sistem</th>
                            <th>Qty Fisik</th>
                            <th>Selisih</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($details as $d): 
                            $color = 'secondary';
                            if ($d->selisih > 0) $color = 'success';
                            if ($d->selisih < 0) $color = 'danger';
                        ?>
                        <tr>
                            <td><?= $d->item_name; ?></td>
                            <td class="text-center"><?= $d->item_unit; ?></td>
                            <td class="text-right"><?= (float)$d->qty_sistem; ?></td>
                            <td class="text-right"><?= ($d->qty_fisik !== NULL) ? (float)$d->qty_fisik : '<i>(Belum diisi)</i>'; ?></td>
                            <td class="text-right font-weight-bold">
                                <span class="text-<?= $color; ?>"><?= (float)$d->selisih; ?></span>
                            </td>
                            <td><?= $d->notes; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>