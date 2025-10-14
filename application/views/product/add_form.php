<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah Produk Baru</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('product/save'); ?>" method="post">
            <div class="form-group"><label>Tipe Produk</label>
                <select name="product_type" class="form-control" required>
                    <option value="Barang Jadi">Barang Jadi</option>
                    <option value="Semi-Finished Goods">Semi-Finished Goods</option>
                </select>
            </div>
            <div class="form-group"><label>Kode Kategori Produk</label><input type="text" name="product_category" class="form-control" placeholder="Contoh: SH (Sepatu), UP (Upper), SO (Sole)" required></div>
            <div class="form-group"><label>Nama Spesifik Produk</label><input type="text" name="product_name" class="form-control" placeholder="Contoh: Sepatu VULCAN-X Black" required></div>
            <div class="form-group"><label>Satuan</label><input type="text" name="unit" class="form-control" placeholder="Contoh: PAIRS, PCS" required></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('product'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>