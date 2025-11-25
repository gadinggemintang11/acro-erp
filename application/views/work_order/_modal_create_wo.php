<!-- Modal Buat Work Order Baru -->
<div class="modal fade" id="createWoModal" tabindex="-1" role="dialog" aria-labelledby="createWoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createWoModalLabel">Buat Work Order Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- ✅ FIX: Action form mengarah ke work_order/create -->
            <form action="<?= base_url('work_order/create'); ?>" method="POST">
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label for="select-sc">Pilih Sales Contract</label>
                        <select class="form-control" id="select-sc" name="sales_contract_id" required style="width: 100%;"></select>
                    </div>

                    <div class="form-group">
                        <label>Finished Good (Otomatis)</label>
                        <input type="text" id="fg_name" class="form-control" readonly>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="planned_qty">Qty Rencana Produksi</label>
                            <input type="number" step="0.01" class="form-control" id="planned_qty" name="planned_qty" required>
                            <small class="form-text text-muted" id="sc_qty_info">Jumlah FG yang akan diproduksi.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="planned_start_date">Tgl Mulai Rencana</label>
                            <input type="date" class="form-control" id="planned_start_date" name="planned_start_date" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="planned_end_date">Tgl Selesai Rencana</label>
                            <input type="date" class="form-control" id="planned_end_date" name="planned_end_date">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Catatan</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Lanjut ke Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript untuk Modal -->
<script>
$(document).ready(function() {
    // Inisialisasi Select2 di dalam modal
    $('#select-sc').select2({
        dropdownParent: $('#createWoModal'),
        placeholder: 'Cari No SC / Nama Barang...',
        width: '100%',
        ajax: {
            // ✅ FIX: URL AJAX mengarah ke fungsi yang benar
            url: "<?= base_url('work_order/ajax_search_open_sc'); ?>",
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

    // Saat SC dipilih, ambil detailnya
    $('#select-sc').on('select2:select', function(e) {
        var sc_id = e.params.data.id;
        
        $.ajax({
            url: "<?= base_url('work_order/ajax_get_sc_details'); ?>",
            type: "GET",
            dataType: "json",
            data: { sc_id: sc_id },
            success: function(response) {
                if(response.success) {
                    $('#fg_name').val(response.details.fg_name + ' (' + response.details.fg_code + ')');
                    $('#planned_qty').val(response.details.po_qty);
                    $('#sc_qty_info').text('Qty dari SC: ' + response.details.po_qty);
                } else {
                    alert('Gagal mengambil detail SC.');
                }
            }
        });
    });

    // Reset form saat modal ditutup
    $('#createWoModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#select-sc').val(null).trigger('change');
        $('#fg_name').val('');
        $('#sc_qty_info').text('Jumlah FG yang akan diproduksi.');
    });
});
</script>