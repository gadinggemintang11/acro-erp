<div class="container-fluid">
    <?php
        $header = $data['header'];
        $details = $data['details'];
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-eye text-info mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <div>
            <?php if ($header->status == 'Draft'): ?>
                <a href="<?= base_url('Scrap_In/confirm/' . $header->id); ?>" class="btn btn-success btn-confirm" data-message="<?= lang_line('scrap_in_confirm_message', 'Konfirmasi penerimaan ini? Stok scrap akan bertambah.'); ?>">
                    <i class="fas fa-check mr-1"></i> <?= lang_line('scrap_in_confirm_btn', 'Konfirmasi'); ?>
                </a>
                 <a href="<?= base_url('Scrap_In/delete/' . $header->id); ?>" class="btn btn-danger btn-delete ml-1" title="<?= lang_line('scrap_in_delete', 'Hapus'); ?>" data-message="<?= lang_line('scrap_in_confirm_delete', 'Yakin hapus Dokumen Draft ini?'); ?>">
                     <i class="fas fa-trash"></i> <?= lang_line('scrap_in_delete_draft_btn', 'Hapus Draft'); ?>
                 </a>
            <?php elseif ($header->status == 'Confirmed'): ?>
                 <a href="<?= base_url('Scrap_In/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fas fa-print mr-1"></i> <?= lang_line('scrap_in_print_pdf', 'Cetak PDF'); ?>
                 </a>
                 <?php endif; ?>
             <a href="<?= base_url('Scrap_In'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('scrap_in_back_to_list', 'Kembali ke List'); ?>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success"><?= lang_line('scrap_in_header_doc', 'Header Dokumen'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td width="150"><strong><?= lang_line('scrap_in_doc_number', 'No. Dokumen'); ?></strong></td><td width="10">:</td><td><?= htmlspecialchars($header->doc_no); ?></td></tr>
                        <tr><td><strong><?= lang_line('scrap_in_doc_date', 'Tgl Dokumen'); ?></strong></td><td>:</td><td><?= date('d M Y', strtotime($header->doc_date)); ?></td></tr>
                        <tr><td><strong><?= lang_line('scrap_in_status', 'Status'); ?></strong></td><td>:</td>
                            <td>
                                <?php
                                    $badge_class = 'secondary';
                                    $status_text = $header->status;
                                    if ($header->status == 'Draft') {
                                        $badge_class = 'warning';
                                        $status_text = lang_line('scrap_in_status_draft', 'Draft');
                                    }
                                    if ($header->status == 'Confirmed') {
                                        $badge_class = 'success';
                                        $status_text = lang_line('scrap_in_status_confirmed', 'Confirmed');
                                    }
                                ?>
                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($status_text); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                     <table class="table table-sm table-borderless">
                        <tr><td width="150"><strong><?= lang_line('scrap_in_source', 'Sumber'); ?></strong></td><td width="10">:</td>
                            <td>
                                <?php if (isset($header->source_type) && $header->source_type == 'warehouse'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-warehouse mr-1"></i><?= lang_line('scrap_in_warehouse_ng', 'Warehouse NG/Rusak'); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-info"><i class="fas fa-industry mr-1"></i><?= lang_line('scrap_in_from_production', 'Hasil Produksi'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><td><strong><?= lang_line('scrap_in_created_by', 'Dibuat Oleh'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->created_by_name ?: '-'); ?></td></tr>
                        <tr><td><strong><?= lang_line('scrap_in_confirmed_by', 'Dikonfirmasi Oleh'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->confirmed_by_name ?: '-'); ?></td></tr>
                        <tr><td><strong><?= lang_line('scrap_in_notes', 'Catatan'); ?></strong></td><td>:</td><td><?= nl2br(htmlspecialchars($header->notes ?: '-')); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success"><?= lang_line('scrap_in_detail_received', 'Detail Item Diterima'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
<?php $is_warehouse = (isset($header->source_type) && $header->source_type == 'warehouse'); ?>
                <table class="table table-bordered table-striped">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('scrap_in_table_code', 'Kode Scrap'); ?></th>
                            <th><?= lang_line('scrap_in_table_name', 'Nama Scrap'); ?></th>
                            <th><?= lang_line('scrap_in_table_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('scrap_in_table_total_qty', 'Total Qty Diterima'); ?></th>
                            <th><?= $is_warehouse ? lang_line('scrap_in_table_source_damage', 'Sumber Material Rusak') : lang_line('scrap_in_table_source_prod_wo', 'Sumber Produksi / WO'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($details)): foreach($details as $item): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($item['scrap_code']); ?></td>
                            <td><?= htmlspecialchars($item['scrap_name']); ?></td>
                            <td class="text-center"><?= htmlspecialchars($item['satuan_desc']); ?></td>
                            <td class="text-right"><?= (float)$item['qty']; ?></td>
                            <td>
                                <?php if ($is_warehouse): ?>
                                    <?php if (!empty($item['warehouse_sources'])): ?>
                                        <ul class="list-unstyled mb-0 pl-2">
                                            <?php foreach ($item['warehouse_sources'] as $source): ?>
                                                <li class="mb-1">
                                                    <span class="badge badge-secondary text-uppercase"><?= htmlspecialchars($source['source_item_type']); ?></span>
                                                    <strong><?= htmlspecialchars($source['item_code']); ?></strong> - <?= htmlspecialchars($source['item_name']); ?>
                                                    <br/>
                                                    <small class="text-muted">
                                                        <?= lang_line('scrap_in_qty_damaged', 'Qty Rusak'); ?>: <strong><?= (float)$source['qty_damaged']; ?></strong> <?= htmlspecialchars($source['item_satuan']); ?>
                                                        <?php if (!empty($source['damage_reason'])): ?>
                                                            | <?= lang_line('scrap_in_reason_label', 'Alasan'); ?>: <em><?= htmlspecialchars($source['damage_reason']); ?></em>
                                                        <?php endif; ?>
                                                    </small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (!empty($item['sources'])): ?>
                                        <ul class="list-unstyled mb-0 pl-3">
                                            <?php foreach ($item['sources'] as $source): ?>
                                                <li>
                                                    <small>
                                                        <?= htmlspecialchars($source['wo_number'] ?: 'N/A'); ?>
                                                        (<?= lang_line('scrap_in_table_prod_no', 'No. Produksi'); ?>: <?= htmlspecialchars($source['production_number'] ?: 'N/A'); ?>)
                                                        - <?= lang_line('scrap_in_table_qty', 'Qty'); ?>: <?= (float)$source['qty_collected']; ?>
                                                    </small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted"><?= lang_line('scrap_in_no_detail', 'Tidak ada item detail.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
