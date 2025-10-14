<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Tambah Supplier Baru</h1>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?php echo base_url('supplier/save'); ?>" method="post">
            <div class="form-group">
                <label>Kode Supplier</label>
                <input type="text" name="supplier_code" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nama Supplier</label>
                <input type="text" name="supplier_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Telepon</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label>NPWP</label>
                <input type="text" name="npwp" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('supplier'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>