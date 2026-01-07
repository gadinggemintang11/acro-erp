<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

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

        /* Table Modern */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th, .table td {
            vertical-align: middle;
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
            border-color: var(--border-color);
        }
        
        .table thead th {
            background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .table tbody tr {
            -webkit-transition: all 0.2s ease;
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.05);
            -webkit-transform: scale(1.005);
            transform: scale(1.005);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(249, 250, 251, 0.5);
        }

        /* Badge Stats */
        .stat-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .stat-badge.positive {
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
        }
        
        .stat-badge.negative {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            color: #991B1B;
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
        }
        
        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.25rem; }
            .export-buttons { flex-direction: column; width: 100%; }
            .export-buttons .btn { width: 100%; }
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
                            <?php if (check_access('stock_ledger', 'export')): ?>
                            <a href="<?= base_url('stock_ledger/export_excel?' . $export_query_string); ?>" target="_blank" class="btn btn-success btn-sm shadow">
                                <i class="fas fa-file-excel mr-1"></i> <?= lang_line('stock_ledger_export_excel', 'Export Excel'); ?>
                            </a>
                            <a href="<?= base_url('stock_ledger/export_pdf?' . $export_query_string); ?>" target="_blank" class="btn btn-danger btn-sm shadow">
                                <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('stock_ledger_export_pdf', 'Export PDF'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="m-0"><i class="fas fa-filter mr-2"></i><?= lang_line('stock_ledger_filter', 'Filter Laporan'); ?></h6>
                    </div>
                    <div class="card-body py-3">
                        <form action="<?= base_url('stock_ledger/index'); ?>" method="GET" class="form-inline flex-wrap">
                            <div class="form-group mb-2 mr-2">
                                <label class="mr-2 text-muted small"><i class="far fa-calendar-alt mr-1"></i>Bulan:</label>
                                <select name="month" id="month" class="form-control form-control-sm">
                                    <?php for ($m = 1; $m <= 12; $m++):
                                        $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                                        $name = date('F', mktime(0,0,0,$m,1));
                                    ?>
                                        <option value="<?= $val; ?>" <?= ($selected_month == $val) ? 'selected' : ''; ?>><?= $name; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 mr-2">
                                <label class="mr-2 text-muted small">Tahun:</label>
                                <select name="year" id="year" class="form-control form-control-sm">
                                    <?php $currYear = date('Y'); ?>
                                    <?php for ($y = $currYear + 1; $y >= $currYear - 5; $y--): ?>
                                        <option value="<?= $y; ?>" <?= ($selected_year == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 mr-2">
                                <label class="mr-2 text-muted small"><i class="fas fa-warehouse mr-1"></i>Gudang:</label>
                                <select name="warehouse_type" id="warehouse_type" class="form-control form-control-sm" style="min-width: 150px;">
                                    <option value=""><?= lang_line('stock_ledger_all_warehouse', '-- Semua Gudang --'); ?></option>
                                    <?php if (!empty($item_types)): foreach ($item_types as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key); ?>" <?= (isset($filters['warehouse_type']) && $filters['warehouse_type'] == $key) ? 'selected' : ''; ?>><?= htmlspecialchars($label); ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                             <div class="form-group mb-2 mr-2 flex-grow-1" style="min-width: 200px;">
                                <label class="mr-2 text-muted small"><i class="fas fa-search mr-1"></i>Cari:</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('stock_ledger_search', 'Kode/Nama Barang...'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                            </div>
                            <div class="form-group mb-2">
                                <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search mr-1"></i> <?= lang_line('stock_ledger_apply', 'Terapkan'); ?></button>
                                <a href="<?= base_url('stock_ledger/index'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-undo mr-1"></i> <?= lang_line('stock_ledger_reset', 'Reset'); ?></a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                         <h6 class="m-0"><i class="fas fa-chart-line mr-2"></i><?= lang_line('stock_ledger_summary_title', 'Ringkasan Stok Periode'); ?>: <span class="text-primary font-weight-bold"><?= date('F Y', strtotime($filters['start_date'])); ?></span></h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="info-text m-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <i class="fas fa-info-circle mr-2 text-info"></i>
                                    <strong><?= lang_line('stock_ledger_showing', 'Menampilkan'); ?>:</strong> <?= count($report); ?> <?= lang_line('stock_ledger_of', 'dari'); ?> <strong><?= $total_rows ?? 0; ?></strong> <?= lang_line('stock_ledger_total_items', 'total item'); ?>
                                    <?php if (!empty($pagination)): ?>
                                        | <?= lang_line('stock_ledger_page', 'Halaman'); ?> <strong><?= $this->pagination->cur_page ?? 1; ?></strong> <?= lang_line('stock_ledger_of', 'dari'); ?> <strong><?= $this->pagination->total_pages ?? 1; ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" id="stockReportTable" width="100%">
                                <thead class="thead-light text-center">
                                    <tr>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_no', 'No'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_warehouse_col', 'Gudang'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_item_code', 'Kode Barang'); ?></th>
                                        <th rowspan="2" style="min-width: 200px;" class="align-middle text-left"><?= lang_line('stock_ledger_item_name', 'Nama Barang'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_unit', 'Satuan'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_opening_stock', 'Stok Awal'); ?></th>
                                        <th colspan="2" class="border-bottom-0"><?= lang_line('stock_ledger_in', 'Masuk'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_out', 'Keluar'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_closing_stock', 'Stok Akhir'); ?></th>
                                        <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_actions', 'Aksi'); ?></th>
                                    </tr>
                                    <tr>
                                        <th class="border-top-0"><?= lang_line('stock_ledger_in_grn', 'GRN'); ?></th>
                                        <th class="border-top-0"><?= lang_line('stock_ledger_in_return', 'Retur'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($report)): ?>
                                        <tr><td colspan="11" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block text-muted"></i>
                                            <strong><?= lang_line('stock_ledger_no_data', 'Tidak ada data stok untuk filter yang dipilih.'); ?></strong>
                                        </td></tr>
                                    <?php else: foreach ($report as $row): ?>
                                        <tr>
                                            <td class="text-center font-weight-bold"><?= $start_no++; ?></td>
                                            <td><span class="badge badge-secondary"><?= htmlspecialchars($item_types[$row->item_type] ?? ucfirst($row->item_type)); ?></span></td>
                                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($row->item_code); ?></td>
                                            <td class="text-left"><?= htmlspecialchars($row->item_name); ?></td>
                                            <td class="text-center"><span class="badge badge-light"><?= htmlspecialchars($row->item_unit); ?></span></td>
                                            <td class="text-right"><?= number_format($row->opening_stock, 2, ',', '.'); ?></td>
                                            <td class="text-right text-info font-weight-bold"><?= number_format($row->receive_qty, 2, ',', '.'); ?></td>
                                            <td class="text-right text-success font-weight-bold"><?= number_format($row->return_qty, 2, ',', '.'); ?></td>
                                            <td class="text-right text-warning font-weight-bold"><?= number_format($row->out_qty, 2, ',', '.'); ?></td>
                                            <td class="text-right">
                                                <span class="stat-badge <?= $row->closing_stock > 0 ? 'positive' : 'negative'; ?>">
                                                    <?= number_format($row->closing_stock, 2, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-info btn-xs btn-detail"
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
                        <?php if (!empty($pagination)): ?>
                        <div class="d-flex justify-content-center p-3 border-top">
                            <?= $pagination; ?>
                        </div>
                        <?php endif; ?>
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