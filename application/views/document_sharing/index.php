<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-folder-open text-primary mr-2"></i><?= lang_line('doc_sharing_title', 'Document Sharing'); ?>
        </h3>
    </div>
    <p class="text-muted mb-4"><?= lang_line('doc_sharing_index_hint', 'Pilih folder departemen untuk melihat/upload dokumen. Dokumen bisa diatur privat (cuma Anda) atau dibagikan ke role tertentu saat upload.'); ?></p>

    <div class="row">
        <?php foreach ($departments as $d): ?>
        <div class="col-md-6 col-lg-3 mb-4">
            <a href="<?= base_url('document_sharing/folder/' . rawurlencode($d->code)); ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100 border-left-primary doc-folder-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-folder fa-3x text-warning mb-3"></i>
                        <div class="font-weight-bold text-gray-800"><?= htmlspecialchars($d->description ?: $d->code); ?></div>
                        <div class="small text-muted mt-1">
                            <?= (int) ($counts[$d->code] ?? 0); ?> <?= lang_line('doc_sharing_doc_count_suffix', 'dokumen'); ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>

        <?php if (empty($departments)): ?>
        <div class="col-12">
            <div class="alert alert-info"><?= lang_line('doc_sharing_no_departments', 'Belum ada data departemen di Common Code.'); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.doc-folder-card { transition: transform .15s ease, box-shadow .15s ease; cursor: pointer; }
.doc-folder-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
</style>
