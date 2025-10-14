<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Penerimaan Barang</h1>
    <a href="<?php echo base_url('goods_receipt/add'); ?>" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Buat Penerimaan Baru</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filter Data</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo base_url('goods_receipt/index'); ?>" method="get">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo isset($filters['start_date']) ? $filters['start_date'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo isset($filters['end_date']) ? $filters['end_date'] : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipe BC</label>
                        <select name="bc_type" class="form-control">
                            <option value="">-- Semua Tipe BC --</option>
                            <?php foreach($bc_types as $type): ?>
                                <option value="<?php echo $type['code_name']; ?>" <?php echo (isset($filters['bc_type']) && $filters['bc_type'] == $type['code_name']) ? 'selected' : ''; ?>>
                                    <?php echo $type['code_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label><br>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                        <a href="<?php echo base_url('goods_receipt'); ?>" class="btn btn-secondary"><i class="fas fa-sync"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
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
                        <th>No. Penerimaan</th>
                        <th>No. Dok. Pabean</th>
                        <th>Tipe BC</th> <th>Tgl. Terima</th>
                        <th>Supplier</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($receipts)) : ?>
                        <?php foreach ($receipts as $item) : ?>
                            <tr>
                                <td><strong><?php echo html_escape($item['receipt_no']); ?></strong></td>
                                <td><?php echo html_escape($item['pib_no']); ?></td>
                                <td><span class="badge badge-secondary"><?php echo html_escape($item['bc_type']); ?></span></td> <td><?php echo date('d M Y', strtotime($item['receipt_date'])); ?></td>
                                <td><?php echo html_escape($item['supplier_name']); ?></td>
                                <td>
                                    <a href="<?php echo base_url('goods_receipt/detail/'.$item['id']); ?>" class="btn btn-info btn-sm" title="Lihat/Isi Item Barang"><i class="fas fa-eye"></i></a>
                                    <a href="<?php echo base_url('goods_receipt/edit/'.$item['id']); ?>" class="btn btn-warning btn-sm" title="Edit Dokumen"><i class="fas fa-edit"></i></a>
                                    <a href="<?php echo base_url('goods_receipt/delete/'.$item['id']); ?>" class="btn btn-danger btn-sm" title="Hapus Dokumen" onclick="return confirm('PERINGATAN: Menghapus dokumen ini akan menghapus SEMUA item di dalamnya. Lanjutkan?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data yang ditemukan sesuai filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>