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
                    <th class="text-nowrap">BC Type</th>
                    <th class="text-nowrap">BC Number</th>
                    <th class="text-nowrap">BC Date</th>
                    <th class="text-nowrap text-right">Masuk</th>
                    <th class="text-nowrap text-right">Keluar</th>
                    <th class="text-nowrap text-right">Saldo</th>
                    <th class="text-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($trace)):
                    echo '<tr><td colspan="10" class="text-center">Tidak ada transaksi ditemukan.</td></tr>';
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
                            case 'RTN_SUPPLIER':
                                $badge = 'secondary';
                                $doc_info = "RTN No: <strong>" . (!empty($t->rtn_number) ? htmlspecialchars($t->rtn_number) : '-') . "</strong>";
                                break;
                        }
                        
                        // Format BC info
                        $bc_type = !empty($t->bc_type_code) ? $t->bc_type_code : '-';
                        $bc_number = !empty($t->bc_number) ? $t->bc_number : '-';
                        $bc_date = !empty($t->bc_date) ? date('d-m-Y', strtotime($t->bc_date)) : '-';
                ?>
                <tr>
                    <td class="text-nowrap"><?= date('d-m-Y H:i', strtotime($t->transaction_date)); ?></td>
                    <td class="text-nowrap"><span class="badge badge-<?= $badge; ?>"><?= $t->transaction_type; ?></span></td>
                    <td><?= $doc_info; ?></td>
                    <td class="text-center"><span class="badge badge-secondary"><?= $bc_type; ?></span></td>
                    <td class="text-center"><?= $bc_number; ?></td>
                    <td class="text-center"><?= $bc_date; ?></td>
                    <td class="text-right text-success font-weight-bold"><?= $t->qty_in > 0 ? (float)$t->qty_in : ''; ?></td>
                    <td class="text-right text-danger font-weight-bold"><?= $t->qty_out > 0 ? (float)$t->qty_out : ''; ?></td>
                    <td class="text-right font-weight-bold" style="background-color: #f8f9fa;"><?= (float)$balance; ?></td>
                        <td class="text-nowrap">
                        <?php if (!empty($t->bc_number)): ?>
                            <a href="<?= base_url('BeaCukai/detail/' . urlencode($t->bc_number)); ?>" class="btn btn-primary btn-xs" target="_blank" title="Lihat Dokumen BC: <?= htmlspecialchars($t->bc_number); ?>">
                                <i class="fas fa-file-invoice"></i> BC
                            </a>
                        <?php elseif (!empty($t->grn_number)): ?>
                            <a href="<?= base_url('BeaCukai/detail/' . base64_encode($t->grn_number)); ?>" class="btn btn-primary btn-xs" target="_blank" title="Lihat Dokumen GRN: <?= htmlspecialchars($t->grn_number); ?>">
                                <i class="fas fa-file-invoice"></i> GRN
                            </a>
                        <?php elseif ($t->transaction_type === 'adjustment' && !empty($t->adjustment_header_id)): ?>
                            <button class="btn btn-info btn-xs" onclick="viewAdjustmentDocuments(<?= $t->adjustment_header_id; ?>)" title="Lihat Dokumen Adjustment">
                                <i class="fas fa-file-alt"></i> Docs
                            </button>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
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