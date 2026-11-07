<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-dolly-flatbed text-primary mr-2"></i><?= lang_line('gc_issuance_title', 'Pengeluaran Barang'); ?>
        </h3>
    </div>
    <p class="text-muted mb-4"><?= lang_line('gc_issuance_hint', 'Pilih jenis pengeluaran barang di bawah ini.'); ?></p>

    <div class="row">
        <?php foreach ($tiles as $t): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <a href="<?= base_url($t['url']); ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100 border-left-<?= $t['color']; ?> goods-chooser-card">
                    <div class="card-body text-center py-4">
                        <i class="fas <?= $t['icon']; ?> fa-3x text-<?= $t['color']; ?> mb-3"></i>
                        <div class="font-weight-bold text-gray-800"><?= htmlspecialchars($t['title']); ?></div>
                        <div class="small text-muted mt-1"><?= htmlspecialchars($t['subtitle']); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.goods-chooser-card { transition: transform .15s ease, box-shadow .15s ease; cursor: pointer; }
.goods-chooser-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
</style>
