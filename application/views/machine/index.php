<?php
$month_labels = [
    '01' => lang_line('month_jan', 'Januari'),
    '02' => lang_line('month_feb', 'Februari'),
    '03' => lang_line('month_mar', 'Maret'),
    '04' => lang_line('month_apr', 'April'),
    '05' => lang_line('month_may', 'Mei'),
    '06' => lang_line('month_jun', 'Juni'),
    '07' => lang_line('month_jul', 'Juli'),
    '08' => lang_line('month_aug', 'Agustus'),
    '09' => lang_line('month_sep', 'September'),
    '10' => lang_line('month_oct', 'Oktober'),
    '11' => lang_line('month_nov', 'November'),
    '12' => lang_line('month_dec', 'Desember'),
];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-cogs text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div>
            <?php if (check_access('master_machine', 'view')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_machine/export_excel'); ?>" class="btn btn-sm btn-success" title="Export Excel">
                        <i class="fas fa-file-excel"></i> <?= lang_line('mch_btn_excel', 'Excel'); ?>
                    </a>
                    <a href="<?= base_url('master_machine/export_pdf'); ?>" class="btn btn-sm btn-danger" title="Export PDF">
                        <i class="fas fa-file-pdf"></i> <?= lang_line('mch_btn_pdf', 'PDF'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (check_access('master_machine', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('mch_btn_add', 'Tambah Mesin / Peralatan'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('label_no', 'No'); ?></th>
                            <th><?= lang_line('mch_th_code', 'Kode'); ?></th>
                            <th><?= lang_line('mch_th_name', 'Nama Mesin'); ?></th>
                            <th><?= lang_line('mch_th_category', 'Kategori'); ?></th>
                            <th><?= lang_line('mch_th_brand', 'Merk'); ?></th>
                            <th><?= lang_line('mch_th_type', 'Tipe'); ?></th>
                            <th><?= lang_line('mch_th_year', 'Tahun Pengadaan'); ?></th>
                            <th><?= lang_line('mch_th_month', 'Bulan Pengadaan'); ?></th>
                            <th><?= lang_line('mch_th_pic', 'PIC Departemen'); ?></th>
                            <th><?= lang_line('mch_th_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('mch_th_status', 'Status'); ?></th>
                            <th width="180"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($machines)): $no=1; foreach($machines as $m): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($m->kode_mesin); ?></td>
                            <td class="text-left"><?= htmlspecialchars($m->nama_mesin); ?></td>
                            <td><?= htmlspecialchars($m->kategori_desc ?? $m->kategori); ?></td>
                            <td><?= htmlspecialchars($m->merk ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m->tipe ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m->tahun_pengadaan ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m->bulan_pengadaan ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m->pic_departemen ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m->satuan_desc ?? $m->satuan); ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($m->status)=='active'?'success':'secondary'; ?>">
                                    <?= ucfirst($m->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_machine', 'edit')): ?>
                                    <button class="btn btn-sm btn-warning text-white btn-edit"
                                        data-toggle="modal"
                                        data-target="#editModal"
                                        data-id="<?= $m->id; ?>"
                                        data-kode="<?= htmlspecialchars($m->kode_mesin); ?>"
                                        data-nama="<?= htmlspecialchars($m->nama_mesin); ?>"
                                        data-kategori="<?= htmlspecialchars($m->kategori); ?>"
                                        data-merk="<?= htmlspecialchars($m->merk ?? ''); ?>"
                                        data-tipe="<?= htmlspecialchars($m->tipe ?? ''); ?>"
                                        data-tahun="<?= htmlspecialchars($m->tahun_pengadaan ?? ''); ?>"
                                        data-bulan="<?= htmlspecialchars($m->bulan_pengadaan ?? ''); ?>"
                                        data-pic="<?= htmlspecialchars($m->pic_departemen ?? ''); ?>"
                                        data-satuan="<?= htmlspecialchars($m->satuan); ?>"
                                        data-status="<?= htmlspecialchars($m->status); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_machine', 'delete')): ?>
                                    <a href="<?= base_url('master_machine/delete/'.$m->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="12" class="text-center text-muted py-3"><?= lang_line('mch_no_data', 'Belum ada data mesin / peralatan'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_machine/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('mch_add_modal_title', 'Tambah Mesin / Peralatan'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('mch_label_code', 'Kode Mesin'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="kode_mesin" class="form-control" placeholder="<?= lang_line('mch_code_placeholder', 'Contoh: MES-001'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_name', 'Nama Mesin'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="nama_mesin" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_category', 'Kategori'); ?></label>
                        <select name="kategori" class="form-control">
                            <option value=""><?= lang_line('mch_select_category', '-- Pilih Kategori --'); ?></option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_brand', 'Merk'); ?></label>
                                <input type="text" name="merk" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_type', 'Tipe'); ?></label>
                                <input type="text" name="tipe" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_year', 'Tahun Pengadaan'); ?></label>
                                <input type="number" name="tahun_pengadaan" class="form-control" min="1980" max="<?= date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_month', 'Bulan Pengadaan'); ?></label>
                                <select name="bulan_pengadaan" class="form-control">
                                    <option value=""><?= lang_line('mch_select_month', '-- Pilih Bulan --'); ?></option>
                                    <?php foreach ($month_labels as $code => $label): ?>
                                        <option value="<?= $code; ?>"><?= htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_pic', 'PIC Departemen'); ?></label>
                        <input type="text" name="pic_departemen" class="form-control" placeholder="<?= lang_line('mch_pic_placeholder', 'Nama atau Departemen'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="add_satuan"><?= lang_line('mch_label_unit', 'Satuan'); ?></label>
                        <select name="satuan" id="add_satuan" class="form-control">
                            <option value=""><?= lang_line('mch_select_unit', '-- Pilih Satuan --'); ?></option>
                            <?php foreach ($satuan_list as $satuan): ?>
                                <option value="<?= htmlspecialchars($satuan->code); ?>">
                                    <?= htmlspecialchars($satuan->description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('btn_save', 'Simpan'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('mch_edit_modal_title', 'Edit Mesin / Peralatan'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('mch_label_code', 'Kode Mesin'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="edit_kode_mesin" name="kode_mesin" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_name', 'Nama Mesin'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="edit_nama_mesin" name="nama_mesin" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_category', 'Kategori'); ?></label>
                        <select name="kategori" id="edit_kategori" class="form-control">
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>">
                                    <?= htmlspecialchars($k->description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_brand', 'Merk'); ?></label>
                                <input type="text" id="edit_merk" name="merk" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_type', 'Tipe'); ?></label>
                                <input type="text" id="edit_tipe" name="tipe" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_year', 'Tahun Pengadaan'); ?></label>
                                <input type="number" id="edit_tahun" name="tahun_pengadaan" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('mch_label_month', 'Bulan Pengadaan'); ?></label>
                                <select name="bulan_pengadaan" id="edit_bulan" class="form-control">
                                    <option value=""><?= lang_line('mch_select_month', '-- Pilih Bulan --'); ?></option>
                                    <?php foreach ($month_labels as $code => $label): ?>
                                        <option value="<?= $code; ?>"><?= htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_pic', 'PIC Departemen'); ?></label>
                        <input type="text" id="edit_pic" name="pic_departemen" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="edit_satuan"><?= lang_line('mch_label_unit', 'Satuan'); ?></label>
                        <select name="satuan" id="edit_satuan" class="form-control">
                            <option value=""><?= lang_line('mch_select_unit', '-- Pilih Satuan --'); ?></option>
                            <?php foreach ($satuan_list as $satuan): ?>
                                <option value="<?= htmlspecialchars($satuan->code); ?>">
                                    <?= htmlspecialchars($satuan->description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('mch_label_status', 'Status'); ?></label>
                        <select name="status" id="edit_status" class="form-control">
                            <?php foreach($status_list as $s): ?>
                                <option value="<?= $s->code; ?>">
                                    <?= htmlspecialchars($s->description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning text-white btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('mch_btn_update', 'Update'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const I18N_MCH = {
    swal_title:  <?= json_encode(lang_line('mch_js_swal_title', 'Anda yakin?')); ?>,
    swal_text:   <?= json_encode(lang_line('mch_js_swal_text', 'Data ini akan dinonaktifkan (status: inactive).')); ?>,
    swal_yes:    <?= json_encode(lang_line('mch_js_swal_yes', 'Ya, nonaktifkan!')); ?>,
    swal_cancel: <?= json_encode(lang_line('btn_cancel', 'Batal')); ?>
};

$(document).ready(function() {
    // Inisialisasi DataTable
    $('#dataTable').DataTable();

    // Handle edit button click
    $('.btn-edit').on('click', function() {
        var id = $(this).data('id');
        var kode = $(this).data('kode');
        var nama = $(this).data('nama');
        var kategori = $(this).data('kategori');
        var merk = $(this).data('merk');
        var tipe = $(this).data('tipe');
        var tahun = $(this).data('tahun');
        var bulan = $(this).data('bulan');
        var pic = $(this).data('pic');
        var satuan = $(this).data('satuan');
        var status = $(this).data('status');

        // Set action form
        var form = $('#editModal').find('form');
        form.attr('action', '<?= base_url('master_machine/update/'); ?>' + id);

        // Isi field-field di modal
        form.find('#edit_kode_mesin').val(kode);
        form.find('#edit_nama_mesin').val(nama);
        form.find('#edit_kategori').val(kategori);
        form.find('#edit_merk').val(merk);
        form.find('#edit_tipe').val(tipe);
        form.find('#edit_tahun').val(tahun);
        form.find('#edit_bulan').val(bulan);
        form.find('#edit_pic').val(pic);
        form.find('#edit_satuan').val(satuan);
        form.find('#edit_status').val(status);
    });

    // Peringatan konfirmasi delete
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        Swal.fire({
            title: I18N_MCH.swal_title,
            text: I18N_MCH.swal_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: I18N_MCH.swal_yes,
            cancelButtonText: I18N_MCH.swal_cancel
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});
</script>
