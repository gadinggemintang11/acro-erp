<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Master Material</h1>
    <a href="<?php echo base_url('material/add'); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Material Baru
    </a>
    <?php if($this->session->flashdata('success')): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $this->session->flashdata('success'); ?>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Material</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Kode Barang</th>
                        <th>Sub-Kategori</th> <th>Nama Barang</th>
                        <th>Tipe</th>
                        <th>Satuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($materials)) : ?>
                        <?php foreach ($materials as $item) : ?>
                            <tr>
                                <td><?php echo html_escape($item['item_code']); ?></td>
                                <td><?php echo html_escape($item['sub_category']); ?></td> <td><?php echo html_escape($item['item_name']); ?></td>
                                <td>
                                    <?php if ($item['item_type'] == 'import') : ?>
                                        <span class="badge badge-primary">IMPORT</span>
                                    <?php else : ?>
                                        <span class="badge badge-success">LOKAL</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo html_escape($item['unit']); ?></td>
                                <td>
                                    <a href="<?php echo base_url('material/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm">
    <i class="fas fa-edit"></i>
</a>
                                    <a href="<?php echo base_url('material/delete/'.$item['id']); ?>" 
                                        class="btn btn-danger btn-sm" 
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>