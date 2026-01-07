<div class="container-fluid">
    <?php 
        // Ingat, $data['details'] sekarang adalah array, bukan object
        $header = $data['header']; 
        $details = $data['details'];
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-eye text-info mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <div>
            <?php if ($header->status == 'Draft'): ?>
                <a href="<?= base_url('Scrap_In/confirm/' . $header->id); ?>" class="btn btn-success btn-confirm" data-message="Konfirmasi penerimaan ini? Stok scrap akan bertambah.">
                    <i class="fas fa-check mr-1"></i> Konfirmasi
                </a>
                 <a href="<?= base_url('Scrap_In/delete/' . $header->id); ?>" class="btn btn-danger btn-delete ml-1" title="Hapus" data-message="Yakin hapus Dokumen Draft ini?">
                     <i class="fas fa-trash"></i> Hapus Draft
                 </a>
            <?php elseif ($header->status == 'Confirmed'): ?>
                 <a href="<?= base_url('Scrap_In/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fas fa-print mr-1"></i> Cetak PDF
                 </a>
                 <?php endif; ?>
             <a href="<?= base_url('Scrap_In'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke List
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Header Dokumen</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td width="150"><strong>No. Dokumen</strong></td><td width="10">:</td><td><?= htmlspecialchars($header->doc_no); ?></td></tr>
                        <tr><td><strong>Tgl Dokumen</strong></td><td>:</td><td><?= date('d M Y', strtotime($header->doc_date)); ?></td></tr>
                        <tr><td><strong>Status</strong></td><td>:</td>
                            <td>
                                <?php
                                    $badge_class = 'secondary';
                                    if ($header->status == 'Draft') $badge_class = 'warning';
                                    if ($header->status == 'Confirmed') $badge_class = 'success';
                                ?>
                                <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($header->status); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                     <table class="table table-sm table-borderless">
                        <tr><td width="150"><strong>Sumber</strong></td><td width="10">:</td><td>Hasil Produksi</td></tr>
                        <tr><td><strong>Dibuat Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->created_by_name ?: '-'); ?></td></tr>
                        <tr><td><strong>Dikonfirmasi Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->confirmed_by_name ?: '-'); ?></td></tr>
                        <tr><td><strong>Catatan</strong></td><td>:</td><td><?= nl2br(htmlspecialchars($header->notes ?: '-')); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Detail Item Diterima</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>Kode Scrap</th>
                            <th>Nama Scrap</th>
                            <th>Satuan</th>
                            <th>Total Qty Diterima</th>
                            <th>Sumber Produksi / WO</th> </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($details)): foreach($details as $item): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($item['scrap_code']); ?></td>
                            <td><?= htmlspecialchars($item['scrap_name']); ?></td>
                            <td class="text-center"><?= htmlspecialchars($item['satuan_desc']); ?></td>
                            <td class="text-right"><?= (float)$item['qty']; ?></td>
                            <td> <?php if (!empty($item['sources'])): ?>
                                    <ul class="list-unstyled mb-0 pl-3">
                                        <?php foreach ($item['sources'] as $source): ?>
                                            <li>
                                                <small>
                                                    <?= htmlspecialchars($source['wo_number'] ?: 'N/A'); ?> 
                                                    (Prod: <?= htmlspecialchars($source['production_number'] ?: 'N/A'); ?>) 
                                                    - Qty: <?= (float)$source['qty_collected']; ?>
                                                </small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada item detail.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>