<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manajemen User</h1>
    <a href="<?php echo base_url('user/add'); ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Tambah User Baru</a>
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
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $item) : ?>
                        <tr>
                            <td><?php echo html_escape($item['username']); ?></td>
                            <td><?php echo html_escape($item['full_name']); ?></td>
                            <td><?php echo html_escape($item['role_name']); ?></td>
                            <td>
                                <?php if ($item['is_active'] == 1) : ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else : ?>
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo base_url('user/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo base_url('user/delete/'.$item['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus user ini?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>