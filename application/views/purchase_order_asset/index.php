<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <?php if (check_access('purchase_order_asset', 'add')): ?>
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addHeaderModal">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Buat PO Asset Baru
        </button>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Purchase Order Asset</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="poTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th width="18%">Nomor PO</th>
                            <th width="12%">Tanggal PO</th>
                            <th width="27%">Supplier</th>
                            <th width="15%">Total Amount</th>
                            <th width="10%">Status</th>
                            <th width="18%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pos)): foreach ($pos as $po): ?>
                        <tr>
                            <td class="text-center">
                                <a href="<?= base_url('purchase_order_asset/detail/' . $po->id) ?>" class="font-weight-bold text-primary">
                                    <?= htmlspecialchars($po->po_number) ?>
                                </a>
                            </td>
                            <td class="text-center" data-order="<?= strtotime($po->po_date); ?>">
                                <?= date('d/m/Y', strtotime($po->po_date)) ?>
                            </td>
                            <td><?= htmlspecialchars($po->nama_supplier) ?></td>
                            <td class="text-right">
                                <?php
                                    $total = isset($po->total_amount) ? $po->total_amount : 0;
                                    echo '<strong>' . number_format($total, 0, ',', '.') . '</strong>';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $status_colors = [ 'Draft' => 'warning', 'Submitted' => 'info', 'Confirmed' => 'primary', 'Completed' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary' ];
                                    $color = $status_colors[$po->status] ?? 'dark';
                                ?>
                                <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($po->status); ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('purchase_order_asset/detail/' . $po->id) ?>" class="btn btn-info btn-sm btn-load" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($po->status == 'Draft' && check_access('purchase_order_asset', 'delete')): ?>
                                <a href="<?= base_url('purchase_order_asset/delete_header/' . $po->id) ?>" class="btn btn-danger btn-sm btn-delete ml-1" title="Hapus PO" data-message="Yakin hapus PO Draft ini?">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center">Belum ada data Purchase Order Asset.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php if (check_access('purchase_order_asset', 'add')): ?>
<div class="modal fade" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order_asset/add_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Header Purchase Order Asset</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-control select2-modal" required style="width: 100%;">
                                <option value="">-- Pilih Supplier --</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?= $sup->id; ?>"><?= htmlspecialchars($sup->nama_supplier); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tanggal PO <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nomor PO <small class="text-muted">(Biarkan kosong untuk auto-generate)</small></label>
                            <input type="text" name="po_number" class="form-control" placeholder="Contoh: POA/ACRO/26110001">
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 form-group">
                            <label>Mode Pengiriman</label>
                            <select name="shipping_mode_code" class="form-control select2-modal" style="width: 100%;">
                                <option value="">-- Pilih Mode --</option>
                                <?php foreach ($shipping_modes as $mode): ?> <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-6 form-group">
                            <label>Mode Pembayaran</label>
                            <select name="payment_mode_code" class="form-control select2-modal" style="width: 100%;">
                                <option value="">-- Pilih Mode --</option>
                                <?php foreach ($payment_modes as $mode): ?> <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                         <div class="col-md-6 form-group">
                            <label>Mata Uang <span class="text-danger">*</span></label>
                            <select name="currency_code" id="add_currency_code" class="form-control select2-modal" required style="width: 100%;">
                                <?php foreach ($currencies as $curr): ?> <option value="<?= $curr->code; ?>" <?= ($curr->code == 'IDR') ? 'selected' : ''; ?>><?= htmlspecialchars($curr->description); ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" id="add_exchange_rate_div" style="display: none;">
                            <label>Kurs Tukar (Rate) <span class="text-danger">*</span></label>
                            <input type="text" name="exchange_rate" class="form-control autonumeric-4dec text-right" value="1.0000">
                        </div>
                    </div>
                     <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="add_has_ppn" name="has_ppn" value="1">
                        <label class="form-check-label" for="add_has_ppn">Termasuk PPN 11%</label>
                    </div>
                    <div class="form-group">
                        <label>Catatan / Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<script>
$(document).ready(function() {
    if ($('#dataTable').length) {
        $('#dataTable').DataTable();
    }

    if ($('.select2-modal').length) {
        $('.select2-modal').select2({
            dropdownParent: $('#addHeaderModal')
        });
    }

    let anAddExchangeRate;
    const $exchangeRateInput = $('#add_exchange_rate_div input.autonumeric-4dec');

    if ($exchangeRateInput.length && typeof AutoNumeric === 'function') {
       anAddExchangeRate = new AutoNumeric($exchangeRateInput[0], { decimalPlaces: 4, modifyValueOnWheel: false });
    }

    function toggleAddKurs() {
        const $rateDiv = $('#add_exchange_rate_div');
        const curr = $('#add_currency_code').val();

        if (curr !== 'IDR') {
            $rateDiv.slideDown();
            $exchangeRateInput.prop('required', true);
        } else {
            $rateDiv.slideUp();
            $exchangeRateInput.prop('required', false);
            if (anAddExchangeRate) {
                anAddExchangeRate.set(1);
            } else {
                $exchangeRateInput.val('1.0000');
            }
        }
    }

    toggleAddKurs();
    $('#add_currency_code').on('change', toggleAddKurs);

    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var message = $(this).data('message') || 'Yakin ingin menghapus data ini?';

        if (typeof Swal !== 'undefined') {
             Swal.fire({
              title: message, text: 'Data yang dihapus tidak dapat dikembalikan!', icon: 'warning',
              showCancelButton: true, confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal',
              confirmButtonColor: '#e74a3b', cancelButtonColor: '#858796', reverseButtons: true
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = url;
              }
            });
        } else {
            if (confirm(message)) {
                window.location.href = url;
            }
        }
    });

    $('#addHeaderModal form').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    var poTable = $('#poTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        "columnDefs": [
            { "orderable": false, "targets": 5 }
        ]
    });
});
</script>

<style>
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051 !important; }
</style>
