<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
<?= $title ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="mt-4">Master Data Material</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Dashboard</a></li>
    <li class="breadcrumb-item active">Master Material</li>
</ol>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Daftar Material
        <a href="<?= base_url('/material/new') ?>" class="btn btn-primary btn-sm float-end">
            <i class="fas fa-plus"></i> Tambah Data
        </a>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <table id="datatablesSimple">
            <thead>
                <tr>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Tipe</th>
                    <th>HS Code</th>
                    <th>Satuan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $item) : ?>
                    <tr>
                        <td><?= esc($item['item_code']) ?></td>
                        <td><?= esc($item['item_name']) ?></td>
                        <td>
                            <?php if ($item['item_type'] == 'import') : ?>
                                <span class="badge bg-primary">IMPORT</span>
                            <?php else : ?>
                                <span class="badge bg-success">LOKAL</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($item['hs_code']) ?></td>
                        <td><?= esc($item['unit']) ?></td>
                        <td>
                            <a href="#" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="#" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>