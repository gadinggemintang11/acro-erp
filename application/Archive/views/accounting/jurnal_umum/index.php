<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('jurnalumum/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Jurnal Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Jurnal</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Jurnal</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($journals as $j): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('jurnalumum/view/' . $j->id); ?>">
                                    <strong><?= $j->journal_no; ?></strong>
                                </a>
                            </td>
                            
                            <td><?= date('d-m-Y', strtotime($j->journal_date)); ?></td>
                            
                            <td><?= $j->description; ?></td>
                            <td class="text-right"><?= number_format($j->total_debit, 2); ?></td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($j->status == 'Draft') $badge = 'warning';
                                    if ($j->status == 'Posted') $badge = 'success';
                                    if ($j->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $j->status; ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('jurnalumum/view/' . $j->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($j->status == 'Draft'): ?>
                                <a href="<?= base_url('jurnalumum/delete/' . $j->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus jurnal DRAFT ini?');">
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