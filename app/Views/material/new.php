<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= $title ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="mt-4">Tambah Data Material</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="<?= base_url('/material') ?>">Master Material</a></li>
    <li class="breadcrumb-item active">Tambah Data</li>
</ol>

<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-square me-1"></i>
        Form Tambah Material
    </div>
    <div class="card-body">
        <form action="<?= base_url('/material') ?>" method="post">
            <?= csrf_field() ?> <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_name" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="item_name" name="item_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="item_type" class="form-label">Tipe Barang</label>
                    <select class="form-select" id="item_type" name="item_type" required>
                        <option value="import">IMPORT</option>
                        <option value="lokal">LOKAL</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="unit" class="form-label">Satuan</label>
                    <input type="text" class="form-control" id="unit" name="unit" placeholder="Contoh: KG, PCS, MTR" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hs_code" class="form-label">HS Code</label>
                    <input type="text" class="form-control" id="hs_code" name="hs_code">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= base_url('/material') ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>