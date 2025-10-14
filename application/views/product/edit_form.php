<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Edit Produk</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('product/update'); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            <div class="form-group"><label>Kode Produk</label><input type="text" class="form-control" value="<?php echo $product['product_code']; ?>" readonly></div>
            <div class="form-group"><label>Tipe Produk</label>
                <select name="product_type" class="form-control" required>
                    <option value="Barang Jadi" <?php echo ($product['product_type'] == 'Barang Jadi') ? 'selected' : ''; ?>>Barang Jadi</option>
                    <option value="Semi-Finished Goods" <?php echo ($product['product_type'] == 'Semi-Finished Goods') ? 'selected' : ''; ?>>Semi-Finished Goods</option>
                </select>
            </div>
            <div class="form-group"><label>Kode Kategori Produk</label><input type="text" name="product_category" class="form-control" value="<?php echo $product['product_category']; ?>" required></div>
            <div class="form-group"><label>Nama Spesifik Produk</label><input type="text" name="product_name" class="form-control" value="<?php echo $product['product_name']; ?>" required></div>
            <div class="form-group"><label>Satuan</label><input type="text" name="unit" class="form-control" value="<?php echo $product['unit']; ?>" required></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo base_url('product'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>