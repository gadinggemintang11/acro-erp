<?php
/**
 * Header dokumen produksi (hero) + tombol aksi dokumen.
 * Butuh: $header, $current_stage.
 */
$pp_can_edit = check_access('production_process', 'edit');
$pp_can_conf = check_access('production_process', 'confirm'); // = izin yang dicek controller confirm()/unconfirm()
$pp_meta     = pp_stage_meta($current_stage);
?>
<section class="pp-hero">
    <div class="pp-hero-top">
        <div class="pp-hero-id">
            <div class="pp-hero-icon"><i class="fas <?= $pp_meta['icon']; ?>"></i></div>
            <div style="min-width:0">
                <div class="pp-hero-kicker">
                    <?= htmlspecialchars(lang_line('title_pp_main')); ?> · <?= htmlspecialchars(lang_line('pp_stage_' . $current_stage)); ?>
                </div>
                <h1 class="pp-hero-title"><?= htmlspecialchars($header->production_number); ?> <?= pp_status_pill($header->status); ?></h1>
                <div class="pp-hero-subline">
                    <i class="fas fa-clipboard-list mr-1"></i><?= htmlspecialchars($header->wo_number ?: '-'); ?>
                    <?php if (!empty($header->sc_number)): ?>
                        <span class="mx-1">·</span><i class="fas fa-file-signature mr-1"></i><?= htmlspecialchars($header->sc_number); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="pp-hero-actions">
            <?php if ($header->status == 'Draft' && $pp_can_edit): ?>
                <button type="button" class="btn pp-btn-glass" data-toggle="modal" data-target="#ppHeaderModal">
                    <i class="fas fa-pen mr-1"></i><?= lang_line('pp2_btn_edit_header', 'Ubah Header'); ?>
                </button>
            <?php endif; ?>
            <?php if ($header->status == 'Draft' && $pp_can_conf): ?>
                <a href="<?= base_url('production_process/confirm/' . $header->id); ?>" class="btn pp-btn-light pp-confirm-link"
                   data-title="<?= htmlspecialchars(lang_line('pp_btn_confirm_process')); ?>"
                   data-text="<?= htmlspecialchars(lang_line('pp_confirm_process_msg')); ?>">
                    <i class="fas fa-check-circle mr-1"></i><?= lang_line('pp_btn_confirm_process'); ?>
                </a>
            <?php elseif ($header->status == 'In Progress'): ?>
                <?php if ($pp_can_edit): ?>
                <a href="<?= base_url('production_process/complete_production/' . $header->id); ?>" class="btn pp-btn-light pp-confirm-link"
                   data-title="<?= htmlspecialchars(lang_line('pp_btn_finish_production')); ?>"
                   data-text="<?= htmlspecialchars(lang_line('pp_finish_production_msg')); ?>">
                    <i class="fas fa-flag-checkered mr-1"></i><?= lang_line('pp_btn_finish_production'); ?>
                </a>
                <?php endif; ?>
                <?php if ($pp_can_conf): ?>
                <a href="<?= base_url('production_process/unconfirm/' . $header->id); ?>" class="btn pp-btn-glass pp-confirm-link"
                   data-title="<?= htmlspecialchars(lang_line('pp_btn_cancel_confirm')); ?>"
                   data-text="<?= htmlspecialchars(lang_line('pp_cancel_confirm_msg')); ?>" data-danger="1">
                    <i class="fas fa-undo mr-1"></i><?= lang_line('pp_btn_cancel_confirm'); ?>
                </a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= base_url('production_process/export_' . $current_stage . '_pdf/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass">
                <i class="fas fa-file-pdf mr-1"></i><?= lang_line('pp_btn_pdf'); ?> <?= htmlspecialchars(lang_line('pp_stage_' . $current_stage)); ?>
            </a>
            <a href="<?= base_url('production_process'); ?>" class="btn pp-btn-glass">
                <i class="fas fa-arrow-left mr-1"></i><?= lang_line('pp_btn_back'); ?>
            </a>
        </div>
    </div>

    <div class="pp-hero-meta">
        <div class="pp-meta">
            <div class="pp-meta-label"><?= lang_line('pp2_meta_fg', 'Barang Jadi'); ?></div>
            <div class="pp-meta-value" title="<?= htmlspecialchars(($header->fg_name ?? '') . ' ' . ($header->fg_code ?? '')); ?>">
                <?= htmlspecialchars($header->fg_name ?: '-'); ?> <?php if (!empty($header->fg_code)): ?><small>(<?= htmlspecialchars($header->fg_code); ?>)</small><?php endif; ?>
            </div>
        </div>
        <div class="pp-meta">
            <div class="pp-meta-label"><?= lang_line('pp2_meta_planned', 'Qty Rencana'); ?></div>
            <div class="pp-meta-value"><?= pp_qty($header->planned_qty, 2); ?> <small><?= htmlspecialchars($header->fg_unit ?? ''); ?></small></div>
        </div>
        <div class="pp-meta">
            <div class="pp-meta-label"><?= lang_line('pp2_meta_prod_date', 'Tanggal Produksi'); ?></div>
            <div class="pp-meta-value"><?= pp_fmt_dt($header->production_date, '-', false); ?></div>
        </div>
        <div class="pp-meta">
            <div class="pp-meta-label"><?= lang_line('pp2_meta_created_by', 'Dibuat oleh'); ?></div>
            <div class="pp-meta-value"><?= htmlspecialchars($header->creator_name ?: '-'); ?></div>
        </div>
        <?php if (!empty($header->confirmer_name)): ?>
        <div class="pp-meta">
            <div class="pp-meta-label"><?= lang_line('pp2_meta_confirmed_by', 'Dikonfirmasi'); ?></div>
            <div class="pp-meta-value" title="<?= htmlspecialchars(pp_fmt_dt($header->confirmed_at ?? null)); ?>"><?= htmlspecialchars($header->confirmer_name); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($header->remarks)): ?>
        <div class="pp-meta" style="grid-column: span 2;">
            <div class="pp-meta-label"><?= lang_line('pp2_meta_remarks', 'Catatan'); ?></div>
            <div class="pp-meta-value" title="<?= htmlspecialchars($header->remarks); ?>" style="font-weight:600"><?= htmlspecialchars($header->remarks); ?></div>
        </div>
        <?php endif; ?>
    </div>
</section>
