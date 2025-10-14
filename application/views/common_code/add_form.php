<div class="card shadow mb-4">
    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah Common Code Baru</h6></div>
    <div class="card-body">
        <form action="<?php echo base_url('common_code/save'); ?>" method="post">
            <div class="form-group"><label>Grup Kode (cth: SATUAN, BC_TYPE)</label><input type="text" name="code_group" class="form-control" required></div>
            <div class="form-group"><label>Nama Kode (cth: KG, BC 2.3)</label><input type="text" name="code_name" class="form-control" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-control"></textarea></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?php echo base_url('common_code'); ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>