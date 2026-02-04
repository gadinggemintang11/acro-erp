<?php 
// Prepare data - header_info is object, materials_used is array
$h = $header_info;
$materials = $materials_used ?? [];
?>

<div class="card shadow-sm mb-4 border-0" style="border-left: 5px solid #667eea;">
    <!-- Header -->
    <div class="card-header text-white py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="fw-semibold">
            <i class="fas fa-industry me-2"></i> Detail Produksi
            <small style="opacity: 0.9; display: block; font-weight: 400;">Trace Asal Material Scrap dari Produksi</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('LapMutasiScrap/export_production_trace_pdf/' . $prod_id); ?>" class="btn btn-light btn-sm" target="_blank" title="Export ke PDF">
                <i class="fas fa-file-pdf" style="color: #dc3545;"></i> PDF
            </a>
            <a href="<?= base_url('LapMutasiScrap/export_production_trace_excel/' . $prod_id); ?>" class="btn btn-light btn-sm" target="_blank" title="Export ke Excel">
                <i class="fas fa-file-excel" style="color: #28a745;"></i> Excel
            </a>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body bg-light">

        <!-- Info Produksi -->
        <h6 class="text-secondary fw-bold mb-3">
            <i class="fas fa-industry text-primary me-1"></i> Informasi Produksi
        </h6>

        <div class="row mb-3">
            <div class="col-md-6">
                <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                    <strong style="color: #667eea;">No. Produksi:</strong>
                    <div style="margin-top: 5px; font-size: 14px;"><?= htmlspecialchars($h->production_number ?? '-'); ?></div>
                </div>
                <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px;">
                    <strong style="color: #667eea;">Nama FG:</strong>
                    <div style="margin-top: 5px; font-size: 14px;">
                        <span class="badge bg-success"><?= htmlspecialchars($h->fg_name ?? '-'); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                    <strong style="color: #667eea;">No. WO:</strong>
                    <div style="margin-top: 5px; font-size: 14px;"><?= htmlspecialchars($h->wo_number ?? '-'); ?></div>
                </div>
                <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px;">
                    <strong style="color: #667eea;">Sales Contract:</strong>
                    <div style="margin-top: 5px; font-size: 14px;"><?= htmlspecialchars($h->sales_contract ?? '-'); ?></div>
                </div>
            </div>
        </div>

        <!-- Material -->
        <h6 class="text-secondary fw-bold mb-2 mt-4">
            <i class="fas fa-boxes text-primary me-1"></i> Material yang Menghasilkan Scrap (Input)
        </h6>

        <?php if (!empty($materials)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm align-middle">
                    <thead class="text-center text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <tr>
                            <th>Kode Material</th>
                            <th>Nama Material</th>
                            <th>Satuan</th>
                            <th>Qty Digunakan</th>
                            <th>BC Asal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $m): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($m['item_code'] ?? '-'); ?></code></td>
                                <td><?= htmlspecialchars($m['item_name'] ?? '-'); ?></td>
                                <td class="text-center"><?= htmlspecialchars($m['unit'] ?? '-'); ?></td>
                                <td class="text-end"><strong><?= number_format((float)($m['qty_used'] ?? 0), 2); ?></strong></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= htmlspecialchars(($m['ex_bc_type'] ?? '-') . '/' . ($m['ex_bc_number'] ?? '-')); ?></span>
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

<style>
    .card {
        border-radius: 10px;
        transition: all 0.25s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    .table th, .table td {
        font-size: 13px;
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
        transition: 0.2s ease;
    }
    .badge {
        font-size: 12px;
        padding: 5px 8px;
        border-radius: 5px;
    }
</style>
