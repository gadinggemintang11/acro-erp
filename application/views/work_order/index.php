<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-industry text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('work_order', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#createWoModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat Work Order Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('work_order/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No WO / No SC / Nama Style..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> Cari</button>
                    <a href="<?= base_url('work_order'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> Reset</a>
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
                            <th>No. Work Order</th>
                            <th>No. Sales Contract</th>
                            <th>Style</th>
                            <th>Qty Rencana</th>
                            <th>Tgl Mulai Rencana</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($wos)): foreach($wos as $wo): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($wo->wo_number); ?></td>
                            <td><?= htmlspecialchars($wo->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($wo->fg_name); ?></td>
                            <td class="text-right"><?= number_format($wo->planned_qty); ?></td>
                            <td><?= $wo->planned_start_date ? date('d M Y', strtotime($wo->planned_start_date)) : '-'; ?></td>
                            <td><span class="badge badge-<?= $wo->status == 'Completed' ? 'success' : ($wo->status == 'In Progress' ? 'warning' : 'info') ?>"><?= $wo->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('work_order/detail/'.$wo->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if ($wo->status == 'Planned' && check_access('work_order', 'delete')): ?>
                                <a href="<?= base_url('work_order/delete_wo/'.$wo->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus Work Order ini?">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data Work Order</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createWoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="createWoForm" method="post" action="<?= base_url('work_order/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Work Order Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Sales Contract</label>
                        <select name="sales_contract_id" id="sc_selector" class="form-control" required style="width: 100%;">
                            <option value="">-- Pilih Sales Contract --</option>
                            <?php foreach($open_contracts as $sc): ?>
                                <option value="<?= $sc->id; ?>" data-qty="<?= $sc->po_qty; ?>">
                                    <?= htmlspecialchars($sc->sc_number . ' - ' . $sc->style_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="sc_details_info" style="display: none;" class="alert alert-secondary p-2 mb-3">
                        <small>
                            Style: <strong id="info_style_name">-</strong> (<span id="info_style_code">-</span>)<br>
                            Qty Kontrak: <strong id="info_sc_qty">-</strong>
                        </small>
                    </div>
                     <div class="row">
                         <div class="col-md-4 form-group">
                             <label>Qty Rencana Produksi</label>
                            <input type="number" name="planned_qty" id="planned_qty" class="form-control" required>
                             <small class="form-text text-muted">Jumlah FG yang akan diproduksi.</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tgl Mulai Rencana</label>
                            <input type="date" name="planned_start_date" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tgl Selesai Rencana</label>
                            <input type="date" name="planned_end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load">
                        <i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Material
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
<style>.select2-container { width: 100% !important; }</style>

<script>
$(document).ready(function() {
    $('#sc_selector').select2({
        placeholder: '-- Pilih Sales Contract --',
        dropdownParent: $('#createWoModal')
    });

    $('#sc_selector').on('change', function() {
        const scId = $(this).val();
        $('#sc_details_info').hide();
        if (scId) {
            $.get('<?= base_url("work_order/get_sc_details_json"); ?>', { sc_id: scId }, function(response) {
                if (response.success) {
                    $('#info_style_name').text(response.details.fg_name);
                    $('#info_style_code').text(response.details.fg_code);
                    $('#info_sc_qty').text(response.details.po_qty);
                    $('#sc_details_info').show();
                } else { alert('Detail SC tidak ditemukan!'); }
            }, 'json').fail(function(){ alert('Gagal ambil detail SC.'); });
        }
    });
});
</script>