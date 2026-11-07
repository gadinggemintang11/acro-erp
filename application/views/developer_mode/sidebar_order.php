<?php
/**
 * Developer Mode — Atur Urutan Sidebar.
 *
 * Tampilan dirombak 2026-09-16 (user: "perbaiki tampilannya agar lebih modern
 * dan user friendly buat se smooth mungkin"):
 *   - urutan TERSIMPAN OTOMATIS (tidak ada lagi tombol Simpan per kartu),
 *   - baris pindah dengan animasi, ada garis penanda posisi saat diseret,
 *   - tombol panah naik/turun sebagai pengganti seret yang lebih pasti,
 *   - pindah antar segmen langsung memindahkan barisnya (tanpa muat ulang),
 *   - kotak pencarian untuk menemukan menu di antara puluhan kartu.
 * Endpoint & struktur data tidak berubah.
 */
$row_renderer = function ($args) use ($can_edit, $hidden_ready) {
    $key      = $args['key'];
    $label    = $args['label'];
    $hidden   = !empty($args['hidden']);
    $is_group = !empty($args['is_group']);
    $small    = !empty($args['small']);
    ob_start(); ?>
    <li class="sa-row<?= $hidden ? ' is-hidden' : '' ?><?= $is_group ? ' sa-row-group' : '' ?><?= $small ? ' sa-row-sm' : '' ?>"
        data-key="<?= htmlspecialchars($key) ?>" data-label="<?= htmlspecialchars(mb_strtolower($label)) ?>"
        <?= $can_edit ? 'draggable="true"' : '' ?>>
        <div class="sa-row-main">
            <?php if ($can_edit): ?>
            <span class="sa-grip" title="<?= lang_line('devmode_sa_drag_hint', 'Seret untuk memindahkan'); ?>"><i class="fas fa-grip-vertical"></i></span>
            <span class="sa-arrows">
                <button type="button" class="sa-arrow sa-up" title="<?= lang_line('devmode_sa_up', 'Naikkan'); ?>"><i class="fas fa-chevron-up"></i></button>
                <button type="button" class="sa-arrow sa-down" title="<?= lang_line('devmode_sa_down', 'Turunkan'); ?>"><i class="fas fa-chevron-down"></i></button>
            </span>
            <?php endif; ?>
            <span class="sa-label"><?php if ($is_group): ?><i class="fas fa-folder-open mr-1 text-warning"></i><?php endif; ?><span class="sa-label-text"><?= htmlspecialchars($label) ?></span></span>
            <span class="sa-tools">
                <?php if ($can_edit && !empty($args['lang_key'])): ?>
                <button type="button" class="sa-pencil" data-lang-key="<?= htmlspecialchars($args['lang_key']) ?>"
                        title="<?= lang_line('devmode_sa_rename_hint', 'Ganti tulisan menu ini'); ?>"><i class="fas fa-pen"></i></button>
                <?php endif; ?>
                <?= $args['tools'] ?? '' ?>
                <?php if ($can_edit && !empty($hidden_ready)): ?>
                <button type="button" class="sa-eye btn-toggle-hidden<?= $hidden ? ' is-off' : '' ?>"
                        data-scope="<?= htmlspecialchars($args['scope']) ?>" data-key="<?= htmlspecialchars($key) ?>"
                        <?= !empty($args['dropdown_key']) ? 'data-dropdown-key="' . htmlspecialchars($args['dropdown_key']) . '"' : '' ?>
                        data-hidden="<?= $hidden ? 1 : 0 ?>"
                        title="<?= lang_line('devmode_sa_hide_hint', 'Copot dari sidebar / pasang lagi'); ?>">
                    <i class="fas <?= $hidden ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                </button>
                <?php endif; ?>
            </span>
        </div>
        <?= $args['nested'] ?? '' ?>
    </li>
    <?php return ob_get_clean();
};
?>
<div class="container-fluid sidebar-arranger">

    <div class="d-sm-flex align-items-start justify-content-between mb-3">
        <div>
            <h3 class="text-gray-800 mb-1">
                <i class="fas fa-bars text-primary mr-2"></i>
                <?= lang_line('devmode_sidebar_order_page_title', 'Developer Mode — Atur Urutan Sidebar'); ?>
            </h3>
            <div class="text-muted small">
                <?= lang_line('devmode_sa_subtitle', 'Seret baris atau pakai tombol panah untuk mengubah urutan. Perubahan tersimpan otomatis.'); ?>
            </div>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="<?= base_url('developer_mode') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-language mr-1"></i> <?= lang_line('devmode_assets_go_text', 'Kelola Teks'); ?></a>
            <a href="<?= base_url('developer_mode/assets') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-image mr-1"></i> <?= lang_line('devmode_assets_go_images', 'Kelola Logo & Gambar'); ?></a>
            <a href="<?= base_url('developer_mode/columns') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_assets_go_columns', 'Kelola Kolom List'); ?></a>
            <a href="<?= base_url('developer_mode/pdf_layout') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-pdf mr-1"></i> <?= lang_line('devmode_assets_go_pdf', 'Atur PDF'); ?></a>
        </div>
    </div>

    <div class="sa-toolbar mb-4">
        <div class="sa-search">
            <i class="fas fa-search"></i>
            <input type="text" id="saSearch" class="form-control" autocomplete="off"
                   placeholder="<?= lang_line('devmode_sa_search_ph', 'Cari menu… (mis. Payment Request, Jurnal, Stock Opname)'); ?>">
            <button type="button" id="saSearchClear" class="sa-search-clear" title="<?= lang_line('common_clear', 'Bersihkan'); ?>">&times;</button>
        </div>
        <div class="sa-toolbar-right small text-muted">
            <span class="sa-chip"><i class="fas fa-bolt mr-1"></i><?= lang_line('devmode_sa_autosave', 'Tersimpan otomatis'); ?></span>
            <a href="#" id="saHelpToggle" class="ml-2"><i class="fas fa-question-circle mr-1"></i><?= lang_line('devmode_sa_help', 'Cara pakai'); ?></a>
        </div>
    </div>

    <div class="alert alert-info py-2 mb-4" id="saHelp" style="display:none;">
        <ul class="mb-0 pl-3 small">
            <li><?= lang_line('devmode_sa_help_1', '<strong>Menu Utama</strong> mengatur urutan menu besar di sidebar (Dashboard selalu paling atas). Kartu lainnya mengatur isi tiap dropdown.'); ?></li>
            <li><?= lang_line('devmode_sa_help_2', 'Baris berlabel folder adalah <strong>segmen</strong> (mis. KEUANGAN, LAPORAN:). Memindahkan segmen ikut membawa seluruh isinya.'); ?></li>
            <li><?= lang_line('devmode_sa_help_3', 'Untuk memindahkan satu menu ke segmen lain, pakai pilihan segmen di kanan barisnya — misalnya "Daftar Payment Request" dari LAPORAN: ke KEUANGAN.'); ?></li>
            <li><?= lang_line('devmode_sa_help_rename', 'Ikon pensil mengganti <strong>tulisan</strong> menu itu (untuk bahasa yang sedang aktif), termasuk nama segmen seperti KEUANGAN atau LAPORAN:.'); ?></li>
            <li><?= lang_line('devmode_sa_help_4', 'Ikon mata mencopot menu dari sidebar (hanya tampilan; izin Role Access tidak berubah).'); ?></li>
            <li><?= lang_line('devmode_sa_help_5', 'Kartu baru muncul setelah menu-nya pernah dibuka minimal sekali oleh siapa pun.'); ?></li>
        </ul>
    </div>

    <div class="row" id="saGrid">
    <?php if (!empty($sections)): ?>
    <div class="col-xl-4 col-lg-6 mb-4 sa-card-col">
        <div class="card sa-card">
            <div class="card-header sa-card-header">
                <button type="button" class="sa-collapse" title="<?= lang_line('devmode_sa_collapse', 'Sembunyikan / tampilkan'); ?>"><i class="fas fa-chevron-down"></i></button>
                <span class="sa-card-title"><i class="fas fa-layer-group mr-1 text-primary"></i><?= lang_line('devmode_sidebar_order_group_label', 'Menu Utama'); ?></span>
                <span class="sa-count"><?= count($sections) ?></span>
                <span class="sa-status" data-status-for="list_main"></span>
            </div>
            <div class="sa-card-body">
                <ul class="sa-list" id="list_main"
                    data-endpoint="<?= base_url('developer_mode/save_sidebar_order') ?>" data-scope-field="none">
                    <?php foreach ($sections as $s): ?>
                    <?= $row_renderer([
                        'key'    => $s->section_key,
                        'label'  => $s->section_label ?: $s->section_key,
                        'hidden' => ($s->is_hidden ?? 0),
                        'scope'  => 'section',
                        'lang_key' => $section_lang_keys[$s->section_key][0] ?? '',
                    ]); ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($dropdowns as $d): ?>
    <?php
        $dropdown_list_id = 'list_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $d->key);
        // Segmen (grup) yang tersedia di dropdown ini -> pilihan "pindah ke segmen".
        $segments = [];
        foreach ($d->items as $__it) {
            if (strpos($__it->item_key, 'grp_') === 0) $segments[$__it->item_key] = $__it->item_label ?: $__it->item_key;
        }
        $can_move = $can_edit && !empty($parent_ready) && !empty($segments);
        $segment_select = function ($item_key, $current, $from_scope) use ($segments, $d) {
            $html  = '<select class="sa-move" data-dropdown-key="' . htmlspecialchars($d->key, ENT_QUOTES) . '"';
            $html .= ' data-item-key="' . htmlspecialchars($item_key, ENT_QUOTES) . '"';
            $html .= ' data-from-scope="' . htmlspecialchars($from_scope, ENT_QUOTES) . '"';
            $html .= ' title="' . htmlspecialchars(lang_line('devmode_sa_move_hint', 'Pindahkan menu ini ke segmen lain'), ENT_QUOTES) . '">';
            $html .= '<option value=""' . ($current === '' ? ' selected' : '') . '>' . htmlspecialchars(lang_line('devmode_sa_no_segment', '— tanpa segmen —')) . '</option>';
            foreach ($segments as $gk => $glabel) {
                $html .= '<option value="' . htmlspecialchars($gk, ENT_QUOTES) . '"' . ($current === $gk ? ' selected' : '') . '>' . htmlspecialchars($glabel) . '</option>';
            }
            return $html . '</select>';
        };
        $total_rows = count($d->items);
        foreach ($d->items as $__it) { $total_rows += count($__it->children ?? []); }
    ?>
    <div class="col-xl-4 col-lg-6 mb-4 sa-card-col">
        <div class="card sa-card">
            <div class="card-header sa-card-header">
                <button type="button" class="sa-collapse" title="<?= lang_line('devmode_sa_collapse', 'Sembunyikan / tampilkan'); ?>"><i class="fas fa-chevron-down"></i></button>
                <span class="sa-card-title"><i class="fas fa-folder mr-1 text-primary"></i><?= htmlspecialchars($d->label) ?></span>
                <span class="sa-count"><?= (int) $total_rows ?></span>
                <span class="sa-status" data-status-for="<?= $dropdown_list_id ?>"></span>
            </div>
            <div class="sa-card-body">
                <ul class="sa-list" id="<?= $dropdown_list_id ?>"
                    data-endpoint="<?= base_url('developer_mode/save_item_order') ?>" data-scope-field="dropdown_key" data-scope-value="<?= htmlspecialchars($d->key) ?>">
                    <?php foreach ($d->items as $it): ?>
                    <?php
                        $is_group = strpos($it->item_key, 'grp_') === 0;
                        $nested = '';
                        if ($is_group) {
                            // Level-3: isi 1 segmen, scope tersimpan "dropdown_key::item_key".
                            $child_list_id = $dropdown_list_id . '__' . preg_replace('/[^a-zA-Z0-9_]/', '_', $it->item_key);
                            $child_scope   = $d->key . '::' . $it->item_key;
                            ob_start(); ?>
                            <div class="sa-nest">
                                <div class="sa-nest-head">
                                    <span><?= lang_line('devmode_sidebar_order_sub_label', 'Isi segmen ini:'); ?></span>
                                    <span class="sa-status" data-status-for="<?= $child_list_id ?>"></span>
                                </div>
                                <ul class="sa-list sa-list-sm" id="<?= $child_list_id ?>"
                                    data-endpoint="<?= base_url('developer_mode/save_item_order') ?>" data-scope-field="dropdown_key" data-scope-value="<?= htmlspecialchars($child_scope) ?>">
                                    <?php foreach ($it->children as $ch): ?>
                                    <?= $row_renderer([
                                        'key'          => $ch->item_key,
                                        'label'        => $ch->item_label ?: $ch->item_key,
                                        'hidden'       => ($ch->is_hidden ?? 0),
                                        'scope'        => 'item',
                                        'dropdown_key' => $child_scope,
                                        'lang_key'     => $ch->lang_key ?? '',
                                        'small'        => true,
                                        'tools'        => $can_move ? $segment_select($ch->item_key, $it->item_key, $child_scope) : '',
                                    ]); ?>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="sa-nest-empty" style="<?= empty($it->children) ? '' : 'display:none;' ?>">
                                    <?= lang_line('devmode_sa_segment_empty', 'Segmen kosong — segmen yang tidak berisi menu tidak muncul di sidebar.'); ?>
                                </div>
                            </div>
                            <?php $nested = ob_get_clean();
                        }
                    ?>
                    <?= $row_renderer([
                        'key'          => $it->item_key,
                        'label'        => $it->item_label ?: $it->item_key,
                        'hidden'       => ($it->is_hidden ?? 0),
                        'scope'        => 'item',
                        'dropdown_key' => $d->key,
                        'is_group'     => $is_group,
                        'lang_key'     => $it->lang_key ?? '',
                        'tools'        => (!$is_group && $can_move) ? $segment_select($it->item_key, '', $d->key) : '',
                        'nested'       => $nested,
                    ]); ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="col-12" id="saEmpty" style="display:none;">
        <div class="card sa-card"><div class="card-body text-center text-muted py-4">
            <i class="fas fa-search mr-1"></i> <?= lang_line('devmode_sa_no_match', 'Tidak ada menu yang cocok dengan pencarian.'); ?>
        </div></div>
    </div>

    <?php if (empty($sections) && empty($dropdowns)): ?>
        <div class="col-12"><div class="card sa-card"><div class="card-body text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('devmode_sidebar_order_no_data', 'Belum ada data urutan sidebar (migration belum jalan?).'); ?></div></div></div>
    <?php endif; ?>
    </div>
