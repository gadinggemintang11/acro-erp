<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div>
            <a href="<?= base_url('qa_sampling/report'); ?>" class="btn btn-info btn-sm">
                <i class="fas fa-chart-bar"></i> <?= lang_line('qas_btn_report', 'Laporan'); ?>
            </a>
            <?php if (check_access('qa_sampling','add')): ?>
            <a href="<?= base_url('qa_sampling/create'); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> <?= lang_line('qas_btn_create', 'Buat Sampling'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($msg = $this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= $msg; ?></div>
    <?php endif; ?>
    <?php if ($msg = $this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= $msg; ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card shadow mb-3">
        <div class="card-body py-3">
            <form method="get" class="form-inline">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>"
                       class="form-control form-control-sm mr-2 mb-1" placeholder="<?= lang_line('qas_search_placeholder', 'Cari no/tujuan...'); ?>">
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>"
                       class="form-control form-control-sm mr-2 mb-1">
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>"
                       class="form-control form-control-sm mr-2 mb-1">
                <select name="status" class="form-control form-control-sm mr-2 mb-1">
                    <option value=""><?= lang_line('qas_filter_status_all', 'Semua Status'); ?></option>
                    <?php foreach (['Draft','Submitted','Approved','Posted','Closed','Cancelled'] as $st): ?>
                        <option value="<?= $st; ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : ''; ?>><?= $st; ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm mb-1" type="submit"><i class="fas fa-filter"></i> <?= lang_line('qas_btn_filter', 'Filter'); ?></button>
                <a href="<?= base_url('qa_sampling'); ?>" class="btn btn-secondary btn-sm mb-1 ml-1"><?= lang_line('btn_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('qas_th_doc_no', 'No Dokumen'); ?></th>
                            <th><?= lang_line('qas_th_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('qas_th_purpose', 'Tujuan'); ?></th>
                            <th><?= lang_line('qas_th_requester', 'Requester'); ?></th>
                            <th><?= lang_line('qas_th_status', 'Status'); ?></th>
                            <th><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="6" class="text-center"><?= lang_line('qas_no_data', 'Belum ada data.'); ?></td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td><a href="<?= base_url('qa_sampling/view/'.$r->id); ?>"><strong><?= htmlspecialchars($r->doc_no); ?></strong></a></td>
                                <td><?= date('d-m-Y', strtotime($r->doc_date)); ?></td>
                                <td><?= htmlspecialchars($r->purpose); ?></td>
                                <td><?= htmlspecialchars($r->requester_name ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $badge = ['Draft'=>'secondary','Submitted'=>'info','Approved'=>'primary',
                                              'Posted'=>'success','Closed'=>'dark','Cancelled'=>'danger'];
                                    $b = $badge[$r->status] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $b; ?>"><?= $r->status; ?></span>
                                </td>
                                <td>
                                    <a href="<?= base_url('qa_sampling/view/'.$r->id); ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                    <?php if (in_array($r->status, ['Draft','Submitted']) && check_access('qa_sampling','edit')): ?>
                                        <a href="<?= base_url('qa_sampling/edit/'.$r->id); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav><ul class="pagination pagination-sm justify-content-center">
                <?php for ($p = 1; $p <= $total_pages; $p++):
                    $q = $_GET; $q['page'] = $p;
                    $url = base_url('qa_sampling?'.http_build_query($q));
                ?>
                    <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?= $url; ?>"><?= $p; ?></a>
                    </li>
                <?php endfor; ?>
            </ul></nav>
            <?php endif; ?>
            <div class="text-muted small"><?= sprintf(lang_line('qas_total_docs', 'Total: %d dokumen'), $total); ?></div>
        </div>
    </div>
</div>
