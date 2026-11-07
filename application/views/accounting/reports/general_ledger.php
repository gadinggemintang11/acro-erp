<style>
    /* Professional General Ledger Styling */
    body {
        background-color: #f8f9fc;
    }
    
    .ledger-header {
        background: #fff;
        border-left: 5px solid #1e40af;
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .ledger-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .ledger-subtitle {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 400;
    }
    
    .ledger-subtitle strong {
        color: #1e40af;
        font-weight: 600;
    }
    
    .modern-card {
        border: none;
        border-radius: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        background: #fff;
    }
    
    .modern-card-header {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }
    
    .card-title-modern {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .btn-modern {
        border-radius: 2px;
        padding: 0.4rem 1rem;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.15s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .btn-back {
        background: #fff;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    
    .btn-back:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }
    
    .btn-export {
        background: #1e40af;
        color: white;
        border: 1px solid #1e40af;
    }
    
    .btn-export:hover {
        background: #1e3a8a;
        color: white;
        border-color: #1e3a8a;
    }
    
    .dropdown-menu {
        border-radius: 2px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 0.25rem 0;
    }
    
    .dropdown-item {
        padding: 0.5rem 1.25rem;
        font-size: 0.875rem;
        color: #475569;
        transition: all 0.15s ease;
    }
    
    .dropdown-item:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    
    .dropdown-item i {
        width: 20px;
    }
    
    #ledgerTable {
        font-size: 0.875rem;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    #ledgerTable thead th {
        background: #1e293b;
        color: #fff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 0.875rem 0.75rem;
        border: none;
        border-bottom: 3px solid #1e40af;
        white-space: nowrap;
    }
    
    #ledgerTable tbody tr {
        transition: background-color 0.15s ease;
        border-bottom: 1px solid #e2e8f0;
    }
    
    #ledgerTable tbody tr:hover {
        background: #f8fafc;
    }
    
    #ledgerTable tbody td {
        padding: 0.75rem;
        vertical-align: middle;
        border-left: 1px solid #f1f5f9;
        color: #334155;
    }
    
    #ledgerTable tbody td:first-child {
        border-left: none;
    }
    
    #ledgerTable tbody td a {
        color: #1e40af;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    
    #ledgerTable tbody td a:hover {
        color: #1e3a8a;
        text-decoration: underline;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        border-radius: 2px;
        background: #e0e7ff;
        color: #1e40af;
        border: 1px solid #c7d2fe;
    }
    
    .saldo-row {
        background: #f8fafc;
        font-weight: 700;
        font-size: 0.9rem;
        border-top: 2px solid #cbd5e1;
        border-bottom: 2px solid #cbd5e1;
    }
    
    .saldo-row td {
        padding: 1rem 0.75rem;
        color: #1e293b;
    }
    
    .saldo-awal-row {
        background: #ecfdf5;
        border-left: 4px solid #059669;
    }
    
    .saldo-akhir-row {
        background: #fef3c7;
        border-left: 4px solid #d97706;
    }
    
    .amount-cell {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
    }
    
    .text-debit {
        color: #059669 !important;
    }
    
    .text-kredit {
        color: #dc2626 !important;
    }
    
    .text-zero {
        color: #94a3b8 !important;
    }
    
    .table-responsive {
        border: 1px solid #e2e8f0;
        background: #fff;
    }
    
    /* Print Styles */
    @media print {
        .modern-card-header,
        .btn-toolbar {
            display: none !important;
        }
        
        .ledger-header {
            border: none;
            box-shadow: none;
        }
        
        #ledgerTable tbody tr:hover {
            background: transparent !important;
        }
    }
</style>

<div class="container-fluid">

    <!-- Professional Header -->
    <div class="ledger-header">
        <div class="ledger-title"><i class="fas fa-book-open mr-2" style="color: #1e40af;"></i><?= $title; ?></div>
        <div class="ledger-subtitle"><?= $subtitle; ?> • <?= lang_line('rpt_period'); ?>: <strong><?= $start_date; ?></strong> s/d <strong><?= $end_date; ?></strong></div>
    </div>

    <div class="card modern-card mb-4">
        <div class="modern-card-header d-flex flex-row align-items-center justify-content-between">
            <h6 class="card-title-modern"><?= lang_line('rpt_gl_detail_heading'); ?></h6>
            <div class="btn-toolbar">
                <div class="btn-group mr-2">
                    <a href="javascript:history.back()" class="btn btn-modern btn-back btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('rpt_back'); ?>
                    </a>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-modern btn-export btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-download mr-1"></i> <?= lang_line('rpt_export'); ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="<?= current_url() . '?' . $_SERVER['QUERY_STRING'] . '&export=pdf'; ?>" target="_blank">
                            <i class="fas fa-file-pdf mr-2" style="color: #dc2626;"></i> <?= lang_line('rpt_export_pdf'); ?>
                        </a>
                        <a class="dropdown-item" href="<?= current_url() . '?' . $_SERVER['QUERY_STRING'] . '&export=excel'; ?>">
                            <i class="fas fa-file-excel mr-2" style="color: #059669;"></i> <?= lang_line('rpt_export_excel'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="ledgerTable" width="100%" cellspacing="0">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 10%;"><?= lang_line('rpt_gl_date'); ?></th>
                            <th style="width: 12%;"><?= lang_line('rpt_gl_journal_no'); ?></th>
                            <th style="width: 12%;"><?= lang_line('rpt_gl_source_no'); ?></th>
                            <th style="width: 26%;"><?= lang_line('rpt_gl_description'); ?></th>
                            <th style="width: 13%;"><?= lang_line('rpt_gl_debit'); ?></th>
                            <th style="width: 13%;"><?= lang_line('rpt_gl_credit'); ?></th>
                            <th style="width: 14%;"><?= lang_line('rpt_gl_balance'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $saldo = $report_data['saldo_awal'];
                        ?>
                        <tr class="saldo-row saldo-awal-row">
                            <td colspan="6" class="text-right" style="font-weight: 600;"><?= sprintf(lang_line('rpt_gl_beginning_balance'), $start_date); ?></td>
                            <td class="text-right amount-cell" style="font-size: 0.95rem;"><?= number_format($saldo, 2); ?></td>
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
                                <td class="text-center" style="white-space: nowrap;"><?= date('d/m/Y', strtotime($tx->journal_date)); ?></td>
                                <td><span class="badge"><?= $tx->journal_no; ?></span></td>
                                
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
                                <td style="color: #475569;"><?= $tx->description; ?></td>
                                <td class="text-right amount-cell <?= $debit > 0 ? 'text-debit' : 'text-zero'; ?>"><?= $debit > 0 ? number_format($debit, 2) : '-'; ?></td>
                                <td class="text-right amount-cell <?= $kredit > 0 ? 'text-kredit' : 'text-zero'; ?>"><?= $kredit > 0 ? number_format($kredit, 2) : '-'; ?></td>
                                <td class="text-right amount-cell font-weight-bold" style="color: #1e293b;"><?= number_format($saldo, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                    <tfoot>
                        <tr class="saldo-row saldo-akhir-row">
                            <td colspan="6" class="text-right" style="font-weight: 700;"><?= sprintf(lang_line('rpt_gl_ending_balance'), $end_date); ?></td>
                            <td class="text-right amount-cell" style="font-size: 1rem; font-weight: 700;"><?= number_format($saldo, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>