<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('stockopname/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Sesi Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Sesi Stock Opname</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Opname</th>
                            <th>Tanggal</th>
                            <th>Tipe Item</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th>Dok. Adjustment</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('stockopname/view/' . $s->id); ?>">
                                    <strong><?= $s->opname_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($s->opname_date)); ?></td>
                            <td><span class="badge badge-info"><?= $s->item_type; ?></span></td>
                            <td><?= $s->notes; ?></td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($s->status == 'Draft') $badge = 'secondary';
                                    if ($s->status == 'Counting') $badge = 'warning';
                                    if ($s->status == 'Completed') $badge = 'success';
                                    if ($s->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $s->status; ?></span>
                            </td>
                            <td>
                                <?php if($s->adjustment_doc): ?>
                                    <a href="<?= base_url('adjustment/view/' . $s->adjustment_header_id); ?>" target="_blank">
                                        <?= $s->adjustment_doc; ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($s->status == 'Draft' || $s->status == 'Counting'): ?>
                                    <a href="<?= base_url('stockopname/counting/' . $s->id); ?>" class="btn btn-warning btn-circle btn-sm" title="Input/Edit Hitungan">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('stockopname/view/' . $s->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Hasil">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if($s->status == 'Draft'): ?>
                                <a href="<?= base_url('stockopname/delete/' . $s->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus sesi DRAFT ini?');">
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