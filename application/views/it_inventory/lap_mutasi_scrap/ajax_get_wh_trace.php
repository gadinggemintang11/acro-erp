<?php if (!empty($scrap_sources)): ?>
    <?php foreach ($scrap_sources as $src): ?>
        <div class="card shadow-sm mb-4 border-0 animate__animated animate__fadeIn" style="border-left: 5px solid #198754;">
            <!-- Header -->
            <div class="card-header bg-gradient-success text-white py-2 px-3 d-flex justify-content-between align-items-center">
                <div class="fw-semibold">
                    <i class="fas fa-folder-open me-2"></i> Deep Trace Produksi
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="$(this).closest('.card').fadeOut(300, function(){ $(this).remove(); });"></button>
            </div>

            <!-- Body -->
            <div class="card-body bg-light">

                <!-- Info Produksi -->
                <h6 class="text-secondary fw-bold mb-3">
                    <i class="fas fa-warehouse text-success me-1"></i> Asal Scrap Warehouse
                </h6>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-borderless align-middle">
                        <tbody>
                            <tr>
                                <th class="text-secondary" style="width: 180px;">Nomor Produksi</th>
                                <td><strong><?= htmlspecialchars($src['production_number'] ?? '-') ?></strong></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nomor WO</th>
                                <td><?= htmlspecialchars($src['wo_number'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Proses Produksi</th>
                                <td><?= htmlspecialchars($src['process_name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nama FG</th>
                                <td><span class="badge bg-success text-white"><?= htmlspecialchars($src['fg_name'] ?? '-') ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Material -->
                <h6 class="text-secondary fw-bold mb-2">
                    <i class="fas fa-cubes text-success me-1"></i> Material Asal Produksi
                </h6>

                <?php if (!empty($src['materials'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle text-nowrap shadow-sm">
                            <thead class="text-center text-white" style="background-color:#5C5F72;">
                                <tr>
                                    <th>Kode Material</th>
                                    <th>Nama Material</th>
                                    <th>Satuan</th>
                                    <th>Qty Digunakan</th>
                                    <th>Tipe BC</th>
                                    <th>No BC</th>
                                    <th>Tgl BC</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($src['materials'] as $m): ?>
                                    <?php
                                        $bc_type  = htmlspecialchars($m['bc_type_code'] ?? '-');
                                        $bc_no    = htmlspecialchars($m['bc_number'] ?? '-');
                                        $bc_date  = !empty($m['bc_date']) ? date('Y-m-d', strtotime($m['bc_date'])) : '-';
                                        $has_bc   = !empty($m['bc_number']);
                                        $bc_link  = $has_bc ? base_url('BeaCukai/detail/' . $m['bc_number']) : '#';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['item_code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($m['item_name'] ?? '-') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($m['unit'] ?? '-') ?></td>
                                        <td class="text-end"><?= number_format($m['qty_used'] ?? 0, 2) ?></td>
                                        <td class="text-center"><?= $bc_type ?></td>
                                        <td class="text-center"><?= $bc_no ?></td>
                                        <td class="text-center"><?= $bc_date ?></td>
                                        <td class="text-center">
                                            <?php if ($has_bc): ?>
                                                <a href="<?= $bc_link ?>" target="_blank" class="btn btn-outline-success btn-sm shadow-sm">
                                                    <i class="fas fa-file-invoice"></i> Lihat Dokumen BC
                                                </a>
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
                        <i class="fas fa-info-circle me-1"></i> Tidak ada material asal produksi yang ditemukan.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-warning border-0 shadow-sm">
        <i class="fas fa-info-circle me-1"></i> Data asal scrap tidak ditemukan.
    </div>
<?php endif; ?>

<style>
/* === CARD === */
.card {
    border-radius: 10px;
    transition: all 0.25s ease;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

/* === TABLE === */
.table th, .table td {
    font-size: 13px;
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: #f8f9fa !important;
    transition: 0.2s ease;
}

/* === HEADER GRADIENT === */
.bg-gradient-success {
    background: linear-gradient(45deg, #198754, #5C5F72);
}

/* === BUTTONS === */
.btn-outline-success {
    border-color: #198754;
    color: #198754;
    font-size: 12.5px;
    border-radius: 5px;
    transition: all 0.2s ease;
}
.btn-outline-success:hover {
    background-color: #198754;
    color: #fff;
}
.btn-close {
    font-size: 0.8rem;
}

/* === BADGES & ALERTS === */
.badge {
    font-size: 12px;
    padding: 5px 8px;
    border-radius: 5px;
}
.alert-warning {
    background-color: #fff8e1;
    border-left: 4px solid #ffc107;
    font-size: 13px;
}
</style>
