<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>

<style>
    :root {
        --primary-color: #4F46E5;
        --success-color: #10B981;
        --danger-color: #EF4444;
        --warning-color: #F59E0B;
        --info-color: #3B82F6;
        --dark-color: #1F2937;
        --light-bg: #F9FAFB;
        --border-color: #E5E7EB;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    body { background-color: var(--light-bg); }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6366F1 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .page-header h1 i {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem;
            border-radius: 8px;
            margin-right: 1rem;
        }

        /* Card Modern */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            -webkit-transition: all 0.3s ease;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            -webkit-transform: translateY(-2px);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-header h6 {
            color: var(--dark-color);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .card-header i {
            color: var(--primary-color);
        }

        /* Filter Form */
        .filter-form {
            background: linear-gradient(135deg, #F9FAFB 0%, #FFFFFF 100%);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .form-control-sm {
            border-radius: 6px;
            border: 1px solid var(--border-color);
            padding: 0.5rem 0.75rem;
            -webkit-transition: all 0.2s ease;
            transition: all 0.2s ease;
        }
        
        .form-control-sm:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Filter Form Styling */
        .filter-row-form .form-group label {
            font-size: 0.85rem;
            color: #495057;
            margin-bottom: 0.4rem;
            display: block;
        }

        .filter-row-form .form-control-sm {
            font-size: 0.875rem;
            height: 2.25rem;
        }

        .btn-group-sm .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            -webkit-transition: all 0.2s ease;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6366F1 100%);
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:hover {
            -webkit-transform: translateY(-1px);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #DC2626 100%);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info-color) 0%, #2563EB 100%);
        }
        
        .btn-xs {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1.25;
            border-radius: 4px;
        }

        /* Table Professional */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th, .table td {
            vertical-align: middle;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            border-color: #dee2e6;
        }
        
        .table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            white-space: nowrap;
            border-bottom: 2px solid #dee2e6;
            border-top: none;
        }
        
        .table tbody tr {
            -webkit-transition: background-color 0.15s ease;
            transition: background-color 0.15s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f1f3f5;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff;
        }
        
        .table-striped tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }

        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_filter {
            float: right;
            text-align: right;
        }

        .dataTables_wrapper .dataTables_filter label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding: 0.75rem 0;
            color: #6c757d;
            font-size: 0.875rem;
        }

        .dt-buttons {
            margin-bottom: 1rem;
        }

        /* Column Search Boxes */
        .column-search {
            width: 100%;
            padding: 0.375rem 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .column-search:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        tfoot input {
            width: 100%;
        }

        tfoot th {
            padding: 0.5rem !important;
        }

        /* Number Formatting */
        .num-positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .num-negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .num-neutral {
            color: #6c757d;
        }
        
        .text-number {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-feature-settings: "tnum";
        }

        /* Modal Modern */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--info-color) 0%, #2563EB 100%);
        }
        
        .modal-xl { max-width: 95%; }
        
        #detailContent table th,
        #detailContent table td {
            font-size: 0.8rem;
            padding: 0.6rem 0.5rem;
        }
        
        #detailContent .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Loading Animation */
        .spinner-border {
            border-width: 3px;
        }

        /* Pagination */
        .pagination {
            margin-top: 1rem;
        }
        
        .pagination .page-link {
            border-radius: 6px;
            margin: 0 2px;
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            -webkit-transition: all 0.2s ease;
            transition: all 0.2s ease;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6366F1 100%);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link:hover {
            background-color: rgba(79, 70, 229, 0.1);
            -webkit-transform: translateY(-1px);
            transform: translateY(-1px);
        }

        /* Info Text */
        .info-text {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border-left: 4px solid var(--info-color);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        /* Export Buttons Group */
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .export-buttons .btn {
            white-space: nowrap;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.25rem; }
            .export-buttons { 
                width: 100%;
                margin-top: 0.5rem;
            }
            .export-buttons .btn { 
                flex: 1;
                min-width: 100px;
            }
        }

        /* Smooth Animations */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>

