<?php
$labels = [
    'pr'   => ['icon'=>'fa-clipboard-list', 'name'=>lang_line('mtd_doc_pr')],
    'po'   => ['icon'=>'fa-shopping-cart',  'name'=>lang_line('mtd_doc_po')],
    'grn'  => ['icon'=>'fa-truck-loading',  'name'=>lang_line('mtd_doc_grn')],
    'mr'   => ['icon'=>'fa-dolly',          'name'=>lang_line('mtd_doc_mr')],
    'prod' => ['icon'=>'fa-industry',       'name'=>lang_line('mtd_doc_prod')],
];
$lbl = $labels[$doc_type] ?? ['icon'=>'fa-file','name'=>lang_line('mtd_doc_default')];
$h = $doc['header'];
$doc_no = $h->pr_number ?? $h->po_number ?? $h->grn_number ?? $h->mr_number ?? $h->production_number ?? ('#'.(int)$h->id);
?>
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb bg-transparent p-0 small mb-0">
        <li class="breadcrumb-item"><a href="<?= site_url('material_traceability'); ?>"><i class="fas fa-route mr-1"></i><?= lang_line('mtd_breadcrumb_root'); ?></a></li>
        <?php if (!empty($material)): ?>
            <li class="breadcrumb-item"><a href="<?= site_url('material_traceability/detail/'.$material->id); ?>"><?= htmlspecialchars($material->kode_material); ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><i class="fas <?= $lbl['icon']; ?> mr-1"></i><?= $lbl['name']; ?>: <?= htmlspecialchars($doc_no); ?></li>
    </ol>
</nav>
