<?php if($item): ?>
<div class="modal-header">
    <h5 class="modal-title" id="mainModalLabel">Kartu Stok Mesin: <?= $item->nama_mesin; ?> (<?= $item->kode_mesin; ?>)</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <p class="text-muted small">Menampilkan riwayat transaksi mesin (Pemasukan & Penyesuaian).</p>

    <div class="table-responsive" style="max-height: 60vh;">
        <table class="table table-sm table-bordered table-hover" id="detailTraceTable" style="font-size: 0.85rem;">
            
            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                <tr>
                    <th class="text-nowrap">Tgl. Transaksi</th>
                    <th class="text-nowrap">Tipe</th>
                    <th class="text-nowrap">Dokumen Sumber</th>
                    <th class="text-nowrap text-right">Masuk</th>
                    <th class="text-nowrap text-right">Keluar</th>
                    <th class="text-nowrap text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($trace)):
                    echo '<tr><td colspan="6" class="text-center">Tidak ada transaksi ditemukan.</td></tr>';
                else:
                    foreach($trace as $t): 
                        $balance = $t->balance;
                        $doc_info = '';
                        $badge = 'secondary';
                        
                        switch($t->transaction_type) {
                            case 'in':
                                $badge = 'success';
                                $doc_info = "GRN No: <strong>{$t->grn_number}</strong>";
                                break;
                            case 'adjustment':
                                $badge = 'warning';
                                $doc_info = "ADJ No: <strong>{$t->adjustment_doc}</strong><br><small>Ket: {$t->adjustment_notes}</small>";
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
<div class="modal-header"><h5 class="modal-title">Error</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><div class="alert alert-danger">Item mesin tidak ditemukan.</div></div>
<?php endif; ?>