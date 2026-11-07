<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/*
 * Detail PO versi LAPORAN. View ini dipakai 4 modul: Po_report, Po_monitoring,
 * Pmpo_list & Gmpo_list.
 *
 * Halaman ini hanya menampilkan ringkasan + jejak PR->PO->GRN, BUKAN dokumen
 * PO-nya sendiri (tidak ada tombol approve, edit, cetak PO, dsb). User
 * 2026-10-01: "berikan button untuk menuju ke po nya, karna itu hanya ke
 * detail report bukan menuju ke PO aslinya".
 *
 * Tujuan tombol ditentukan dari tipe PO-nya, karena tiap tipe punya modulnya
 * sendiri.
 */
$po_type   = $po_header->type ?? '';
$po_modul  = [
    'produksi'     => ['purchase_order',         'PO Material'],
    'non_produksi' => ['purchase_order_general', 'PO General'],
    'jasa'         => ['purchase_order_jasa',    'PO Jasa'],
    'asset'        => ['purchase_order_asset',   'PO Asset'],
];
$po_route = $po_modul[$po_type][0] ?? null;
$po_label = $po_modul[$po_type][1] ?? 'PO';

// Digate izin menu tujuan: kalau user tidak boleh membuka modul PO-nya,
// tombolnya tidak usah ditampilkan daripada mengantar ke halaman 403.
$bisa_buka_po = $po_route && check_access($po_route, 'view');

