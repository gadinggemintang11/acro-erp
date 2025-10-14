<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Master Pelanggan</h1>
    <a href="<?php echo base_url('customer/add'); ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pelanggan</a>
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
                        <th>Kode</th>
                        <th>Nama Pelanggan</th>
                        <th>Negara</th>
                        <th>Telepon</th>
                        <th>NPWP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $item) : ?>
                        <tr>
                            <td><?php echo html_escape($item['customer_code']); ?></td>
                            <td><?php echo html_escape($item['customer_name']); ?></td>
                            <td><?php echo html_escape($item['country']); ?></td>
                            <td><?php echo html_escape($item['phone']); ?></td>
                            <td><?php echo html_escape($item['npwp']); ?></td>
                            <td>
                                <a href="<?php echo base_url('customer/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo base_url('customer/delete/'.$item['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>