<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-columns text-primary mr-2"></i>
                <?= lang_line('devmode_columns_page_title', 'Developer Mode — Kelola Kolom List'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('devmode_columns_subtitle', 'Pilih kolom mana yang ditampilkan di halaman list tertentu.'); ?></small>
        </div>
        <div>
            <a href="<?= base_url('developer_mode') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-language mr-1"></i> <?= lang_line('devmode_assets_go_text', 'Kelola Teks'); ?>
            </a>
            <a href="<?= base_url('developer_mode/assets') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-image mr-1"></i> <?= lang_line('devmode_assets_go_images', 'Kelola Logo & Gambar'); ?>
            </a>
            <a href="<?= base_url('developer_mode/sidebar_order') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-bars mr-1"></i> <?= lang_line('devmode_assets_go_sidebar_order', 'Atur Urutan Sidebar'); ?>
            </a>
            <a href="<?= base_url('developer_mode/pdf_layout') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('devmode_assets_go_pdf', 'Atur PDF'); ?>
            </a>
        </div>
    </div>

    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        <?= lang_line('devmode_columns_info', 'Fitur ini baru dipasang di sebagian halaman list (contoh: Daftar Permintaan Barang) — halaman lain menyusul bertahap.'); ?>
    </div>

    <?php if (empty($pages)): ?>
        <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('devmode_columns_no_data', 'Belum ada halaman list yang dipasangi fitur ini.'); ?></div></div>
    <?php else: foreach ($pages as $p): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3">
            <strong><code><?= htmlspecialchars($p->page_key) ?></code></strong>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($p->columns as $c): ?>
                <div class="col-md-3 col-sm-4 col-6 mb-2">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input col-toggle" id="col_<?= htmlspecialchars($p->page_key . '_' . $c->column_key) ?>"
                               data-page="<?= htmlspecialchars($p->page_key) ?>" data-column="<?= htmlspecialchars($c->column_key) ?>"
                               <?= empty($c->is_hidden) ? 'checked' : '' ?> <?= empty($can_edit) ? 'disabled' : '' ?>>
                        <label class="custom-control-label small" for="col_<?= htmlspecialchars($p->page_key . '_' . $c->column_key) ?>">
                            <?= htmlspecialchars($c->column_label ?: $c->column_key) ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<script>
$(document).ready(function() {
    $('.col-toggle').on('change', function() {
        var el = $(this);
        $.post('<?= base_url("developer_mode/save_column") ?>', {
            page_key: el.data('page'),
            column_key: el.data('column'),
            is_hidden: el.is(':checked') ? 0 : 1
        }, function(res) {
            if (!res.success) alert(res.message || '<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
        }, 'json').fail(function() {
            alert('<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
        });
    });
});
</script>
