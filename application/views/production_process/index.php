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

    <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div><?php endif; ?>


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
                        <?php if (!empty($processes)): foreach ($processes as $proc): ?>
                                <tr class="text-center">
                                    <td class="font-weight-bold text-primary"><?= htmlspecialchars($proc->production_number); ?></td>
                                    <td><?= htmlspecialchars($proc->wo_number); ?></td>
                                    <td><?= htmlspecialchars($proc->sc_number); ?></td>
                                    <td class="text-left"><?= htmlspecialchars($proc->fg_name); ?></td>
                                    <td class="text-right"><?= number_format($proc->planned_qty); ?></td>
                                    <td><?= date('d M Y', strtotime($proc->production_date)); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'secondary';
                                        if ($proc->status == 'Draft') $badge_class = 'warning';
                                        if ($proc->status == 'In Progress') $badge_class = 'primary';
                                        if ($proc->status == 'Completed') $badge_class = 'success';
                                        ?>
                                        <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($proc->status); ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('production_process/preparation/' . $proc->id); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-cogs"></i> Proses
                                        </a>
                                        <?php if ($proc->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                            <a href="<?= base_url('production_process/delete_header/' . $proc->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus Proses Produksi ini beserta semua detail input/outputnya?">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">Belum ada data Proses Produksi</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>


<div class="modal fade" id="createProcessModal" tabindex="-1" aria-labelledby="createProcessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="createProcessForm" method="post" action="<?= base_url('production_process/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="createProcessModalLabel"><i class="fas fa-plus-circle mr-2"></i>Buat Proses Produksi Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="wo_selector">Pilih Work Order (Status: In Progress)</label>
                        <select name="wo_header_id" id="wo_selector" class="form-control" required style="width: 100%;">
                            <option value="">-- Cari No. Work Order --</option>
                        </select>
                        <div class="invalid-feedback">Work Order wajib dipilih.</div>
                    </div>
                    <div id="wo_details_info" style="display: none;" class="alert alert-secondary p-2 mb-3">
                        <small>
                            Sales Contract: <strong id="info_sc_number">-</strong><br>
                            Style: <strong id="info_fg_name">-</strong><br>
                            Qty Rencana: <strong id="info_planned_qty">-</strong>
                        </small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="production_date">Tanggal Mulai Produksi</label>
                            <input type="date" name="production_date" id="production_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            <div class="invalid-feedback">Tanggal wajib diisi.</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="remarks">Catatan</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Proses Preparation
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(function() { // Shorthand for $(document).ready()

        // --- Inisialisasi Select2 untuk WO Selector ---
        $('#wo_selector').select2({
            theme: 'bootstrap4', // Terapkan tema Bootstrap 4
            placeholder: '-- Cari No. Work Order (In Progress) --',
            minimumInputLength: 1, // Bisa mulai dari 1 karakter
            ajax: {
                url: '<?= base_url("production_process/search_open_wos"); ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    console.log("Searching WO with term:", params.term); // Log pencarian
                    return {
                        q: params.term
                    };
                },
                processResults: function(data, params) {
                    console.log("Received data for search_open_wos:", data); // Log data diterima
                    // Menambahkan logging jika data tidak sesuai format
                    if (!data || !$.isArray(data.results)) { // Pastikan results adalah array
                        console.error("Format data dari search_open_wos tidak sesuai. 'results' property missing or not an array. Data:", data);
                        Swal.fire('Error Memuat Data', 'Format respons server untuk pencarian WO salah.', 'error');
                        return {
                            results: []
                        }; // Kembalikan array kosong
                    }
                    // Tambahkan paging jika perlu (contoh sederhana)
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: {
                            more: (params.page * 10) < (data.total_count || 0) // Ganti 10 & total_count sesuai logic backend jika ada paging
                        }
                    };
                },
                cache: true,
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error for search_open_wos:", textStatus, errorThrown, jqXHR.responseText);
                    // Menampilkan pesan error yang lebih detail dari server jika ada
                    let errorMsg = 'Gagal menghubungi server untuk mencari WO. Cek koneksi atau log server.';
                    if (jqXHR.responseText) {
                        // Coba parse response text jika mungkin berisi detail error
                        try {
                            const errResponse = JSON.parse(jqXHR.responseText);
                            if (errResponse && errResponse.message) {
                                errorMsg = errResponse.message;
                            }
                        } catch (e) {
                            // Jika bukan JSON, tampilkan teks response (mungkin HTML error)
                            // errorMsg = jqXHR.responseText; // Hindari menampilkan HTML mentah di Swal
                            console.error("Server response was not valid JSON:", jqXHR.responseText);
                        }
                    }
                    Swal.fire('Error Pencarian WO', errorMsg, 'error');
                }
            },
            dropdownParent: $('#createProcessModal') // Penting untuk modal
        });

        // --- Ambil Detail WO saat dipilih ---
        $('#wo_selector').on('change', function() {
            const woId = $(this).val();
            $('#wo_details_info').hide(); // Sembunyikan detail
            $('#info_sc_number, #info_fg_name, #info_planned_qty').text('-'); // Reset teks

            if (woId) {
                console.log("WO ID selected:", woId); // Log WO ID yang dipilih
                $.ajax({
                    url: '<?= base_url("production_process/ajax_get_wo_details"); ?>',
                    type: 'GET',
                    data: {
                        wo_id: woId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Response from ajax_get_wo_details:", response); // Log respons server
                        if (response && response.success && response.details) {
                            $('#info_sc_number').text(response.details.sc_number || '-');
                            $('#info_fg_name').text(response.details.fg_name || '-');
                            $('#info_planned_qty').text(response.details.planned_qty || '-');
                            $('#wo_details_info').show(); // Tampilkan detail
                        } else {
                            console.error("Gagal mendapatkan detail WO atau format respons salah.");
                            Swal.fire('Error Detail WO', response.message || 'Detail WO tidak ditemukan!', 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error for ajax_get_wo_details:", textStatus, errorThrown, jqXHR.responseText);
                        Swal.fire('Error Detail WO', 'Gagal mengambil detail WO. Cek koneksi atau log server.', 'error');
                    }
                });
            } else {
                console.log("WO ID deselected or empty.");
            }
        });

        // --- Logging & Validasi sebelum Submit Form Header ---
        $('#createProcessForm').on('submit', function(event) {
            console.log("Attempting to submit createProcessForm...");

            // Hapus tanda error sebelumnya
            $('#wo_selector').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').hide();
            $('#production_date').removeClass('is-invalid').closest('.form-group').find('.invalid-feedback').hide();

            let isValid = true;
            const woId = $('#wo_selector').val();
            const prodDate = $('#production_date').val();

            // Validasi WO ID
            if (!woId || woId === '') {
                console.error("Validation failed: WO ID is empty.");
                $('#wo_selector').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show(); // Tampilkan pesan error
                isValid = false;
            } else {
                console.log("WO ID selected for submission:", woId);
            }

            // Validasi Tanggal
            if (!prodDate || prodDate === '') {
                console.error("Validation failed: Production Date is empty.");
                $('#production_date').addClass('is-invalid').closest('.form-group').find('.invalid-feedback').show();
                isValid = false;
            } else {
                console.log("Production Date for submission:", prodDate);
            }

            // Jika tidak valid, hentikan submit dan beri pesan
            if (!isValid) {
                console.warn("Form submission prevented due to validation errors.");
                event.preventDefault(); // Mencegah form disubmit
                Swal.fire({
                    title: 'Input Tidak Lengkap',
                    text: 'Harap pilih Work Order dan isi Tanggal Produksi.',
                    icon: 'warning',
                    toast: true, // Tampilkan sebagai notifikasi kecil
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false; // Hentikan eksekusi lebih lanjut
            }

            // Jika valid, log data dan biarkan submit
            console.log("Form data seems valid. Submitting form with WO ID:", woId, "and Date:", prodDate);
            // Menonaktifkan tombol submit secara manual setelah validasi lolos (jika tidak pakai btn-load)
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            // Form akan disubmit secara normal
        });

        // SweetAlert untuk konfirmasi hapus di tabel utama
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const message = $(this).data('message') || 'Yakin ingin menghapus data ini?'; // Ambil pesan dari data-message
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading sebelum redirect
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu...',
                        didOpen: () => {
                            Swal.showLoading()
                        },
                        allowOutsideClick: false
                    });
                    window.location.href = url; // Lanjutkan ke URL hapus
                }
            });
        });

    }); // Akhir $(function() {})
</script>