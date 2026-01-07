<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                 <a href="<?= base_url('material_request/submit_mr/'.$header->id); ?>" class="btn btn-info btn-sm mr-1" onclick="return confirm('Yakin ingin submit MR ini ke Gudang?');">
                    <i class="fas fa-paper-plane fa-sm"></i> Submit ke Gudang
                 </a>
            <?php elseif ($header->status == 'Submitted' && check_access('material_request', 'edit')): ?>
                 <a href="<?= base_url('material_request/unsubmit_mr/'.$header->id); ?>" class="btn btn-warning btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan submit MR ini?');">
                    <i class="fas fa-undo fa-sm"></i> Batal Submit
                 </a>
            <?php endif; ?>
            
             <a href="<?= base_url('material_request/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> Export PDF
            </a>
            <a href="<?= base_url('material_request'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>No. MR:</strong> <?= htmlspecialchars($header->mr_number); ?></div>
                <div class="col-md-4"><strong>Tanggal:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong>Status:</strong> <span class="badge badge-<?= $header->status == 'Draft' ? 'secondary' : 'info'; ?>"><?= $header->status; ?></span></div>
             </div>
             <div class="row mt-2">
                 <div class="col-md-4"><strong>Dibuat Oleh:</strong> <?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong>Disiapkan (Gudang):</strong> <?= htmlspecialchars($header->issuer_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong>Diterima (Produksi):</strong> <?= htmlspecialchars($header->receiver_name ?? '-'); ?></div>
             </div>
             <?php if($header->remarks): ?> <hr> <p class="mb-0"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($header->remarks)); ?></p> <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-secondary">Referensi Work Order</h6></div>
                <div class="card-body">
                    <?php if ($header->status == 'Draft'): // Hanya bisa tambah WO jika Draft ?>
                    <div class="form-group">
                        <label>Pilih WO untuk melihat referensi:</label>
                        <select id="wo_selector" class="form-control" style="width: 100%;">
                            <option value="">-- Cari No. Work Order --</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div id="wo_reference_div" class="mt-3" style="font-size: 0.9em;">
                        <p class="text-muted text-center">Pilih Work Order untuk menampilkan referensi BOM dan Rencana Material.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
             <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Item Permintaan Material (MR)</h6>
                    <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addItemModal"> <i class="fas fa-plus fa-sm"></i> Tambah Item</button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light"> <tr><th>WO</th><th>Kode</th><th>Nama Item</th><th>Qty Diminta</th><th>Satuan</th><th>Aksi</th></tr> </thead>
                            <tbody>
                                <?php if(!empty($details)): foreach($details as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d->wo_number); ?></td>
                                    <td><?= htmlspecialchars($d->item_code); ?></td>
                                    <td><?= htmlspecialchars($d->item_name); ?></td>
                                    <td class="text-right"><?= number_format($d->requested_qty, 4); ?></td>
                                    <td><?= htmlspecialchars($d->unit_measure ?? '-'); ?></td>
                                    <td class="text-center">
                                         <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                                        <a href="<?= base_url('material_request/delete_item/'.$d->mr_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete"> <i class="fas fa-trash"></i> </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?> <tr><td colspan="6" class="text-center text-muted py-3">Belum ada material diminta.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($header->status == 'Draft'): ?>
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="<?= base_url('material_request/add_item/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Item ke MR</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Untuk Work Order (WO)</label>
                        <select name="wo_header_id" id="wo_selector_modal" class="form-control" required style="width: 100%;">
                            <option value="">-- Cari No. Work Order --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Komponen (Material / Semifinished)</label>
                        <select name="component_id" id="component_search" class="form-control" required style="width: 100%;">
                             <option value="">-- Cari Kode / Nama Komponen --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Qty Diminta</label>
                        <input type="number" step="any" name="requested_qty" class="form-control" required placeholder="0.0000">
                    </div>
                    <div class="form-group">
                        <label>Catatan Item (Opsional)</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Simpan Item</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>


<?php $this->load->view('templates/footer'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Inisialisasi Select2 untuk pencarian WO (di Referensi)
    $('#wo_selector').select2({
        placeholder: '-- Cari No. Work Order --',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url("material_request/search_open_wos"); ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        }
    });

    // Event saat WO di Referensi dipilih
    $('#wo_selector').on('change', function() {
        const woId = $(this).val();
        $('#wo_reference_div').html('<p class="text-center text-muted">Memuat referensi...</p>');
        if (woId) {
            $.get('<?= base_url("material_request/get_wo_references_json"); ?>', { wo_id: woId }, function(response) {
                if(response.success) {
                    $('#wo_reference_div').html(response.html);
                } else {
                    $('#wo_reference_div').html('<p class="text-center text-danger">Gagal memuat referensi.</p>');
                }
            }, 'json').fail(function(){
                 $('#wo_reference_div').html('<p class="text-center text-danger">Error AJAX.</p>');
            });
        } else {
             $('#wo_reference_div').html('<p class="text-muted text-center">Pilih Work Order untuk menampilkan referensi.</p>');
        }
    });

    // Inisialisasi Select2 untuk Modal Tambah Item (HANYA JIKA STATUS DRAFT)
    if ('<?= $header->status; ?>' === 'Draft') {
        
        // ✅ FIX: Kesalahan copy-paste dihapus dari sini.
        $('#wo_selector_modal').select2({
            placeholder: '-- Cari No. Work Order --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("material_request/search_open_wos"); ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
                cache: true
            },
            dropdownParent: $('#addItemModal')
        });

        $('#component_search').select2({
            placeholder: '-- Cari Kode / Nama Komponen --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("material_request/search_components"); ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
            },
            dropdownParent: $('#addItemModal')
        });
    }
});
</script>

<style>
/* Style Select2 di dalam modal */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051; border: 1px solid #d1d3e2; }
/* Style untuk tabel referensi agar lebih kecil */
#wo_reference_div .table-sm td, #wo_reference_div .table-sm th { padding: .3rem; }
</style>