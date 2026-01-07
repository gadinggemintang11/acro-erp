<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <?php if (check_access('master_inventory', 'add')): ?>
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Tambah Inventaris Baru
        </button>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
     <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
     <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('warning'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Inventaris</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr class="text-center">
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Satuan</th> <th>Merk</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventaris as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item->kode_barang) ?></td>
                            <td><?= htmlspecialchars($item->nama_barang) ?></td>
                            <td><?= htmlspecialchars($item->kategori_desc ?? $item->kategori) ?></td>
                             <td class="text-center"><?= htmlspecialchars($item->satuan_desc ?? $item->satuan ?? '-') ?></td>
                            <td><?= htmlspecialchars($item->merk ?? '-') ?></td>
                            <td><?= htmlspecialchars($item->lokasi ?? '-') ?></td>
                            <td class="text-center"><?= htmlspecialchars($item->kondisi ?? '-') ?></td>
                            <td class="text-center">
                                <?php if ($item->status == 'Active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (check_access('master_inventory', 'edit')): ?>
                                <button class="btn btn-warning btn-sm"
                                        data-toggle="modal" data-target="#editModal"
                                        data-id="<?= $item->id ?>"
                                        data-kode="<?= htmlspecialchars($item->kode_barang) ?>"
                                        data-nama="<?= htmlspecialchars($item->nama_barang) ?>"
                                        data-kategori="<?= htmlspecialchars($item->kategori) ?>"
                                        data-satuan="<?= htmlspecialchars($item->satuan) ?>" data-merk="<?= htmlspecialchars($item->merk ?? '') ?>"
                                        data-lokasi="<?= htmlspecialchars($item->lokasi ?? '') ?>"
                                        data-kondisi="<?= htmlspecialchars($item->kondisi) ?>"
                                        data-status="<?= htmlspecialchars($item->status) ?>"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (check_access('master_inventory', 'delete') && $item->status == 'Active'): ?>
                                <a href="<?= base_url('master_inventory/delete/' . $item->id) ?>" class="btn btn-danger btn-sm btn-delete" title="Nonaktifkan" data-message="Yakin ingin menonaktifkan item ini?">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('master_inventory/add') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Inventaris Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                     <div class="form-group">
                        <label>Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-control" required>
                             <option value="">-- Pilih Kategori --</option>
                             <?php foreach ($kategori_list as $kat): ?>
                                 <option value="<?= $kat->code ?>"><?= htmlspecialchars($kat->description) ?></option>
                             <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="form-group">
                        <label>Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Nama barang..." required>
                     </div>
                     <div class="form-group">
                        <label>Satuan <span class="text-danger">*</span></label>
                        <select name="satuan" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuan_list as $sat): ?>
                                <option value="<?= $sat->code ?>"><?= htmlspecialchars($sat->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="form-group">
                        <label>Merk (Opsional)</label>
                        <input type="text" name="merk" class="form-control" placeholder="Merk...">
                     </div>
                    <div class="form-group">
                        <label>Lokasi (Opsional)</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Lokasi penyimpanan...">
                    </div>
                     <div class="form-group">
                        <label>Kondisi <span class="text-danger">*</span></label>
                        <select name="kondisi" class="form-control" required>
                             <option value="">-- Pilih Kondisi --</option>
                            <?php foreach ($kondisi_list as $kon): ?>
                                <option value="<?= $kon->code ?>"><?= htmlspecialchars($kon->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="post" id="editForm"> <div class="modal-header">
                    <h5 class="modal-title">Edit Inventaris</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                     <div class="form-group">
                        <label>Kode Barang</label>
                        <input type="text" id="edit_kode" class="form-control" readonly>
                     </div>
                      <div class="form-group">
                        <label>Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" id="edit_kategori" class="form-control" required>
                             <option value="">-- Pilih Kategori --</option>
                             <?php foreach ($kategori_list as $kat): ?>
                                 <option value="<?= $kat->code ?>"><?= htmlspecialchars($kat->description) ?></option>
                             <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="form-group">
                        <label>Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_barang" id="edit_nama" class="form-control" placeholder="Nama barang..." required>
                     </div>
                     <div class="form-group">
                        <label>Satuan <span class="text-danger">*</span></label>
                        <select name="satuan" id="edit_satuan" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuan_list as $sat): ?>
                                <option value="<?= $sat->code ?>"><?= htmlspecialchars($sat->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                     </div>
                      <div class="form-group">
                        <label>Merk (Opsional)</label>
                        <input type="text" name="merk" id="edit_merk" class="form-control" placeholder="Merk...">
                     </div>
                    <div class="form-group">
                        <label>Lokasi (Opsional)</label>
                        <input type="text" name="lokasi" id="edit_lokasi" class="form-control" placeholder="Lokasi penyimpanan...">
                    </div>
                     <div class="form-group">
                        <label>Kondisi <span class="text-danger">*</span></label>
                        <select name="kondisi" id="edit_kondisi" class="form-control" required>
                            <option value="">-- Pilih Kondisi --</option>
                           <?php foreach ($kondisi_list as $kon): ?>
                                <option value="<?= $kon->code ?>"><?= htmlspecialchars($kon->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="Active">Active</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable (jika belum)
    // $('#dataTable').DataTable(); // Uncomment jika Anda pakai library DataTable

    // Populate Edit Modal
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var modal = $(this);

        // Ambil data dari atribut data-*
        var id = button.data('id');
        var kode = button.data('kode');
        var nama = button.data('nama');
        var kategori = button.data('kategori');
        var satuan = button.data('satuan'); // ✅ Ambil data satuan
        var merk = button.data('merk');
        var lokasi = button.data('lokasi');
        var kondisi = button.data('kondisi');
        var status = button.data('status');

        // Set action form
        modal.find('#editForm').attr('action', '<?= base_url('master_inventory/update/') ?>' + id);

        // Isi field di modal
        modal.find('#edit_kode').val(kode);
        modal.find('#edit_nama').val(nama);
        modal.find('#edit_kategori').val(kategori);
        modal.find('#edit_satuan').val(satuan); // ✅ Set value dropdown satuan
        modal.find('#edit_merk').val(merk);
        modal.find('#edit_lokasi').val(lokasi);
        modal.find('#edit_kondisi').val(kondisi);
        modal.find('#edit_status').val(status);
    });

    // Konfirmasi Hapus (jika belum ada di script global)
    $('.btn-delete').on('click', function(e) {
        e.preventDefault(); // Mencegah link langsung dieksekusi
        var url = $(this).attr('href');
        var message = $(this).data('message') || 'Yakin ingin menghapus data ini?';

        if (confirm(message)) {
            window.location.href = url; // Lanjutkan ke URL delete
        }
    });
});
</script>