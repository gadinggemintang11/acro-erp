<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('adjustment/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Dokumen Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Dokumen Penyesuaian Stok</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No. Dokumen</th>
                            <th>Tanggal</th>
                            <th>Catatan</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustments as $adj): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('adjustment/view/' . $adj->id); ?>">
                                    <strong><?= $adj->doc_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($adj->doc_date)); ?></td>
                            <td><?= $adj->notes; ?></td>
                            <td><?= $adj->creator_name; ?></td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($adj->status == 'Draft') $badge = 'warning';
                                    if ($adj->status == 'Confirmed') $badge = 'success';
                                    if ($adj->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $adj->status; ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('adjustment/view/' . $adj->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($adj->status == 'Draft'): ?>
                                <a href="<?= base_url('adjustment/edit/' . $adj->id); ?>" class="btn btn-warning btn-circle btn-sm" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="<?= base_url('adjustment/delete/' . $adj->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus dokumen DRAFT ini?');">
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