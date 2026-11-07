<style>
/* Baris item NONAKTIF dibikin lebih pudar dari baris aktif.
   Dua sebab kenapa `class="text-muted"` di <tr> saja TIDAK cukup (dites):
   1. Light mode: `.table` bawaan SB Admin 2 warnanya SUDAH #858796, sama
      persis dengan `.text-muted` -> tidak ada bedanya sama sekali.
   2. Dark mode: `body.dark-mode .table tbody td` menyetel color LANGSUNG di
      <td>, jadi warna yang diwarisi dari <tr> kalah.
   Makanya rule ini menyasar <td> langsung + !important, dgn abu-abu yang
   memang lebih terang dari warna teks tabel normal. Badge Status/Kategori
   tidak ikut pudar karena punya `color` sendiri. */
tr.item-inactive > td {
    color: #b7b9cc !important;
}
body.dark-mode tr.item-inactive > td {
    color: #7a7a7a !important;
}

/* Header tabel tetap terlihat saat baris di-scroll (user 2026-09-09).
   Kotak tabel diberi tinggi maksimum supaya DIA yang men-scroll: sticky
   selalu mengacu ke scroll container terdekat, dan .table-responsive
   (overflow-x:auto) SUDAH jadi scroll container -- tanpa max-height,
   containernya tidak pernah scroll vertikal & header tidak pernah menempel.
   background WAJIB opaque, kalau tidak baris yang lewat terlihat menembus.
   border th tidak ikut menempel gara-gara `border-collapse: collapse`
   bawaan SB Admin 2, jadi garisnya ditiru pakai box-shadow inset. */
