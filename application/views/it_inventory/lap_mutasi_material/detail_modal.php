<?php if($item): ?>
<div class="modal-header">
    <h5 class="modal-title" id="mainModalLabel">Kartu Stok Gudang: <?= $item->name; ?> (<?= $item->code; ?>)</h5>
    <div>
        <a href="<?= base_url('LapMutasiMaterial/export_detail_excel/' . $item_type . '/' . $item->id); ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="<?= base_url('LapMutasiMaterial/export_detail_pdf/' . $item_type . '/' . $item->id); ?>" target="_blank" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-left: 10px;">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
<div class="modal-body">
    <p class="text-muted small">
        Menampilkan riwayat transaksi GUDANG (Pemasukan, Pengeluaran, Retur, dan Penyesuaian).
        <br>
    </p>

    <div class="table-responsive" style="max-height: 60vh;">
        <table class="table table-sm table-bordered table-hover" id="detailTraceTable" style="font-size: 0.85rem;">
            
            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                <tr>
                    <th class="text-nowrap">Tgl. Transaksi</th>
                    <th class="text-nowrap">Tipe</th>
                    <th class="text-nowrap">No. Dokumen</th>
                    <th class="text-nowrap">Ref. Sumber</th>
                    <th class="text-nowrap">Tipe BC</th>
                    <th class="text-nowrap">No. BC</th>
                    <th class="text-nowrap">Tgl. BC</th>
                    <th>Keterangan</th>
                    <th class="text-nowrap text-right">Masuk</th>
                    <th class="text-nowrap text-right">Keluar</th>
                    <th class="text-nowrap text-right">Saldo</th>
                    <th class="text-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($trace)):
                    echo '<tr><td colspan="12" class="text-center">Tidak ada transaksi gudang ditemukan.</td></tr>';
                else:
                    foreach($trace as $t): 
                        // Reset variabel untuk setiap baris
                        $doc_num = '';
                        $doc_ref = '';
                        $bc_type = '';
                        $bc_num = '';
                        $bc_date_formatted = '';
                        $notes = '';
                        $badge = 'secondary';
                        
                        switch($t->transaction_type) {
                            case 'in':
                                $badge = 'success';
                                $doc_num = $t->grn_number;
                                $doc_ref = $t->po_number;
                                $bc_type = $t->bc_type_code;
                                $bc_num = $t->bc_number;
                                $bc_date_formatted = $t->bc_date ? date('d-m-Y', strtotime($t->bc_date)) : '';
                                break;
                            case 'production_out':
                            case 'OUT_ISSUE':
                                $badge = 'danger';
                                $doc_num = $t->issuance_number ?: $t->mr_number;
                                $doc_ref = $t->mr_number;
                                break;
                            case 'IN_RETURN_WIP':
                                $badge = 'info';
                                $doc_num = $t->prod_return_no;
                                break;
                            case 'adjustment':
                                $badge = 'warning';
                                $doc_num = $t->adjustment_doc;
                                $notes = $t->adjustment_notes;
                                // Jika Anda ingin Tgl. Adjusment di kolom Tgl BC, Anda bisa tambahkan:
                                // $bc_date_formatted = $t->adjustment_date ? date('d-m-Y', strtotime($t->adjustment_date)) : '';
                                break;
                            case 'RTN_SUPPLIER':
                                $badge = 'secondary';
                                // Use RTN-specific fields returned by the model
                                $doc_num = isset($t->rtn_number) ? $t->rtn_number : '';
                                $bc_type = isset($t->rtn_bc_type) ? $t->rtn_bc_type : '';
                                $bc_num = isset($t->rtn_bc_no) ? $t->rtn_bc_no : '';
                                $bc_date_formatted = !empty($t->rtn_bc_date) ? date('d-m-Y', strtotime($t->rtn_bc_date)) : '';
                                // show supplier name as reference
                                $doc_ref = isset($t->rtn_supplier_name) ? $t->rtn_supplier_name : '';
                                break;
                        }
                ?>
                <tr>
                    <td class="text-nowrap"><?= date('d-m-Y H:i', strtotime($t->transaction_date)); ?></td>
                    <td class="text-nowrap"><span class="badge badge-<?= $badge; ?>"><?= $t->transaction_type; ?></span></td>
                    <td class="text-nowrap"><strong><?= $doc_num; ?></strong></td>
                    <td class="text-nowrap"><?= $doc_ref; ?></td>
                    <td class="text-nowrap"><?= $bc_type; ?></td>
                    <td class="text-nowrap"><?= $bc_num; ?></td>
                    <td class="text-nowrap"><?= $bc_date_formatted; ?></td>
                    <td style="min-width: 150px;"><?= $notes; ?></td>
                    <td class="text-right text-success font-weight-bold"><?= $t->qty_in > 0 ? (float)$t->qty_in : ''; ?></td>
                    <td class="text-right text-danger font-weight-bold"><?= $t->qty_out > 0 ? (float)$t->qty_out : ''; ?></td>
                    <td class="text-right font-weight-bold" style="background-color: #f8f9fa;"><?= (float)$t->balance; ?></td>
                    <td class="text-nowrap">
                        <?php if ($t->transaction_type === 'adjustment' && !empty($t->adjustment_header_id)): ?>
                            <button class="btn btn-info btn-xs" onclick="viewAdjustmentDocuments(<?= $t->adjustment_header_id; ?>)" title="Lihat Dokumen Adjustment">
                                <i class="fas fa-file-alt"></i> Docs
                            </button>
                        <?php endif; ?>
                        
                        <?php 
                            $bc_link_identifier = '';
                            if (!empty($bc_num)) {
                                if (!empty($t->grn_number)) { $bc_link_identifier = $t->grn_number; }
                                else if (!empty($t->rtn_number)) { $bc_link_identifier = $t->rtn_number; }
                            }
                        ?>
                        <?php if (!empty($bc_num) && !empty($bc_link_identifier)): ?>
                                     <a href="<?= base_url('BeaCukai/detail/' . rawurlencode(base64_encode($bc_link_identifier))); ?>" 
                               class="btn btn-primary btn-xs" 
                               target="_blank" 
                               title="Lihat Detail BC">
                                <i class="fas fa-file-invoice"></i> BC
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($t->transaction_type !== 'adjustment' && empty($bc_num)): ?>
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
<div class="modal-header"><h5 class="modal-title">Error</h5><button type="button" class="close" data-dismissmodal">&times;</button></div>
<div class="modal-body"><div class="alert alert-danger">Item tidak ditemukan.</div></div>
<?php endif; ?>