<div class="container-fluid">

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h1><i class="fas fa-boxes"></i><?= lang_line('stock_ledger_title', 'Laporan Ringkasan Stok Barang'); ?></h1>
                        <div class="export-buttons">
                            <?php
                                $export_query_string = http_build_query([
                                    'month' => $selected_month ?? date('m'),
                                    'year' => $selected_year ?? date('Y'),
                                    'warehouse_type' => $filters['warehouse_type'] ?? null,
                                    'search' => $filters['search'] ?? null
                                ]);
                            ?>
                            <a href="<?= base_url('stock_ledger/export_excel?' . $export_query_string); ?>" target="_blank" class="btn btn-success btn-sm shadow" title="Download Excel">
                                <i class="fas fa-file-excel mr-1"></i> Excel
                            </a>
                            <a href="<?= base_url('stock_ledger/export_pdf?' . $export_query_string); ?>" target="_blank" class="btn btn-danger btn-sm shadow" title="Download PDF">
                                <i class="fas fa-file-pdf mr-1"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="m-0"><i class="fas fa-filter mr-2"></i><?= lang_line('stock_ledger_filter', 'Filter Laporan'); ?></h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url('stock_ledger/index'); ?>" method="GET" class="filter-row-form">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="small font-weight-600"><i class="far fa-calendar-alt mr-1"></i>Bulan</label>
                                        <select name="month" id="month" class="form-control form-control-sm">
                                            <?php for ($m = 1; $m <= 12; $m++):
                                                $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                                                $name = date('F', mktime(0,0,0,$m,1));
                                            ?>
                                                <option value="<?= $val; ?>" <?= ($selected_month == $val) ? 'selected' : ''; ?>><?= $name; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="small font-weight-600">Tahun</label>
                                        <select name="year" id="year" class="form-control form-control-sm">
                                            <?php $currYear = date('Y'); ?>
                                            <?php for ($y = $currYear + 1; $y >= $currYear - 5; $y--): ?>
                                                <option value="<?= $y; ?>" <?= ($selected_year == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="small font-weight-600"><i class="fas fa-warehouse mr-1"></i>Gudang</label>
                                        <select name="warehouse_type" id="warehouse_type" class="form-control form-control-sm">
                                            <option value="">Semua</option>
                                            <?php if (!empty($item_types)): foreach ($item_types as $key => $label): ?>
                                                <option value="<?= htmlspecialchars($key); ?>" <?= (isset($filters['warehouse_type']) && $filters['warehouse_type'] == $key) ? 'selected' : ''; ?>><?= htmlspecialchars($label); ?></option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="small font-weight-600"><i class="fas fa-search mr-1"></i>Cari Barang</label>
                                        <input type="text" name="search" id="global_search" class="form-control form-control-sm" placeholder="Kode/Nama..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="btn-group btn-group-sm w-100">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Terapkan</button>
                                        <a href="<?= base_url('stock_ledger/index'); ?>" class="btn btn-outline-secondary"><i class="fas fa-undo mr-1"></i>Reset</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                         <h6 class="m-0"><i class="fas fa-chart-line mr-2"></i><?= lang_line('stock_ledger_summary_title', 'Ringkasan Stok Periode'); ?>: <span class="text-primary font-weight-bold"><?= date('F Y', strtotime($filters['start_date'])); ?></span></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 table-sm" id="stockReportTable" width="100%">
                                <thead class="text-center">
                                    <tr>
                                        <th style="width: 50px;"><?= lang_line('stock_ledger_no', 'No'); ?></th>
                                        <th style="width: 110px;"><?= lang_line('stock_ledger_warehouse_col', 'Gudang'); ?></th>
                                        <th style="width: 120px;"><?= lang_line('stock_ledger_item_code', 'Kode Barang'); ?></th>
                                        <th style="min-width: 220px;" class="text-left"><?= lang_line('stock_ledger_item_name', 'Nama Barang'); ?></th>
                                        <th style="width: 80px;"><?= lang_line('stock_ledger_unit', 'Satuan'); ?></th>
                                        <th style="width: 100px;"><?= lang_line('stock_ledger_opening_stock', 'Stok Awal'); ?></th>
                                        <th style="width: 100px;"><?= lang_line('stock_ledger_in_grn', 'Masuk (GRN)'); ?></th>
                                        <th style="width: 100px;"><?= lang_line('stock_ledger_in_return', 'Masuk (Retur)'); ?></th>
                                        <th style="width: 100px;"><?= lang_line('stock_ledger_out', 'Keluar'); ?></th>
                                        <th style="width: 110px;"><?= lang_line('stock_ledger_closing_stock', 'Stok Akhir'); ?></th>
                                        <th style="width: 80px;" class="text-center"><?= lang_line('stock_ledger_actions', 'Aksi'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th></th>
                                        <th><input type="text" class="column-search" placeholder="Gudang"></th>
                                        <th><input type="text" class="column-search" placeholder="Kode"></th>
                                        <th><input type="text" class="column-search" placeholder="Nama"></th>
                                        <th><input type="text" class="column-search" placeholder="Satuan"></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php if (empty($report)): ?>
                                        <tr><td colspan="11" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block text-muted"></i>
                                            <strong><?= lang_line('stock_ledger_no_data', 'Tidak ada data stok untuk filter yang dipilih.'); ?></strong>
                                        </td></tr>
                                    <?php else: $no = 1; foreach ($report as $row): ?>
                                        <tr>
                                            <td class="text-center text-muted"><?= $no++; ?></td>
                                            <td class="text-center"><span class="badge badge-secondary px-2 py-1"><?= htmlspecialchars($item_types[$row->item_type] ?? ucfirst($row->item_type)); ?></span></td>
                                            <td class="font-weight-bold text-dark"><?= htmlspecialchars($row->item_code); ?></td>
                                            <td class="text-left"><?= htmlspecialchars($row->item_name); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($row->item_unit); ?></td>
                                            <td class="text-right text-number text-dark"><?= number_format($row->opening_stock, 2, ',', '.'); ?></td>
                                            <td class="text-right text-number <?= $row->receive_qty > 0 ? 'num-positive' : 'num-neutral'; ?>"><?= number_format($row->receive_qty, 2, ',', '.'); ?></td>
                                            <td class="text-right text-number <?= $row->return_qty > 0 ? 'num-positive' : 'num-neutral'; ?>"><?= number_format($row->return_qty, 2, ',', '.'); ?></td>
                                            <td class="text-right text-number <?= $row->out_qty > 0 ? 'num-negative' : 'num-neutral'; ?>"><?= number_format($row->out_qty, 2, ',', '.'); ?></td>
                                            <td class="text-right text-number font-weight-bold <?= $row->closing_stock > 0 ? 'num-positive' : ($row->closing_stock < 0 ? 'num-negative' : 'num-neutral'); ?>">
                                                <?= number_format($row->closing_stock, 2, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary btn-detail"
                                                        data-itemid="<?= $row->item_id; ?>"
                                                        data-itemtype="<?= $row->item_type; ?>"
                                                        data-itemname="<?= htmlspecialchars($row->item_name); ?>"
                                                        data-startdate="<?= htmlspecialchars($filters['start_date']); ?>"
                                                        data-enddate="<?= htmlspecialchars($filters['end_date']); ?>"
                                                        data-toggle="modal" data-target="#detailModal"
                                                        title="<?= lang_line('stock_ledger_view_history', 'Lihat Riwayat Transaksi'); ?>">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

</div>

<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="detailModalLabel">
                    <i class="fas fa-history mr-2"></i><?= lang_line('stock_ledger_modal_title', 'Riwayat Transaksi'); ?> - <span id="detailItemName" class="font-weight-bold"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="info-text mb-3">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <strong><?= lang_line('stock_ledger_modal_period', 'Periode'); ?>:</strong> <span id="detailPeriode" class="font-weight-bold"></span>
                </div>
                <div id="detailContent" class="text-center py-5">
                    <div class="spinner-border text-info" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted"><?= lang_line('stock_ledger_modal_loading', 'Memuat data histori transaksi...'); ?></p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Fungsi format tanggal (dd/mm/YYYY)
    function formatDateSimple(dateStr) {
        if (!dateStr || dateStr.length < 10) return '-';
        try {
            const date = new Date(dateStr.substring(0, 10) + 'T00:00:00'); // Set time to avoid timezone issues
            const d = ('0' + date.getDate()).slice(-2);
            const m = ('0' + (date.getMonth() + 1)).slice(-2);
            const y = date.getFullYear();
            return `${d}/${m}/${y}`;
        } catch (e) {
            console.error("Error formatting date:", dateStr, e);
            return '-';
        }
    }

    // Event handler tombol detail
    $('.btn-detail').on('click', function() {
        const btn = $(this);
        const dataParams = {
            item_id: btn.data('itemid'),
            item_type: btn.data('itemtype'),
            start_date: btn.data('startdate'),
            end_date: btn.data('enddate')
        };
        
        // Set info modal
        $('#detailItemName').text(btn.data('itemname') + ' (' + dataParams.item_type + ')');
        $('#detailPeriode').text(formatDateSimple(dataParams.start_date) + ' - ' + formatDateSimple(dataParams.end_date));
        $('#detailContent').html('<div class="text-center py-5"><div class="spinner-border text-info"></div><p>Memuat data histori...</p></div>');
        $('#detailModal').modal('show'); // Tampilkan modal

        console.log("Fetching details for:", dataParams);

        // Request AJAX
        $.ajax({
            url: "<?= base_url('stock_ledger/get_item_details'); ?>",
            type: "GET",
            dataType: "json",
            data: dataParams,
            success: function(res) {
                console.log("AJAX Response:", res);
                if (res && res.success) {
                    $('#detailContent').html(res.html);
                } else {
                    let errorMsg = res.message || 'Gagal memuat data. Format respons tidak dikenal.';
                    console.error("Failed to load details:", errorMsg);
                    $('#detailContent').html('<div class="alert alert-danger">' + errorMsg + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                let errorMsg = 'Terjadi kesalahan saat menghubungi server.';
                if (xhr.status === 500) {
                    errorMsg = 'Terjadi error di server (500). Cek log aplikasi.';
                } else if (xhr.status === 404) {
                     errorMsg = 'Endpoint tidak ditemukan (404).';
                }
                $('#detailContent').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    });

    // Reset modal saat ditutup
    $('#detailModal').on('hidden.bs.modal', function (e) {
         $('#detailContent').html('<div class="text-center py-5"><div class="spinner-border text-info"></div><p><?= lang_line('stock_ledger_modal_loading', 'Memuat data histori...'); ?></p></div>');
         $('#detailItemName').text('');
         $('#detailPeriode').text('');
    });

});
</script>

<script>
$(document).ready(function() {
    // Initialize DataTables
    const table = $('#stockReportTable').DataTable({
        processing: true,
        serverSide: false,
        paging: true,
        pageLength: 25,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        columnDefs: [
            { orderable: false, targets: [0, 10] }
        ],
        order: [[1, 'asc']],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Column Search
    $('#stockReportTable tfoot .column-search').on('keyup change', function() {
        const columnIndex = $(this).parent().index();
        table.column(columnIndex).search($(this).val()).draw();
    });

    // Global search from filter form
    $('#global_search').on('keyup', function() {
        table.search($(this).val()).draw();
    });

});
</script>