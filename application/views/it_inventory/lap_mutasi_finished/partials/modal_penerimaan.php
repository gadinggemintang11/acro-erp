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
                        No. Sales Contract: <strong><?= $detail->sc_number; ?></strong>
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
                <p>Menampilkan material yang diminta (Issued) untuk WO ini dan yang terpakai (Used) di No. Produksi ini.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover" style="font-size: 0.9rem;">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>Kode Material</th>
                                <th>Nama Material</th>
                                <th>Qty Issued (MR)</th>
                                <th>Qty Terpakai (Produksi)</th>
                                <th>Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($materials)): ?>
                                <?php foreach ($materials as $mat): ?>
                                    <tr>
                                        <td><?= $mat['kode_material']; ?></td>
                                        <td><?= $mat['nama_material']; ?></td>
                                        <td class="text-right"><?= number_format($mat['issued_qty'], 2); ?></td>
                                        <td class="text-right font-weight-bold"><?= number_format($mat['qty_terpakai'], 2); ?></td>
                                        <td class="text-center"><?= $mat['satuan']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Data material tidak ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-2" style="font-size: 0.8rem;">
                    <strong>Info BC Material:</strong> Untuk melacak No. BC material, diperlukan penelusuran lebih dalam per-batch material di modul Laporan Mutasi Bahan Baku.
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Data detail penerimaan tidak ditemukan.</div>
<?php endif; ?>