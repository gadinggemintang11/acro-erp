<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manajemen Role</h1>
    <a href="<?php echo base_url('role/add'); ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Tambah Role Baru</a>
</div>

<?php if($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $this->session->flashdata('success'); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Role</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $item) : ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td><?php echo html_escape($item['role_name']); ?></td>
                            <td><?php echo html_escape($item['description']); ?></td>
                            <td>
                                <a href="<?php echo base_url('role/access/'.$item['id']); ?>" class="btn btn-info btn-sm" title="Hak Akses">
                                    <i class="fas fa-shield-alt"></i>
                                <a href="<?php echo base_url('role/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo base_url('role/delete/'.$item['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus role ini?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>