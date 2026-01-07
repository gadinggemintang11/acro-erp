<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter <?= htmlspecialchars($title ?? ''); ?></h6>
    </div>
    <div class="card-body">
        <?php // Ganti action ke controller yang sesuai (jika berbeda per laporan) ?>
        <form action="<?= base_url($this->uri->segment(1) . '/index'); ?>" method="GET" class="form-inline">
            <div class="form-group mb-2 mr-sm-2">
                <label for="start_date" class="mr-2">Tgl Mulai</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['start_date'] ?? date('Y-m-01')); ?>" required>
            </div>
             <div class="form-group mb-2 mr-sm-2">
                <label for="end_date" class="mr-2">Tgl Akhir</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['end_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <?php // Tampilkan filter BC Type HANYA jika bc_types ada ?>
            <?php if (isset($bc_types) && is_array($bc_types)): ?>
            <div class="form-group mb-2 mr-sm-2">
                <label for="bc_type" class="mr-2">BC Type</label>
                <select name="bc_type" id="bc_type" class="form-control form-control-sm" style="min-width: 150px;">
                    <option value="">-- Semua BC Type --</option>
                    <?php foreach ($bc_types as $bc): ?>
                        <option value="<?= $bc->code; ?>" <?= (($filters['bc_type'] ?? '') == $bc->code) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($bc->description); ?> (<?= $bc->code; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php // Tambahkan filter Tipe Item (jika perlu) ?>
            <?php /*
            <div class="form-group mb-2 mr-sm-2">
                <label for="item_type" class="mr-2">Tipe Item</label>
                <select name="item_type" id="item_type" class="form-control form-control-sm">
                    <option value="">-- Semua Tipe --</option>
                    <?php foreach ($item_types as $key => $label): ?>
                        <option value="<?= $key; ?>" <?= (($filters['item_type'] ?? '') == $key) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            */ ?>

             <div class="form-group mb-2 mr-sm-2">
                <label for="search" class="sr-only">Cari</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm"
                       placeholder="Cari..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-sm mb-2 btn-load"><i class="fas fa-search fa-sm mr-1"></i> Tampilkan</button>
            <?php // Link reset kembali ke index controller saat ini ?>
            <a href="<?= base_url($this->uri->segment(1)); ?>" class="btn btn-secondary btn-sm mb-2 ml-1 btn-load"><i class="fas fa-undo fa-sm mr-1"></i> Reset</a>
        </form>
    </div>
</div>