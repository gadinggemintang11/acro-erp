<?php
/**
 * Keterangan "PR mana yang sudah dibuat" untuk satu baris outstanding IRF
 * (user 2026-09-20: "kasih note lah untuk user"). Dipakai halaman detail
 * pengeluaran & IRF Outstanding.
 *
 * @var array $docs baris dari Consumable_outstanding_model::get_pr_docs_map()
 */
$docs = $docs ?? [];
if ($docs): ?>
<div class="mt-1">
    <?php foreach ($docs as $d):
        $url  = pr_doc_url($d->type, $d->pr_id);
        $tip  = sprintf(lang_line('ost_pr_doc_hint', 'PR %s · qty %s · status %s'),
                    $d->pr_number, number_format((float) $d->qty, 2), $d->status);
    ?>
        <?php if ($url): ?>
        <a href="<?= $url ?>" class="badge badge-light border text-primary mr-1" title="<?= htmlspecialchars($tip) ?>">
            <i class="fas fa-external-link-alt mr-1"></i><?= htmlspecialchars($d->pr_number) ?>
        </a>
        <?php else: ?>
        <span class="badge badge-light border text-muted mr-1" title="<?= htmlspecialchars($tip) ?>"><?= htmlspecialchars($d->pr_number) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
