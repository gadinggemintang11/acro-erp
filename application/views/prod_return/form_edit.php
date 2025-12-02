<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ambil data header dan detail dari $return_data
$header = $return_data['header'] ?? null;
$details = $return_data['details'] ?? [];

// Redirect jika data header tidak ada (seharusnya sudah dicek controller)
if (!$header) {
    $this->session->set_flashdata('error', 'Data retur tidak ditemukan untuk diedit.');
    redirect('ProdReturn/index');
    // exit; // Optional exit
}
// Redirect jika status bukan Draft (seharusnya sudah dicek controller)
if ($header['status'] != 'Draft') {
     $this->session->set_flashdata('error', 'Hanya status DRAFT yang bisa diedit.');
     redirect('ProdReturn/view/' . $header['id']);
     // exit; // Optional exit
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Edit Material Return'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table th, .table td { vertical-align: middle; padding: 0.5rem; }
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
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
                        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-edit text-warning mr-2"></i><?= htmlspecialchars($title); ?></h3>
                        <a href="<?= base_url('ProdReturn/view/' . $header['id']); ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('back_to_detail', 'Kembali ke Detail'); ?>
                        </a>
                    </div>

                    <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                    <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
                    <?php if ($this->session->flashdata('warning')): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>

                    <div class="card shadow mb-4">
                        <form action="<?= base_url('ProdReturn/update/' . $header['id']); ?>" method="post" id="form-return-edit">
                            <div class="card-header py-3 bg-gradient-warning text-white">
                                <h6 class="m-0 font-weight-bold"><?= lang_line('prodreturn_edit_header', 'Edit Header Retur'); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label><?= lang_line('prodreturn_return_no', 'No. Retur'); ?></label>
                                        <input type="text" name="prod_return_no_display" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($header['prod_return_no']); ?>" readonly>
                                        <?php // No Retur tidak perlu disubmit lagi saat update ?>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="return_date"><?= lang_line('prodreturn_return_date', 'Tanggal Retur'); ?></label>
                                        <input type="date" name="return_date" id="return_date" class="form-control form-control-sm" value="<?= htmlspecialchars($header['return_date']); ?>" required>
                                        <div class="invalid-feedback"><?= lang_line('required_field', 'Wajib diisi'); ?></div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label><?= lang_line('prodreturn_work_order', 'Work Order (WO)'); ?></label>
                                        <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($header['wo_number']); ?>" readonly title="<?= lang_line('wo_cannot_change', 'Work Order tidak dapat diubah'); ?>">
                                        <input type="hidden" name="work_order_id" value="<?= $header['work_order_id']; ?>"> <?php // WO ID tetap disubmit jika diperlukan controller ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes"><?= lang_line('prodreturn_notes', 'Catatan'); ?></label>
                                    <textarea name="notes" id="notes" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($header['notes']); ?></textarea>
                                </div>
                            </div>

                            <div class="card-header py-3 mt-3 bg-gradient-warning text-white">
                                <h6 class="m-0 font-weight-bold"><?= lang_line('prodreturn_edit_details', 'Edit Detail Material'); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info small p-2">
                                    <strong><?= lang_line('attention', 'Perhatian'); ?>:</strong> <?= lang_line('edit_warning', 'Mengupdate draft ini akan mengganti semua item detail dengan daftar di bawah. Pastikan semua item tercantum dengan Qty yang benar.'); ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="material_table">
                                        <thead class="thead-light">
                                            <tr class="text-center">
                                                <th><?= lang_line('prodreturn_material_name', 'Material'); ?></th>
                                                <th><?= lang_line('prodreturn_unit', 'Satuan'); ?></th>
                                                <th width="150"><?= lang_line('prodreturn_qty_return', 'Qty Retur'); ?></th>
                                                <th><?= lang_line('prodreturn_reason', 'Alasan'); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(!empty($details)): foreach($details as $index => $item): ?>
                                            <tr>
                                                <td>
                                                    <?php // Hidden fields untuk data yang dikirim ke controller update ?>
                                                    <input type="hidden" name="detail_id[<?= $index ?>]" value="<?= $item['id']; ?>">
                                                    <input type="hidden" name="material_id[<?= $index ?>]" value="<?= $item['material_id']; ?>">
                                                    <?= htmlspecialchars($item['nama_material']); ?>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($item['satuan_code']); // Ganti ke satuan_code ?></td>
                                                <td>
                                                    <input type="number" name="qty_return[<?= $index ?>]" class="form-control form-control-sm text-right qty-return-input"
                                                           value="<?= number_format((float)$item['qty_return'], 4, '.', ''); // Format tanpa pemisah ribuan ?>"
                                                           step="0.0001" min="0.0001" required>
                                                     <div class="invalid-feedback"><?= lang_line('qty_required', 'Qty > 0'); ?></div>
                                                     <?php /* Jika ingin validasi max, perlu data sisa WIP di sini
                                                     <small class="text-muted">Maks: <?= $wip_balance[$item['material_id']] ?? '?' ?></small>
                                                     */ ?>
                                                </td>
                                                <td>
                                                    <input type="text" name="reason[<?= $index ?>]" class="form-control form-control-sm"
                                                           value="<?= htmlspecialchars($item['reason']); ?>" placeholder="<?= lang_line('optional', 'Opsional'); ?>">
                                                </td>
                                                 </tr>
                                            <?php endforeach; else: ?>
                                            <tr><td colspan="5" class="text-center text-danger py-3"><?= lang_line('no_items_or_failed', 'Gagal memuat detail item atau tidak ada item'); ?></td></tr> <?php // Colspan disesuaikan ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-danger" id="detail-error-msg" style="display: none;"><?= lang_line('prodreturn_min_item_required', 'Minimal 1 item dengan Qty > 0'); ?></small>
                            </div>

                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save mr-1"></i> <?= lang_line('update_draft', 'Update Draft'); ?>
                                </button>
                                <a href="<?= base_url('ProdReturn/view/' . $header['id']); ?>" class="btn btn-secondary"> <?= lang_line('prodreturn_cancel', 'Batal'); ?> </a>
                            </div>
                        </form>
                    </div> </div></div><?php //$this->load->view('templates/footer_content'); ?>
        </div></div><a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <?php //$this->load->view('templates/logout_modal'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {

        const form = $('#form-return-edit');
        const tableBody = $('#material_table tbody');

        // --- Validasi Real-time Qty Retur ---
        tableBody.on('input', '.qty-return-input', function() {
            var input = $(this);
            var qty = parseFloat(input.val() || 0);

            input.removeClass('is-invalid'); // Hapus error dulu
            if (isNaN(qty) || qty <= 0) {
                // Jika tidak valid atau <= 0, tandai error (tapi jangan ubah value)
                 if (input.prop('required')) { // Hanya tandai error jika required
                     input.addClass('is-invalid');
                     console.warn("Invalid Qty:", qty);
                 }
                 // Set value ke 0.0001 jika kurang dari min? Atau biarkan?
                 // if (qty < 0.0001 && qty != 0) input.val('0.0001');

            }
            // Validasi Max tidak bisa dilakukan client-side tanpa data sisa WIP terbaru
        });

        // --- Validasi Client-Side sebelum Submit Form ---
        form.on('submit', function(event) {
            console.log("Attempting to submit form-return-edit...");

            // Hapus tanda error sebelumnya
            $('#return_date').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').hide();
            tableBody.find('.qty-return-input').removeClass('is-invalid');
            $('#detail-error-msg').hide();

            let isValid = true;
            let totalReturn = 0;
            const returnDate = $('#return_date').val();
            const returnQtys = tableBody.find('.qty-return-input');

            // 1. Validasi Header
            if (!returnDate || returnDate === '') {
                 console.error("Validation failed: Return Date is empty.");
                 $('#return_date').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
                 isValid = false;
            }

            // 2. Validasi Detail (minimal 1 item & qty > 0)
            if (returnQtys.length === 0) { // Jika tidak ada baris detail sama sekali
                console.error("Validation failed: No material detail rows found.");
                $('#detail-error-msg').text('Tidak ada item material dalam daftar.').show();
                isValid = false;
            } else {
                returnQtys.each(function() {
                    let input = $(this);
                    let qty = parseFloat(input.val()) || 0;
                    if (isNaN(qty) || qty <= 0) {
                        // Jika ada input qty <= 0, anggap tidak valid
                        if(input.prop('required')) { // Tandai error jika field required
                           input.addClass('is-invalid');
                           console.error("Validation failed: Qty is zero or invalid for:", input.closest('tr').find('td:first').text().trim());
                           isValid = false;
                        }
                    } else {
                        totalReturn += qty;
                    }
                });

                // Cek apakah total Qty > 0 (jika individual valid semua tapi total 0)
                if (totalReturn <= 0 && isValid) {
                    console.error("Validation failed: Total return quantity is zero.");
                    $('#detail-error-msg').text('Minimal harus ada 1 item dengan Qty Retur lebih dari 0.').show();
                    isValid = false;
                }
            }


            // Jika tidak valid, hentikan submit dan beri pesan
            if (!isValid) {
                console.warn("Form submission prevented due to validation errors.");
                event.preventDefault(); // Mencegah form disubmit
                Swal.fire({
                    title: 'Input Tidak Valid',
                    text: 'Harap periksa Tanggal Retur dan pastikan minimal ada 1 item dengan Qty Retur lebih dari 0.',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500
                });
                return false;
            }

            // Jika valid, log data dan biarkan submit
            console.log("Form data seems valid. Submitting form...");
            // Menonaktifkan tombol submit
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            // Form akan disubmit secara normal
        });


        // SweetAlert untuk konfirmasi hapus (jika ada tombol delete di halaman lain)
        $('body').on('click', '.btn-delete', function(e) { /* ... Kode SweetAlert sama ... */ });

         // Opsi: Hapus baris detail (jika model update menghapus & insert ulang, ini tidak perlu)
         /*
         tableBody.on('click', '.btn-remove-item', function() {
             if (tableBody.find('tr').length > 1) { // Jangan hapus jika sisa 1
                 $(this).closest('tr').remove();
             } else {
                  Swal.fire('Info', 'Minimal harus ada satu item dalam retur.', 'info');
             }
         });
         */

    }); // Akhir $(document).ready()
    </script>

</body>
</html>