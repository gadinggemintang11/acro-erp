<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-coins text-warning mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <div>
            <?php if (check_access('kurs_pajak', 'add')): ?>
                <a href="<?= base_url('kurs_pajak/sync_now'); ?>" class="btn btn-info shadow-sm mr-1"
                   onclick="return confirm('<?= lang_line('kp_confirm_sync', 'Ambil kurs terbaru dari Kemenkeu sekarang?'); ?>');">
                    <i class="fas fa-sync-alt mr-1"></i> <?= lang_line('kp_btn_sync', 'Sync Sekarang'); ?>
                </a>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('kp_btn_input_manual', 'Input Manual'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- KURS AKTIF SAAT INI -->
    <div class="row mb-4">
        <?php foreach ($currencies as $cc):
            $r = $latest[$cc] ?? null; ?>
            <div class="col-md-6">
                <div class="card border-left-<?= $cc === 'USD' ? 'success' : 'warning'; ?> shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-<?= $cc === 'USD' ? 'success' : 'warning'; ?> text-uppercase mb-1">
                                    <?= sprintf(lang_line('kp_active_rate', 'Kurs Pajak %s Aktif'), $cc); ?>
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php if ($r): ?>
                                        Rp <?= number_format($r->rate, 4, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?= lang_line('kp_no_data_yet', '- belum ada data -'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($r): ?>
                                    <small class="text-muted">
                                        <?= lang_line('kp_period_label', 'Periode'); ?>: <?= date('d M Y', strtotime($r->effective_date_start)); ?>
                                        s/d <?= date('d M Y', strtotime($r->effective_date_end)); ?>
                                        &middot; <?= lang_line('kp_source_label', 'Sumber'); ?>: <span class="badge badge-<?= $r->source === 'auto' ? 'info' : 'secondary'; ?>"><?= strtoupper($r->source); ?></span>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTER -->
    <div class="card border-0 shadow-sm rounded mb-3">
        <div class="card-body py-2">
            <form method="get" class="form-inline">
                <label class="mr-2"><?= lang_line('kp_filter_currency', 'Mata Uang'); ?>:</label>
                <select name="currency_code" class="form-control form-control-sm mr-3">
                    <option value=""><?= lang_line('kp_filter_all', 'Semua'); ?></option>
                    <?php foreach ($currencies as $cc): ?>
                        <option value="<?= $cc; ?>" <?= ($filter['currency_code'] ?? '') === $cc ? 'selected' : ''; ?>><?= $cc; ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="mr-2"><?= lang_line('kp_filter_year', 'Tahun'); ?>:</label>
                <input type="number" name="year" class="form-control form-control-sm mr-3" style="width:90px"
                       value="<?= htmlspecialchars($filter['year'] ?? ''); ?>" placeholder="<?= date('Y'); ?>">
                <label class="mr-2"><?= lang_line('kp_filter_month', 'Bulan'); ?>:</label>
                <select name="month" class="form-control form-control-sm mr-3">
                    <option value=""><?= lang_line('kp_filter_all', 'Semua'); ?></option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i; ?>" <?= (int)($filter['month'] ?? 0) === $i ? 'selected' : ''; ?>>
                            <?= date('F', mktime(0,0,0,$i,1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <div class="form-check mr-3">
                    <input type="checkbox" name="snapshot_only" value="1" class="form-check-input" id="snapOnly"
                           <?= !empty($filter['snapshot_only']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="snapOnly"><?= lang_line('kp_filter_snapshot_only', 'Snapshot Akhir Bulan saja'); ?></label>
                </div>
                <button class="btn btn-sm btn-primary mr-1"><i class="fas fa-filter"></i> <?= lang_line('qas_btn_filter', 'Filter'); ?></button>
                <a href="<?= base_url('kurs_pajak'); ?>" class="btn btn-sm btn-secondary"><?= lang_line('btn_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('label_no', 'No'); ?></th>
                            <th><?= lang_line('kp_th_currency', 'Mata Uang'); ?></th>
                            <th><?= lang_line('kp_th_rate', 'Kurs (IDR)'); ?></th>
                            <th><?= lang_line('kp_th_effective_start', 'Berlaku Mulai'); ?></th>
                            <th><?= lang_line('kp_th_effective_end', 'Berlaku s/d'); ?></th>
                            <th><?= lang_line('kp_th_type', 'Jenis'); ?></th>
                            <th><?= lang_line('kp_th_source', 'Sumber'); ?></th>
                            <th><?= lang_line('kp_th_fetched_at', 'Fetched At'); ?></th>
                            <th width="120"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rates)): $no = 1; foreach ($rates as $r): ?>
                            <tr class="text-center">
                                <td><?= $no++; ?></td>
                                <td class="font-weight-bold"><?= $r->currency_code; ?></td>
                                <td class="text-right">Rp <?= number_format($r->rate, 4, ',', '.'); ?></td>
                                <td><?= date('d M Y', strtotime($r->effective_date_start)); ?></td>
                                <td><?= date('d M Y', strtotime($r->effective_date_end)); ?></td>
                                <td>
                                    <?php if ($r->is_month_end_snapshot): ?>
                                        <span class="badge badge-warning"><?= sprintf(lang_line('kp_type_snapshot', 'Snapshot %s'), htmlspecialchars($r->snapshot_month)); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><?= lang_line('kp_type_weekly', 'Mingguan'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $r->source === 'auto' ? 'success' : 'secondary'; ?>">
                                        <?= strtoupper($r->source); ?>
                                    </span>
                                </td>
                                <td><?= $r->fetched_at ? date('d M Y H:i', strtotime($r->fetched_at)) : '-'; ?></td>
                                <td>
                                    <?php if (check_access('kurs_pajak', 'edit')): ?>
                                        <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $r->id; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (check_access('kurs_pajak', 'delete')): ?>
                                        <a href="<?= base_url('kurs_pajak/delete/' . $r->id); ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('<?= lang_line('kp_confirm_delete', 'Hapus data kurs ini?'); ?>');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $r->id; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="post" action="<?= base_url('kurs_pajak/update/' . $r->id); ?>">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-gradient-primary text-white">
                                                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= sprintf(lang_line('kp_edit_title', 'Edit Kurs %s'), $r->currency_code); ?></h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label><?= lang_line('kp_label_currency', 'Mata Uang'); ?></label>
                                                    <input type="text" class="form-control" value="<?= $r->currency_code; ?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label><?= sprintf(lang_line('kp_label_rate_per', 'Kurs (IDR per 1 %s)'), $r->currency_code); ?></label>
                                                    <input type="text" name="rate" class="form-control"
                                                           value="<?= number_format($r->rate, 4, '.', ''); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label><?= lang_line('kp_label_effective_start', 'Berlaku Mulai'); ?></label>
                                                    <input type="date" name="effective_date_start" class="form-control"
                                                           value="<?= $r->effective_date_start; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label><?= lang_line('kp_label_effective_end', 'Berlaku s/d'); ?></label>
                                                    <input type="date" name="effective_date_end" class="form-control"
                                                           value="<?= $r->effective_date_end; ?>" required>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="is_month_end_snapshot" value="1"
                                                           class="form-check-input" id="snap<?= $r->id; ?>"
                                                           <?= $r->is_month_end_snapshot ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="snap<?= $r->id; ?>">
                                                        <?= lang_line('kp_label_mark_snapshot', 'Tandai sebagai Snapshot Akhir Bulan'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> <?= lang_line('kp_btn_update', 'Update'); ?></button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('kp_no_rate_data', 'Belum ada data kurs pajak.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- LOG SYNC -->
    <div class="card border-0 shadow-sm rounded mt-4">
        <div class="card-header bg-light">
            <strong><i class="fas fa-history mr-1"></i> <?= lang_line('kp_section_sync_log', 'Log Sinkronisasi Terakhir'); ?></strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><?= lang_line('kp_log_th_time', 'Waktu'); ?></th>
                        <th><?= lang_line('kp_log_th_type', 'Tipe'); ?></th>
                        <th><?= lang_line('kp_log_th_status', 'Status'); ?></th>
                        <th><?= lang_line('kp_log_th_insert', 'Insert'); ?></th>
                        <th><?= lang_line('kp_log_th_update', 'Update'); ?></th>
                        <th><?= lang_line('kp_log_th_message', 'Pesan'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sync_logs)): foreach ($sync_logs as $log): ?>
                        <tr>
                            <td><?= date('d M Y H:i', strtotime($log->created_at)); ?></td>
                            <td><span class="badge badge-secondary"><?= $log->sync_type; ?></span></td>
                            <td>
                                <span class="badge badge-<?= $log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning'); ?>">
                                    <?= strtoupper($log->status); ?>
                                </span>
                            </td>
                            <td><?= $log->total_inserted; ?></td>
                            <td><?= $log->total_updated; ?></td>
                            <td><small><?= htmlspecialchars($log->message ?: '-'); ?></small></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-3"><?= lang_line('kp_no_sync_log', 'Belum ada log sinkronisasi.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('kurs_pajak/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('kp_add_modal_title', 'Input Manual Kurs Pajak'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('kp_label_currency', 'Mata Uang'); ?></label>
                        <select name="currency_code" class="form-control" required>
                            <option value=""><?= lang_line('kp_select_placeholder', '-- Pilih --'); ?></option>
                            <?php foreach ($currencies as $cc): ?>
                                <option value="<?= $cc; ?>"><?= $cc; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('kp_label_rate_per_unit', 'Kurs (IDR per 1 unit)'); ?></label>
                        <input type="text" name="rate" class="form-control" placeholder="<?= lang_line('kp_rate_placeholder', 'contoh: 15750.5000'); ?>" required>
                        <small class="text-muted"><?= lang_line('kp_decimal_hint', 'Gunakan titik sebagai pemisah desimal.'); ?></small>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('kp_label_effective_start', 'Berlaku Mulai'); ?></label>
                        <input type="date" name="effective_date_start" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('kp_label_effective_end', 'Berlaku s/d'); ?></label>
                        <input type="date" name="effective_date_end" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_month_end_snapshot" value="1" class="form-check-input" id="snapAdd">
                        <label class="form-check-label" for="snapAdd">
                            <?= lang_line('kp_label_mark_snapshot_full', 'Tandai sebagai Snapshot Akhir Bulan (untuk closing)'); ?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> <?= lang_line('btn_save', 'Simpan'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
