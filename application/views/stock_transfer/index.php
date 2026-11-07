<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-exchange-alt text-primary mr-2"></i><?= lang_line('title_st_index', 'Stock Transfer'); ?>
        </h3>
        <?php if (check_access('stock_transfer', 'add')): ?>
        <a href="<?= base_url('stock_transfer/add'); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('st_create', 'Buat Transfer Baru'); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle mr-1"></i>
        <?= lang_line('st_info', 'Gunakan modul ini kalau saldo General/Produksi tidak sesuai kenyataan fisik (mis. hasil cutover awal atau perlu pinjam sementara antar gudang). Transfer langsung efektif begitu disimpan — tidak ada tahap approval terpisah.'); ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history mr-1"></i> <?= lang_line('st_list_title', 'Riwayat Transfer'); ?></h6>
            <form method="get" class="form-inline mt-2 mt-md-0">
                <input type="text" name="search" class="form-control form-control-sm mr-1"
                       placeholder="<?= lang_line('st_search_ph', 'Cari no. transfer...'); ?>"
                       value="<?= htmlspecialchars($this->input->get('search') ?? '') ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm"></i></button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('st_th_number', 'No. Transfer'); ?></th>
                            <th><?= lang_line('label_date'); ?></th>
                            <th><?= lang_line('st_th_from', 'Dari'); ?></th>
                            <th><?= lang_line('st_th_to', 'Ke'); ?></th>
                            <th><?= lang_line('st_th_notes', 'Catatan'); ?></th>
                            <th><?= lang_line('adjustment_created_by'); ?></th>
                            <th width="100"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): foreach ($records as $r): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($r->transfer_number); ?></td>
                            <td><?= date('d M Y', strtotime($r->transfer_date)); ?></td>
                            <td><span class="badge badge-<?= $r->from_category === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($r->from_category); ?></span></td>
                            <td><span class="badge badge-<?= $r->to_category === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($r->to_category); ?></span></td>
                            <td class="text-left"><?= htmlspecialchars(mb_strimwidth($r->notes ?? '-', 0, 50, '...')); ?></td>
                            <td><?= htmlspecialchars($r->created_by_name ?? '-'); ?></td>
                            <td>
                                <a href="<?= base_url('stock_transfer/detail/' . $r->id); ?>" class="btn btn-sm btn-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transfer.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
