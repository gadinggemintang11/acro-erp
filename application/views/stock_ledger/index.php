<div class="container-fluid mb-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-boxes text-primary mr-2"></i><?= lang_line('stock_ledger_title', 'Laporan Ringkasan Stok Barang'); ?></h1>
        <div>
            <?php
            $export_query_string = http_build_query([
                'month' => $selected_month ?? date('m'),
                'year' => $selected_year ?? date('Y'),
                'warehouse_type' => $filters['warehouse_type'] ?? null,
                'search' => $filters['search'] ?? null
            ]);
            ?>
            <?php if (check_access('stock_ledger', 'export')): ?>
                <a href="<?= base_url('stock_ledger/export_excel?' . $export_query_string); ?>" target="_blank" class="btn btn-success btn-sm shadow-sm mr-1">
                    <i class="fas fa-file-excel fa-sm text-white-50 mr-1"></i> <?= lang_line('stock_ledger_export_excel', 'Export Excel'); ?>
                </a>
                <a href="<?= base_url('stock_ledger/export_pdf?' . $export_query_string); ?>" target="_blank" class="btn btn-danger btn-sm shadow-sm">
                    <i class="fas fa-file-pdf fa-sm text-white-50 mr-1"></i> <?= lang_line('stock_ledger_export_pdf', 'Export PDF'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i><?= lang_line('stock_ledger_filter', 'Filter Laporan'); ?></h6>
        </div>
        <div class="card-body py-2">
            <form action="<?= base_url('stock_ledger/index'); ?>" method="GET" class="form-inline flex-wrap">
                <div class="form-group mb-2 mr-sm-2">
                    <select name="month" id="month" class="form-control form-control-sm">
                        <?php for ($m = 1; $m <= 12; $m++):
                            $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                            $name = date('F', mktime(0, 0, 0, $m, 1));
                        ?>
                            <option value="<?= $val; ?>" <?= ($selected_month == $val) ? 'selected' : ''; ?>><?= $name; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-sm-2">
                    <select name="year" id="year" class="form-control form-control-sm">
                        <?php $currYear = date('Y'); ?>
                        <?php for ($y = $currYear + 1; $y >= $currYear - 5; $y--): ?>
                            <option value="<?= $y; ?>" <?= ($selected_year == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-sm-2">
                    <select name="warehouse_type" id="warehouse_type" class="form-control form-control-sm">
                        <option value=""><?= lang_line('stock_ledger_all_warehouse', '-- Semua Gudang --'); ?></option>
                        <?php if (!empty($item_types)): foreach ($item_types as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key); ?>" <?= (isset($filters['warehouse_type']) && $filters['warehouse_type'] == $key) ? 'selected' : ''; ?>><?= htmlspecialchars($label); ?></option>
                        <?php endforeach;
                        endif; ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-sm-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('stock_ledger_search', 'Cari Kode/Nama Barang...'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mb-2"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('stock_ledger_apply', 'Terapkan'); ?></button>
                <a href="<?= base_url('stock_ledger/index'); ?>" class="btn btn-secondary btn-sm mb-2 ml-1"><i class="fas fa-undo fa-sm mr-1"></i> <?= lang_line('stock_ledger_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('stock_ledger_summary_title', 'Ringkasan Stok Periode'); ?>: <?= date('F Y', strtotime($filters['start_date'])); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" id="stockReportTable" width="100%">
                    <thead class="thead-light text-center">
                        <tr>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_no', 'No'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_warehouse_col', 'Gudang'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_item_code', 'Kode Barang'); ?></th>
                            <th rowspan="2" style="min-width: 200px;" class="align-middle"><?= lang_line('stock_ledger_item_name', 'Nama Barang'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_unit', 'Satuan'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_opening_stock', 'Stok Awal'); ?></th>
                            <th colspan="2"><?= lang_line('stock_ledger_in', 'Masuk'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_out', 'Keluar (Issue/Ship)'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_closing_stock', 'Stok Akhir'); ?></th>
                            <th rowspan="2" class="align-middle"><?= lang_line('stock_ledger_actions', 'Aksi'); ?></th>
                        </tr>
                        <tr>
                            <th><?= lang_line('stock_ledger_in_grn', 'GRN'); ?></th>
                            <th><?= lang_line('stock_ledger_in_return', 'Retur / Lainnya'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3"><?= lang_line('stock_ledger_no_data', 'Tidak ada data stok untuk filter yang dipilih.'); ?></td>
                            </tr>
                            <?php else: foreach ($report as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $start_no++; ?></td>
                                    <td><?= htmlspecialchars($item_types[$row->item_type] ?? ucfirst($row->item_type)); ?></td>
                                    <td><?= htmlspecialchars($row->item_code); ?></td>
                                    <td class="text-left"><?= htmlspecialchars($row->item_name); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row->item_unit); ?></td>
                                    <td class="text-right"><?= number_format($row->opening_stock, 4, ',', '.'); ?></td>
                                    <td class="text-right"><?= number_format($row->receive_qty, 4, ',', '.'); ?></td>
                                    <td class="text-right text-success font-weight-bold"><?= number_format($row->return_qty, 4, ',', '.'); ?></td>
                                    <td class="text-right"><?= number_format($row->out_qty, 4, ',', '.'); ?></td>
                                    <td class="text-right font-weight-bold"><?= number_format($row->closing_stock, 4, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-info btn-xs btn-detail"
                                            data-itemid="<?= $row->item_id; ?>"
                                            data-itemtype="<?= $row->item_type; ?>"
                                            data-itemname="<?= htmlspecialchars($row->item_name); ?>"
                                            data-startdate="<?= htmlspecialchars($filters['start_date']); ?>"
                                            data-enddate="<?= htmlspecialchars($filters['end_date']); ?>"
                                            data-toggle="modal" data-target="#detailModal"
                                            title="<?= lang_line('stock_ledger_view_history', 'Lihat Riwayat Transaksi'); ?>">
                                            <i class="fas fa-history fa-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <p class="text-muted small mb-0">
                    <?= lang_line('stock_ledger_showing', 'Menampilkan'); ?> <?= count($report); ?> <?= lang_line('stock_ledger_of', 'dari'); ?> <?= $total_rows ?? 0; ?> <?= lang_line('stock_ledger_total_items', 'total item'); ?>.
                    <?php if (!empty($pagination)): ?>
                        (<?= lang_line('stock_ledger_page', 'Halaman'); ?> <?= $this->pagination->cur_page ?? 1; ?> <?= lang_line('stock_ledger_of', 'dari'); ?> <?= $this->pagination->total_pages ?? 1; ?>)
                    <?php endif; ?>
                </p>
                <?= $pagination ?? ''; ?>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="detailModalLabel"><i class="fas fa-history mr-2"></i><?= lang_line('stock_ledger_modal_title', 'Riwayat Transaksi'); ?> - <span id="detailItemName" class="font-weight-bold"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p><?= lang_line('stock_ledger_modal_period', 'Periode'); ?>: <strong id="detailPeriode"></strong></p>
                <div id="detailContent" class="text-center py-5">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2"><?= lang_line('stock_ledger_modal_loading', 'Memuat data histori...'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function() {
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
        $('#detailModal').on('hidden.bs.modal', function(e) {
            $('#detailContent').html('<div class="text-center py-5"><div class="spinner-border text-info"></div><p><?= lang_line('stock_ledger_modal_loading', 'Memuat data histori...'); ?></p></div>');
            $('#detailItemName').text('');
            $('#detailPeriode').text('');
        });

    });
</script>
</body>

</html>