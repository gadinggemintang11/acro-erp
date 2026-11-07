<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div>
            <a href="<?= base_url('stockopname/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm" title="<?= lang_line('so_print_worksheet_title'); ?>">
                <i class="fas fa-print"></i> <?= lang_line('so_btn_print_worksheet'); ?>
            </a>
            <a href="<?= base_url('stockopname/export_excel/' . $header->id); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> <?= lang_line('so_btn_export_excel'); ?>
            </a>
            <a href="<?= base_url('stockopname'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('so_btn_back'); ?></a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_doc_no'); ?></strong><br>
                    <?= $header->opname_no; ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_date_short'); ?></strong><br>
                    <?= date('d M Y', strtotime($header->opname_date)); ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_item_type_short'); ?></strong><br>
                    <span class="badge badge-info"><?= $header->item_type; ?></span>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_status'); ?></strong><br>
                    <span class="badge badge-warning"><?= $header->status; ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php
        $progress = $progress ?? ['total' => 0, 'counted' => 0, 'pending' => 0];
        $pct = ($progress['total'] > 0) ? round($progress['counted'] / $progress['total'] * 100) : 0;
    ?>
    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted"><?= lang_line('so_progress_label'); ?>
                    <strong><?= $progress['counted']; ?></strong> <?= lang_line('so_progress_count'); ?> <strong><?= $progress['total']; ?></strong> <?= lang_line('so_progress_unit'); ?>
                    (<?= $progress['pending']; ?> <?= lang_line('so_progress_pending'); ?>)
                </small>
                <small class="text-muted"><?= $pct; ?>%</small>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct; ?>%"></div>
            </div>
        </div>
    </div>

    <?php if (empty($details) && $progress['total'] == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?= lang_line('so_empty_warning_title'); ?></strong>
            <?= sprintf(lang_line('so_empty_warning_body'), htmlspecialchars($header->item_type)); ?>
            <a href="<?= base_url('stockopname/regenerate/' . $header->id); ?>" class="btn btn-warning btn-sm ml-2">
                <i class="fas fa-sync"></i> <?= lang_line('so_btn_refresh_items'); ?>
            </a>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('stockopname/save_counts/' . $header->id); ?>" method="POST">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('so_card_items_to_count'); ?></h6>
                    <small class="text-muted"><?= sprintf(lang_line('so_page_info'), (int)$current_page, max(1,(int)$total_pages)); ?></small>
                </div>
                <div>
                    <a href="<?= base_url('stockopname/regenerate/' . $header->id); ?>" class="btn btn-outline-warning btn-sm" title="<?= lang_line('so_refresh_tooltip'); ?>" onclick="return confirm('<?= htmlspecialchars(addslashes(lang_line('so_refresh_confirm'))); ?>');">
                        <i class="fas fa-sync"></i> <?= lang_line('so_btn_refresh'); ?>
                    </a>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-save"></i> <?= lang_line('so_btn_save'); ?>
                    </button>
                    <a href="<?= base_url('stockopname/confirm/' . $header->id); ?>" class="btn btn-primary btn-sm" onclick="return confirm('<?= htmlspecialchars(addslashes(lang_line('so_confirm_msg'))); ?>');">
                        <i class="fas fa-check-circle"></i> <?= lang_line('so_btn_confirm'); ?>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="opnameTable" width="100%" cellspacing="0">
                        <thead>
                            <tr class="text-center">
                                <th style="width: 12%;"><?= lang_line('so_th_code'); ?></th>
                                <th style="width: 25%;"><?= lang_line('so_th_item_name'); ?></th>
                                <th style="width: 13%;"><?= lang_line('so_th_qty_system'); ?></th>
                                <th style="width: 15%;"><?= lang_line('so_th_qty_physical_input'); ?></th>
                                <th style="width: 13%;"><?= lang_line('so_th_variance'); ?></th>
                                <th style="width: 22%;"><?= lang_line('so_th_notes'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($details as $d):
                                // Data sudah di-merge di controller, langsung pakai dari $d
                                $display_value = ($d->qty_fisik !== NULL && $d->qty_fisik !== '') ? $d->qty_fisik : '';
                                $display_notes = $d->notes ?: '';

                                // Hitung selisih berdasarkan display value
                                if ($display_value !== '') {
                                    $selisih = (float)$display_value - (float)$d->qty_sistem;
                                } else {
                                    $selisih = 0;
                                }

                                $color = 'secondary';
                                if ($selisih > 0) $color = 'success';
                                if ($selisih < 0) $color = 'danger';
                            ?>
                            <tr>
                                <td><small><?= htmlspecialchars($d->item_code ?? ''); ?></small></td>
                                <td>
                                    <?= htmlspecialchars($d->item_name); ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($d->item_unit); ?></small>
                                </td>
                                <td class="text-right align-middle">
                                    <strong><?= rtrim(rtrim(number_format((float)$d->qty_sistem, 4, '.', ''), '0'), '.'); ?></strong>
                                </td>
                                <td class="p-1 align-middle">
                                    <input type="number"
                                           name="details[<?= $d->id; ?>][qty_fisik]"
                                           class="form-control form-control-sm qty-fisik"
                                           value="<?= $display_value; ?>"
                                           step="0.0001"
                                           min="0"
                                           data-row-id="<?= $d->id; ?>"
                                           data-qty-sistem="<?= $d->qty_sistem; ?>"
                                           placeholder="<?= htmlspecialchars(lang_line('so_input_placeholder')); ?>">
                                </td>
                                <td class="text-right align-middle">
                                    <strong id="selisih-<?= $d->id; ?>" class="text-<?= $color; ?>">
                                        <?= ($display_value === '') ? '-' : rtrim(rtrim(number_format((float)$selisih, 4, '.', ''), '0'), '.'); ?>
                                    </strong>
                                </td>
                                <td class="p-1 align-middle">
                                    <input type="text"
                                           name="details[<?= $d->id; ?>][notes]"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($display_notes); ?>"
                                           placeholder="<?= htmlspecialchars(lang_line('so_notes_input_placeholder')); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($pagination)): ?>
                <div class="d-flex justify-content-center mt-3">
                    <?= $pagination; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const table = document.getElementById("opnameTable");

    // Auto calculate selisih when qty_fisik changes
    table.addEventListener("input", function(e) {
        if (e.target.classList.contains("qty-fisik")) {
            const input = e.target;
            const rowId = input.dataset.rowId;
            const qtySistem = parseFloat(input.dataset.qtySistem) || 0;
            const raw = input.value.trim();
            const selisihEl = document.getElementById(`selisih-${rowId}`);
            selisihEl.classList.remove('text-secondary', 'text-success', 'text-danger');

            if (raw === '') {
                // Belum dihitung → tampilkan dash, abu-abu
                selisihEl.textContent = '-';
                selisihEl.classList.add('text-secondary');
                return;
            }

            const qtyFisik = parseFloat(raw) || 0;
            const selisih = qtyFisik - qtySistem;

            selisihEl.textContent = selisih.toFixed(4).replace(/\.?0+$/, '') || '0';
            if (selisih > 0) {
                selisihEl.classList.add('text-success');
            } else if (selisih < 0) {
                selisihEl.classList.add('text-danger');
            } else {
                selisihEl.classList.add('text-secondary');
            }
        }
    });

    // Auto-save data every 10 seconds
    setInterval(function() {
        autoSaveData(); // silent save
    }, 10000);

    // Auto-save sebelum pindah halaman (on any navigation)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.pagination a') || e.target.closest('a[href*="stockopname"]')) {
            autoSaveData();
        }
    });
});

function autoSaveData() {
    const form = document.querySelector('form');
    const formData = new FormData(form);

    fetch('<?= base_url('stockopname/auto_save_data/' . $header->id); ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .catch(error => {
        console.error('Auto-save error:', error);
    });
}

// Auto-save before page unload or navigation
window.addEventListener('beforeunload', function() {
    autoSaveData();
});

// Auto-save before form submission
document.querySelector('form').addEventListener('submit', function() {
    autoSaveData();
});
</script>
