<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 text-gray-800 mb-0">
            <i class="fas fa-file-contract text-primary me-2"></i><?= lang_line('subkon_order_detail_title', 'Detail Kontrak Subkon'); ?>: <?= $header->subkon_order_no; ?>
        </h3>
        <div>
            <a href="<?= base_url('subkon_order/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm me-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="<?= base_url('subkon_order'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> <?= lang_line('subkon_order_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?= $this->load->view('templates/partials/alerts', [], true); ?>

    <!-- HEADER KONTRAK -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light py-2">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-info-circle"></i> <?= lang_line('subkon_order_header_info', 'Header Kontrak'); ?></h6>
        </div>
        <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
                <tr><th style="width: 180px;"><?= lang_line('subkon_order_contract_number', 'Nomor Kontrak'); ?></th><td><?= $header->subkon_order_no; ?></td></tr>
                <tr><th><?= lang_line('subkon_order_order_date', 'Tanggal Order'); ?></th><td><?= $header->order_date; ?></td></tr>
                <tr><th><?= lang_line('subkon_order_supplier', 'Supplier'); ?></th><td><?= $header->nama_supplier; ?></td></tr>
                <tr><th><?= lang_line('subkon_order_tax_percent', 'Pajak (%)'); ?></th><td><?= number_format($header->pajak_persen, 2); ?>%</td></tr>
                <tr><th><?= lang_line('subkon_order_status', 'Status'); ?></th>
                    <td>
                        <?php if ($header->status == 'Confirmed'): ?>
                            <span class="badge bg-success"><?= lang_line('subkon_order_status_confirmed', 'Confirmed'); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= lang_line('subkon_order_status_draft', 'Draft'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><th><?= lang_line('subkon_order_notes', 'Catatan'); ?></th><td><?= htmlspecialchars($header->notes ?? '-'); ?></td></tr>
            </table>
        </div>
    </div>

    <!-- ITEM HASIL -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-cubes"></i> <?= lang_line('subkon_order_result_items', 'Item Hasil'); ?></h6>
            <?php if ($header->status == 'Draft'): ?>
                <a href="<?= base_url('subkon_order/confirm/'.$header->id); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-check"></i> <?= lang_line('subkon_order_confirm_contract', 'Konfirmasi Kontrak'); ?>
                </a>
            <?php else: ?>
                <a href="<?= base_url('subkon_order/unconfirm/'.$header->id); ?>" class="btn btn-warning btn-sm">
                    <i class="fas fa-undo"></i> <?= lang_line('subkon_order_cancel_confirm', 'Batalkan Konfirmasi'); ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle table-hover" id="tableHasil">
                    <thead class="text-center text-white" style="background-color:#5C5F72;">
                        <tr>
                            <th><?= lang_line('subkon_order_item_no', 'No'); ?></th>
                            <th><?= lang_line('subkon_order_item_name', 'Nama Item Hasil'); ?></th>
                            <th><?= lang_line('subkon_order_qty', 'Qty'); ?></th>
                            <th><?= lang_line('subkon_order_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('subkon_order_service_per_pcs', 'Jasa per pcs (Rp)'); ?></th>
                            <th><?= lang_line('subkon_order_total_service_item', 'Total Jasa'); ?></th>
                            <th><?= lang_line('subkon_order_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        if (empty($output_items)): ?>
                            <tr><td colspan="7" class="text-center text-muted"><?= lang_line('subkon_order_no_items', 'Belum ada item hasil'); ?></td></tr>
                        <?php else: 
                            $no=1; 
                            foreach ($output_items as $out): 
                                $grand_total += $out->total_jasa_item;
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= htmlspecialchars($out->item_name); ?></td>
                                <td class="text-end"><?= number_format($out->qty, 2); ?></td>
                                <td class="text-center"><?= $out->satuan; ?></td>
                                <td class="text-end"><?= number_format($out->jasa_per_pcs, 2); ?></td>
                                <td class="text-end text-primary fw-bold"><?= number_format($out->total_jasa_item, 2); ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('subkon_order/delete_output_item/'.$header->id.'/'.$out->id); ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('<?= lang_line('subkon_order_delete_confirm', 'Hapus item hasil ini?'); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php if (!empty($bom_items_grouped[$out->id])): ?>
                                <tr class="bg-light">
                                    <td></td>
                                    <td colspan="6">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th><?= lang_line('subkon_order_material_code', 'Kode Material'); ?></th>
                                                    <th><?= lang_line('subkon_order_material_name', 'Nama Material'); ?></th>
                                                    <th><?= lang_line('subkon_order_qty_per_pcs', 'Qty / pcs'); ?></th>
                                                    <th><?= lang_line('subkon_order_total_qty', 'Total Qty'); ?></th>
                                                    <th><?= lang_line('subkon_order_unit', 'Satuan'); ?></th>
                                                    <th><?= lang_line('subkon_order_actions', 'Aksi'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bom_items_grouped[$out->id] as $bom): ?>
                                                    <tr>
                                                        <td><?= $bom->item_code; ?></td>
                                                        <td><?= $bom->item_name; ?></td>
                                                        <td class="text-end"><?= number_format($bom->qty_per_pcs, 3); ?></td>
                                                        <td class="text-end"><?= number_format($bom->total_qty, 3); ?></td>
                                                        <td class="text-center"><?= $bom->satuan; ?></td>
                                                        <td class="text-center">
                                                            <a href="<?= base_url('subkon_order/delete_bom_item/'.$header->id.'/'.$bom->id); ?>" 
                                                               class="btn btn-outline-danger btn-sm"
                                                               onclick="return confirm('<?= lang_line('subkon_order_delete_bom_confirm', 'Hapus BOM ini?'); ?>')">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php 
                        $pajak = $header->pajak_persen ? ($grand_total * ($header->pajak_persen / 100)) : 0;
                        $grand_final = $grand_total + $pajak;
                    ?>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="5" class="text-end"><?= lang_line('subkon_order_subtotal_service', 'Subtotal Jasa'); ?></th>
                            <th class="text-end"><?= number_format($grand_total, 2); ?></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end"><?= lang_line('subkon_order_tax', 'Pajak'); ?> (<?= number_format($header->pajak_persen, 2); ?>%)</th>
                            <th class="text-end text-danger"><?= number_format($pajak, 2); ?></th>
                            <th></th>
                        </tr>
                        <tr class="table-primary">
                            <th colspan="5" class="text-end fw-bold"><?= lang_line('subkon_order_grand_total', 'Grand Total'); ?></th>
                            <th class="text-end fw-bold text-primary"><?= number_format($grand_final, 2); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.table th, .table td { font-size: 13px; vertical-align: middle; }
tfoot th { font-weight: 600; font-size: 13px; }
.table-primary { background-color: #eaf3ff !important; }
.btn-outline-danger { font-size: 12px; }
</style>
