<?php if (!empty($scrap_sources)): ?>
    <?php foreach ($scrap_sources as $src): ?>
        <div class="card shadow-sm mb-4 border-0 animate__animated animate__fadeIn" style="border-left: 5px solid #667eea;">
            <!-- Header -->
            <div class="card-header text-white py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="fw-semibold">
                    <i class="fas fa-industry me-2"></i> Detail Produksi
                    <small style="opacity: 0.9; display: block; font-weight: 400;">Trace Asal Material Scrap dari Produksi</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="<?= base_url('LapMutasiScrap/export_production_trace_pdf/' . ($src['production_header_id'] ?? '')); ?>" class="btn btn-light btn-sm" target="_blank" title="Export ke PDF">
                        <i class="fas fa-file-pdf me-1" style="color: #dc3545;"></i> PDF
                    </a>
                    <a href="<?= base_url('LapMutasiScrap/export_production_trace_excel/' . ($src['production_header_id'] ?? '')); ?>" class="btn btn-light btn-sm" target="_blank" title="Export ke Excel">
                        <i class="fas fa-file-excel me-1" style="color: #28a745;"></i> Excel
                    </a>
                    <button type="button" class="btn-close btn-close-white" onclick="$(this).closest('.card').fadeOut(300, function(){ $(this).remove(); });"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="card-body bg-light">

                <!-- Info Produksi -->
                <h6 class="text-secondary fw-bold mb-3">
                    <i class="fas fa-industry text-primary me-1"></i> Informasi Produksi
                </h6>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                            <strong style="color: #667eea;">No. Produksi:</strong>
                            <div style="margin-top: 5px; font-size: 14px;"><?= htmlspecialchars($src['production_number'] ?? '-') ?></div>
                        </div>
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                            <strong style="color: #667eea;">Proses Produksi:</strong>
                            <div style="margin-top: 5px; font-size: 14px;"><?= htmlspecialchars($src['process_name'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                            <strong style="color: #667eea;">No. WO:</strong>
                            <div style="margin-top: 5px; font-size: 14px;"><?= htmlspecialchars($src['wo_number'] ?? '-') ?></div>
                        </div>
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px;">
                            <strong style="color: #667eea;">Nama FG:</strong>
                            <div style="margin-top: 5px; font-size: 14px;">
                                <span class="badge bg-success"><?= htmlspecialchars($src['fg_name'] ?? '-') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Material -->
                <h6 class="text-secondary fw-bold mb-2">
                    <i class="fas fa-cubes text-primary me-1"></i> Material Asal Produksi
                </h6>

                <?php if (!empty($src['materials'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle text-nowrap shadow-sm">
                            <thead class="text-center text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
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
                                        // Gunakan GRN number untuk link (di-encode base64 + rawurlencode untuk URL-safe)
                                        $bc_link_identifier = !empty($m['grn_number']) ? $m['grn_number'] : $m['bc_number'];
                                        $bc_link  = $has_bc ? base_url('BeaCukai/detail/' . rawurlencode(base64_encode($bc_link_identifier))) : '#';
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
    box-shadow: 0 6px 15px rgba(102, 126, 234, 0.15) !important;
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* === BUTTONS === */
.btn-outline-success {
    border-color: #667eea;
    color: #667eea;
    font-size: 12.5px;
    border-radius: 5px;
    transition: all 0.2s ease;
}
.btn-outline-success:hover {
    background-color: #667eea;
    color: #fff;
    border-color: #667eea;
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

/* === INFO BOXES === */
.text-primary {
    color: #667eea !important;
}
</style>
