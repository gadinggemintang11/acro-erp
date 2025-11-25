<div class="container-fluid">

    <h1 class="h3 mb-1 text-gray-800"><?= $title; ?></h1>
    <p class="mb-4">(<?= $subtitle; ?>) | Periode: <?= $start_date; ?> s/d <?= $end_date; ?></p>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Rincian Transaksi (Buku Besar)</h6>
            <div class="btn-toolbar">
                <div class="btn-group mr-2">
                    <a href="javascript:history.back()" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Kembali</a>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-download"></i> Ekspor
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="<?= current_url() . '?' . $_SERVER['QUERY_STRING'] . '&export=pdf'; ?>" target="_blank"><i class="fa fa-file-pdf"></i> PDF</a>
                        <a class="dropdown-item" href="<?= current_url() . '?' . $_SERVER['QUERY_STRING'] . '&export=excel'; ?>"><i class="fa fa-file-excel"></i> Excel</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="ledgerTable" width="100%" cellspacing="0">
                    <thead>
                        <tr class="text-center">
                            <th>Tanggal</th>
                            <th>No. Jurnal</th>
                            <th>No. Sumber</th> <th>Keterangan</th>
                            <th width="15%">Debit</th>
                            <th width="15%">Kredit</th>
                            <th width="15%">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $saldo = $report_data['saldo_awal'];
                        ?>
                        <tr>
                            <td colspan="6" class="font-weight-bold text-right">Saldo Awal (per <?= $start_date; ?>)</td>
                            <td class="font-weight-bold text-right"><?= number_format($saldo, 2); ?></td>
                        </tr>

                        <?php foreach ($report_data['transaksi'] as $tx) : ?>
                            <?php
                                $debit = (float) $tx->debit_local;
                                $kredit = (float) $tx->kredit_local;
                                $is_debit_normal = in_array($report_data['info_akun']->account_type, ['Aset', 'Biaya', 'HPP']);
                                
                                if($is_debit_normal){
                                     $saldo = $saldo + $debit - $kredit;
                                 } else {
                                     $saldo = $saldo + $kredit - $debit;
                                 }
                            ?>
                            <tr>
                                <td><?= $tx->journal_date; ?></td>
                                <td><?= $tx->journal_no; ?></td>
                                
                                <td>
                                    <?php
                                        $source_doc_text = ''; // Teks yang akan ditampilkan
                                        $source_url = '#';      // Link
                                        $source_module = $tx->source_module;
                                        $source_doc_id = $tx->source_doc_id;

                                        // Logika baru untuk menentukan Teks dan Link
                                        if ($source_module == 'AP_INVOICE') {
                                            $source_url = site_url('apinvoice/view/' . $source_doc_id);
                                            $source_doc_text = $tx->ap_invoice_no;
                                        } 
                                        else if ($source_module == 'AP_PAYMENT') {
                                            $source_url = site_url('appayment/view/' . $source_doc_id);
                                            $source_doc_text = $tx->ap_payment_no;
                                        } 
                                        else if ($source_module == 'AR_INVOICE') {
                                            $source_url = site_url('arinvoice/view/' . $source_doc_id);
                                            $source_doc_text = $tx->ar_invoice_no;
                                        } 
                                        else if ($source_module == 'AR_RECEIPT') {
                                            $source_url = site_url('arreceipt/view/' . $source_doc_id);
                                            $source_doc_text = $tx->ar_receipt_no;
                                        }
                                        
                                        else if ($source_module == 'CASH_BANK') {
                                            // Asumsi controller Anda adalah 'cashbank' (sesuaikan jika perlu)
                                            $source_url = site_url('cashbank/view/' . $source_doc_id); 
                                            $source_doc_text = $tx->cash_bank_no; // Gunakan nomor asli
                                        } 
                                        
                                        // --- Menangani kasus 'Manual' ---
                                        else if ($source_module == 'JURNAL_UMUM' || $source_module == 'Manual' || empty($source_module)) {
                                            $source_url = site_url('jurnalumum/view/' . $tx->journal_header_id); 
                                            $source_doc_text = $tx->journal_no; // Tampilkan No. Jurnal-nya
                                        }
                                        
                                        // --- Fallback jika ada modul lain ---
                                        else {
                                            $source_doc_text = $source_module . '/' . $source_doc_id;
                                        }
                                        
                                        // Render link-nya
                                        echo "<a href='{$source_url}' target='_blank'>{$source_doc_text}</a>";
                                    ?>
                                </td>
                                <td><?= $tx->description; ?></td>
                                <td class="text-right"><?= number_format($debit, 2); ?></td>
                                <td class="text-right"><?= number_format($kredit, 2); ?></td>
                                <td class="text-right"><?= number_format($saldo, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="font-weight-bold text-right">Saldo Akhir (per <?= $end_date; ?>)</td>
                            <td class="font-weight-bold text-right"><?= number_format($saldo, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    </div>