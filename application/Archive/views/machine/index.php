<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-cogs text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <?php if (check_access('master_machine', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Mesin / Peralatan
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable"> <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama Mesin</th>
                            <th>Kategori</th>
                            <th>Merk / Tipe</th>
                            <th>Tahun</th>
                            <th>Lokasi</th>
                            <th>Satuan</th> <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($machines)): $no=1; foreach($machines as $m): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($m->kode_mesin); ?></td>
                            <td class="text-left"><?= htmlspecialchars($m->nama_mesin); ?></td>
                            
                            <td><?= htmlspecialchars($m->kategori_desc ?? $m->kategori); ?></td>
                            
                            <td><?= htmlspecialchars($m->merk); ?></td>
                            <td><?= htmlspecialchars($m->tahun); ?></td>
                            <td><?= htmlspecialchars($m->lokasi); ?></td>

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
                                        data-merk="<?= htmlspecialchars($m->merk); ?>"
                                        data-tahun="<?= htmlspecialchars($m->tahun); ?>"
                                        data-lokasi="<?= htmlspecialchars($m->lokasi); ?>"
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
                            <tr><td colspan="10" class="text-center text-muted py-3">Belum ada data mesin / peralatan</td></tr>
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
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Mesin / Peralatan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nama Mesin</label><input type="text" name="nama_mesin" class="form-control" required></div>
                    
                    <div class="form-group"><label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group"><label>Merk / Tipe</label><input type="text" name="merk" class="form-control"></div>
                    <div class="form-group"><label>Tahun</label><input type="number" name="tahun" class="form-control" min="1980" max="<?= date('Y'); ?>"></div>
                    <div class="form-group"><label>Lokasi</label><input type="text" name="lokasi" class="form-control"></div>
                    
                    <div class="form-group">
                        <label for="add_satuan">Satuan</label>
                        <select name="satuan" id="add_satuan" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuan_list as $satuan): ?>
                                <option value="<?= htmlspecialchars($satuan->code); ?>">
                                    <?= htmlspecialchars($satuan->description); ?> (<?= htmlspecialchars($satuan->code); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Mesin / Peralatan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nama Mesin</label><input type="text" id="edit_nama_mesin" name="nama_mesin" class="form-control" required></div>
                    
                    <div class="form-group"><label>Kategori</label>
                        <select name="kategori" id="edit_kategori" class="form-control">
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>">
                                    <?= htmlspecialchars($k->description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group"><label>Merk / Tipe</label><input type="text" id="edit_merk" name="merk" class="form-control"></div>
                    <div class="form-group"><label>Tahun</label><input type="number" id="edit_tahun" name="tahun" class="form-control"></div>
                    <div class="form-group"><label>Lokasi</label><input type="text" id="edit_lokasi" name="lokasi" class="form-control"></div>

                    <div class="form-group">
                        <label for="edit_satuan">Satuan</label>
                        <select name="satuan" id="edit_satuan" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuan_list as $satuan): ?>
                                <option value="<?= htmlspecialchars($satuan->code); ?>">
                                    <?= htmlspecialchars($satuan->description); ?> (<?= htmlspecialchars($satuan->code); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group"><label>Status</label>
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
                    <button type="submit" class="btn btn-warning text-white btn-load"><i class="fas fa-save mr-1"></i> Update</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    $('#dataTable').DataTable();

    // PERBAIKAN: Menggunakan satu modal edit, diisi oleh JavaScript
    $('.btn-edit').on('click', function() {
        // Ambil data dari tombol
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var kategori = $(this).data('kategori');
        var merk = $(this).data('merk');
        var tahun = $(this).data('tahun');
        var lokasi = $(this).data('lokasi');
        var satuan = $(this).data('satuan'); // <-- AMBIL DATA SATUAN
        var status = $(this).data('status');

        // Set action form
        var form = $('#editModal').find('form');
        form.attr('action', '<?= base_url('master_machine/update/'); ?>' + id);

        // Isi field-field di modal
        form.find('#edit_nama_mesin').val(nama);
        form.find('#edit_kategori').val(kategori);
        form.find('#edit_merk').val(merk);
        form.find('#edit_tahun').val(tahun);
        form.find('#edit_lokasi').val(lokasi);
        form.find('#edit_satuan').val(satuan); // <-- SET NILAI SATUAN
        form.find('#edit_status').val(status);
    });

    // Peringatan konfirmasi delete
    $('.btn-delete').on('click', function(e) {
        e.preventDefault(); 
        var url = $(this).attr('href');
        Swal.fire({
            title: 'Anda yakin?',
            text: "Data ini akan dinonaktifkan (status: inactive).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, nonaktifkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});
</script>