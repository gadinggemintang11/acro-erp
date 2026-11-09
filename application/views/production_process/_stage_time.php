<?php
/**
 * Kartu "Waktu Proses" tahap (2026-11-08). Satu Mulai & Selesai per tahap;
 * waktu Mulai = tanggal transaksi stok WIP semua input/output tahap ini.
 * Butuh: $header, $current_stage, $stage_time, $stage_times_ready.
 */
$pt_start   = $stage_time->started_at ?? null;
$pt_finish  = $stage_time->finished_at ?? null;
$pt_label   = lang_line('pp_stage_' . $current_stage);
$pt_meta    = pp_stage_meta($current_stage);
$pt_can     = $stage_times_ready && $header->status == 'Draft' && check_access('production_process', 'edit');
$pt_dt_val  = function ($dt) { return $dt ? date('Y-m-d\TH:i', strtotime($dt)) : ''; };
?>
<div class="pp-card <?= $pt_meta['tone']; ?>" id="ppStageTime">
    <div class="pp-card-head">
        <h2>
            <span class="pp-ico"><i class="far fa-clock"></i></span>
            <span>
                <?= sprintf(lang_line('pp2_time_title', 'Waktu Proses %s'), htmlspecialchars($pt_label)); ?>
                <span class="d-block pp-sub"><?= lang_line('pp2_time_sub', 'Kapan pekerjaan tahap ini benar-benar dilakukan — boleh tanggal mundur.'); ?></span>
            </span>
        </h2>
        <?php if (!empty($stage_time->updated_by_name)): ?>
            <span class="pp-sub"><i class="fas fa-user-edit mr-1"></i><?= htmlspecialchars($stage_time->updated_by_name); ?> · <?= pp_fmt_dt($stage_time->updated_at); ?></span>
        <?php endif; ?>
    </div>
    <div class="pp-card-body">
        <?php if (!$stage_times_ready): ?>
            <div class="pp-locked"><i class="fas fa-database"></i>
                <div><?= lang_line('pp2_time_no_table', 'Tabel waktu proses belum ada. Jalankan <code>db/migration_tahap_produksi_acropoly.sql</code> terlebih dahulu.'); ?></div>
            </div>
        <?php elseif ($pt_can): ?>
            <form method="post" action="<?= base_url('production_process/save_stage_time/' . $header->id . '/' . $current_stage); ?>" id="ppTimeForm" class="pp-time">
                <div>
                    <label class="pp-label d-flex justify-content-between" for="ppStartedAt">
                        <span><?= lang_line('pp2_time_start', 'Mulai'); ?> <span class="text-danger">*</span></span>
                        <a href="#" class="pp-now text-muted text-capitalize" data-target="#ppStartedAt" style="font-weight:600"><i class="fas fa-bolt mr-1"></i><?= lang_line('pp2_time_now', 'sekarang'); ?></a>
                    </label>
                    <input type="datetime-local" class="form-control" name="started_at" id="ppStartedAt" required
                           value="<?= $pt_dt_val($pt_start); ?>" max="<?= date('Y-m-d\TH:i'); ?>">
                </div>
                <div>
                    <label class="pp-label d-flex justify-content-between" for="ppFinishedAt">
                        <span><?= lang_line('pp2_time_finish', 'Selesai'); ?> <span class="text-muted text-lowercase" style="font-weight:500">(<?= lang_line('pp2_optional', 'opsional'); ?>)</span></span>
                        <a href="#" class="pp-now text-muted text-capitalize" data-target="#ppFinishedAt" style="font-weight:600"><i class="fas fa-bolt mr-1"></i><?= lang_line('pp2_time_now', 'sekarang'); ?></a>
                    </label>
                    <input type="datetime-local" class="form-control" name="finished_at" id="ppFinishedAt"
                           value="<?= $pt_dt_val($pt_finish); ?>" min="<?= $pt_dt_val($pt_start); ?>" max="<?= date('Y-m-d\TH:i'); ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-tone px-4"><i class="fas fa-save mr-1"></i><?= lang_line('pp2_time_save', 'Simpan Waktu'); ?></button>
                </div>
            </form>
            <?php if ($pt_start && $pt_finish): ?>
                <div class="mt-3"><span class="pp-time-duration"><i class="fas fa-hourglass-end"></i><?= lang_line('pp2_time_duration', 'Durasi'); ?>: <?= htmlspecialchars(pp_duration($pt_start, $pt_finish)); ?></span></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="pp-time-view">
                <div class="pp-time-item">
                    <span class="pp-label"><?= lang_line('pp2_time_start', 'Mulai'); ?></span>
                    <div class="pp-time-value<?= $pt_start ? '' : ' is-empty'; ?>"><?= $pt_start ? pp_fmt_dt($pt_start) : lang_line('pp2_time_not_set', 'Waktu belum diisi'); ?></div>
                </div>
                <div class="pp-time-item">
                    <span class="pp-label"><?= lang_line('pp2_time_finish', 'Selesai'); ?></span>
                    <div class="pp-time-value<?= $pt_finish ? '' : ' is-empty'; ?>"><?= $pt_finish ? pp_fmt_dt($pt_finish) : '—'; ?></div>
                </div>
                <?php if ($pt_start && $pt_finish): ?>
                    <span class="pp-time-duration"><i class="fas fa-hourglass-end"></i><?= htmlspecialchars(pp_duration($pt_start, $pt_finish)); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($stage_times_ready): ?>
        <div class="pp-time-note">
            <i class="fas fa-info-circle mt-1"></i>
            <span><?= lang_line('pp2_time_note', 'Semua bahan masuk & hasil tahap ini tercatat di stok WIP pada waktu <b>Mulai</b>, bukan jam input di sistem. Mengubah waktu Mulai ikut memperbarui tanggal transaksinya.'); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
