<?php
    // "Developer Mode" atur-kolom (ERP Revision Notes, user 2026-09-03) —
    // pilot pertama fitur ini, di IRF List. $page_key HARUS unik per
    // halaman list, $column_key HARUS konsisten dipakai persis sama di
    // <th> & <td> supaya header & datanya tersembunyi/tampil bareng2.
    // Kolom "Aksi" SENGAJA tidak dimasukkan (fungsional, bukan data).
    //
    // Revisi (user klarifikasi lebih lanjut): bukan cuma HIDE kolom yang
    // sudah ada — harus bisa TAMBAH juga (dari field yang sudah tersedia
    // di query tapi belum pernah jadi kolom, lihat _select_with_audit_joins()
    // di Consumable_request_model — h.* + beberapa nama approver sudah ke-
    // select tapi belum ditampilkan). 3 kolom baru ditambah di bawah,
    // DEFAULT TERSEMBUNYI (supaya tampilan lama tidak berubah) tapi bisa
    // "ditambahkan" via tombol + di header tabel. Interaksinya juga
    // dipindah LANGSUNG ke halaman ini (bukan cuma di halaman terpisah
    // /developer_mode/columns) — tombol × per kolom (hapus) & + di ujung
    // (tambah dari yg masih tersembunyi), sesuai klarifikasi user.
    $ost_page_key = 'irf_list';
    $col_defs = [
        'request_number' => lang_line('consumable_request_number'),
        'date'            => lang_line('label_date'),
        'department'      => lang_line('consumable_department'),
        'requestor'       => lang_line('consumable_requestor'),
        'category'        => lang_line('cr_category', 'Kategori'),
        'purpose'         => lang_line('consumable_purpose', 'Keperluan'),
        'status'          => lang_line('label_status'),
        'status_reason'   => lang_line('consumable_status_reason', 'Alasan'),
        // Kolom tambahan opsional (default tersembunyi) - datanya sudah
        // ke-select via h.* / join nama user, tinggal ditampilkan.
        'created_by_name'    => lang_line('devmode_col_created_by', 'Dibuat Oleh'),
        'submitted_at'       => lang_line('devmode_col_submitted_at', 'Tgl Submit'),
        // "Tgl Disetujui" SENGAJA baca kolom approved_at, BUKAN final_approved_at.
        // Ditemukan lewat laporan user (kolomnya kosong semua): approve_hod()
        // di model ini langsung lompat status ke 'Approved' (pola "IRF
        // Approval efektif HOD-only", lihat [[irf_approval_workflow]]) dan
        // nulis approved_at/approved_by, TIDAK PERNAH nulis final_approved_at
        // kecuali IRF itu benar2 lewat approve_final() (jarang kepakai di
        // praktiknya). Dicek ke data live: 22/23 IRF Approved/Released py
        // approved_at terisi, 0/23 py final_approved_at terisi.
        'approved_at'        => lang_line('devmode_col_approved_at', 'Tgl Disetujui'),
    ];
    $col_default_visible = [
        'request_number' => true, 'date' => true, 'department' => true, 'requestor' => true,
        'category' => true, 'purpose' => true, 'status' => true, 'status_reason' => true,
        'created_by_name' => false, 'submitted_at' => false, 'approved_at' => false,
    ];
    $col = [];
    foreach ($col_defs as $k => $label) {
        $col[$k] = ui_column_visible($ost_page_key, $k, $label, $col_default_visible[$k]);
    }
    // SEMUA kolom (termasuk yang tersembunyi) SELALU dirender di DOM (cuma
    // display:none) — bukan di-skip server-side lagi. Ini yang bikin
    // interaksi Simpan/Batalkan bisa staged murni di sisi browser (nyala/
    // matiin visibility dulu, BELUM ke server sampai "Simpan Perubahan"
    // diklik) tanpa perlu reload tiap kali user pencet 1 kolom.
    // +2 = kolom "No" urut di kiri & kolom Aksi di kanan; dua-duanya di luar
    // $col_defs (bukan data, jadi tidak ikut diatur Developer Mode).
    $col_colspan = count($col_defs) + 2;

    // Devmode aktif -> tampilkan kontrol atur-kolom di tabel ini.
    // Tombol & panel "Atur Kolom" HANYA muncul kalau user memilih mode
    // 'Atur Kolom' di banner Developer Mode (user 2026-09-06) — supaya
    // tidak bentrok dengan mode edit teks.
    $devmode_active = devmode_is('column');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-inbox text-primary mr-2"></i> <?= lang_line('consumable_request_title', 'Permintaan Barang - General'); ?></h3>
            <small class="text-muted"><?= lang_line('consumable_request_subtitle', 'Buat request pengambilan barang dari HRGA'); ?></small>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="<?= lang_line('consumable_search_placeholder', 'Cari...'); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('common_all'); ?> <?= lang_line('label_status'); ?></option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="category" class="form-control form-control-sm">
                        <option value=""><?= lang_line('common_all'); ?> <?= lang_line('cr_category', 'Kategori'); ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= (($filters['category'] ?? '') === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-1 custom-control custom-checkbox d-flex align-items-center">
                    <input type="checkbox" class="custom-control-input" id="outstanding_only" name="outstanding_only" value="1"
                           <?= !empty($filters['outstanding_only']) ? 'checked' : '' ?>>
                    <label class="custom-control-label small" for="outstanding_only" title="<?= htmlspecialchars(lang_line('consumable_outstanding_filter_hint', 'Hanya Permintaan Barang Approved yang masih ada item belum sepenuhnya dikeluarkan/diselesaikan.')); ?>">
                        <?= lang_line('consumable_outstanding_filter', 'Hanya yang masih Outstanding'); ?>
                    </label>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> <?= lang_line('common_filter'); ?></button>
                <a href="<?= base_url('consumable_request') ?>" class="btn btn-sm btn-secondary mb-1 mr-1"><?= lang_line('btn_reset'); ?></a>
            </form>
            <div class="mt-1">
                <?php
                    // Tombol "Buat Request" & "Lihat Outstanding" DIHILANGKAN dari
                    // halaman ini (user 2026-09-09) — keduanya sudah ada di sidebar:
                    //   Buat Request     -> menu "Create New IRF"
                    //   Lihat Outstanding-> menu "IRF Outstanding" (BARU ditambahkan
                    //                       bareng perubahan ini; sebelumnya tombol
                    //                       inilah SATU-SATUNYA pintu ke halaman itu,
                    //                       padahal di sana letak tombol "Buat PR").
                    // Filter "Hanya yang masih Outstanding" di atas TETAP ADA dan
                    // beda fungsi — itu menyaring daftar IRF ini, bukan halaman
                    // outstanding per-item lintas dokumen.
                ?>
                <?php if ($devmode_active): ?>
                <?php
                    // Tombol "Atur Kolom" (ERP Revision Notes, user 2026-09-03) SENGAJA
                    // ditaruh DI SINI (luar tabel), BUKAN di dalam <th> — dropdown-menu
                    // yang jadi keturunan `.table-responsive` (overflow-x:auto) ke-CROP
                    // sama batas scroll container itu, kelihatan aneh/kepotong terutama
                    // pas tabel pendek (data kosong). Bug ditemukan user via screenshot.
                ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="devmodeColBtn" data-toggle="dropdown" title="<?= htmlspecialchars(lang_line('devmode_col_manage_title', 'Atur kolom')); ?>">
                        <i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_col_manage_btn', 'Atur Kolom'); ?>
                    </button>
                    <div class="dropdown-menu p-2" style="min-width:220px;" id="devmodeColMenu">
                        <div class="small font-weight-bold text-muted mb-2"><?= lang_line('devmode_col_manage_heading', 'Tampilkan kolom'); ?></div>
                        <?php foreach ($col_defs as $ck => $clabel): ?>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input devmode-col-toggle" id="devcol_<?= $ck ?>"
                                   data-page="<?= $ost_page_key ?>" data-column="<?= $ck ?>" <?= $col[$ck] ? 'checked' : '' ?>>
                            <label class="custom-control-label small" for="devcol_<?= $ck ?>"><?= htmlspecialchars($clabel) ?></label>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-end mt-2 pt-2 border-top" id="devmodeColActions" style="display:none !important;">
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="devmodeColCancel"><?= lang_line('btn_cancel', 'Batalkan'); ?></button>
                            <button type="button" class="btn btn-sm btn-primary" id="devmodeColSave"><?= lang_line('devmode_col_save', 'Simpan Perubahan'); ?></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="45" class="text-center"><?= lang_line('label_no', 'No'); ?></th>
                            <?php foreach ($col_defs as $ck => $clabel): ?>
                            <th class="devmode-col devmode-col-<?= $ck ?>" <?= $col[$ck] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($clabel) ?></th>
                            <?php endforeach; ?>
                            <th width="100"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="<?= $col_colspan; ?>" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('consumable_no_data'); ?></td></tr>
                        <?php else: ?>
                            <?php $row_no = 0; $row_offset = $row_offset ?? 0; ?>
                            <?php foreach ($requests as $req): ?>
                            <?php
                            $badge = ['Draft'=>'warning','Submitted'=>'info','Approved Head Dept'=>'info','Endorsed'=>'primary','Approved'=>'success','Released'=>'primary','Rejected'=>'danger','Cancelled'=>'secondary'][$req->status] ?? 'secondary';
                            // Status Reason (ERP Revision Notes, [[erp_revision_irf]] #2):
                            // reject_reason kalau ditolak, return_reason kalau
                            // dikembalikan ke Draft — dua-duanya sudah ada di
                            // consumable_request_header sejak lama, cuma belum
                            // pernah ditampilkan di list.
                            $status_reason = '';
                            if ($req->status === 'Rejected' && !empty($req->reject_reason)) {
                                $status_reason = $req->reject_reason;
                            } elseif ($req->status === 'Draft' && !empty($req->return_reason)) {
                                // return_reason tetap ditampilkan selama status masih
                                // Draft (belum resubmit) — begitu resubmit, riwayat
                                // lengkapnya tetap ada di timeline detail, bukan di list ini.
                                $status_reason = $req->return_reason;
                            }
                            ?>
                            <tr>
                                <td class="text-center text-muted"><?= $row_offset + (++$row_no); ?></td>
                                <td class="devmode-col devmode-col-request_number" <?= $col['request_number'] ? '' : 'style="display:none"' ?>><strong class="text-primary"><?= htmlspecialchars($req->request_number) ?></strong></td>
                                <td class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?= date('d M Y', strtotime($req->request_date)) ?></td>
                                <td class="devmode-col devmode-col-department" <?= $col['department'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($req->department ?? '-') ?></td>
                                <td class="devmode-col devmode-col-requestor" <?= $col['requestor'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($req->requestor_name ?? '-') ?></td>
                                <td class="devmode-col devmode-col-category" <?= $col['category'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= ($req->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($req->category ?? 'General') ?></span></td>
                                <td class="devmode-col devmode-col-purpose small" style="max-width:220px;white-space:normal;<?= $col['purpose'] ? '' : 'display:none' ?>" title="<?= htmlspecialchars($req->purpose ?? '') ?>"><?= htmlspecialchars($req->purpose ?? '-') ?></td>
                                <td class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= $badge ?>"><?= $req->status ?></span></td>
                                <td class="devmode-col devmode-col-status_reason small text-muted" style="max-width:220px;white-space:normal;<?= $col['status_reason'] ? '' : 'display:none' ?>" title="<?= htmlspecialchars($status_reason) ?>"><?= $status_reason !== '' ? htmlspecialchars($status_reason) : '-' ?></td>
                                <td class="devmode-col devmode-col-created_by_name" <?= $col['created_by_name'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($req->created_by_name ?? '-') ?></td>
                                <td class="devmode-col devmode-col-submitted_at" <?= $col['submitted_at'] ? '' : 'style="display:none"' ?>><?= !empty($req->submitted_at) ? date('d M Y H:i', strtotime($req->submitted_at)) : '-' ?></td>
                                <td class="devmode-col devmode-col-approved_at" <?= $col['approved_at'] ? '' : 'style="display:none"' ?>><?= !empty($req->approved_at) ? date('d M Y H:i', strtotime($req->approved_at)) : '-' ?></td>
                                <td>
                                    <?php
                                        // View dibuka di TAB BARU (user 2026-09-09) supaya list
                                        // (beserta filter & posisi scroll-nya) tidak hilang saat
                                        // buka-tutup banyak IRF. rel=noopener wajib: selain alasan
                                        // keamanan, itu juga yang bikin history tab baru = 1,
                                        // dipakai detail.php buat menyembunyikan tombol "Kembali".
                                    ?>
                                    <a href="<?= base_url('consumable_request/detail/' . $req->id) ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       target="_blank" rel="noopener"
                                       title="<?= lang_line('btn_detail'); ?>">
                                        <i class="fas fa-eye"></i> <?= lang_line('btn_view'); ?>
                                    </a>
                                    <?php
                                        // Link ke IRF Status (ERP Revision Notes, [[erp_revision_irf]]
                                        // #3): "di list harus kelihatan status gabungan Sudah
                                        // Approved/Sudah dibeli/Masih Outstanding/Sudah selesai" —
                                        // modul Irf_status SUDAH menghitung persis ini (per-IRF
                                        // fulfillment tracking, progress%, dll), jadi cukup di-link
                                        // dari sini alih-alih membangun ulang logikanya di list ini.
                                        $can_irf_status = check_access('irf_status', 'view')
                                            || check_access('irf_status_general', 'view')
                                            || check_access('irf_status_produksi', 'view');
                                    ?>
                                    <?php if ($can_irf_status && $req->status !== 'Draft'): ?>
                                    <a href="<?= base_url('irf_status/detail/' . $req->id) ?>"
                                       class="btn btn-sm btn-outline-warning"
                                       title="<?= htmlspecialchars(lang_line('consumable_view_status', 'Status gabungan: approval, sudah dibeli, outstanding')); ?>">
                                        <i class="fas fa-tasks"></i> <?= lang_line('consumable_status_btn', 'Status'); ?>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
            // Pagination 20 baris/halaman (user 2026-10-05). Link halaman
            // HARUS membawa filter yang sedang aktif -- kalau tidak, pindah
            // ke halaman 2 diam-diam membuang hasil pencarian/filter user.
            $pg_total   = $total_pages  ?? 1;
            $pg_current = $current_page ?? 1;
            $pg_rows    = $total_rows   ?? count($requests);
            $pg_qs = $this->input->get();
            unset($pg_qs['page']);
            $pg_url = current_url() . '?' . http_build_query($pg_qs) . (empty($pg_qs) ? '' : '&') . 'page=';
            $pg_from = $pg_rows > 0 ? ($row_offset ?? 0) + 1 : 0;
            $pg_to   = ($row_offset ?? 0) + count($requests);
        ?>
        <?php if ($pg_rows > 0): ?>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-2">
            <small class="text-muted mb-0">
                <?= lang_line('label_showing', 'Menampilkan'); ?> <?= $pg_from; ?>&ndash;<?= $pg_to; ?>
                <?= lang_line('label_of', 'dari'); ?> <?= $pg_rows; ?> <?= lang_line('label_data', 'data'); ?>
            </small>
            <?php if ($pg_total > 1): ?>
            <nav aria-label="Page navigation" class="mb-0">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $pg_current <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $pg_current <= 1 ? '#' : $pg_url . ($pg_current - 1); ?>">&laquo;</a>
                    </li>
                    <?php
                        // Jendela 5 halaman di sekitar halaman aktif, dijaga
                        // tetap 5 lebar di ujung-ujung daftar.
                        $pg_start = max(1, min($pg_current - 2, $pg_total - 4));
                        $pg_end   = min($pg_total, max($pg_current + 2, 5));
                    ?>
                    <?php if ($pg_start > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= $pg_url; ?>1">1</a></li>
                        <?php if ($pg_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $pg_start; $i <= $pg_end; $i++): ?>
                        <li class="page-item <?= $i === $pg_current ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $pg_url . $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($pg_end < $pg_total): ?>
                        <?php if ($pg_end < $pg_total - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= $pg_url . $pg_total; ?>"><?= $pg_total; ?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?= $pg_current >= $pg_total ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $pg_current >= $pg_total ? '#' : $pg_url . ($pg_current + 1); ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($devmode_active): ?>
<script>
// Developer Mode — atur kolom Daftar Permintaan Barang (ERP Revision Notes, user 2026-09-03).
// Centang/hilang-centang cuma mengubah TAMPILAN dulu (belum ke server) —
// baru beneran tersimpan begitu "Simpan Perubahan" diklik. "Batalkan" =
// muat ulang halaman (buang semua perubahan yang belum disimpan).
$(document).ready(function() {
    var pending = {}; // { column_key: 0|1 (is_hidden yang mau disimpan) }

    // Jangan biarkan dropdown Bootstrap nutup sendiri tiap kali checkbox/tombol di dalamnya diklik.
    $('#devmodeColMenu').on('click', function(e) { e.stopPropagation(); });

    function refreshActionBar() {
        var dirty = Object.keys(pending).length > 0;
        $('#devmodeColActions').css('display', dirty ? 'flex' : 'none');
    }

    $('.devmode-col-toggle').on('change', function() {
        var el = $(this);
        var key = el.data('column');
        var visible = el.is(':checked');
        $('.devmode-col-' + key).toggle(visible);
        pending[key] = visible ? 0 : 1; // is_hidden
        refreshActionBar();
    });

    $('#devmodeColCancel').on('click', function() {
        location.reload();
    });

    $('#devmodeColSave').on('click', function() {
        var keys = Object.keys(pending);
        if (keys.length === 0) return;

        var btn = $(this).prop('disabled', true);
        var page = '<?= $ost_page_key ?>';
        var done = 0;
        var failed = false;

        keys.forEach(function(key) {
            $.post('<?= base_url("developer_mode/save_column") ?>', {
                page_key: page, column_key: key, is_hidden: pending[key]
            }, null, 'json').fail(function() {
                failed = true;
            }).always(function() {
                done++;
                if (done === keys.length) {
                    btn.prop('disabled', false);
                    if (failed) {
                        alert('<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
                    } else {
                        pending = {};
                        refreshActionBar();
                        $('#devmodeColBtn').dropdown('toggle');
                    }
                }
            });
        });
    });
});
</script>
<?php endif; ?>
