<?php if($item): ?>
<div class="modal-header">
    <h5 class="modal-title" id="mainModalLabel">Kartu Stok WIP: <?= $item->name; ?> (<?= $item->code; ?>)</h5>
    <div>
        <a href="<?= base_url('LapMutasiWIP/export_detail_excel/' . $item_type . '/' . $item->id); ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="<?= base_url('LapMutasiWIP/export_detail_pdf/' . $item_type . '/' . $item->id); ?>" target="_blank" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-left: 10px;">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
<div class="modal-body">
    <p class="text-muted small">
        Menampilkan riwayat transaksi di lini Produksi (WIP).
        <br>Melacak penerimaan material/SFG di produksi, pemakaian, dan retur ke gudang.
    </p>

    <div class="table-responsive" style="max-height: 60vh;">
        <table class="table table-sm table-bordered table-hover" id="detailTraceTable" style="font-size: 0.85rem;">
            
            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                <tr>
                    <th class="text-nowrap">Tgl. Transaksi</th>
                    <th class="text-nowrap">Tipe</th>
                    <th class="text-nowrap">Dokumen Sumber (Traceability)</th>
                    <th class="text-nowrap text-right">Masuk</th>
                    <th class="text-nowrap text-right">Keluar</th>
                    <th class="text-nowrap text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($trace)):
                    echo '<tr><td colspan="6" class="text-center">Tidak ada transaksi WIP ditemukan.</td></tr>';
                else:
                    foreach($trace as $t): 
                        $balance = $t->balance; // Diambil dari sl.balance
                        $doc_info = '';
                        $badge = 'secondary';
                        
                        switch($t->transaction_type) {
                            case 'wip_in':
                                $badge = 'success';
                                if ($item_type == 'material') {
                                    $doc_info = "MR No: <strong>{$t->mr_number}</strong>";
                                } else { // semifinished
                                    $doc_info = "Prod. No: <strong>{$t->prod_num_in}</strong><br><small>Stage: {$t->stage_in}</small>";
                                }
                                break;
                            case 'wip_out':
                                $badge = 'danger';
                                $doc_info = "Prod. No: <strong>{$t->prod_num_out}</strong><br><small>Stage: {$t->stage_out}</small>";
                                break;
                            case 'IN_RETURN_WIP':
                                $badge = 'info';
                                $doc_info = "Return No: <strong>{$t->prod_return_no}</strong>";
                                break;
                        }
                ?>
                <tr>
                    <td class="text-nowrap"><?= date('d-m-Y H:i', strtotime($t->transaction_date)); ?></td>
                    <td class="text-nowrap"><span class="badge badge-<?= $badge; ?>"><?= $t->transaction_type; ?></span></td>
                    <td><?= $doc_info; ?></td>
                    <td class="text-right text-success font-weight-bold"><?= $t->qty_in > 0 ? (float)$t->qty_in : ''; ?></td>
                    <td class="text-right text-danger font-weight-bold"><?= $t->qty_out > 0 ? (float)$t->qty_out : ''; ?></td>
                    <td class="text-right font-weight-bold" style="background-color: #f8f9fa;"><?= (float)$balance; ?></td>
                </tr>
                <?php 
                    endforeach; 
                endif; 
                ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>
<?php else: ?>
<div class="modal-header"><h5 class="modal-title">Error</h5><button type_name" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><div class="alert alert-danger">Item tidak ditemukan.</div></div>
<?php endif; ?>