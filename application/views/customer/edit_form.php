<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit Pelanggan</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('customer/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
            <div class="form-group"><label>Kode Pelanggan</label><input type="text" name="customer_code" class="form-control" value="<?php echo $customer['customer_code']; ?>" required></div>
            <div class="form-group"><label>Nama Pelanggan</label><input type="text" name="customer_name" class="form-control" value="<?php echo $customer['customer_name']; ?>" required></div>
            <div class="form-group"><label>Negara</label><input type="text" name="country" class="form-control" value="<?php echo $customer['country']; ?>"></div>
            <div class="form-group"><label>Alamat</label><textarea name="address" class="form-control" rows="3"><?php echo $customer['address']; ?></textarea></div>
            <div class="form-group"><label>Telepon</label><input type="text" name="phone" class="form-control" value="<?php echo $customer['phone']; ?>"></div>
            <div class="form-group"><label>NPWP</label><input type="text" name="npwp" class="form-control" value="<?php echo $customer['npwp']; ?>"></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo base_url('customer'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>