<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-tasks text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('production_process', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#createProcessModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat Proses Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('production_process/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No Produksi / No WO / No SC..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Cari</button>
                    <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. Produksi</th>
                            <th>No. WO</th>
                            <th>No. SC</th>
                            <th>Style</th>
                            <th>Qty Rencana</th>
                            <th>Tgl Produksi</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($processes)): foreach($processes as $proc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($proc->production_number); ?></td>
                            <td><?= htmlspecialchars($proc->wo_number); ?></td>
                            <td><?= htmlspecialchars($proc->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($proc->fg_name); ?></td>
                            <td class="text-right"><?= number_format($proc->planned_qty); ?></td>
                            <td><?= date('d M Y', strtotime($proc->production_date)); ?></td>
                            <td><span class="badge badge-info"><?= $proc->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('production_process/detail/'.$proc->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-cogs"></i> Proses
                                </a>
                                <?php if ($proc->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                <a href="<?= base_url('production_process/delete_header/'.$proc->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus Proses Produksi ini?">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data Proses Produksi</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createProcessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="createProcessForm" method="post" action="<?= base_url('production_process/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Proses Produksi Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Work Order (Status: In Progress)</label>
                        <select name="wo_header_id" id="wo_selector" class="form-control" required style="width: 100%;">
                            <option value="">-- Cari No. Work Order --</option>
                        </select>
                    </div>
                    <div id="wo_details_info" style="display: none;" class="alert alert-secondary p-2 mb-3">
                        <small>
                            Sales Contract: <strong id="info_sc_number">-</strong><br>
                            Style: <strong id="info_fg_name">-</strong><br>
                            Qty Rencana: <strong id_info_planned_qty">-</strong>
                        </small>
                    </div>
                     <div class="row">
                        <div class="col-md-6 form-group">
                             <label>Tanggal Mulai Produksi</label>
                            <input type="date" name="production_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load">
                        <i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Detail
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .select2-container { width: 100% !important; }
    .select2-dropdown { z-index: 1051; } /* Agar muncul di atas modal */
</style>

<script>
$(function() { // Shorthand untuk $(document).ready()
    
    // Inisialisasi Select2 untuk WO
    $('#wo_selector').select2({
        placeholder: '-- Cari No. Work Order (In Progress) --',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url("production_process/search_open_wos"); ?>', // URL AJAX
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        dropdownParent: $('#createProcessModal') // Penting agar nempel di modal
    });

    // Event handler saat WO dipilih
    $('#wo_selector').on('change', function() {
        const woId = $(this).val();
        $('#wo_details_info').hide();
        if (woId) {
            $.get('<?= base_url("production_process/ajax_get_wo_details"); ?>', { wo_id: woId }, function(response) {
                if (response.success) {
                    $('#info_sc_number').text(response.details.sc_number);
                    $('#info_fg_name').text(response.details.fg_name);
                    $('#info_planned_qty').text(response.details.planned_qty);
                    $('#wo_details_info').show();
                } else { 
                    alert('Detail WO tidak ditemukan!'); 
                }
            }, 'json').fail(function(){ 
                alert('Gagal mengambil detail WO.'); 
            });
        }
    });
});
</script>