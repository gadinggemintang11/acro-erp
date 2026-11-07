<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-exchange-alt text-primary mr-2"></i><?= $title ?>
        </h3>
        <a href="<?= base_url('stock_transfer'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> <?= lang_line('co_back', 'Kembali'); ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('st_th_number', 'No. Transfer'); ?></small>
                    <strong class="text-primary"><?= htmlspecialchars($header->transfer_number) ?></strong>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('label_date'); ?></small>
                    <strong><?= date('d M Y', strtotime($header->transfer_date)) ?></strong>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('st_th_from', 'Dari'); ?></small>
                    <span class="badge badge-<?= $header->from_category === 'Produksi' ? 'dark' : 'secondary' ?>" style="font-size:0.9rem;"><?= htmlspecialchars($header->from_category) ?></span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('st_th_to', 'Ke'); ?></small>
                    <span class="badge badge-<?= $header->to_category === 'Produksi' ? 'dark' : 'secondary' ?>" style="font-size:0.9rem;"><?= htmlspecialchars($header->to_category) ?></span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('adjustment_created_by'); ?></small>
                    <strong><?= htmlspecialchars($header->created_by_name ?? '-') ?></strong>
                    <?php if ($header->created_at): ?>
                        <br><small class="text-muted"><?= date('d M Y H:i', strtotime($header->created_at)) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($header->notes)): ?>
            <div class="row mt-3">
                <div class="col-md-12">
                    <small class="text-muted d-block"><?= lang_line('st_lbl_notes', 'Alasan / Catatan'); ?></small>
                    <?= nl2br(htmlspecialchars($header->notes)) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('st_item_list', 'Barang yang Dipindah'); ?></h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('sgr_th_item_code', 'Kode'); ?></th>
                            <th><?= lang_line('co_th_item_name', 'Nama Barang'); ?></th>
                            <th><?= lang_line('co_th_unit', 'Satuan'); ?></th>
                            <th class="text-right"><?= lang_line('st_th_qty_transfer', 'Qty Dipindah'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d->item_code ?? '-') ?></td>
                            <td><?= htmlspecialchars($d->item_name ?? '-') ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-') ?></td>
                            <td class="text-right"><?= number_format((float)$d->qty, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
