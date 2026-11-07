<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-dolly-flatbed text-success mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('fg_receipt', 'add')): ?>
            <a href="<?= base_url('fg_receipt/create') ?>" class="btn btn-success shadow-sm btn-load"> <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('fg_receipt_create'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('fg_receipt/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only"><?= lang_line('btn_search'); ?></label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('fg_receipt_search_placeholder', 'Cari No Penerimaan / No Produksi / No WO...'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> <?= lang_line('btn_search'); ?></button>
                    <a href="<?= base_url('fg_receipt'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> <?= lang_line('btn_reset'); ?></a>
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
                            <th><?= lang_line('fg_receipt_number'); ?></th>
                            <th><?= lang_line('fg_receipt_date'); ?></th>
                            <th><?= lang_line('fg_receipt_fg_model', 'Barang Jadi (Model)'); ?></th>
                            <th><?= lang_line('production_wo_number'); ?></th>
                            <th><?= lang_line('fg_receipt_ipo_number', 'No. Sales Order'); ?></th>
                            <th><?= lang_line('fg_receipt_receiver', 'Penerima'); ?></th>
                            <th><?= lang_line('text_status'); ?></th>
                            <th width="180"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($receipts)): foreach($receipts as $rcp): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($rcp->receipt_number); ?></td>
                            <td><?= date('d M Y', strtotime($rcp->receipt_date)); ?></td>
                            <td class="text-left">
                                <?php if (!empty($rcp->fg_name)): ?>
                                    <span class="small text-muted"><?= htmlspecialchars($rcp->fg_code); ?></span><br>
                                    <?= htmlspecialchars($rcp->fg_name); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($rcp->wo_number ?: '-'); ?></td>
                            <td class="small"><?= htmlspecialchars($rcp->ipo_number ?: '-'); ?></td>
                            <td class="text-left"><?= htmlspecialchars($rcp->receiver_name); ?></td>
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
                                <a href="<?= base_url('fg_receipt/show/'.$rcp->id); ?>" class="btn btn-sm btn-info btn-load" title="<?= lang_line('btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($rcp->status == 'Draft' && check_access('fg_receipt', 'edit')): ?>
                                <a href="<?= base_url('fg_receipt/edit/'.$rcp->id); ?>" class="btn btn-sm btn-warning btn-load ml-1" title="<?= lang_line('btn_edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($rcp->status == 'Draft' && check_access('fg_receipt', 'delete')) || ($rcp->status == 'Confirmed' && check_access('fg_receipt', 'cancel'))): ?>
                                    <?php
                                        $delete_msg = $rcp->status == 'Draft' ? lang_line('fg_receipt_delete_confirm', 'Yakin hapus Dokumen Draft ini?') : lang_line('fg_receipt_cancel_confirm', 'Yakin batalkan Dokumen Confirmed ini? Stok akan dikembalikan.');
                                        $delete_icon = $rcp->status == 'Draft' ? 'fa-trash' : 'fa-times-circle';
                                        $delete_title = $rcp->status == 'Draft' ? lang_line('btn_delete') : lang_line('btn_cancel');
                                    ?>
                                <a href="<?= base_url('fg_receipt/destroy/'.$rcp->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="<?= $delete_title; ?>" data-message="<?= $delete_msg; ?>">
                                    <i class="fas <?= $delete_icon; ?>"></i>
                                </a>
                                <?php endif; ?>
                                 <a href="<?= base_url('fg_receipt/export_pdf/'.$rcp->id); ?>" target="_blank" class="btn btn-sm btn-secondary ml-1" title="<?= lang_line('text_pdf'); ?>">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('fg_receipt_no_data'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<script>
// Skrip tambahan jika diperlukan (misalnya format tanggal)
</script>