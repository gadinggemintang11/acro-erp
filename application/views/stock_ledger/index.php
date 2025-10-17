<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-boxes text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
         <div>
            <a href="<?= base_url('stock_ledger/export_excel?' . http_build_query($filters)); ?>" target="_blank" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel fa-sm"></i> Export Excel
            </a>
             <a href="<?= base_url('stock_ledger/export_pdf?' . http_build_query($filters)); ?>" target="_blank" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf fa-sm"></i> Export PDF
            </a>
         </div>
    </div>


    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" action="<?= base_url('stock_ledger/index'); ?>" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="month" class="mr-2">Periode:</label>
                    <select name="month" id="month" class="form-control form-control-sm" style="width: auto;">
                        <?php for ($m=1; $m<=12; $m++): $month_name = date('F', mktime(0, 0, 0, $m, 10)); ?>
                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?= $selected_month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                            <?= $month_name; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                 <div class="form-group mr-3 mb-2">
                    <select name="year" id="year" class="form-control form-control-sm" style="width: auto;">
                        <?php for ($y=date('Y'); $y>=date('Y')-5; $y--): // 5 tahun ke belakang ?>
                        <option value="<?= $y; ?>" <?= $selected_year == $y ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group mr-3 mb-2">
                    <label for="warehouse_type" class="mr-2">Gudang:</label>
                    <select name="warehouse_type" id="warehouse_type" class="form-control form-control-sm" style="width: 150px;">
                        <option value="">-- Semua --</option>
                        <?php foreach($item_types as $code => $desc): ?>
                            <option value="<?= $code; ?>" <?= ($filters['warehouse_type'] ?? '') == $code ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($desc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari Kode / Nama Barang..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>

                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                    <a href="<?= base_url('stock_ledger'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" style="font-size: 0.9em;">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>Gudang</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Stok Awal</th>
                            <th>Masuk</th>
                            <th>Return</th>
                            <th>Keluar</th>
                            <th>Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($report)): foreach($report as $row): ?>
                        <tr>
                            <td class="text-center"><?= ucfirst($row->item_type); ?></td>
                            <td>
                                <a href="#" class="btn-item-detail"
                                   data-itemid="<?= $row->item_id; ?>"
                                   data-itemtype="<?= $row->item_type; ?>"
                                   data-itemname="<?= htmlspecialchars($row->item_name); ?>"
                                   data-toggle="modal" data-target="#itemDetailModal">
                                    <?= htmlspecialchars($row->item_code); ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row->item_name); ?></td>
                            <td class="text-center"><?= htmlspecialchars($row->item_unit ?? '-'); ?></td>
                            <td class="text-right"><?= number_format($row->opening_stock, 2); ?></td>
                            <td class="text-right"><?= $row->receive_qty > 0 ? number_format($row->receive_qty, 2) : '-'; ?></td>
                            <td class="text-right"><?= $row->return_qty > 0 ? number_format($row->return_qty, 2) : '-'; ?></td>
                            <td class="text-right"><?= $row->out_qty > 0 ? number_format($row->out_qty, 2) : '-'; ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($row->closing_stock, 2); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-5">Tidak ada data stok ditemukan untuk filter ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                <?= $pagination; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="itemDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histori Transaksi: <span id="detailItemName"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <p class="text-center">Memuat detail...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<?php $this->load->view('templates/footer'); ?>

<script>
$(document).ready(function() {
    $('.btn-item-detail').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const itemId = button.data('itemid');
        const itemType = button.data('itemtype');
        const itemName = button.data('itemname');
        const startDate = '<?= $filters["start_date"]; ?>'; // Ambil dari filter PHP
        const endDate = '<?= $filters["end_date"]; ?>';

        // Set judul modal
        $('#detailItemName').text(itemName + ' (' + itemId + '/' + itemType + ')');
        // Kosongkan body modal & tampilkan loading
        $('#detailModalBody').html('<p class="text-center">Memuat histori...</p>');

        // Panggil AJAX ke controller
        $.ajax({
            url: '<?= base_url("stock_ledger/get_item_details"); ?>',
            type: 'GET',
            dataType: 'json',
            data: {
                item_id: itemId,
                item_type: itemType,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if(response.success) {
                    $('#detailModalBody').html(response.html); // Masukkan tabel HTML
                } else {
                    $('#detailModalBody').html('<p class="text-center text-danger">Gagal memuat detail: ' + response.message + '</p>');
                }
            },
            error: function() {
                 $('#detailModalBody').html('<p class="text-center text-danger">Terjadi kesalahan saat memuat data.</p>');
            }
        });
    });
});
</script>
<style>
/* Optional: Perbaiki tampilan pagination di mobile */
.pagination { margin-bottom: 0; }
</style>