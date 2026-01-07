<?php
function render_coa_tree($accounts, $level = 0) {
    $indent = $level * 30; 

    foreach ($accounts as $acc) {
        $is_header = $acc['is_header'] == 1;
        $font_weight = $is_header ? 'font-weight-bold' : '';
        $color = $is_header ? 'text-primary' : 'text-dark';

        echo '<tr>';
        echo '<td style="padding-left: ' . (10 + $indent) . 'px;" class="' . $font_weight . ' ' . $color . '">';
        echo $acc['account_code'];
        echo '</td>';
    echo '<td class="' . $font_weight . ' ' . $color . '">' . $acc['account_name'] . '</td>';
    echo '<td>' . $acc['account_type'] . '</td>';
    echo '<td>' . $acc['currency'] . '</td>'; // <-- KOLOM MATA UANG
    // Normal balance (fallback: derived from account_type if null)
    $normal = isset($acc['normal_balance']) && $acc['normal_balance'] ? $acc['normal_balance'] : ((in_array($acc['account_type'], ['Aset','Biaya','HPP'])) ? 'Debit' : 'Kredit');
    echo '<td>' . $normal . '</td>';
    echo '<td>' . ($is_header ? '<span class="badge badge-info">Header</span>' : '<span class="badge badge-secondary">Detail</span>') . '</td>';
    echo '<td>' . ($acc['status'] == 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>') . '</td>';
        echo '<td class="text-center">';
        echo '<a href="' . base_url('coa/edit/' . $acc['id']) . '" class="btn btn-warning btn-circle btn-sm" title="Edit"><i class="fas fa-pen"></i></a> ';
        echo '<a href="' . base_url('coa/delete/' . $acc['id']) . '" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm(\'Anda yakin ingin menghapus akun ini? Cek sub-akun dan transaksi terlebih dahulu.\');"><i class="fas fa-trash"></i></a>';
        echo '</td>';
        echo '</tr>';

        if (isset($acc['children'])) {
            render_coa_tree($acc['children'], $level + 1);
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('coa/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Buat Akun Baru</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Struktur Bagan Akun Perusahaan</h6>
            <form method="get" action="" class="form-inline mt-2">
                <input type="text" name="search" class="form-control mr-2" placeholder="Cari kode/nama akun..." value="<?= isset($search) ? htmlspecialchars($search) : '' ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= base_url('coa') ?>" class="btn btn-secondary ml-2">Reset</a>
                <?php endif; ?>
            </form>
            <?php if (!empty($search)): ?>
                <div class="mt-2">
                    <small>Hasil pencarian: "<strong><?= htmlspecialchars($search) ?></strong>"</small>
                    <?php if (isset($search_error)): ?>
                        <div class="text-danger small">Error search: <?= htmlspecialchars($search_error) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="coaTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Tipe Akun</th>
                            <th>Mata Uang</th>
                            <th>Saldo Normal</th>
                            <th>Jenis (Postable)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php render_coa_tree($accounts_tree); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>