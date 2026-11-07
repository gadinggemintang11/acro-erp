<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-image text-primary mr-2"></i>
                <?= lang_line('devmode_assets_page_title', 'Developer Mode — Kelola Logo & Gambar'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('devmode_assets_subtitle', 'Ganti logo perusahaan & gambar lain yang muncul di aplikasi.'); ?></small>
        </div>
        <div>
            <a href="<?= base_url('developer_mode') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-language mr-1"></i> <?= lang_line('devmode_assets_go_text', 'Kelola Teks'); ?>
            </a>
            <a href="<?= base_url('developer_mode/columns') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_assets_go_columns', 'Kelola Kolom List'); ?>
            </a>
            <a href="<?= base_url('developer_mode/sidebar_order') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-bars mr-1"></i> <?= lang_line('devmode_assets_go_sidebar_order', 'Atur Urutan Sidebar'); ?>
            </a>
            <a href="<?= base_url('developer_mode/pdf_layout') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('devmode_assets_go_pdf', 'Atur PDF'); ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <?php /*
        Identitas perusahaan (user 2026-10-08). Nilainya dipakai bareng oleh
        kop PDF, export Excel, halaman login & footer aplikasi lewat
        company_info() -- jadi diubah di sini, berubah di semuanya.
    */ ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <strong><i class="fas fa-building mr-2 text-primary"></i><?= lang_line('devmode_identity_title', 'Identitas Perusahaan'); ?></strong>
            <?php if (!array_reduce($settings, function ($c, $s) { return $c && $s->default; }, true)): ?>
                <span class="badge badge-warning"><?= lang_line('devmode_badge_custom', 'Custom'); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p class="small text-muted"><?= lang_line('devmode_identity_hint', 'Dipakai bersama oleh kop & kaki semua dokumen PDF, export Excel, halaman login, dan footer aplikasi. Kosongkan satu isian untuk mengembalikannya ke nilai bawaan.'); ?></p>
            <form method="POST" action="<?= base_url('developer_mode/save_settings') ?>">
                <div class="form-row">
                    <?php foreach ($settings as $st): ?>
                    <div class="form-group <?= $st->type === 'textarea' ? 'col-md-6' : 'col-md-3' ?>">
                        <label class="small font-weight-bold mb-1">
                            <?= htmlspecialchars($st->label) ?>
                            <?php if (!$st->default): ?><span class="badge badge-warning ml-1"><?= lang_line('devmode_badge_custom', 'Custom'); ?></span><?php endif; ?>
                        </label>
                        <?php if ($st->type === 'textarea'): ?>
                            <textarea name="<?= htmlspecialchars($st->key) ?>" rows="3" class="form-control form-control-sm"
                                      <?= $can_edit ? '' : 'readonly' ?>><?= htmlspecialchars($st->value) ?></textarea>
                        <?php else: ?>
                            <input type="text" name="<?= htmlspecialchars($st->key) ?>" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($st->value) ?>" <?= $can_edit ? '' : 'readonly' ?>>
                        <?php endif; ?>
                        <small class="form-text text-muted"><?= htmlspecialchars($st->hint) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($can_edit): ?>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('devmode_identity_save', 'Simpan Identitas'); ?>
                </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="row">
        <?php foreach ($slots as $slot): ?>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <strong class="small"><?= htmlspecialchars($slot->label) ?></strong>
                    <?php if ($slot->is_custom): ?><span class="badge badge-warning"><?= lang_line('devmode_badge_custom', 'Custom'); ?></span><?php endif; ?>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($slot->hint)): ?>
                    <p class="small text-muted mb-2"><?= htmlspecialchars($slot->hint) ?></p>
                    <?php endif; ?>
                    <img src="<?= base_url($slot->current_path) ?>" alt="<?= htmlspecialchars($slot->label) ?>" style="max-width:100%;max-height:120px;object-fit:contain;" class="mb-3 border rounded p-2">
                    <?php if ($can_edit): ?>
                    <form method="POST" action="<?= base_url('developer_mode/upload_asset') ?>" enctype="multipart/form-data" class="mb-2">
                        <input type="hidden" name="asset_key" value="<?= htmlspecialchars($slot->key) ?>">
                        <div class="input-group input-group-sm">
                            <input type="file" name="file" class="form-control-file" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary btn-block mt-2">
                            <i class="fas fa-upload mr-1"></i> <?= lang_line('devmode_assets_btn_upload', 'Ganti Gambar'); ?>
                        </button>
                    </form>
                    <?php if ($slot->is_custom): ?>
                    <form method="POST" action="<?= base_url('developer_mode/reset_asset') ?>" onsubmit="return confirm('<?= htmlspecialchars(lang_line('devmode_assets_confirm_reset', 'Kembalikan ke gambar default?'), ENT_QUOTES) ?>');">
                        <input type="hidden" name="asset_key" value="<?= htmlspecialchars($slot->key) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary btn-block">
                            <i class="fas fa-undo mr-1"></i> <?= lang_line('devmode_btn_reset_default', 'Reset ke Default'); ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