// Tombol Kembali dulu selalu ke 'po_report', padahal view ini juga dipakai
// Po_monitoring, Pmpo_list & Gmpo_list -- user yang datang dari PMPO List
// terlempar ke daftar modul lain. Sekarang kembali ke modul asalnya.
$modul_asal = $this->uri->segment(1) ?: 'po_report';
?>
<div class="container-fluid py-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-receipt mr-2"></i><?= $title; ?>
        </h1>
        <div>
            <a href="<?= site_url($modul_asal); ?>" class="btn btn-light border mr-2">
                <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('po_report_back_to_list', 'Kembali'); ?>
            </a>
            <?php if ($bisa_buka_po): ?>
            <a href="<?= site_url($po_route . '/detail/' . $po_header->id); ?>" class="btn btn-primary mr-2">
                <i class="fas fa-external-link-alt mr-1"></i>
                <?= sprintf(lang_line('po_report_open_po', 'Buka Dokumen %s'), $po_label); ?>
            </a>
            <?php endif; ?>
            <a href="<?= site_url('po_report/export_pdf/' . $po_header->id); ?>" class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('po_report_export_pdf', 'Export PDF'); ?>
            </a>
        </div>
    </div>

    <!-- PR & Requester Info (if from PR) -->
    <?php if (!empty($po_header->pr_number)): ?>
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-link mr-2"></i><?= lang_line('po_report_traceability', 'Traceability: PR → PO'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_pr_number', 'PR Number'); ?></label>
                    <p class="h6 font-weight-bold text-primary"><?= htmlspecialchars($po_header->pr_number); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_pr_date', 'PR Date'); ?></label>
                    <p class="h6"><?= $po_header->pr_date ? date('d/m/Y', strtotime($po_header->pr_date)) : '-'; ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_requester', 'Requester'); ?></label>
                    <p class="h6"><strong><?= htmlspecialchars($po_header->requester_name ?? '-'); ?></strong></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_requester_email', 'Requester Email'); ?></label>
                    <p class="h6"><small><?= htmlspecialchars($po_header->requester_email ?? '-'); ?></small></p>
                </div>
            </div>
            <?php if (!empty($po_header->pr_description)): ?>
            <div class="row mt-2">
                <div class="col-md-12">
                    <label class="text-muted small"><?= lang_line('po_report_pr_description', 'Purpose (from PR)'); ?></label>
                    <p><?= htmlspecialchars($po_header->pr_description); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PO Header Info -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold"><?= lang_line('po_report_header_info', 'Informasi PO'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_po_number', 'PO Number'); ?></label>
                    <p class="h6 font-weight-bold"><?= htmlspecialchars($po_header->po_number); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_po_date', 'Tanggal PO'); ?></label>
                    <p class="h6"><?= date('d/m/Y', strtotime($po_header->po_date)); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_status', 'Status'); ?></label>
                    <p>
                        <span class="badge bg-<?= ($po_header->status == 'Open' ? 'warning' : 'success'); ?> fs-6">
                            <?= ucfirst($po_header->status); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_po_currency', 'Currency'); ?></label>
                    <p class="h6"><?= $po_header->currency_code ?? 'IDR'; ?></p>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_days_label', 'Days Information'); ?></label>
                    <p class="h6">
                        <?php
                            $days = $po_header->days_to_warehouse_in ?? 0;
                            if ($po_header->first_grn_date) {
                                $formatted_date = date('d/m/Y', strtotime($po_header->first_grn_date));
                                $format_string = lang_line('po_report_days_warehouse_in_format', 'days (Warehouse In: %s)');
                                $days_text = sprintf($format_string, $formatted_date);
                                echo '<span class="badge bg-success fs-6">' . $days . ' ' . $days_text . '</span>';
                            } else {
                                echo '<span class="badge bg-warning fs-6">' . $days . ' ' . lang_line('po_report_days_not_warehouse_in', 'days (Not yet Warehouse In)') . '</span>';
                            }
                        ?>
                    </p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <label class="text-muted small"><?= lang_line('po_report_supplier_name', 'Supplier'); ?></label>
                    <p class="h6 font-weight-bold"><?= htmlspecialchars($po_header->nama_supplier); ?></p>
                    <p class="text-muted small"><?= htmlspecialchars($po_header->supplier_address ?? ''); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small"><?= lang_line('po_report_remarks', 'Catatan'); ?></label>
                    <p><?= htmlspecialchars($po_header->remarks ?? '-'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- PO Line Items with Receipt Status -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold"><?= lang_line('po_report_line_items', 'Detail Item PO & Status Penerimaan'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($po_details)): ?>
                <p class="text-muted text-center"><?= lang_line('po_report_no_items', 'Tidak ada item dalam PO ini'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('po_report_item_code', 'Kode Material'); ?></th>
                                <th><?= lang_line('po_report_item_name', 'Nama Material'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_ordered_qty', 'Qty PO'); ?></th>
                                <th><?= lang_line('po_report_unit', 'Satuan'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_unit_price', 'Harga Satuan'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_total', 'Total'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_received_qty', 'Qty Terima'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_outstanding_qty', 'Sisa'); ?></th>
                                <th><?= lang_line('po_report_status', 'Status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($po_details as $detail): ?>
                            <tr>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($detail->item_code ?? '-'); ?></small><br>
                                    <small class="text-muted"><?= lang_line('po_report_item_type', 'Type'); ?>: <?= htmlspecialchars($detail->item_type ?? '-'); ?></small>
                                </td>
                                <td><?= htmlspecialchars($detail->item_name ?? '-'); ?></td>
                                <td class="text-right"><strong><?= number_format($detail->qty, 2); ?></strong></td>
                                <td><?= htmlspecialchars($detail->satuan ?? ''); ?></td>
                                <td class="text-right">Rp <?= number_format($detail->base_price, 2); ?></td>
                                <td class="text-right">Rp <?= number_format($detail->line_total, 2); ?></td>
                                <td class="text-right">
                                    <strong><?= number_format($detail->qty_received, 2); ?></strong>
                                </td>
                                <td class="text-right">
                                    <strong class="<?= ($detail->qty_pending > 0 ? 'text-warning' : 'text-success'); ?>">
                                        <?= number_format($detail->qty_pending, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php 
                                        $badge_class = 'secondary';
                                        if ($detail->item_receipt_status == 'Not Received') $badge_class = 'danger';
                                        elseif ($detail->item_receipt_status == 'Partial') $badge_class = 'warning';
                                        elseif ($detail->item_receipt_status == 'Complete') $badge_class = 'success';
                                    ?>
                                    <span class="badge bg-<?= $badge_class; ?>">
                                        <?= $detail->item_receipt_status; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="row mt-3">
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th class="text-right"><?= lang_line('po_report_total_ordered', 'Total Qty PO'); ?>:</th>
                                <td class="text-right"><strong><?php echo number_format(array_sum(array_column($po_details, 'qty')), 2); ?></strong></td>
                            </tr>
                            <tr>
                                <th class="text-right"><?= lang_line('po_report_total_received', 'Total Qty Terima'); ?>:</th>
                                <td class="text-right"><strong class="text-success"><?php echo number_format(array_sum(array_column($po_details, 'qty_received')), 2); ?></strong></td>
                            </tr>
                            <tr class="<?= (array_sum(array_column($po_details, 'qty_pending')) > 0 ? 'table-warning' : ''); ?>">
                                <th class="text-right"><?= lang_line('po_report_total_outstanding', 'Total Sisa'); ?>:</th>
                                <td class="text-right"><strong><?php echo number_format(array_sum(array_column($po_details, 'qty_pending')), 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GRN List -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold"><?= lang_line('po_report_receipt_history', 'Riwayat Penerimaan (GRN)'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($grns)): ?>
                <p class="text-muted text-center"><?= lang_line('po_report_no_receipts', 'Belum ada penerimaan untuk PO ini'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('po_report_grn_number', 'GRN Number'); ?></th>
                                <th><?= lang_line('po_report_grn_date', 'Tanggal'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_grn_items', 'Jumlah Item'); ?></th>
                                <th class="text-right"><?= lang_line('po_report_grn_qty', 'Total Qty'); ?></th>
                                <th><?= lang_line('po_report_status', 'Status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($grns as $grn): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($grn->grn_number); ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($grn->grn_date)); ?></td>
                                <td class="text-right"><?= $grn->num_items; ?></td>
                                <td class="text-right"><strong><?= number_format($grn->total_qty_received, 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-success">
                                        Completed
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
