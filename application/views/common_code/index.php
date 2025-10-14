<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Common Codes</h1>
    <a href="<?php echo base_url('common_code/add'); ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm"></i> Tambah Baru</a>
</div>
<?php if($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div><?php endif; ?>
<div class="card shadow mb-4">
    <div class="card-body">
        <table class="table table-bordered" id="dataTable">
            <thead><tr><th>Grup</th><th>Nama Kode</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($codes as $item): ?>
                <tr>
                    <td><?php echo html_escape($item['code_group']); ?></td>
                    <td><?php echo html_escape($item['code_name']); ?></td>
                    <td><?php echo html_escape($item['description']); ?></td>
                    <td>
                        <a href="<?php echo base_url('common_code/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo base_url('common_code/delete/'.$item['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>