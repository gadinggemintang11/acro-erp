<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('qa_sampling/export_excel?'.http_build_query($filters)); ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> <?= lang_line('qas_btn_export_excel', 'Export Excel'); ?>
        </a>
    </div>

    <div class="card shadow mb-3">
        <div class="card-body py-3">
            <form method="get" class="form-row">
                <div class="col-md-2">
                    <label class="small"><?= lang_line('qas_filter_from', 'Dari'); ?></label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']); ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small"><?= lang_line('qas_filter_to', 'Sampai'); ?></label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']); ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small"><?= lang_line('qas_filter_source', 'Sumber'); ?></label>
                    <select name="source" class="form-control form-control-sm">
                        <option value=""><?= lang_line('qas_filter_all', 'Semua'); ?></option>
                        <option value="warehouse" <?= ($filters['source'] ?? '')==='warehouse'?'selected':''; ?>>Warehouse</option>
                        <option value="wip" <?= ($filters['source'] ?? '')==='wip'?'selected':''; ?>>WIP</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small"><?= lang_line('qas_filter_item_type', 'Tipe Item'); ?></label>
                    <select name="item_type" class="form-control form-control-sm">
                        <option value=""><?= lang_line('qas_filter_all', 'Semua'); ?></option>
                        <?php foreach ($item_types as $k=>$v): ?>
                            <option value="<?= $k; ?>" <?= ($filters['item_type'] ?? '')===$k?'selected':''; ?>><?= $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small"><?= lang_line('qas_filter_test_result', 'Hasil Test'); ?></label>
                    <select name="test_status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('qas_filter_all', 'Semua'); ?></option>
                        <?php foreach (['Pending','Pass','Fail'] as $s): ?>
                            <option value="<?= $s; ?>" <?= ($filters['test_status'] ?? '')===$s?'selected':''; ?>><?= $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i> <?= lang_line('qas_btn_show', 'Tampilkan'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" style="font-size:0.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('qas_th_doc_no', 'No Dokumen'); ?></th>
                            <th><?= lang_line('qas_th_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('qas_th_source', 'Sumber'); ?></th>
                            <th><?= lang_line('qas_th_type', 'Tipe'); ?></th>
                            <th><?= lang_line('qas_th_item_code', 'Kode Item'); ?></th>
                            <th><?= lang_line('qas_th_item_name', 'Nama Item'); ?></th>
                            <th><?= lang_line('qas_th_qty', 'Qty'); ?></th>
                            <th><?= lang_line('qas_th_uom', 'UoM'); ?></th>
                            <th><?= lang_line('qas_th_purpose_report', 'Tujuan'); ?></th>
                            <th><?= lang_line('qas_th_test_result_report', 'Hasil Test'); ?></th>
                            <th><?= lang_line('qas_th_test_notes_report', 'Catatan Test'); ?></th>
                            <th><?= lang_line('qas_th_requester', 'Requester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="12" class="text-center text-muted"><?= lang_line('qas_no_data_report', 'Tidak ada data.'); ?></td></tr>
                        <?php else: foreach ($rows as $r):
                            $sBadge = ['Pending'=>'warning','Pass'=>'success','Fail'=>'danger'];
                            $sb = $sBadge[$r->test_status] ?? 'secondary';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r->doc_no); ?></td>
                            <td><?= date('d-m-Y', strtotime($r->doc_date)); ?></td>
                            <td><span class="badge badge-<?= $r->source==='wip'?'warning':'info'; ?>"><?= strtoupper($r->source); ?></span></td>
                            <td><?= htmlspecialchars($item_types[$r->item_type] ?? $r->item_type); ?></td>
                            <td><?= htmlspecialchars($r->item_code ?? '-'); ?></td>
                            <td><?= htmlspecialchars($r->item_name ?? '-'); ?>
                                <?php if ($r->source==='wip' && $r->production_number): ?>
                                <br><small class="text-muted"><?= sprintf(lang_line('qas_prod_prefix', 'Prod: %s'), $r->production_number); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= number_format($r->qty_sample, 4, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($r->uom ?? '-'); ?></td>
                            <td><?= htmlspecialchars($r->purpose); ?></td>
                            <td><span class="badge badge-<?= $sb; ?>"><?= $r->test_status; ?></span></td>
                            <td><?= nl2br(htmlspecialchars($r->test_result_notes ?? '-')); ?></td>
                            <td><?= htmlspecialchars($r->requester_name ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small"><?= sprintf(lang_line('qas_total_rows', 'Total baris: %d'), count($rows)); ?></div>
        </div>
    </div>
</div>
