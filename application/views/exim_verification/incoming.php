<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle"></i> EXIM Verification - Pemasukan Barang
        </h1>
        <a href="<?= site_url('exim_verification'); ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Pencarian</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('exim_verification/incoming'); ?>">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Dari</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Sampai</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">-- Semua Supplier --</option>
                                <?php foreach($suppliers as $supplier): ?>
                                    <option value="<?= $supplier->id; ?>" <?= ($filters['supplier_id'] == $supplier->id ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($supplier->nama_supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>BC Type</label>
                            <select name="bc_type" class="form-control">
                                <option value="">-- Semua BC Type --</option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= ($filters['bc_type'] == $bc->code ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($bc->description); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status Verifikasi</label>
                            <select name="verified_status" class="form-control">
                                <option value="unverified" <?= ($filters['verified_status'] == 'unverified' ? 'selected' : ''); ?>>Belum Diverifikasi</option>
                                <option value="verified" <?= ($filters['verified_status'] == 'verified' ? 'selected' : ''); ?>>Sudah Diverifikasi</option>
                                <option value="all" <?= ($filters['verified_status'] == 'all' ? 'selected' : ''); ?>>Semua</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>&nbsp;</label><br>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="<?= site_url('exim_verification/incoming'); ?>" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">List GRN - Penerimaan Barang</h6>
            <?php if (!empty($grn_list) && $filters['verified_status'] == 'unverified'): ?>
                <button type="button" class="btn btn-sm btn-success" onclick="bulkVerify()">
                    <i class="fas fa-check-double"></i> Verifikasi Terpilih
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($grn_list)): ?>
                <p class="text-center text-muted">Tidak ada data GRN.</p>
            <?php else: ?>
                <form id="bulk-verify-form" method="post" action="<?= site_url('exim_verification/bulk_verify_incoming'); ?>">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable">
                            <thead class="thead-light">
                                <tr>
                                    <?php if ($filters['verified_status'] == 'unverified'): ?>
                                        <th width="30">
                                            <input type="checkbox" id="check-all">
                                        </th>
                                    <?php endif; ?>
                                    <th>GRN Number</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>BC Type</th>
                                    <th>BC Number</th>
                                    <th>BC Date</th>
                                    <th class="text-right">Jumlah Item</th>
                                    <th class="text-right">Total Qty</th>
                                    <th>Status</th>
                                    <th>Status Verifikasi</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($grn_list as $grn): ?>
                                <tr>
                                    <?php if ($filters['verified_status'] == 'unverified'): ?>
                                        <td>
                                            <input type="checkbox" name="grn_ids[]" value="<?= $grn->id; ?>" class="grn-checkbox">
                                        </td>
                                    <?php endif; ?>
                                    <td><strong><?= htmlspecialchars($grn->grn_number); ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($grn->grn_date)); ?></td>
                                    <td><?= htmlspecialchars($grn->nama_supplier ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($grn->bc_type_code ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($grn->bc_number ?? '-'); ?></td>
                                    <td><?= $grn->bc_date ? date('d/m/Y', strtotime($grn->bc_date)) : '-'; ?></td>
                                    <td class="text-right"><?= number_format($grn->num_items, 0); ?></td>
                                    <td class="text-right"><?= number_format($grn->total_qty, 2); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?= $grn->status; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($grn->exim_verified): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Terverifikasi
                                            </span><br>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($grn->exim_verified_at)); ?><br>
                                                oleh: <?= htmlspecialchars($grn->verified_by_name ?? '-'); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Belum Diverifikasi
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info mb-1" onclick="viewDetails(<?= $grn->id; ?>, '<?= htmlspecialchars($grn->grn_number, ENT_QUOTES); ?>')">
                                            <i class="fas fa-list"></i> Detail
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary mb-1" onclick="editBC(<?= $grn->id; ?>, '<?= $grn->bc_type_code ?? ''; ?>', '<?= $grn->bc_number ?? ''; ?>', '<?= $grn->bc_date ?? ''; ?>')">
                                            <i class="fas fa-edit"></i> Edit BC
                                        </button>
                                        <?php if ($grn->exim_verified): ?>
                                            <a href="<?= site_url('exim_verification/unverify_incoming/' . $grn->id); ?>" 
                                               class="btn btn-sm btn-warning mb-1" 
                                               onclick="return confirm('Yakin ingin membatalkan verifikasi GRN ini?');">
                                                <i class="fas fa-undo"></i> Batal
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= site_url('exim_verification/verify_incoming/' . $grn->id); ?>" 
                                               class="btn btn-sm btn-success mb-1"
                                               onclick="return confirm('Yakin ingin memverifikasi GRN ini?');">
                                                <i class="fas fa-check"></i> Verifikasi
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detail Items -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Item GRN: <span id="modal-grn-number"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detail-content">
                    <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit BC -->
<div class="modal fade" id="editBCModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Edit BC Info - GRN</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editBCForm">
                <div class="modal-body">
                    <input type="hidden" id="edit-grn-id" name="grn_id">
                    <div class="form-group">
                        <label>BC Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit-bc-type" name="bc_type_code" required>
                            <option value="">-- Pilih BC Type --</option>
                            <?php foreach($bc_types as $bc): ?>
                                <option value="<?= $bc->code; ?>"><?= htmlspecialchars($bc->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>BC Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-bc-number" name="bc_number" required>
                    </div>
                    <div class="form-group">
                        <label>BC Date</label>
                        <input type="date" class="form-control" id="edit-bc-date" name="bc_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Check all checkboxes
document.getElementById('check-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.grn-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Bulk verify function
function bulkVerify() {
    const checkboxes = document.querySelectorAll('.grn-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal 1 GRN untuk diverifikasi');
        return;
    }
    
    if (confirm(`Yakin ingin memverifikasi ${checkboxes.length} GRN yang dipilih?`)) {
        document.getElementById('bulk-verify-form').submit();
    }
}

// View details function
function viewDetails(grnId, grnNumber) {
    $('#modal-grn-number').text(grnNumber);
    $('#detailModal').modal('show');
    $('#detail-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    
    $.ajax({
        url: '<?= site_url('exim_verification/get_grn_details/'); ?>' + grnId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                html += '<thead class="thead-light"><tr><th>No</th><th>Kode Barang</th><th>Nama Barang</th><th>Qty</th><th>Satuan</th><th>Unit Value</th></tr></thead><tbody>';
                
                if (response.data.length === 0) {
                    html += '<tr><td colspan="6" class="text-center">Tidak ada detail item</td></tr>';
                } else {
                    response.data.forEach(function(item, index) {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td>' + (item.item_code || '-') + '</td>';
                        html += '<td>' + (item.item_name || '-') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.receive_qty).toFixed(2) + '</td>';
                        html += '<td>' + (item.item_unit || '-') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.unit_value || 0).toLocaleString('id-ID') + '</td>';
                        html += '</tr>';
                    });
                }
                html += '</tbody></table></div>';
                $('#detail-content').html(html);
            } else {
                $('#detail-content').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#detail-content').html('<div class="alert alert-danger">Terjadi kesalahan saat memuat data</div>');
        }
    });
}

// Edit BC function
function editBC(grnId, bcType, bcNumber, bcDate) {
    $('#edit-grn-id').val(grnId);
    $('#edit-bc-type').val(bcType);
    $('#edit-bc-number').val(bcNumber);
    $('#edit-bc-date').val(bcDate);
    $('#editBCModal').modal('show');
}

// Handle BC edit form submission
$('#editBCForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: '<?= site_url('exim_verification/update_grn_bc'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#editBCModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menyimpan data');
        }
    });
});
</script>
