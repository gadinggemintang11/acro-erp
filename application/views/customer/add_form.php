<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah Pelanggan Baru</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('customer/save'); ?>" method="post">
            <div class="form-group"><label>Kode Pelanggan</label><input type="text" name="customer_code" class="form-control" required></div>
            <div class="form-group"><label>Nama Pelanggan</label><input type="text" name="customer_name" class="form-control" required></div>
            <div class="form-group"><label>Negara</label><input type="text" name="country" class="form-control"></div>
            <div class="form-group"><label>Alamat</label><textarea name="address" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Telepon</label><input type="text" name="phone" class="form-control"></div>
            <div class="form-group"><label>NPWP</label><input type="text" name="npwp" class="form-control"></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('customer'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>