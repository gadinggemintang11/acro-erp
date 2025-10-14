<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="mt-4">Master Data Supplier</h1>
<a href="/supplier/new" class="btn btn-primary mb-3">Tambah Data</a>
<?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="alert">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>
<div class="card">
    <div class="card-body">
        <table id="datatablesSimple"> <thead>
                <tr>
                    <th>Kode Supplier</th>
                    <th>Nama Supplier</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($suppliers as $supplier): ?>
                <tr>
                    <td><?= $supplier['supplier_code'] ?></td>
                    <td><?= $supplier['supplier_name'] ?></td>
                    <td><?= $supplier['address'] ?></td>
                    <td><?= $supplier['phone'] ?></td>
                    <td>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>