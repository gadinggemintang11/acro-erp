<?php
$labels = [
    'irf' => ['icon' => 'fa-file-alt',      'name' => lang_line('it_doc_irf', 'Permintaan Barang')],
    'pr'  => ['icon' => 'fa-clipboard-list','name' => lang_line('it_doc_pr', 'Purchase Request')],
    'po'  => ['icon' => 'fa-shopping-cart', 'name' => lang_line('it_doc_po', 'Purchase Order')],
    'sgr' => ['icon' => 'fa-truck-loading', 'name' => lang_line('it_doc_sgr', 'Penerimaan Barang')],
    'out' => ['icon' => 'fa-dolly',         'name' => lang_line('it_doc_out', 'Pengeluaran Barang')],
];
$lbl = $labels[$doc_type] ?? ['icon' => 'fa-file', 'name' => lang_line('it_doc_default', 'Dokumen')];
$h = $doc['header'];
$doc_no = $h->request_number ?? $h->pr_number ?? $h->po_number ?? $h->sgr_number ?? $h->doc_number ?? ('#' . (int) $h->id);
?>
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb bg-transparent p-0 small mb-0">
        <li class="breadcrumb-item"><a href="<?= site_url('inventory_traceability'); ?>"><i class="fas fa-route mr-1"></i><?= lang_line('it_title', 'Penelusuran Barang General'); ?></a></li>
        <?php if (!empty($item)): ?>
            <li class="breadcrumb-item"><a href="<?= site_url('inventory_traceability/detail/' . $item->id); ?>"><?= htmlspecialchars($item->kode_barang); ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><i class="fas <?= $lbl['icon']; ?> mr-1"></i><?= $lbl['name']; ?>: <?= htmlspecialchars($doc_no); ?></li>
    </ol>
</nav>
