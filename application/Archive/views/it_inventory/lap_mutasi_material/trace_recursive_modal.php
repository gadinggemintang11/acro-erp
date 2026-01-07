<?php 
if($item): 
    // Tentukan controller berdasarkan URL referer atau default ke LapMutasiMaterial
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $controller = (strpos($referer, 'LapMutasiWIP') !== false) ? 'LapMutasiWIP' : 'LapMutasiMaterial';
?>
<div class="modal-header">
    <h5 class="modal-title" id="recursiveModalLabel">Jejak Produksi (WIP): <?= $item->name; ?> (<?= $item->code; ?>)</h5>
    <div>
        <a href="<?= base_url($controller . '/export_trace_excel/' . ($item_type ?? '') . '/' . ($item->id ?? '')); ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="<?= base_url($controller . '/export_trace_pdf/' . ($item_type ?? '') . '/' . ($item->id ?? '')); ?>" target="_blank" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-left: 10px;">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
<div class="modal-body">
    <p>Menampilkan jejak produksi (WIP). Menunjukkan item ini <strong>dibuat dari apa</strong> (INPUT) dan <strong>dipakai untuk apa</strong> (OUTPUT).</p>
    
    <div class="table-responsive" style="max-height: 60vh;">
        <table class="table table-sm table-bordered" id="recursiveTraceTable">
            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                <tr>
                    <th>Tgl. Produksi</th>
                    <th>No. Produksi</th>
                    <th>No. WO</th>
                    <th>Arah Jejak</th>
                    <th>Item Terkait</th>
                    <th class="text-right">Qty Terkait</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($trace)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada jejak produksi (WIP) ditemukan.</td></tr>
            <?php else: ?>
                <?php foreach($trace as $t): ?>
                    <?php 
                        $badge_class = ($t->direction == 'input') ? 'badge-primary' : 'badge-success';
                        $badge_text = ($t->direction == 'input') ? 'DIBUAT DARI' : 'DIPAKAI UNTUK';
                        $text_class = ($t->direction == 'input') ? 'text-danger' : 'text-success'; // Qty terkait
                    ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($t->transaction_date)); ?></td>
                        <td><?= htmlspecialchars($t->production_number); ?></td>
                        <td><?= htmlspecialchars($t->wo_number); ?></td>
                        <td style="font-size: 0.9em; white-space: nowrap;">
                            <span class="badge <?= $badge_class; ?>"><?= $badge_text; ?></span>
                            <br>
                            <small class="text-muted">
                                (Qty Asal: <?= (float)$t->original_qty; ?> <?= htmlspecialchars($t->original_satuan); ?>)
                            </small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($t->related_item_name); ?></strong>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($t->related_item_type); ?></small>
                        </td>
                        <td class="text-right font-weight-bold <?= $text_class; ?>">
                            <?= (float)$t->related_qty; ?> <?= htmlspecialchars($t->related_satuan); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>

<?php else: ?>
<div class="modal-header">
    <h5 class="modal-title">Error</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
</div>
<div class="modal-body">
    <div class="alert alert-danger">Item tidak ditemukan.</div>
</div>
<?php endif; ?>