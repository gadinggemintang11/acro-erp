<?php
/**
 * Stepper tahap Proses Produksi (pengganti tab lama).
 * Urutan Acropoly (2026-11-08): Cutting -> Sewing -> Assembling -> Finishing.
 * Tiap langkah: nomor/centang, waktu proses tahap, jumlah input/output & QC pending.
 * Butuh: $pp_stages (Production_process::_load_views), $stage_overview,
 *        $current_stage, $header.
 */
$pp_stages = $pp_stages ?? ['cutting', 'sewing', 'assembling', 'finishing'];
$pp_last   = count($pp_stages) - 1;
?>
<nav class="pp-steps" aria-label="<?= htmlspecialchars(lang_line('pp2_steps_aria', 'Tahap produksi')); ?>">
    <?php foreach ($pp_stages as $pp_i => $pp_st):
        $pp_ov     = $stage_overview[$pp_st] ?? null;
        $pp_meta   = pp_stage_meta($pp_st);
        $pp_active = ($pp_st === $current_stage);
        $pp_done   = $pp_ov && !empty($pp_ov->finished_at);
    ?>
    <a href="<?= base_url('production_process/' . $pp_st . '/' . $header->id); ?>"
       class="pp-step <?= $pp_meta['tone']; ?><?= $pp_active ? ' is-active' : ''; ?><?= $pp_done ? ' is-done' : ''; ?>"
       <?= $pp_active ? 'aria-current="step"' : ''; ?>>
        <span class="pp-step-dot"><?= $pp_done ? '<i class="fas fa-check"></i>' : ($pp_i + 1); ?></span>
        <span class="pp-step-body">
            <span class="pp-step-name"><i class="fas <?= $pp_meta['icon']; ?>"></i><?= htmlspecialchars(lang_line('pp_stage_' . $pp_st)); ?></span>
            <?php if ($pp_ov && !empty($pp_ov->started_at)): ?>
                <span class="pp-step-time d-block" title="<?= htmlspecialchars(pp_fmt_range($pp_ov->started_at, $pp_ov->finished_at)); ?>">
                    <i class="far fa-clock mr-1"></i><?= htmlspecialchars(pp_fmt_range($pp_ov->started_at, $pp_ov->finished_at)); ?>
                    <?= empty($pp_ov->finished_at) ? ' · ' . htmlspecialchars(lang_line('pp2_running', 'berjalan')) : ''; ?>
                </span>
            <?php else: ?>
                <span class="pp-step-time is-empty d-block"><i class="far fa-clock mr-1"></i><?= htmlspecialchars(lang_line('pp2_time_not_set', 'Waktu belum diisi')); ?></span>
            <?php endif; ?>
            <span class="pp-step-stats">
                <span class="pp-chip" title="<?= htmlspecialchars(lang_line('pp2_chip_in_title', 'Baris bahan masuk')); ?>"><i class="fas fa-sign-in-alt"></i><?= (int) ($pp_ov->inputs ?? 0); ?></span>
                <span class="pp-chip" title="<?= htmlspecialchars(lang_line('pp2_chip_out_title', 'Baris hasil')); ?>"><i class="fas fa-sign-out-alt"></i><?= (int) ($pp_ov->outputs ?? 0); ?></span>
                <?php if (!empty($pp_ov->qc_pending)): ?>
                    <span class="pp-chip pp-chip--warn"><i class="fas fa-hourglass-half"></i><?= (int) $pp_ov->qc_pending; ?> QC</span>
                <?php elseif (!empty($pp_ov->outputs)): ?>
                    <span class="pp-chip pp-chip--ok"><i class="fas fa-check"></i>QC</span>
                <?php endif; ?>
            </span>
        </span>
        <?php if ($pp_i < $pp_last): ?><span class="pp-step-arrow"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>