</div>

<style>
.sidebar-arranger .sa-card { border: 1px solid #e9ecf3; border-radius: .6rem; box-shadow: 0 1px 2px rgba(20,25,50,.05); overflow: hidden; }
.sidebar-arranger .sa-card-header { display: flex; align-items: center; gap: .5rem; background: #fbfcfe; border-bottom: 1px solid #eef0f6; padding: .6rem .75rem; }
.sidebar-arranger .sa-card-title { font-weight: 600; font-size: .82rem; color: #3f4a60; letter-spacing: .01em; }
.sidebar-arranger .sa-count { font-size: .68rem; color: #7a849b; background: #eef1f8; border-radius: 999px; padding: .05rem .45rem; }
.sidebar-arranger .sa-status { margin-left: auto; font-size: .7rem; color: #8a93a8; transition: opacity .2s ease; }
.sidebar-arranger .sa-status.is-ok { color: #1cc88a; }
.sidebar-arranger .sa-status.is-err { color: #e74a3b; }
.sidebar-arranger .sa-collapse { background: none; border: 0; color: #9aa3b8; padding: 0 .15rem; transition: transform .2s ease; }
.sidebar-arranger .sa-card.is-collapsed .sa-collapse { transform: rotate(-90deg); }
.sidebar-arranger .sa-card-body { max-height: 2000px; transition: max-height .28s ease, opacity .2s ease; }
.sidebar-arranger .sa-card.is-collapsed .sa-card-body { max-height: 0; opacity: 0; overflow: hidden; }

.sidebar-arranger .sa-list { list-style: none; margin: 0; padding: .35rem; }
.sidebar-arranger .sa-row { background: #fff; border: 1px solid #edeff5; border-radius: .45rem; margin-bottom: .3rem; transition: box-shadow .16s ease, border-color .16s ease, background .16s ease, transform .22s ease; }
.sidebar-arranger .sa-row:last-child { margin-bottom: 0; }
.sidebar-arranger .sa-row:hover { border-color: #d8def0; box-shadow: 0 2px 6px rgba(28,40,80,.07); }
.sidebar-arranger .sa-row-main { display: flex; align-items: center; gap: .4rem; padding: .4rem .5rem; }
.sidebar-arranger .sa-row-sm .sa-row-main { padding: .28rem .45rem; }
.sidebar-arranger .sa-row-group { background: #fcfbf7; border-color: #f0e6cf; }
.sidebar-arranger .sa-grip { color: #c2c8d8; cursor: grab; padding: 0 .1rem; }
.sidebar-arranger .sa-row:active .sa-grip { cursor: grabbing; }
.sidebar-arranger .sa-label { font-size: .8rem; color: #3f4a60; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sidebar-arranger .sa-row-sm .sa-label { font-size: .76rem; }
.sidebar-arranger .sa-tools { margin-left: auto; display: flex; align-items: center; gap: .3rem; }

.sidebar-arranger .sa-arrows { display: inline-flex; flex-direction: column; gap: 1px; }
.sidebar-arranger .sa-arrow { border: 1px solid #e3e7f2; background: #fff; color: #8892a8; border-radius: .25rem; line-height: 1; padding: .05rem .25rem; font-size: .6rem; transition: background .15s ease, color .15s ease, border-color .15s ease; }
.sidebar-arranger .sa-arrow:hover { background: #4e73df; border-color: #4e73df; color: #fff; }
.sidebar-arranger .sa-arrow:disabled { opacity: .35; cursor: default; background: #fff; color: #8892a8; border-color: #e3e7f2; }

.sidebar-arranger .sa-pencil { border: 1px solid #e3e7f2; background: #fff; color: #8892a8; border-radius: .25rem; padding: .1rem .35rem; font-size: .68rem; transition: background .15s ease, color .15s ease, border-color .15s ease; }
.sidebar-arranger .sa-pencil:hover { background: #4e73df; border-color: #4e73df; color: #fff; }
.sidebar-arranger .sa-rename { font-size: .78rem; border: 1px solid #4e73df; border-radius: .3rem; padding: .05rem .35rem; min-width: 8rem; max-width: 16rem; outline: none; box-shadow: 0 0 0 .18rem rgba(78,115,223,.12); }
.sidebar-arranger .sa-eye { border: 1px solid #e3e7f2; background: #fff; color: #e74a3b; border-radius: .25rem; padding: .1rem .35rem; font-size: .7rem; transition: background .15s ease, color .15s ease; }
.sidebar-arranger .sa-eye:hover { background: #fdf0ef; }
.sidebar-arranger .sa-eye.is-off { color: #9aa3b8; }

.sidebar-arranger .sa-move { font-size: .7rem; border: 1px solid #e3e7f2; border-radius: .3rem; background: #fff url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238892a8' stroke-width='3'%3e%3cpath d='M6 9l6 6 6-6'/%3e%3c/svg%3e") no-repeat right .3rem center/.55rem; -webkit-appearance: none; appearance: none; padding: .1rem 1.1rem .1rem .35rem; max-width: 9.5rem; color: #6b7590; }
.sidebar-arranger .sa-move:hover, .sidebar-arranger .sa-move:focus { border-color: #4e73df; color: #3f4a60; outline: none; }

.sidebar-arranger .sa-row.is-dragging { opacity: .45; border-style: dashed; }
.sidebar-arranger .sa-row.is-moved { animation: saPulse .6s ease; }
@keyframes saPulse { 0% { background:#e8f0ff; } 100% { background:#fff; } }

.sidebar-arranger .sa-nest { margin: .1rem .4rem .45rem 1.6rem; border-left: 2px solid #ecd9a8; padding-left: .5rem; }
.sidebar-arranger .sa-nest-head { display: flex; align-items: center; font-size: .68rem; color: #9aa3b8; text-transform: uppercase; letter-spacing: .03em; padding: .15rem 0; }
.sidebar-arranger .sa-nest-head .sa-status { margin-left: auto; text-transform: none; letter-spacing: 0; }
.sidebar-arranger .sa-nest-empty { font-size: .7rem; color: #b0b7c8; font-style: italic; padding: .2rem .35rem; }
.sidebar-arranger .sa-list-sm { padding: .2rem 0; }

.sidebar-arranger .sa-row.is-hidden .sa-label { opacity: .45; text-decoration: line-through; }

.sidebar-arranger .sa-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
.sidebar-arranger .sa-search { position: relative; flex: 1 1 320px; max-width: 420px; }
.sidebar-arranger .sa-search i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: #aab1c4; font-size: .8rem; }
.sidebar-arranger .sa-search input { padding-left: 2rem; border-radius: 999px; border-color: #e3e7f2; font-size: .82rem; height: calc(1.5em + .9rem); }
.sidebar-arranger .sa-search input:focus { border-color: #4e73df; box-shadow: 0 0 0 .18rem rgba(78,115,223,.12); }
.sidebar-arranger .sa-search-clear { position: absolute; right: .55rem; top: 50%; transform: translateY(-50%); border: 0; background: none; color: #aab1c4; font-size: 1.1rem; line-height: 1; display: none; }
.sidebar-arranger .sa-chip { background: #eef7f1; color: #1a9e6c; border-radius: 999px; padding: .12rem .55rem; font-size: .7rem; }

#saToast { position: fixed; right: 1rem; bottom: 1rem; z-index: 1080; display: flex; flex-direction: column; gap: .4rem; align-items: flex-end; }
#saToast .sa-toast { background: #2b3450; color: #fff; border-radius: .45rem; padding: .45rem .75rem; font-size: .78rem; box-shadow: 0 6px 18px rgba(20,25,50,.22); opacity: 0; transform: translateY(6px); transition: opacity .2s ease, transform .2s ease; }
#saToast .sa-toast.show { opacity: 1; transform: translateY(0); }
#saToast .sa-toast.is-err { background: #b23b30; }
</style>
<div id="saToast"></div>

<?php if ($can_edit): ?>
<script>
// ============================================================
// Atur Urutan Sidebar — interaksi.
// Semua perubahan urutan TERSIMPAN OTOMATIS (debounce), tidak ada tombol
// Simpan lagi. Endpoint & payload sama seperti sebelumnya.
// ============================================================
(function () {
    var I18N = {
        saving:  <?= json_encode(lang_line('devmode_saving', 'Menyimpan...')); ?>,
        saved:   <?= json_encode(lang_line('devmode_saved', 'Tersimpan.')); ?>,
        failed:  <?= json_encode(lang_line('devmode_save_err', 'Gagal menyimpan.')); ?>,
        moved:   <?= json_encode(lang_line('devmode_sa_moved', 'Menu dipindahkan ke segmen lain.')); ?>,
        retry:   <?= json_encode(lang_line('devmode_sa_retry', 'Gagal — coba lagi')); ?>,
        renamed: <?= json_encode(lang_line('devmode_sa_renamed', 'Tulisan menu diperbarui. Muat ulang halaman untuk melihatnya di sidebar.')); ?>
    };
    var URL_ITEM_PARENT = <?= json_encode(base_url('developer_mode/save_item_parent')); ?>;
    var URL_TOGGLE      = <?= json_encode(base_url('developer_mode/toggle_sidebar_hidden')); ?>;

    function rowsOf(list) {
        return Array.prototype.filter.call(list.children, function (n) { return n.tagName === 'LI'; });
    }
    function toast(msg, isErr) {
        var box = document.getElementById('saToast');
        var el = document.createElement('div');
        el.className = 'sa-toast' + (isErr ? ' is-err' : '');
        el.textContent = msg;
        box.appendChild(el);
        requestAnimationFrame(function () { el.classList.add('show'); });
        setTimeout(function () {
            el.classList.remove('show');
            setTimeout(function () { el.remove(); }, 250);
        }, isErr ? 4000 : 1800);
    }
    function status(list) { return document.querySelector('[data-status-for="' + list.id + '"]'); }

    // Animasi halus saat baris berpindah posisi (teknik FLIP): catat posisi
    // sebelum & sesudah perubahan DOM, lalu animasikan selisihnya.
    function animateReorder(list, mutate) {
        var rows = rowsOf(list);
        var before = rows.map(function (r) { return r.getBoundingClientRect().top; });
        mutate();
        rows.forEach(function (r, i) {
            var delta = before[i] - r.getBoundingClientRect().top;
            if (!delta) return;
            r.style.transition = 'none';
            r.style.transform = 'translateY(' + delta + 'px)';
            requestAnimationFrame(function () {
                r.style.transition = 'transform .22s ease';
                r.style.transform = '';
            });
        });
    }

    var timers = {};
    function scheduleSave(list) {
        var st = status(list);
        if (st) { st.className = 'sa-status'; st.textContent = I18N.saving; }
        clearTimeout(timers[list.id]);
        timers[list.id] = setTimeout(function () { saveOrder(list); }, 450);
    }
    function saveOrder(list) {
        var keys = rowsOf(list).map(function (li) { return li.getAttribute('data-key'); });
        var payload = { order: keys };
        var scopeField = list.getAttribute('data-scope-field');
        if (scopeField && scopeField !== 'none') payload[scopeField] = list.getAttribute('data-scope-value');
        var st = status(list);

        $.post(list.getAttribute('data-endpoint'), payload, function (res) {
            if (st) {
                st.className = 'sa-status ' + (res && res.success ? 'is-ok' : 'is-err');
                st.textContent = res && res.success ? I18N.saved : (res.message || I18N.failed);
                if (res && res.success) setTimeout(function () { if (st.textContent === I18N.saved) st.textContent = ''; }, 2200);
            }
            if (!res || !res.success) toast((res && res.message) || I18N.failed, true);
        }, 'json').fail(function () {
            if (st) { st.className = 'sa-status is-err'; st.textContent = I18N.retry; }
            toast(I18N.failed, true);
        });
    }

    function refreshArrows(list) {
        var rows = rowsOf(list);
        rows.forEach(function (li, i) {
            var up = li.querySelector(':scope > .sa-row-main .sa-up');
            var dn = li.querySelector(':scope > .sa-row-main .sa-down');
            if (up) up.disabled = (i === 0);
            if (dn) dn.disabled = (i === rows.length - 1);
        });
        // Segmen yang isinya habis: beri keterangan, jangan biarkan kosong tanpa penjelasan.
        var nest = list.closest('.sa-nest');
        if (nest) {
            var empty = nest.querySelector('.sa-nest-empty');
            if (empty) empty.style.display = rows.length ? 'none' : '';
        }
    }

    document.querySelectorAll('.sa-list').forEach(function (list) {
        var dragging = null;

        list.addEventListener('dragstart', function (e) {
            dragging = e.target.closest('li');
            if (!dragging) return;
            dragging.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', dragging.getAttribute('data-key')); } catch (err) {}
            e.stopPropagation();
        });
        list.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (!dragging) return;                 // yang diseret milik list lain
            var target = e.target.closest('li');
            // Naik sampai ketemu <li> anak LANGSUNG list ini, supaya menjatuhkan
            // di atas isi sebuah segmen tetap memindahkan segmennya.
            while (target && target.parentElement !== list) {
                target = target.parentElement ? target.parentElement.closest('li') : null;
            }
            if (!target || target === dragging) return;
            var rect = target.getBoundingClientRect();
            var before = (e.clientY - rect.top) < (rect.height / 2);
            list.insertBefore(dragging, before ? target : target.nextSibling);
        });
        list.addEventListener('dragend', function (e) {
            e.stopPropagation();
            if (!dragging) return;
            dragging.classList.remove('is-dragging');
            dragging.classList.add('is-moved');
            setTimeout(function () { dragging && dragging.classList.remove('is-moved'); }, 600);
            dragging = null;
            refreshArrows(list);
            scheduleSave(list);
        });

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('.sa-arrow');
            if (!btn || btn.closest('ul') !== list) return;
            e.preventDefault();
            var li = btn.closest('li');
            var up = btn.classList.contains('sa-up');
            var sib = up ? li.previousElementSibling : li.nextElementSibling;
            if (!sib) return;
            animateReorder(list, function () {
                if (up) list.insertBefore(li, sib); else list.insertBefore(sib, li);
            });
            li.classList.add('is-moved');
            setTimeout(function () { li.classList.remove('is-moved'); }, 600);
            refreshArrows(list);
            scheduleSave(list);
        });

        refreshArrows(list);
    });

    // ---- Pindah menu ke segmen lain: barisnya langsung dipindahkan, tanpa muat ulang ----
    document.addEventListener('change', function (e) {
        var sel = e.target.closest ? e.target.closest('.sa-move') : null;
        if (!sel) return;

        var li          = sel.closest('li');
        var dropdownKey = sel.getAttribute('data-dropdown-key');
        var fromScope   = sel.getAttribute('data-from-scope');
        var parentKey   = sel.value;
        var toScope     = parentKey === '' ? dropdownKey : dropdownKey + '::' + parentKey;
        var sourceList  = li.parentElement;

        sel.disabled = true;
        li.style.opacity = '.5';
        $.post(URL_ITEM_PARENT, {
            dropdown_key: dropdownKey, item_key: sel.getAttribute('data-item-key'),
            from_scope: fromScope, parent_key: parentKey
        }, function (res) {
            sel.disabled = false;
            li.style.opacity = '';
            if (!res || !res.success) { toast((res && res.message) || I18N.failed, true); return; }

            // Cari list tujuan: segmen (list nested di dalam baris segmen itu)
            // atau list utama dropdown ini kalau "tanpa segmen".
            var targetList = null;
            if (parentKey === '') {
                targetList = document.getElementById('list_' + dropdownKey.replace(/[^a-zA-Z0-9_]/g, '_'));
            } else {
                var groupRow = document.querySelector('.sa-list [data-key="' + parentKey + '"]');
                targetList = groupRow ? groupRow.querySelector('.sa-list') : null;
            }
            if (targetList && targetList !== sourceList) {
                targetList.appendChild(li);
                li.classList.toggle('sa-row-sm', targetList.classList.contains('sa-list-sm'));
                sel.setAttribute('data-from-scope', toScope);
                var eye = li.querySelector('.btn-toggle-hidden');
                if (eye) eye.setAttribute('data-dropdown-key', toScope);
                refreshArrows(sourceList);
                refreshArrows(targetList);
            }
            li.classList.add('is-moved');
            setTimeout(function () { li.classList.remove('is-moved'); }, 600);
            toast(I18N.moved);
        }, 'json').fail(function () {
            sel.disabled = false; li.style.opacity = '';
            toast(I18N.failed, true);
        });
    });

    // ---- Ganti tulisan menu langsung di sini (user 2026-09-16) ----
    // Menyimpan override lang_line lewat endpoint Developer Mode yang sudah ada,
    // untuk BAHASA YANG SEDANG AKTIF. Sidebar ikut berubah setelah dimuat ulang.
    var ACTIVE_LANG = <?= json_encode($active_language ?? 'indonesian'); ?>;
    var URL_SAVE_TEXT = <?= json_encode(base_url('developer_mode/save')); ?>;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.sa-pencil') : null;
        if (!btn) return;
        e.preventDefault(); e.stopPropagation();

        var li    = btn.closest('li');
        var holder = li.querySelector('.sa-label-text');
        if (!holder || holder.querySelector('input')) return;

        var original = holder.textContent;
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'sa-rename';
        input.value = original;
        holder.textContent = '';
        holder.appendChild(input);
        li.setAttribute('draggable', 'false');
        input.focus();
        input.select();

        var done = false;
        function finish(save) {
            if (done) return; done = true;
            var value = input.value.trim();
            li.setAttribute('draggable', 'true');
            if (!save || value === '' || value === original) {
                holder.textContent = original;
                return;
            }
            holder.textContent = value;
            $.post(URL_SAVE_TEXT, { lang_key: btn.getAttribute('data-lang-key'), language: ACTIVE_LANG, value: value },
                function (res) {
                    if (res && res.success) { toast(I18N.renamed); return; }
                    holder.textContent = original;
                    toast((res && res.message) || I18N.failed, true);
                }, 'json').fail(function () {
                    holder.textContent = original;
                    toast(I18N.failed, true);
                });
        }
        input.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); finish(true); }
            if (ev.key === 'Escape') { ev.preventDefault(); finish(false); }
        });
        input.addEventListener('blur', function () { finish(true); });
    });

    // ---- Copot & pasang (hanya tampilan sidebar; izin Role Access tidak berubah) ----
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.btn-toggle-hidden') : null;
        if (!btn) return;
        e.preventDefault(); e.stopPropagation();

        var li = btn.closest('li');
        var next = btn.getAttribute('data-hidden') === '1' ? 0 : 1;
        var body = new URLSearchParams();
        body.append('scope', btn.getAttribute('data-scope'));
        body.append('key', btn.getAttribute('data-key'));
        body.append('hidden', next);
        if (btn.getAttribute('data-dropdown-key')) body.append('dropdown_key', btn.getAttribute('data-dropdown-key'));

        btn.disabled = true;
        fetch(URL_TOGGLE, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                btn.disabled = false;
                if (!res.success) { toast(res.message || I18N.failed, true); return; }
                btn.setAttribute('data-hidden', next ? '1' : '0');
                btn.classList.toggle('is-off', !!next);
                btn.querySelector('i').className = next ? 'fas fa-eye-slash' : 'fas fa-eye';
                if (li) li.classList.toggle('is-hidden', !!next);
            })
            .catch(function () { btn.disabled = false; toast(I18N.failed, true); });
    });

    // ---- Lipat / buka kartu ----
    document.querySelectorAll('.sa-card-header').forEach(function (head) {
        head.addEventListener('click', function (e) {
            if (e.target.closest('.sa-status')) return;
            head.closest('.sa-card').classList.toggle('is-collapsed');
        });
    });

    // ---- Pencarian ----
    var search = document.getElementById('saSearch');
    var clear  = document.getElementById('saSearchClear');
    function applyFilter() {
        var q = (search.value || '').trim().toLowerCase();
        clear.style.display = q ? 'block' : 'none';
        var anyCard = false;
        document.querySelectorAll('.sa-card-col').forEach(function (col) {
            var hit = false;
            // Dari baris terdalam ke luar, supaya baris segmen ikut tampil
            // kalau salah satu isinya cocok (anaknya sudah dinilai duluan).
            var rows = Array.prototype.slice.call(col.querySelectorAll('li.sa-row')).reverse();
            rows.forEach(function (li) {
                var match = !q || (li.getAttribute('data-label') || '').indexOf(q) !== -1;
                if (!match) {
                    var kids = li.querySelectorAll('li.sa-row');
                    for (var i = 0; i < kids.length; i++) {
                        if (kids[i].style.display !== 'none') { match = true; break; }
                    }
                }
                li.style.display = match ? '' : 'none';
                if (match) hit = true;
            });
            col.style.display = (!q || hit) ? '' : 'none';
            if (!q || hit) anyCard = true;
        });
        document.getElementById('saEmpty').style.display = anyCard ? 'none' : '';
    }
    if (search) {
        search.addEventListener('input', applyFilter);
        clear.addEventListener('click', function () { search.value = ''; applyFilter(); search.focus(); });
    }

    // ---- Bantuan ----
    var help = document.getElementById('saHelp');
    var helpToggle = document.getElementById('saHelpToggle');
    if (helpToggle) helpToggle.addEventListener('click', function (e) {
        e.preventDefault();
        help.style.display = help.style.display === 'none' ? '' : 'none';
    });
})();
</script>
<?php endif; ?>
