<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Buat Material Return'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .select2-container { width: 100% !important; }
        .select2-container--bootstrap4 .select2-selection--single { height: calc(1.5em + .75rem + 2px) !important; padding: .375rem .75rem !important; line-height: 1.5; border-radius: .25rem; }
        .table th, .table td { vertical-align: middle; padding: 0.5rem; }
        .is-invalid + .select2-container--bootstrap4 .select2-selection--single { border-color: #e74a3b; }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body id="page-top">

    <div id="wrapper">
        <?php //$this->load->view('templates/sidebar'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php //$this->load->view('templates/topbar'); ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-undo-alt text-warning mr-2"></i><?= htmlspecialchars($title); ?></h3>
                        <a href="<?= base_url('ProdReturn'); ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke List
                        </a>
                    </div>

                    <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                    <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                    <?php if ($this->session->flashdata('warning')): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <form action="<?= base_url('ProdReturn/store'); ?>" method="post" id="form-return">
                            <div class="card-header py-3 bg-gradient-warning text-white">
                                <h6 class="m-0 font-weight-bold">Header Retur</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label for="prod_return_no">No. Retur</label>
                                        <input type="text" name="prod_return_no" id="prod_return_no" class="form-control form-control-sm" value="<?= htmlspecialchars($return_no); ?>" readonly required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="return_date">Tanggal Retur</label>
                                        <input type="date" name="return_date" id="return_date" class="form-control form-control-sm" value="<?= date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">Tanggal wajib diisi.</div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="work_order_id">Work Order (WO)</label>
                                        <select name="work_order_id" id="work_order_id" class="form-control form-control-sm select2-wo" required data-placeholder="-- Pilih Work Order --">
                                            <option value=""></option>
                                            <?php if (!empty($work_orders)): ?>
                                                <?php foreach($work_orders as $wo): ?>
                                                    <option value="<?= $wo['id']; ?>"><?= htmlspecialchars($wo['wo_number']); ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <div class="invalid-feedback">Work Order wajib dipilih.</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Catatan</label>
                                    <textarea name="notes" id="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan atau keterangan opsional..."></textarea>
                                </div>
                            </div>

                            <div class="card-header py-3 mt-3 bg-gradient-warning text-white">
                                <h6 class="m-0 font-weight-bold">Detail Material (Sisa WIP)</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="material_table">
                                        <thead class="thead-light">
                                            <tr class="text-center">
                                                <th>Material</th>
                                                <th>Satuan</th>
                                                <th width="150">Sisa di WIP</th>
                                                <th width="150">Qty Retur</th>
                                                <th>Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="material_list_body">
                                            <tr id="placeholder-row">
                                                <td colspan="5" class="text-center text-muted">
                                                    Pilih Work Order untuk memuat sisa material.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-danger" id="detail-error-msg" style="display: none;">Minimal harus ada 1 item dengan Qty Retur lebih dari 0.</small>
                            </div>

                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save mr-1"></i> Simpan Draft
                                </button>
                                <a href="<?= base_url('ProdReturn'); ?>" class="btn btn-secondary"> Batal </a>
                            </div>
                        </form>
                    </div> </div></div><?php //$this->load->view('templates/footer_content'); ?>
        </div></div><a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <?php //$this->load->view('templates/logout_modal'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {

        $('.select2-wo').select2({
            theme: 'bootstrap4',
            placeholder: $(this).data('placeholder'),
            allowClear: false
        });

        const tableBody = $('#material_list_body');
        const placeholderRowHtml = tableBody.html(); 
        const form = $('#form-return');

        $('#work_order_id').on('change', function() {
            const woId = $(this).val();
            tableBody.html(''); 

            if (!woId) {
                tableBody.html(placeholderRowHtml);
                return;
            }

            tableBody.html('<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data sisa material...</td></tr>');

            console.log("Fetching WIP details for WO ID:", woId);
            $.ajax({
                url: '<?= base_url("ProdReturn/get_wip_details_ajax/"); ?>' + woId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log("Response from get_wip_details_ajax:", response);
                    tableBody.html(''); 

                    if (response && response.success && response.data && response.data.length > 0) {
                        $.each(response.data, function(index, item) {
                            if(!item.material_id || !item.nama_material) return true; 
                            const sisaWip = parseFloat(item.qty_sisa_wip).toFixed(4);
                            
                            // --- 
                            // --- INI ADALAH BAGIAN PERBAIKAN UTAMA ---
                            // --- Pastikan nama input (name="...") sudah benar ---
                            // --- 
                            const newRow = `
                                <tr>
                                    <td>
                                        <input type="hidden" name="material_id[]" value="${item.material_id}">
                                        
                                        ${$('<div/>').text(item.nama_material).html()}
                                    </td>
                                    <td class="text-center">${$('<div/>').text(item.satuan).html()}</td>
                                    <td class="text-center">
                                        <input type="text" class="form-control form-control-sm text-right bg-light" value="${sisaWip}" readonly>
                                    </td>
                                    <td>
                                        <input type="number" name="qty_return[]" class="form-control form-control-sm text-right qty-return-input"
                                               step="0.0001" min="0" max="${sisaWip}" placeholder="0.0000" data-max-qty="${sisaWip}">
                                        <div class="invalid-feedback">Qty melebihi sisa.</div>
                                    </td>
                                    <td>
                                        <input type="text" name="reason[]" class="form-control form-control-sm" placeholder="Opsional">
                                    </td>
                                </tr>
                            `;
                            // --- AKHIR PERBAIKAN NAMA INPUT ---
                            
                            tableBody.append(newRow);
                        });

                    } else {
                        const message = response.message || 'Tidak ada sisa material atau terjadi kesalahan.';
                        console.warn("WIP details status:", (response.success ? 'Success but empty' : 'Failed'), "| Message:", message);
                        tableBody.html(`<tr><td colspan="5" class="text-center text-warning py-3">${message}</td></tr>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching WIP data:', status, error, xhr.responseText);
                    tableBody.html('<tr><td colspan="5" class="text-center text-danger py-3">Gagal memuat data sisa material. Periksa koneksi atau log server.</td></tr>');
                    Swal.fire('Error', 'Gagal memuat data sisa material. Status: ' + status, 'error');
                }
            });
        });

        // Validasi Real-time Qty Retur
        tableBody.on('input', '.qty-return-input', function() {
            var input = $(this);
            var qty = parseFloat(input.val() || 0);
            var maxQty = parseFloat(input.data('max-qty') || 0);
            if (isNaN(qty)) qty = 0;
            if (isNaN(maxQty)) maxQty = 0;
            input.removeClass('is-invalid');
            if (qty > maxQty) {
                input.val(maxQty.toFixed(4));
                input.addClass('is-invalid');
            }
            if (qty < 0) {
                input.val('0.0000');
            }
        });

        // Validasi Client-Side sebelum Submit Form
        form.on('submit', function(event) {
            console.log("Attempting to submit form-return...");
            $('#work_order_id').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
            $('#return_date').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
            $('#detail-error-msg').hide();
            tableBody.find('.qty-return-input').removeClass('is-invalid');

            let isValid = true;
            let totalReturn = 0;
            const woId = $('#work_order_id').val();
            const returnDate = $('#return_date').val();
            const returnQtys = tableBody.find('.qty-return-input');
            const materialInputs = tableBody.find('input[name="material_id[]"]'); // Cek input material

            // 1. Validasi Header
            if (!woId || woId === '') {
                console.error("Validation failed: WO ID is empty.");
                $('#work_order_id').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
                isValid = false;
            }
            if (!returnDate || returnDate === '') {
                 console.error("Validation failed: Return Date is empty.");
                 $('#return_date').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
                 isValid = false;
            }

            // 2. Validasi Detail
            if (materialInputs.length === 0) { // Cek jika tidak ada baris item
                console.error("Validation failed: No material items loaded.");
                $('#detail-error-msg').text('Silakan pilih Work Order dan tunggu material dimuat.').show();
                isValid = false;
            } else {
                returnQtys.each(function() {
                    let qty = parseFloat($(this).val()) || 0;
                    let max = parseFloat($(this).data('max-qty')) || 0;
                     if (isNaN(qty)) qty = 0; if (isNaN(max)) max = 0;
                    if (qty > max) { $(this).addClass('is-invalid'); isValid = false; console.error("Validation failed: Qty exceeds max."); }
                    if (qty < 0) { $(this).addClass('is-invalid'); isValid = false; console.error("Validation failed: Negative Qty."); }
                    totalReturn += qty;
                });
                if (totalReturn <= 0 && isValid) { // Cek total hanya jika individual valid
                    console.error("Validation failed: Total return quantity is zero.");
                    $('#detail-error-msg').text('Minimal harus ada 1 item dengan Qty Retur lebih dari 0.').show();
                    isValid = false;
                }
            }

            if (!isValid) {
                console.warn("Form submission prevented due to validation errors.");
                event.preventDefault();
                Swal.fire({
                    title: 'Input Tidak Valid',
                    text: 'Harap periksa kembali field yang wajib diisi dan Qty Retur.',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500
                });
                return false;
            }

            console.log("Form data seems valid. Submitting form...");
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        });

    }); // Akhir $(document).ready()
    </script>

</body>
</html>