#inventoryTableWrap {
    max-height: 65vh;
    overflow: auto;
}
#inventoryTableWrap thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background-color: #eaecf4;
    box-shadow: inset 0 1px 0 #dddfeb, inset 0 -1px 0 #dddfeb;
}
body.dark-mode #inventoryTableWrap thead th {
    background-color: var(--color-table-header);
    box-shadow: inset 0 1px 0 var(--color-border), inset 0 -1px 0 var(--color-border);
}
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <div>
            <?php if (check_access('master_inventory', 'view')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_inventory/export_excel'); ?>" class="btn btn-sm btn-success" title="Export Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="<?= base_url('master_inventory/export_pdf'); ?>" class="btn btn-sm btn-danger" title="Export PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            <?php endif; ?>
            <?php if (check_access('master_inventory', 'add')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_inventory/download_template'); ?>" class="btn btn-sm btn-info" title="Download Template Excel">
                        <i class="fas fa-download"></i> Template
                    </a>
                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#importExcelModal" title="Import dari Excel">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                </div>
                <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> <?= lang_line('inventory_add_new'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form method="get" class="form-inline" id="searchForm">
                <div class="form-group mr-2 mb-2 mb-md-0">
                    <input type="text" name="search" class="form-control" placeholder="Cari kode barang atau nama barang..." value="<?= htmlspecialchars($search); ?>">
                </div>
                <?php // Filter kode kategori & status (user 2026-09-09). Pilihannya
                      // diambil dari kode yang BENAR-BENAR dipakai item, bukan seluruh
                      // master kategori -- supaya tidak ada opsi yang hasilnya 0 baris. ?>
                <div class="form-group mr-2 mb-2 mb-md-0">
                    <select name="kategori" class="form-control">
                        <option value="">-- <?= lang_line('inventory_filter_all_category', 'Semua Kode Kategori'); ?> --</option>
                        <?php foreach (($kategori_used_list ?? []) as $k): ?>
                            <option value="<?= htmlspecialchars($k->code) ?>" <?= (($filter_kategori ?? '') === $k->code) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k->code) ?><?= !empty($k->description) ? ' — ' . htmlspecialchars($k->description) : '' ?> (<?= (int) $k->total ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2 mb-md-0">
                    <select name="status" class="form-control">
                        <option value="">-- <?= lang_line('inventory_filter_all_status', 'Semua Status'); ?> --</option>
                        <option value="Active"   <?= (($filter_status ?? '') === 'Active')   ? 'selected' : '' ?>><?= lang_line('inventory_status_active'); ?></option>
                        <option value="Nonaktif" <?= (($filter_status ?? '') === 'Nonaktif') ? 'selected' : '' ?>><?= lang_line('inventory_status_inactive'); ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                <?php if (!empty($search) || !empty($filter_kategori) || !empty($filter_status)): ?>
                    <a href="<?= base_url('master_inventory'); ?>" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
     <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
     <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('warning'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('inventory_list_title'); ?></h6>
                <small class="text-muted">
                    Ditampilkan: <?= count($inventaris); ?> item |
                    Item: <?php
                        $offset = $this->input->get('page') ?? 0;
                        echo ($offset + 1) . '-' . ($offset + count($inventaris));
                    ?>
                </small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive" id="inventoryTableWrap">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr class="text-center">
                            <th><?= lang_line('inventory_code'); ?></th>
                            <th><?= lang_line('inventory_name'); ?></th>
                            <th><?= lang_line('inventory_category_code'); ?></th>
                            <th><?= lang_line('inventory_category'); ?></th>
                            <th><?= lang_line('inventory_unit'); ?></th>
                            <th><?= lang_line('inventory_brand'); ?></th>
                            <th><?= lang_line('inventory_item_type', 'Jenis Item'); ?></th>
                            <th><?= lang_line('inventory_usage_category', 'Jenis Pemakaian'); ?></th>
                            <th><?= lang_line('inventory_condition'); ?></th>
                            <th><?= lang_line('inventory_status'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventaris as $item): ?>
                        <?php // Baris nonaktif ditulis abu-abu supaya gampang dibedakan sekilas
                              // dari item yang masih dipakai (user 2026-09-09). Badge status &
                              // kategori tetap berwarna sendiri (punya color eksplisit). ?>
                        <tr<?= $item->status != 'Active' ? ' class="item-inactive"' : '' ?>>
                            <td><?= htmlspecialchars($item->kode_barang) ?></td>
                            <td><?= htmlspecialchars($item->nama_barang) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($item->kategori) ?></span></td>
                            <td><?= htmlspecialchars($item->kategori_desc ?? $item->kategori) ?></td>
                             <td class="text-center"><?= htmlspecialchars($item->satuan_desc ?? $item->satuan ?? '-') ?></td>
                            <td><?= htmlspecialchars($item->merk ?? '-') ?></td>
                            <td class="text-center">
                                <?php $it_type = $item->item_type ?? ''; ?>
                                <?php if ($it_type !== ''): ?>
                                    <span class="badge badge-<?= $it_type === 'Non-Stock' ? 'secondary' : 'primary' ?>"><?= htmlspecialchars($it_type) ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php // Kolom baru (migration_inventory_usage_category.sql). DB yang
                                      // belum dimigrasi tidak punya properti ini -> tampil '-'. ?>
                                <?php $usg = $item->usage_category ?? ''; ?>
                                <?php if ($usg !== ''): ?>
                                    <span class="badge badge-<?= $usg === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($usg) ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($item->kondisi ?? '-') ?></td>
                            <td class="text-center">
                                <?php if ($item->status == 'Active'): ?>
                                    <span class="badge badge-success"><?= lang_line('inventory_status_active'); ?></span>
                                <?php elseif (!empty($item->replaced_by_kode)): ?>
                                    <?php
                                        // Item dinonaktifkan KARENA ada penggantinya — infonya muncul
                                        // saat kursor diarahkan ke badge status (user 2026-09-09).
                                        // data-html=true => isi title WAJIB sudah di-escape di sini.
                                        $repl_tip = '<b>' . htmlspecialchars($item->replaced_by_kode) . '</b><br>'
                                                  . htmlspecialchars($item->replaced_by_nama ?? '');
                                        if (!empty($item->replaced_at)) {
                                            $repl_tip .= '<br><small>' . lang_line('inventory_replaced_since', 'sejak') . ' '
                                                       . date('d M Y', strtotime($item->replaced_at)) . '</small>';
                                        }
                                    ?>
                                    <span class="badge badge-danger" style="cursor: help;"
                                          data-toggle="tooltip" data-html="true" data-placement="left"
                                          title="<?= htmlspecialchars(lang_line('inventory_replaced_tooltip', 'Diganti ke') . ':<br>' . $repl_tip, ENT_QUOTES) ?>">
                                        <?= lang_line('inventory_status_inactive'); ?>
                                        <i class="fas fa-exchange-alt ml-1"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= lang_line('inventory_status_inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (check_access('master_inventory', 'edit')): ?>
                                <button class="btn btn-warning btn-sm"
                                        data-toggle="modal" data-target="#editModal"
                                        data-id="<?= $item->id ?>"
                                        data-kode="<?= htmlspecialchars($item->kode_barang) ?>"
                                        data-nama="<?= htmlspecialchars($item->nama_barang) ?>"
                                        data-kategori="<?= htmlspecialchars($item->kategori) ?>"
                                        data-kategori_desc="<?= htmlspecialchars($item->kategori_desc ?? '') ?>"
                                        data-satuan="<?= htmlspecialchars($item->satuan) ?>" data-merk="<?= htmlspecialchars($item->merk ?? '') ?>"
                                        data-item_type="<?= htmlspecialchars($item->item_type ?? '') ?>"
                                        data-usage_category="<?= htmlspecialchars($item->usage_category ?? 'General') ?>"
                                        data-kondisi="<?= htmlspecialchars($item->kondisi) ?>"
                                        data-status="<?= htmlspecialchars($item->status) ?>"
                                        data-replaced_by="<?= (int) ($item->replaced_by_id ?? 0) ?>"
                                        title="<?= lang_line('btn_edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (check_access('master_inventory', 'delete') && $item->status == 'Active'): ?>
                                <?php // Tombol ini menonaktifkan TANPA pengganti (item obsolete). Kalau ada
                                      // penggantinya, jalurnya lewat Edit -> Status Nonaktif -> pilih item pengganti. ?>
                                <a href="<?= base_url('master_inventory/delete/' . $item->id) ?>" class="btn btn-danger btn-sm btn-delete" title="<?= lang_line('btn_delete'); ?>" data-message="<?= lang_line('inventory_deactivate_confirm', 'Nonaktifkan item ini? Kalau item ini diganti item berkode baru, pakai tombol Edit supaya penggantinya bisa dicatat.'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="d-flex justify-content-center">
                        <?= $pagination; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('master_inventory/add') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang_line('inventory_add_title'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                     <div class="form-group">
                        <label>Jenis Item <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="item_type" class="form-control item-type-select" required>
                             <?php foreach ($item_type_list as $it): ?>
                                 <option value="<?= htmlspecialchars($it->code) ?>"><?= htmlspecialchars($it->description) ?></option>
                             <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Menentukan kode MAIN (STK/ITM) pada kode barang baru.</small>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_usage_category', 'Jenis Pemakaian'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="usage_category" class="form-control" required>
                            <option value="General">General</option>
                            <option value="Produksi">Produksi</option>
                        </select>
                        <small class="form-text text-muted"><?= lang_line('inventory_usage_category_hint', 'Menentukan segmen GNR/PRD di kode barang, sekaligus barang ini muncul di pencarian item Permintaan Barang kategori mana. Barang General tidak muncul saat Permintaan Barang-nya Produksi, begitu juga sebaliknya.'); ?></small>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_category'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="kategori" class="form-control kategori-select" required>
                             <option value=""><?= lang_line('inventory_select_category'); ?></option>
                             <?php foreach ($sub_kategori_list as $kat): ?>
                                 <option value="<?= $kat->code ?>"><?= htmlspecialchars($kat->description) ?></option>
                             <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_code'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <div class="input-group">
                            <input type="text" name="kode_barang" id="add_kode_barang" class="form-control" placeholder="<?= lang_line('inventory_code_placeholder'); ?>" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="toggle_kode_manual"><?= lang_line('inventory_code_manual'); ?></button>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= lang_line('inventory_code_hint'); ?></small>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_name'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="<?= lang_line('inventory_name_placeholder'); ?>" required>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_unit'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="satuan" class="form-control" required>
                            <option value=""><?= lang_line('inventory_select_unit'); ?></option>
                            <?php foreach ($satuan_list as $sat): ?>
                                <option value="<?= $sat->code ?>"><?= htmlspecialchars($sat->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_brand'); ?> (<?= lang_line('inventory_optional'); ?>)</label>
                        <input type="text" name="merk" class="form-control" placeholder="<?= lang_line('inventory_brand_placeholder'); ?>">
                     </div>
                    <?php // Field "Lokasi" DIHAPUS dari form Tambah (user 2026-09-09):
                          // digantikan "Jenis Item" yang di modal ini SUDAH ada di
                          // bagian atas (dipakai juga utk generate kode STK/ITM), jadi
                          // tidak perlu field kedua. Kolom DB `lokasi` sengaja TIDAK
                          // di-drop supaya data lama tidak hilang. ?>
                     <div class="form-group">
                        <label><?= lang_line('inventory_condition'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="kondisi" class="form-control" required>
                             <option value=""><?= lang_line('inventory_select_condition'); ?></option>
                            <?php foreach ($kondisi_list as $kon): ?>
                                <option value="<?= htmlspecialchars($kon->code); ?>"><?= htmlspecialchars($kon->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('inventory_min_stock_label'); ?> <small class="text-muted">- <?= lang_line('inventory_min_stock_hint'); ?></small></label>
                        <input type="number" step="0.01" name="minimum_stock" class="form-control" placeholder="<?= lang_line('inventory_min_stock_placeholder'); ?>" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('inventory_btn_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang_line('inventory_btn_save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden data untuk auto-generate kode -->
<script>
var kategoriKodeMap = {
    <?php foreach ($kategori_list as $kat): ?>
        '<?= $kat->code ?>': '<?= $kat->code ?>',
    <?php endforeach; ?>
};
</script>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="post" id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang_line('inventory_edit_title'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                     <div class="form-group">
                        <label><?= lang_line('inventory_code'); ?></label>
                        <input type="text" id="edit_kode" class="form-control" readonly>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_category_code'); ?></label>
                        <input type="text" id="edit_kategori_code" class="form-control" readonly>
                     </div>
                      <div class="form-group">
                        <label><?= lang_line('inventory_category'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="kategori" id="edit_kategori" class="form-control" required>
                             <option value=""><?= lang_line('inventory_select_category'); ?></option>
                             <?php foreach ($kategori_list as $kat): ?>
                                 <option value="<?= $kat->code ?>"><?= htmlspecialchars($kat->description) ?></option>
                             <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_name'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <input type="text" name="nama_barang" id="edit_nama" class="form-control" placeholder="<?= lang_line('inventory_name_placeholder'); ?>" required>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_unit'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="satuan" id="edit_satuan" class="form-control" required>
                            <option value=""><?= lang_line('inventory_select_unit'); ?></option>
                            <?php foreach ($satuan_list as $sat): ?>
                                <option value="<?= $sat->code ?>"><?= htmlspecialchars($sat->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                     </div>
                      <div class="form-group">
                        <label><?= lang_line('inventory_brand'); ?> (<?= lang_line('inventory_optional'); ?>)</label>
                        <input type="text" name="merk" id="edit_merk" class="form-control" placeholder="<?= lang_line('inventory_brand_placeholder'); ?>">
                     </div>
                    <div class="form-group">
                        <label><?= lang_line('inventory_item_type', 'Jenis Item'); ?></label>
                        <select name="item_type" id="edit_item_type" class="form-control">
                            <?php foreach ($item_type_list as $it): ?>
                                <option value="<?= htmlspecialchars($it->code) ?>"><?= htmlspecialchars($it->description) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            <?= lang_line('inventory_item_type_edithint', 'Kode barang TIDAK ikut berubah kalau jenis item diganti (kode dibuat sekali saat item dibuat).'); ?>
                        </small>
                    </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_usage_category', 'Jenis Pemakaian'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="usage_category" id="edit_usage_category" class="form-control" required>
                            <option value="General">General</option>
                            <option value="Produksi">Produksi</option>
                        </select>
                        <small class="form-text text-muted"><?= lang_line('inventory_usage_category_hint', 'Menentukan segmen GNR/PRD di kode barang, sekaligus barang ini muncul di pencarian item Permintaan Barang kategori mana. Barang General tidak muncul saat Permintaan Barang-nya Produksi, begitu juga sebaliknya.'); ?></small>
                     </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_condition'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="kondisi" id="edit_kondisi" class="form-control" required>
                            <option value=""><?= lang_line('inventory_select_condition'); ?></option>
                            <?php foreach ($kondisi_list as $kon): ?>
                                <option value="<?= htmlspecialchars($kon->code); ?>"><?= htmlspecialchars($kon->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('inventory_min_stock_label'); ?> <small class="text-muted">- <?= lang_line('inventory_min_stock_hint'); ?></small></label>
                        <input type="number" step="0.01" id="edit_minimum_stock" name="minimum_stock" class="form-control" placeholder="<?= lang_line('inventory_min_stock_placeholder'); ?>" value="0">
                    </div>
                     <div class="form-group">
                        <label><?= lang_line('inventory_status'); ?> <span class="text-danger"><?= lang_line('inventory_required'); ?></span></label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="Active"><?= lang_line('inventory_status_active'); ?></option>
                            <option value="Nonaktif"><?= lang_line('inventory_status_inactive'); ?></option>
                        </select>
                    </div>
                    <?php if (!empty($has_replacement)): ?>
                    <!-- Hanya tampil kalau status = Nonaktif (di-toggle JS). Isian
                         OPSIONAL: item yang memang obsolete boleh tanpa pengganti. -->
                    <div class="form-group d-none" id="edit_replaced_wrap">
                        <label><?= lang_line('inventory_replaced_by_label', 'Diganti oleh (item pengganti)'); ?></label>
                        <select name="replaced_by_id" id="edit_replaced_by" class="form-control" style="width:100%;">
                            <option value="">-- <?= lang_line('inventory_replaced_by_none', 'Tidak ada pengganti'); ?> --</option>
                        </select>
                        <small class="form-text text-muted">
                            <?= lang_line('inventory_replaced_by_hint', 'Pilih item berkode baru yang menggantikan barang ini. Nanti muncul saat kursor diarahkan ke kolom Status.'); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('inventory_btn_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang_line('inventory_btn_save_changes'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var isKodeManual = false; // Track whether kode is in manual mode

    // Kandidat item pengganti (semua item Active) + flag apakah kolom
    // replaced_by_id sudah ada di DB (migration_inventory_replacement.sql).
    var REPLACEMENT_ENABLED = <?= !empty($has_replacement) ? 'true' : 'false' ?>;
    var REPLACEMENT_ITEMS = <?= json_encode($replacement_candidates ?? []) ?>;

    // Auto-generate Kode Barang when kategori/jenis-item/jenis-pemakaian changes (Add Modal)
    // Jenis Pemakaian ikut memicu: sejak 2026-10-06 dia jadi segmen GNR/PRD di
    // tengah kode (STK-GNR-OFC-000001), jadi kalau tidak ikut didengarkan,
    // kode di layar akan berbeda dengan kode yang akhirnya tersimpan.
    $('#addModal').on('change', '.kategori-select, .item-type-select, select[name="usage_category"]', function() {
        if (!isKodeManual) {
            var kategoriCode = $('#addModal .kategori-select').val();
            var itemType = $('#addModal .item-type-select').val();
            var usageCategory = $('#addModal select[name="usage_category"]').val();
            if (kategoriCode) {
                // Call AJAX to generate kode
                $.ajax({
                    url: '<?= base_url('master_inventory/generate_kode') ?>',
                    type: 'POST',
                    data: { kategori: kategoriCode, item_type: itemType, usage_category: usageCategory },
                    dataType: 'json',
                    success: function(response) {
                        if (response.kode) {
                            $('#add_kode_barang').val(response.kode);
                        }
                    }
                });
            }
        }
    });

    // Toggle between auto-generate and manual input
    $(document).on('click', '#toggle_kode_manual', function() {
        isKodeManual = !isKodeManual;
        var $input = $('#add_kode_barang');
        var $btn = $(this);

        if (isKodeManual) {
            // Switch to manual mode
            $input.attr('readonly', false).val('').focus();
            $btn.text('<?= lang_line('inventory_code_auto'); ?>').removeClass('btn-outline-secondary').addClass('btn-warning');
            $input.attr('placeholder', '<?= lang_line('inventory_code_manual_input'); ?>');
        } else {
            // Switch back to auto-generate mode
            $input.attr('readonly', true).attr('placeholder', '<?= lang_line('inventory_code_placeholder'); ?>');
            $btn.text('<?= lang_line('inventory_code_manual'); ?>').removeClass('btn-warning').addClass('btn-outline-secondary');
            // Trigger kategori change to re-generate
            $('.kategori-select').trigger('change');
        }
    });

    // Inisialisasi DataTable (jika belum)
    // $('#dataTable').DataTable(); // Uncomment jika Anda pakai library DataTable

    // Populate Edit Modal
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var modal = $(this);

        // Ambil data dari atribut data-*
        var id = button.data('id');
        var kode = button.data('kode');
        var nama = button.data('nama');
        var kategori = button.data('kategori');
        var kategori_desc = button.data('kategori_desc');
        var satuan = button.data('satuan'); // ✅ Ambil data satuan
        var merk = button.data('merk');
        var item_type = button.data('item_type');
        var usage_category = button.data('usage_category');
        var kondisi = button.data('kondisi');
        var status = button.data('status');

        // Set action form
        modal.find('#editForm').attr('action', '<?= base_url('master_inventory/update/') ?>' + id);

        // Isi field di modal
        modal.find('#edit_kode').val(kode);
        modal.find('#edit_kategori_code').val(kategori);
        modal.find('#edit_nama').val(nama);
        modal.find('#edit_kategori').val(kategori);
        modal.find('#edit_satuan').val(satuan); // ✅ Set value dropdown satuan
        modal.find('#edit_merk').val(merk);
        modal.find('#edit_item_type').val(item_type || 'Stock');
        modal.find('#edit_usage_category').val(usage_category || 'General');
        modal.find('#edit_kondisi').val(kondisi);
        modal.find('#edit_status').val(status);

        // --- Item pengganti (user 2026-09-09) ---
        // Daftar kandidat dikirim sekali dari controller; di sini dibuang
        // baris yang sedang diedit, lalu item BERNAMA SAMA ditaruh di grup
        // paling atas — itu kasus yang dimaksud user (barang dibuat ulang
        // dgn kode baru, nama tetap sama).
        if (REPLACEMENT_ENABLED) {
            var currentReplacedBy = String(button.data('replaced_by') || '');
            var namaLower = (nama || '').toString().trim().toLowerCase();
            var sameName = [], others = [];

            REPLACEMENT_ITEMS.forEach(function(it) {
                if (String(it.id) === String(id)) return; // tidak boleh dirinya sendiri
                ((it.nama_barang || '').trim().toLowerCase() === namaLower ? sameName : others).push(it);
            });

            var optHtml = '<option value="">-- <?= lang_line('inventory_replaced_by_none', 'Tidak ada pengganti'); ?> --</option>';
            var renderOpts = function(list) {
                return list.map(function(it) {
                    return $('<option></option>')
                        .attr('value', it.id)
                        .text(it.kode_barang + ' - ' + it.nama_barang)
                        .prop('outerHTML');
                }).join('');
            };
            if (sameName.length) {
                optHtml += '<optgroup label="<?= lang_line('inventory_replaced_group_same', 'Nama sama'); ?>">'
                         + renderOpts(sameName) + '</optgroup>';
            }
            optHtml += '<optgroup label="<?= lang_line('inventory_replaced_group_other', 'Item lain'); ?>">'
                     + renderOpts(others) + '</optgroup>';

            var $repl = modal.find('#edit_replaced_by');
            if ($repl.data('select2')) { $repl.select2('destroy'); }
            $repl.html(optHtml).val(currentReplacedBy && currentReplacedBy !== '0' ? currentReplacedBy : '');
            $repl.select2({
                width: '100%',
                dropdownParent: modal,
                placeholder: '-- <?= lang_line('inventory_replaced_by_none', 'Tidak ada pengganti'); ?> --',
                allowClear: true
            });
            toggleReplacedField();
        }
    });

    // Field pengganti cuma relevan saat item dinonaktifkan.
    function toggleReplacedField() {
        if (!REPLACEMENT_ENABLED) return;
        var isInactive = $('#edit_status').val() === 'Nonaktif';
        $('#edit_replaced_wrap').toggleClass('d-none', !isInactive);
        if (!isInactive) { $('#edit_replaced_by').val('').trigger('change.select2'); }
    }
    $('#edit_status').on('change', toggleReplacedField);

    // Tooltip badge status ("Diganti ke: ...") — SB Admin 2 tidak meng-init
    // tooltip Bootstrap secara global, jadi di-init di sini.
    $('[data-toggle="tooltip"]').tooltip();

    // Konfirmasi Hapus (jika belum ada di script global)
    $('.btn-delete').on('click', function(e) {
        e.preventDefault(); // Mencegah link langsung dieksekusi
        var url = $(this).attr('href');
        var message = $(this).data('message') || '<?= lang_line('common_confirm_delete'); ?>';

        if (confirm(message)) {
            window.location.href = url; // Lanjutkan ke URL delete
        }
    });
});
</script>

<?php if (check_access('master_inventory', 'add')): ?>
<div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post" action="<?= base_url('master_inventory/upload_excel'); ?>" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i> <?= lang_line('mst_inv_import_title'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <strong><?= lang_line('mst_inv_instructions'); ?></strong>
                        <ol class="mb-0 pl-3">
                            <li><?= lang_line('mst_inv_inst1'); ?></li>
                            <li><?= lang_line('mst_inv_inst2'); ?></li>
                            <li><?= lang_line('mst_inv_inst3'); ?></li>
                            <li><?= lang_line('mst_inv_inst4'); ?></li>
                        </ol>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('mst_inv_excel_file'); ?> <span class="text-danger"><?= lang_line('mst_label_required'); ?></span></label>
                        <input type="file" name="excel_file" class="form-control-file" accept=".xlsx,.xls" required>
                        <small class="text-muted"><?= lang_line('mst_inv_excel_format'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= base_url('master_inventory/download_template'); ?>" class="btn btn-info">
                        <i class="fas fa-download mr-1"></i> <?= lang_line('mst_inv_download_template'); ?>
                    </a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-upload mr-1"></i> <?= lang_line('mst_inv_btn_import'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('mst_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>