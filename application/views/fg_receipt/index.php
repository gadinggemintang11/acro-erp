<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-dolly-flatbed text-success mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('fg_receipt', 'add')): ?>
            <a href="<?= base_url('fg_receipt/create') ?>" class="btn btn-success shadow-sm btn-load"> <i class="fas fa-plus-circle mr-1"></i> Buat Penerimaan Baru
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('fg_receipt/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No Penerimaan / No Produksi / No WO..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Cari</button>
                    <a href="<?= base_url('fg_receipt'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. Penerimaan</th>
                            <th>Tgl Terima</th>
                            <th>No. Produksi</th>
                            <th>No. WO</th>
                            <th>Penerima</th>
                            <th>Pengkonfirmasi</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($receipts)): foreach($receipts as $rcp): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($rcp->receipt_number); ?></td>
                            <td><?= date('d M Y', strtotime($rcp->receipt_date)); ?></td>
                            <td><?= htmlspecialchars($rcp->production_number); ?></td>
                            <td><?= htmlspecialchars($rcp->wo_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($rcp->receiver_name); ?></td>
                             <td class="text-left"><?= htmlspecialchars($rcp->confirmer_name ?: '-'); ?></td>
                            <td>
                                <?php
                                    $badge_class = 'secondary';
                                    if ($rcp->status == 'Draft') $badge_class = 'warning';
                                    if ($rcp->status == 'Confirmed') $badge_class = 'success';
                                    if ($rcp->status == 'Cancelled') $badge_class = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($rcp->status); ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('fg_receipt/show/'.$rcp->id); ?>" class="btn btn-sm btn-info btn-load" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($rcp->status == 'Draft' && check_access('fg_receipt', 'edit')): ?>
                                <a href="<?= base_url('fg_receipt/edit/'.$rcp->id); ?>" class="btn btn-sm btn-warning btn-load ml-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($rcp->status == 'Draft' && check_access('fg_receipt', 'delete')) || ($rcp->status == 'Confirmed' && check_access('fg_receipt', 'cancel'))): ?>
                                    <?php
                                        $delete_msg = $rcp->status == 'Draft' ? 'Yakin hapus Dokumen Draft ini?' : 'Yakin batalkan Dokumen Confirmed ini? Stok akan dikembalikan.';
                                        $delete_icon = $rcp->status == 'Draft' ? 'fa-trash' : 'fa-times-circle';
                                        $delete_title = $rcp->status == 'Draft' ? 'Hapus' : 'Batal';
                                    ?>
                                <a href="<?= base_url('fg_receipt/destroy/'.$rcp->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="<?= $delete_title; ?>" data-message="<?= $delete_msg; ?>">
                                    <i class="fas <?= $delete_icon; ?>"></i>
                                </a>
                                <?php endif; ?>
                                 <a href="<?= base_url('fg_receipt/export_pdf/'.$rcp->id); ?>" target="_blank" class="btn btn-sm btn-secondary ml-1" title="Export PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data Penerimaan Barang Jadi</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>

<script>
// Skrip tambahan jika diperlukan (misalnya format tanggal)
</script>