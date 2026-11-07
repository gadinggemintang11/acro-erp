<!-- Modal Buat Work Order Baru -->
<div class="modal fade" id="createWoModal" tabindex="-1" role="dialog" aria-labelledby="createWoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createWoModalLabel"><?= lang_line('work_order_modal_create_title', 'Buat Work Order Baru'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('work_order/create'); ?>" method="POST" id="createWoForm">
                <div class="modal-body">

                    <?php /*
                        IPO dipilih lewat modal daftar, bukan diketik. Sebelumnya
                        select2 hanya memunculkan hasil setelah user mengetik nomor
                        IPO — user tidak tahu mana yang masih Open tanpa menghafal
                        nomornya (user 2026-09-30: "jadi user bisa tau tanpa susah
                        cuma ngetik nomor ipo nya, jadi kaya modal tarik PR di PO").
                    */ ?>
                    <div class="form-group">
                        <label><?= lang_line('work_order_modal_select_ipo', 'Pilih Sales Order Number'); ?></label>
                        <input type="hidden" id="sales_contract_id" name="sales_contract_id" value="">
                        <div class="input-group">
                            <input type="text" id="ipo_display" class="form-control" readonly
                                   placeholder="<?= lang_line('work_order_ipo_none_selected', 'Belum ada Sales Order dipilih'); ?>">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="btnPickIpo" data-toggle="modal" data-target="#pickIpoModal">
                                    <i class="fas fa-list mr-1"></i> <?= lang_line('work_order_btn_pick_ipo', 'Pilih dari Daftar'); ?>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted" id="ipo_hint"></small>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('work_order_modal_fg_auto', 'Finished Good (Otomatis)'); ?></label>
                        <input type="text" id="fg_name" class="form-control" readonly>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="planned_qty"><?= lang_line('work_order_modal_qty_plan', 'Qty Rencana Produksi'); ?></label>
                            <input type="number" step="0.01" class="form-control" id="planned_qty" name="planned_qty" required>
                            <small class="form-text text-muted" id="sc_qty_info"><?= lang_line('work_order_modal_qty_fg_info', 'Jumlah FG yang akan diproduksi.'); ?></small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="planned_start_date"><?= lang_line('work_order_modal_start_date', 'Tgl Mulai Rencana'); ?></label>
                            <input type="date" class="form-control" id="planned_start_date" name="planned_start_date" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="planned_end_date"><?= lang_line('work_order_modal_end_date', 'Tgl Selesai Rencana'); ?></label>
                            <input type="date" class="form-control" id="planned_end_date" name="planned_end_date">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks"><?= lang_line('work_order_modal_notes', 'Catatan'); ?></label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang_line('work_order_modal_save_next', 'Simpan & Lanjut ke Material'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php /*
    Modal daftar IPO — polanya mengikuti "Tarik dari PR" di Purchase Order:
    pencarian + filter tanggal, tabel berkepala lengket, satu tombol pilih per
    baris. Bedanya di sini hanya boleh SATU IPO, jadi tidak ada checkbox.
*/ ?>
<div class="modal fade" id="pickIpoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list mr-2"></i><?= lang_line('work_order_ipo_modal_title', 'Daftar Sales Order yang Masih Open'); ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('work_order_ipo_modal_hint', 'Yang tampil hanya Sales Order berstatus Open. Kolom "Sudah di WO" dan "Sisa" menunjukkan berapa qty yang sudah tertutup Work Order sebelumnya — Sales Order yang sisanya 0 tetap bisa dipilih.'); ?>
                </p>
                <div class="form-row mb-2">
                    <div class="col-md-6 mb-1">
                        <input type="text" id="ipoSearch" class="form-control form-control-sm"
                               placeholder="<?= lang_line('work_order_ipo_search_ph', 'Cari no. Sales Order / finished good / customer...'); ?>">
                    </div>
                    <div class="col-md-2 mb-1">
                        <input type="date" id="ipoFrom" class="form-control form-control-sm" title="<?= lang_line('work_order_ipo_date_from', 'Tanggal Sales Order dari'); ?>">
                    </div>
                    <div class="col-md-2 mb-1">
                        <input type="date" id="ipoTo" class="form-control form-control-sm" title="<?= lang_line('work_order_ipo_date_to', 'Tanggal Sales Order sampai'); ?>">
                    </div>
                    <div class="col-md-2 mb-1">
                        <button type="button" id="ipoRefresh" class="btn btn-sm btn-outline-secondary btn-block">
                            <i class="fas fa-sync-alt mr-1"></i><?= lang_line('btn_refresh', 'Muat ulang'); ?>
                        </button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:52vh; overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light small" style="position:sticky; top:0; z-index:2;">
                            <tr class="text-center">
                                <th style="width:130px;"><?= lang_line('work_order_ipo_th_number', 'No. Sales Order'); ?></th>
                                <th style="width:92px;"><?= lang_line('work_order_ipo_th_date', 'Tanggal'); ?></th>
                                <th><?= lang_line('work_order_ipo_th_customer', 'Customer'); ?></th>
                                <th><?= lang_line('work_order_ipo_th_fg', 'Finished Good'); ?></th>
                                <th style="width:86px;"><?= lang_line('work_order_ipo_th_qty', 'Qty Sales Order'); ?></th>
                                <th style="width:96px;"><?= lang_line('work_order_ipo_th_on_wo', 'Sudah di WO'); ?></th>
                                <th style="width:86px;"><?= lang_line('work_order_ipo_th_left', 'Sisa'); ?></th>
                                <th style="width:70px;">BOM</th>
                                <th style="width:80px;"><?= lang_line('common_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ipoBody" class="small">
                            <tr><td colspan="9" class="text-center text-muted py-4"><?= lang_line('common_loading', 'Memuat…'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    var I18N_WO = {
        loading:    <?= json_encode(lang_line('common_loading', 'Memuat…')); ?>,
        empty:      <?= json_encode(lang_line('work_order_ipo_empty', 'Tidak ada Sales Order berstatus Open yang cocok.')); ?>,
        failed:     <?= json_encode(lang_line('work_order_ipo_failed', 'Gagal memuat daftar Sales Order.')); ?>,
        pick:       <?= json_encode(lang_line('work_order_ipo_btn_choose', 'Pilih')); ?>,
        no_bom:     <?= json_encode(lang_line('work_order_ipo_no_bom', 'Belum ada BOM Active untuk finished good ini — tarik material dari BOM tidak akan menghasilkan apa-apa.')); ?>,
        full:       <?= json_encode(lang_line('work_order_ipo_full', 'Qty Sales Order sudah tertutup Work Order sebelumnya.')); ?>,
        need_ipo:   <?= json_encode(lang_line('work_order_ipo_required', 'Pilih Sales Order terlebih dahulu lewat tombol "Pilih dari Daftar".')); ?>,
        qty_info:   <?= json_encode(lang_line('work_order_modal_qty_fg_info', 'Jumlah FG yang akan diproduksi.')); ?>,
        qty_from:   <?= json_encode(lang_line('work_order_modal_qty_from_ipo', 'Qty dari Sales Order')); ?>,
        wo_count:   <?= json_encode(lang_line('work_order_ipo_wo_count', 'WO dibuat')); ?>
    };

    function fmt(n) {
        n = parseFloat(n || 0);
        return (Math.round(n * 100) / 100).toLocaleString('id-ID');
    }
    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    // ---------- Muat daftar Sales Order ----------
    function loadIpoList() {
        var $body = $('#ipoBody');
        $body.html('<tr><td colspan="9" class="text-center text-muted py-4">' + I18N_WO.loading + '</td></tr>');

        $.getJSON("<?= base_url('work_order/ajax_open_ipo_list'); ?>", {
            search:    $('#ipoSearch').val() || '',
            date_from: $('#ipoFrom').val() || '',
            date_to:   $('#ipoTo').val() || ''
        }).done(function (res) {
            if (!res || !res.success) {
                $body.html('<tr><td colspan="9" class="text-center text-danger py-4">' +
                    esc((res && res.message) || I18N_WO.failed) + '</td></tr>');
                return;
            }
            if (!res.rows.length) {
                $body.html('<tr><td colspan="9" class="text-center text-muted py-4">' + I18N_WO.empty + '</td></tr>');
                return;
            }

            var html = '';
            res.rows.forEach(function (r) {
                var penuh   = r.qty_left <= 0.0001;
                var bomCell = r.has_bom
                    ? '<span class="badge badge-success">OK</span>'
                    : '<span class="badge badge-warning text-dark" title="' + esc(I18N_WO.no_bom) + '">-</span>';
                var sisaCls = penuh ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';
                var woCell  = r.wo_count > 0
                    ? fmt(r.qty_on_wo) + ' <small class="text-muted">(' + r.wo_count + ' WO)</small>'
                    : '<span class="text-muted">-</span>';

                html += '<tr' + (penuh ? ' class="table-warning"' : '') + '>'
                     +  '<td><strong>' + esc(r.sc_number) + '</strong>'
                     +      (penuh ? '<br><small class="text-danger">' + esc(I18N_WO.full) + '</small>' : '') + '</td>'
                     +  '<td class="text-center">' + esc(r.contract_date || '-') + '</td>'
                     +  '<td>' + (esc(r.customer_name) || '<span class="text-muted">-</span>') + '</td>'
                     +  '<td>' + esc(r.fg_name || '-') + ' <small class="text-muted">(' + esc(r.fg_code || '') + ')</small></td>'
                     +  '<td class="text-right">' + fmt(r.po_qty) + '</td>'
                     +  '<td class="text-right">' + woCell + '</td>'
                     +  '<td class="text-right ' + sisaCls + '">' + fmt(r.qty_left) + '</td>'
                     +  '<td class="text-center">' + bomCell + '</td>'
                     +  '<td class="text-center">'
                     +      '<button type="button" class="btn btn-primary btn-xs btn-pick-ipo"'
                     +          ' data-id="' + r.id + '"'
                     +          ' data-sc="' + esc(r.sc_number) + '"'
                     +          ' data-fg="' + esc((r.fg_name || '') + ' (' + (r.fg_code || '') + ')') + '"'
                     +          ' data-qty="' + r.po_qty + '"'
                     +          ' data-left="' + r.qty_left + '"'
                     +          ' data-bom="' + (r.has_bom ? 1 : 0) + '">'
                     +          '<i class="fas fa-check mr-1"></i>' + I18N_WO.pick + '</button>'
                     +  '</td>'
                     +  '</tr>';
            });
            $body.html(html);
        }).fail(function () {
            $body.html('<tr><td colspan="9" class="text-center text-danger py-4">' + I18N_WO.failed + '</td></tr>');
        });
    }

    $('#pickIpoModal').on('shown.bs.modal', loadIpoList);
    $('#ipoRefresh').on('click', loadIpoList);
    $('#ipoFrom, #ipoTo').on('change', loadIpoList);

    // Pencarian ditunda sejenak supaya tidak menembak server tiap ketikan.
    var ipoTimer = null;
    $('#ipoSearch').on('input', function () {
        clearTimeout(ipoTimer);
        ipoTimer = setTimeout(loadIpoList, 350);
    });

    // ---------- Pilih satu Sales Order ----------
    $('#ipoBody').on('click', '.btn-pick-ipo', function () {
        var d = $(this).data();

        $('#sales_contract_id').val(d.id);
        $('#ipo_display').val(d.sc);
        $('#fg_name').val(d.fg);

        // Default qty = SISA yang belum tertutup WO, bukan qty Sales Order penuh —
        // kalau sudah pernah dibuat WO, mengisi qty penuh hampir pasti keliru.
        // Sisa 0 (sudah tertutup semua) dikembalikan ke qty Sales Order supaya
        // field-nya tidak kosong; user tinggal mengubahnya.
        var qty = parseFloat(d.left) > 0 ? d.left : d.qty;
        $('#planned_qty').val(qty);
        $('#sc_qty_info').text(I18N_WO.qty_from + ': ' + fmt(d.qty)
            + (parseFloat(d.left) !== parseFloat(d.qty) ? ' — sisa belum di-WO: ' + fmt(d.left) : ''));

        var $hint = $('#ipo_hint');
        if (parseInt(d.bom, 10) !== 1) {
            $hint.html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>' + esc(I18N_WO.no_bom) + '</span>');
        } else {
            $hint.html('');
        }

        $('#pickIpoModal').modal('hide');
    });

    // ---------- Modal bertumpuk (Bootstrap 4) ----------
    // Modal daftar Sales Order dibuka DARI dalam modal Buat WO. Bootstrap 4 tidak
    // menangani ini sendiri: backdrop modal kedua tampil di atas isinya, dan
    // saat modal kedua ditutup body kehilangan kelas `modal-open` sehingga
    // modal pertama tidak bisa di-scroll lagi. Dua penyesuaian di bawah ini
    // yang memperbaikinya.
    // Modal luar (createWoModal) memakai z-index bawaan Bootstrap: modal 1050,
    // backdrop 1040. Modal daftar Sales Order harus berada DI ATAS keduanya, dan
    // backdrop-nya persis di antara keduanya supaya modal luar tampak redup
    // selama daftar terbuka.
    var Z_INNER_MODAL    = 1062;
    var Z_INNER_BACKDROP = 1061;

    $('#pickIpoModal').on('show.bs.modal', function () {
        $(this).css('z-index', Z_INNER_MODAL);
        setTimeout(function () {
            // Bootstrap MENAMBAHKAN backdrop baru di akhir <body>, jadi urutan
            // DOM-nya [backdrop modal luar, backdrop modal ini]. Harus .last().
            // Sempat memakai .first() -- itu menaikkan backdrop modal LUAR ke
            // atas modal Buat WO, jadi seluruh halaman tampak gelap dan tidak
            // pulih setelah daftar ditutup.
            $('.modal-backdrop').last().css('z-index', Z_INNER_BACKDROP).addClass('ipo-backdrop');
        }, 0);
    });

    $('#pickIpoModal').on('hidden.bs.modal', function () {
        // Backdrop modal ini dihapus sendiri oleh Bootstrap; baris ini hanya
        // jaring pengaman kalau ada yang tertinggal (mis. modal ditutup saat
        // animasi sebelumnya belum selesai).
        $('.modal-backdrop.ipo-backdrop').remove();
        // Menutup modal kedua membuat Bootstrap melepas `modal-open` dari body,
        // padahal modal pertama masih terbuka -- tanpa ini isinya tidak bisa
        // di-scroll lagi.
        if ($('.modal:visible').length) $('body').addClass('modal-open');
    });

    // ---------- Validasi & reset ----------
    $('#createWoForm').on('submit', function (e) {
        if (!$('#sales_contract_id').val()) {
            e.preventDefault();
            if (window.Swal) { Swal.fire('', I18N_WO.need_ipo, 'warning'); } else { alert(I18N_WO.need_ipo); }
            $('#btnPickIpo').focus();
        }
    });

    $('#createWoModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#sales_contract_id').val('');
        $('#ipo_display').val('');
        $('#fg_name').val('');
        $('#ipo_hint').html('');
        $('#sc_qty_info').text(I18N_WO.qty_info);
    });

    // Dibuka dari shortcut Dashboard (work_order?open=create) -- 2026-11-05.
    // Dulu shortcut mengarah ke work_order/create (penerima POST form), jadi
    // klik biasa langsung memunculkan "Gagal membuat WO".
    if (new URLSearchParams(window.location.search).get('open') === 'create') {
        $('#createWoModal').modal('show');
    }
});
</script>
