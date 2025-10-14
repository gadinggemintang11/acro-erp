<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="mt-4">Tambah Supplier Baru</h1>
<div class="card">
    <div class="card-body">
        <form action="/supplier/create" method="post">
            <?= csrf_field() ?> <div class="mb-3">
                <label for="supplier_code" class="form-label">Kode Supplier</label>
                <input type="text" class="form-control" id="supplier_code" name="supplier_code" required>
            </div>
            <div class="mb-3">
                <label for="supplier_name" class="form-label">Nama Supplier</label>
                <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Alamat</label>
                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Telepon</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="/supplier" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>