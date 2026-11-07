<?php if ($detail): ?>
<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-invoice"></i> Info Dokumen Penerimaan</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No. Dokumen: <strong><?= $detail->receipt_number; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Tgl. Konfirmasi: <strong><?= date('d M Y H:i', strtotime($detail->confirmed_at)); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Diterima Oleh: <strong><?= $detail->confirmed_by_user; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Qty Diterima: <strong class="h5 text-success"><?= number_format($detail->qty_received, 2); ?> PCS</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-project-diagram"></i> Info Sumber Produksi</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No. Produksi: <strong><?= $detail->production_number; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No. Work Order: <strong><?= $detail->wo_number; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        No. Sales Order: <strong><?= $detail->sc_number; ?></strong>
                    </li>
                    <li class="list-group-item">
                        Size Breakdown:
                        <?php
                            $sizes = json_decode($detail->size_breakdown);
                            if ($sizes && is_array($sizes) && count($sizes) > 0) {
                                echo "<ul class'list-group list-group-flush'>";
                                foreach ($sizes as $size) {
                                    echo "<li class='list-group-item d-flex justify-content-between align-items-center p-1'>";
                                    echo "Size: <strong>" . htmlspecialchars($size->size) . "</strong>";
                                    echo "<span class='badge badge-primary badge-pill'>" . number_format($size->qty) . "</span>";
                                    echo "</li>";
                                }
                                echo "</ul>";
                            } else { echo "-"; }
                        ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cogs"></i> Bahan Baku Digunakan (Traceability)</h6>
            </div>
            <div class="card-body">
                <p>Menampilkan material yang diminta (Issued) untuk WO ini dan yang terpakai (Used) di No. Produksi ini dengan informasi BC dan tanggal kedatangan.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead class="thead-light text-center">
                            <tr>
                                <th rowspan="2" class="align-middle">Kode Material</th>
                                <th rowspan="2" class="align-middle">Nama Material</th>
                                <th colspan="2" class="bg-info text-white">Quantity</th>
                                <th rowspan="2" class="align-middle">Satuan</th>
                                <th colspan="4" class="bg-warning">Informasi BC & Kedatangan</th>
                                <th rowspan="2" class="align-middle">Supplier</th>
                            </tr>
                            <tr>
                                <th class="bg-info text-white">Qty Issued (MR)</th>
                                <th class="bg-info text-white">Qty Terpakai</th>
                                <th class="bg-warning">Tipe BC</th>
                                <th class="bg-warning">No. BC</th>
                                <th class="bg-warning">Tgl. BC</th>
                                <th class="bg-warning">Tgl. Kedatangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($materials)): ?>
                                <?php foreach ($materials as $mat): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($mat['kode_material']); ?></strong></td>
                                        <td><?= htmlspecialchars($mat['nama_material']); ?></td>
                                        <td class="text-right"><?= number_format($mat['issued_qty'], 2); ?></td>
                                        <td class="text-right font-weight-bold text-primary"><?= number_format($mat['qty_terpakai'], 2); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($mat['satuan']); ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-<?= !empty($mat['bc_type_code']) && $mat['bc_type_code'] != '-' ? 'success' : 'secondary' ?>">
                                                <?= htmlspecialchars($mat['bc_type_code'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($mat['bc_number']) && $mat['bc_number'] != '-'): ?>
                                                <?php
                                                    // Gunakan GRN number untuk link (di-encode base64 + rawurlencode untuk URL-safe)
                                                    $bc_link_identifier = !empty($mat['grn_number']) ? $mat['grn_number'] : $mat['bc_number'];
                                                    $bc_link = base_url('BeaCukai/detail/' . rawurlencode(base64_encode($bc_link_identifier)));
                                                ?>
                                                <a href="<?= $bc_link; ?>" 
                                                   target="_blank" class="text-decoration-none">
                                                    <strong><?= htmlspecialchars($mat['bc_number']); ?></strong>
                                                    <i class="fas fa-external-link-alt fa-xs ml-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= !empty($mat['bc_date']) ? '<strong>' . date('d-M-Y', strtotime($mat['bc_date'])) . '</strong>' : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= !empty($mat['arrival_date']) ? '<strong class="text-success">' . date('d-M-Y', strtotime($mat['arrival_date'])) . '</strong>' : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td><?= htmlspecialchars($mat['nama_supplier'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">Data material tidak ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-success mt-2" style="font-size: 0.8rem;">
                    <i class="fas fa-info-circle"></i> <strong>Enhanced Deep Traceability:</strong> 
                    Tabel ini menampilkan informasi lengkap material termasuk BC Number, BC Type, tanggal BC, tanggal kedatangan material, dan supplier. 
                    Data diambil dari transaksi penerimaan material terakhir sebelum tanggal produksi.
                </div>
                
                <!-- Enhanced Export Buttons -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="<?= base_url('LapMutasiFinished/export_bc_traceability_excel/' . $detail->fg_receipt_detail_id); ?>" 
                               class="btn btn-success btn-sm" target="_blank">
                                <i class="fas fa-file-excel"></i> Export BC Excel
                            </a>
                            <a href="<?= base_url('LapMutasiFinished/export_bc_traceability_pdf/' . $detail->fg_receipt_detail_id); ?>" 
                               class="btn btn-danger btn-sm" target="_blank">
                                <i class="fas fa-file-pdf"></i> Export BC PDF
                            </a>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-download"></i> Export lengkap dengan informasi BC dan tanggal kedatangan material
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Data detail penerimaan tidak ditemukan.</div>
<?php endif; ?>