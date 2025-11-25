<?php if (!empty($trace_data)): ?>
    <?php foreach ($trace_data as $trace): ?>
        <div class="card shadow-sm mb-4 border-0 animate__animated animate__fadeIn" style="border-left: 5px solid #0dcaf0;">
            <div class="card-header bg-gradient-info text-white py-2 d-flex align-items-center">
                <i class="fas fa-industry me-2"></i>
                <strong>Deep Trace Material</strong>
            </div>
            <div class="card-body bg-light">
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-secondary" style="width: 25%;">Nomor Produksi</th>
                                <td><strong><?= htmlspecialchars($trace['production_number']); ?></strong></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nomor WO</th>
                                <td><?= htmlspecialchars($trace['wo_number']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Proses</th>
                                <td><?= htmlspecialchars($trace['process_name']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nama FG</th>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($trace['fg_name']); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="text-secondary fw-bold mb-2">
                    <i class="fas fa-cubes me-1 text-info"></i> Material Asal
                </h6>

                <?php if (!empty($trace['materials'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle table-hover text-nowrap">
                            <thead style="background-color: #5C5F72; color: #fff;" class="text-center">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Material</th>
                                    <th>Satuan</th>
                                    <th>Qty</th>
                                    <th>Tipe BC</th>
                                    <th>No BC</th>
                                    <th>Tgl BC</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trace['materials'] as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['item_code']); ?></td>
                                        <td><?= htmlspecialchars($m['item_name']); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($m['unit']); ?></td>
                                        <td class="text-end"><?= number_format($m['qty_used'], 2); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($m['bc_type_code']); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($m['bc_number']); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($m['bc_date']); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($m['production_output_id'])): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm btn-show-material-trace shadow-sm"
                                                        data-prod-output="<?= (int)$m['production_output_id']; ?>">
                                                    <i class="fas fa-search"></i> Trace Lagi
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0 py-2 small">
                        <i class="fas fa-exclamation-circle me-1"></i> Tidak ada material asal.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-warning shadow-sm border-0">
        <i class="fas fa-info-circle me-1"></i> Data trace material tidak ditemukan.
    </div>
<?php endif; ?>

<style>
.card {
    border-radius: 10px;
    transition: all 0.25s ease;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.table th, .table td {
    font-size: 13px;
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}
.btn-outline-primary {
    border-color: #5C5F72;
    color: #5C5F72;
    transition: all 0.2s ease;
}
.btn-outline-primary:hover {
    background-color: #5C5F72;
    color: #fff;
}
.bg-gradient-info {
    background: linear-gradient(45deg, #0dcaf0, #5C5F72);
}
.badge {
    font-size: 12px;
    padding: 5px 8px;
    border-radius: 5px;
}
.alert-warning {
    background-color: #fff8e1;
    border-left: 4px solid #ffc107;
}
</style>
