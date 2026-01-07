<?php
$doc = isset($doc) ? $doc : [];
$type_label = isset($doc['type']) && strtoupper($doc['type']) === 'OUT' ? 'Keluar' : 'Masuk';
?>

<div class="container-fluid px-4 py-3 animate__animated animate__fadeIn">

    <!-- Header Dokumen -->
    <div class="card border-0 shadow-lg mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-file-invoice me-2"></i>
                Dokumen BC <?= htmlspecialchars($doc['bc_number'] ?? '-'); ?>
            </h5>
            <a href="<?= base_url('LapMutasiScrap'); ?>" class="btn btn-light btn-sm shadow-sm rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div class="card-body bg-light-subtle">
            <!-- Informasi Dokumen -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless align-middle">
                        <tbody>
                            <tr>
                                <th class="text-secondary" width="160">Nomor BC</th>
                                <td><strong><?= htmlspecialchars($doc['bc_number'] ?? '-'); ?></strong></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Tipe BC</th>
                                <td><?= htmlspecialchars($doc['bc_type_code'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Tanggal BC</th>
                                <td><?= htmlspecialchars($doc['bc_date'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nomor Dokumen</th>
                                <td><?= htmlspecialchars($doc['doc_no'] ?? $doc['document_number'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Tanggal Dokumen</th>
                                <td><?= htmlspecialchars($doc['doc_date'] ?? $doc['created_at'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-sm table-borderless align-middle">
                        <tbody>
                            <tr>
                                <th class="text-secondary" width="150">Tujuan</th>
                                <td><?= htmlspecialchars($doc['destination'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Dibuat Oleh</th>
                                <td><?= htmlspecialchars($doc['created_by'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Dibuat Pada</th>
                                <td><?= htmlspecialchars($doc['created_at'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Divider -->
            <hr class="border-secondary-subtle mb-4">

            <!-- Detail Scrap -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-primary fw-semibold mb-0">
                    <i class="fas fa-recycle me-2"></i> Detail Scrap <?= $type_label; ?>
                </h6>
                <span class="badge bg-<?= $type_label === 'Keluar' ? 'danger' : 'success'; ?> bg-gradient px-3 py-2 rounded-pill">
                    <?= strtoupper($type_label); ?>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped align-middle rounded-3 shadow-sm">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th width="60">No</th>
                            <th>Kode Item</th>
                            <th>Nama Item</th>
                            <th width="120">Qty</th>
                            <th width="100">Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($doc['details'])): ?>
                            <?php $no = 1; foreach ($doc['details'] as $d): ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($d['item_code'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($d['item_name'] ?? '-'); ?></td>
                                    <td class="text-end fw-semibold"><?= number_format($d['qty'] ?? 0, 2); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($d['unit'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-1"></i> Tidak ada data scrap yang tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styling -->
<style>
    .bg-gradient-primary {
        background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
    }
    .bg-light-subtle {
        background-color: #f8f9fc !important;
    }
    table.table th {
        font-weight: 600;
        color: #495057;
    }
    table.table td {
        vertical-align: middle;
    }
    .card {
        border-radius: 1rem;
    }
    hr {
        opacity: 0.2;
    }
</style>
