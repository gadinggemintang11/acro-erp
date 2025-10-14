<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Master Barang Jadi & SFG</h1>
    <a href="<?php echo base_url('product/add'); ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Tambah Produk Baru</a>
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
                <thead><tr><th>Kode Produk</th><th>Nama Produk</th><th>Tipe</th><th>Kategori</th><th>Satuan</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $item) : ?>
                        <tr>
                            <td><?php echo html_escape($item['product_code']); ?></td>
                            <td><?php echo html_escape($item['product_name']); ?></td>
                            <td>
                                <?php if ($item['product_type'] == 'Barang Jadi') : ?>
                                    <span class="badge badge-info">Barang Jadi</span>
                                <?php else : ?>
                                    <span class="badge badge-secondary">Semi-Finished</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo html_escape($item['product_category']); ?></td>
                            <td><?php echo html_escape($item['unit']); ?></td>
                            <td>
                                <a href="<?php echo base_url('product/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo base_url('product/delete/'.$item['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin hapus?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>