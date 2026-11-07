<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Dashboard ERP Acropoly — dirombak total 2026-11-06.
 * User: "berkreasi sekreatif mungkin ... tampilan modern & menarik; keuangan
 * gausa ditampilkan di dashboard; grafik sales order dibatasi 5 terbaru".
 *
 * Data dari Dashboard::index(): $kpi, $pipeline, $so_progress,
 * $outstanding_tasks, $outstanding_total, $recent_docs, $quick_actions,
 * $low_stock_alerts, $low_stock_total, $inventory_fg_qty,
 * $inventory_wip_qty, $can_trace_so, $can_stock_alert.
 * Gaya di-scope ke .db2 & pakai variabel tema --brand-* (header.php).
 */
$lang_code = function_exists('get_current_language') ? get_current_language() : 'indonesian';
$hour      = (int) date('G');
$slot      = $hour < 11 ? 0 : ($hour < 15 ? 1 : ($hour < 18 ? 2 : 3));
$greetings = [
    'indonesian' => ['Selamat pagi', 'Selamat siang', 'Selamat sore', 'Selamat malam'],
    'english'    => ['Good morning', 'Good afternoon', 'Good afternoon', 'Good evening'],
    'chinese'    => ['早上好', '中午好', '下午好', '晚上好'],
];
$greet = ($greetings[$lang_code] ?? $greetings['indonesian'])[$slot];
if ($lang_code === 'english') {
    $today_label = date('l, j F Y');
} elseif ($lang_code === 'chinese') {
    $today_label = date('Y年n月j日') . ' · 星期' . ['日', '一', '二', '三', '四', '五', '六'][(int) date('w')];
} else {
    $days   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $today_label = $days[(int) date('w')] . ', ' . date('j') . ' ' . $months[(int) date('n')] . ' ' . date('Y');
}
$first_name = explode(' ', trim((string) ($username ?: 'User')))[0];
$nf = function ($n) { return number_format((float) $n, 0, ',', '.'); };
$e  = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$status_tone = function ($st) {
    $st = (string) $st;
    if (in_array($st, ['Approved', 'Open', 'Confirmed', 'Completed', 'Finished', 'Released', 'Received', 'Closed', 'Paid'], true)) return 'ok';
    if (in_array($st, ['Rejected', 'Cancelled'], true)) return 'bad';
    if (in_array($st, ['Submitted', 'Pending Endorsement', 'Pending Approval'], true)) return 'wait';
    if (in_array($st, ['Approved Head Dept', 'Endorsed'], true)) return 'mid';
    if ($st === 'Draft') return 'draft';
    return 'run';
};
$task_tone = ['warning' => 'amber', 'info' => 'sky', 'danger' => 'rose', 'primary' => 'brand', 'success' => 'green', 'secondary' => 'slate', 'dark' => 'slate'];
$link = function ($menu, $url) { return check_access($menu, 'view') ? base_url($url) : null; };

$kpi_cards = [
    ['tone' => 'blue',   'icon' => 'fa-file-signature', 'label' => lang_line('db2_kpi_so', 'Sales Order Aktif'),
     'value' => $kpi['so_open'],   'sub' => sprintf(lang_line('db2_kpi_waiting', '%s menunggu approval'), $nf($kpi['so_waiting'])), 'href' => $link('sales_contract', 'sales_contract')],
    ['tone' => 'orange', 'icon' => 'fa-cogs',           'label' => lang_line('db2_kpi_wo', 'Work Order Aktif'),
     'value' => $kpi['wo_active'], 'sub' => sprintf(lang_line('db2_kpi_running', '%s sedang berjalan'), $nf($kpi['wo_running'])), 'href' => $link('work_order', 'work_order')],
    ['tone' => 'teal',   'icon' => 'fa-hand-holding',   'label' => lang_line('db2_kpi_pb', 'Permintaan Barang'),
     'value' => $kpi['pb_month'],  'sub' => sprintf(lang_line('db2_kpi_waiting', '%s menunggu approval'), $nf($kpi['pb_waiting'])), 'href' => $link('consumable_request', 'consumable_request'), 'note' => lang_line('db2_this_month', 'bulan ini')],
    ['tone' => 'purple', 'icon' => 'fa-shopping-cart',  'label' => lang_line('db2_kpi_po', 'Purchase Order'),
     'value' => $kpi['po_month'],  'sub' => sprintf(lang_line('db2_kpi_waiting', '%s menunggu approval'), $nf($kpi['po_waiting'])), 'href' => $link('po_monitoring', 'po_monitoring'), 'note' => lang_line('db2_this_month', 'bulan ini')],
    ['tone' => 'green',  'icon' => 'fa-truck',          'label' => lang_line('db2_kpi_do', 'Pengiriman'),
     'value' => $kpi['do_today'],  'sub' => sprintf(lang_line('db2_kpi_draft', '%s draft belum dikonfirmasi'), $nf($kpi['do_draft'])), 'href' => $link('fg_delivery', 'fg_delivery'), 'note' => lang_line('db2_today', 'hari ini')],
    ['tone' => 'slate',  'icon' => 'fa-boxes',          'label' => lang_line('db2_kpi_fg', 'Stok Barang Jadi'),
     'value' => $inventory_fg_qty, 'sub' => sprintf(lang_line('db2_kpi_wip', 'WIP %s unit'), $nf($inventory_wip_qty)), 'href' => $link('fg_stock', 'fg_stock'), 'note' => lang_line('db2_unit', 'unit')],
];
$attention = (int) ($low_stock_total ?? 0) + (int) ($kpi['do_draft'] ?? 0);
?>
<div class="container-fluid db2">

    <!-- ===================== HERO ===================== -->
    <section class="db2-hero db2-reveal">
        <img class="db2-hero-mark" src="<?= base_url(site_asset('logo_login', 'assets/img/logo_mini.png')); ?>" alt="">
        <div class="db2-hero-orb db2-hero-orb--a"></div>
        <div class="db2-hero-orb db2-hero-orb--b"></div>
        <div class="db2-hero-main">
            <span class="db2-eyebrow"><i class="far fa-calendar-alt"></i> <?= $e($today_label); ?></span>
            <h1 class="db2-hero-title"><?= $e($greet); ?>, <span><?= $e($first_name); ?></span></h1>
            <p class="db2-hero-sub">
                <?= $e(company_info('company_name')); ?>
                <?php foreach (array_slice(current_role_names(), 0, 3) as $rn): ?>
                    <span class="db2-role"><?= $e($rn); ?></span>
                <?php endforeach; ?>
            </p>
        </div>
        <div class="db2-hero-side">
            <div class="db2-clock"><span id="db2Clock"><?= date('H:i'); ?></span><small><?= lang_line('db2_local_time', 'WIB'); ?></small></div>
            <a href="#db2-tasks" class="db2-hero-cta <?= $outstanding_total ? '' : 'is-clear'; ?>">
                <?php if ($outstanding_total): ?>
                    <i class="fas fa-bell"></i>
                    <span><strong><?= $nf($outstanding_total); ?></strong> <?= lang_line('db2_hero_waiting', 'dokumen menunggu tindakan Anda'); ?></span>
                <?php else: ?>
                    <i class="fas fa-check-circle"></i>
                    <span><?= lang_line('db2_hero_clear', 'Tidak ada yang menunggu Anda'); ?></span>
                <?php endif; ?>
            </a>
        </div>
    </section>

    <!-- ===================== KPI ===================== -->
    <section class="db2-kpis">
        <?php foreach ($kpi_cards as $i => $k): ?>
            <<?= $k['href'] ? 'a href="' . $k['href'] . '"' : 'div'; ?> class="db2-kpi db2-tone-<?= $k['tone']; ?> db2-reveal" style="--d: <?= 60 + $i * 60; ?>ms">
                <div class="db2-kpi-icon"><i class="fas <?= $k['icon']; ?>"></i></div>
                <div class="db2-kpi-body">
                    <div class="db2-kpi-label"><?= $e($k['label']); ?><?php if (!empty($k['note'])): ?> <em><?= $e($k['note']); ?></em><?php endif; ?></div>
                    <div class="db2-kpi-value" data-count="<?= (float) $k['value']; ?>"><?= $nf($k['value']); ?></div>
                    <div class="db2-kpi-sub"><?= $e($k['sub']); ?></div>
                </div>
                <?php if ($k['href']): ?><i class="fas fa-arrow-right db2-kpi-go"></i><?php endif; ?>
            </<?= $k['href'] ? 'a' : 'div'; ?>>
        <?php endforeach; ?>
    </section>

    <div class="row">
        <!-- ===================== PERLU TINDAKAN ===================== -->
        <div class="col-xl-8 mb-4">
            <section class="db2-card db2-reveal" id="db2-tasks" style="--d: 380ms">
                <header class="db2-card-head">
                    <div>
                        <h2><i class="fas fa-inbox"></i> <?= lang_line('db2_tasks_title', 'Perlu Tindakan Anda'); ?></h2>
                        <p><?= lang_line('db2_tasks_sub', 'Dokumen yang menunggu persetujuan atau proses Anda, sesuai hak akses.'); ?></p>
                    </div>
                    <?php if ($outstanding_total): ?><span class="db2-pill db2-pill--amber"><?= $nf($outstanding_total); ?> <?= lang_line('db2_docs', 'dokumen'); ?></span><?php endif; ?>
                </header>
                <?php if (empty($outstanding_tasks)): ?>
                    <div class="db2-empty">
                        <div class="db2-empty-art"><i class="fas fa-mug-hot"></i></div>
                        <h3><?= lang_line('db2_tasks_empty_title', 'Semua beres!'); ?></h3>
                        <p><?= lang_line('db2_tasks_empty', 'Tidak ada dokumen yang menunggu tindakan Anda saat ini.'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="db2-tasks">
                        <?php foreach ($outstanding_tasks as $t):
                            $tone  = $task_tone[$t['color']] ?? 'brand';
                            $label = !empty($t['label_key']) ? lang_line($t['label_key'], $t['label']) : $t['label']; ?>
                            <div class="db2-task db2-tone-<?= $tone; ?>">
                                <a class="db2-task-main" href="<?= base_url($t['url']); ?>">
                                    <span class="db2-task-icon"><i class="<?= $e($t['icon']); ?>"></i></span>
                                    <span class="db2-task-label"><?= $e($label); ?></span>
                                    <span class="db2-task-count"><?= $nf($t['count']); ?></span>
                                </a>
                                <div class="db2-task-items">
                                    <?php foreach (array_slice($t['items'], 0, 3) as $it): ?>
                                        <span class="db2-chip" title="<?= !empty($it->doc_date) ? $e(date('d M Y', strtotime($it->doc_date))) : ''; ?>"><?= $e($it->doc_number ?? '-'); ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($t['count'] > 3): ?><span class="db2-chip db2-chip--more">+<?= $nf($t['count'] - 3); ?></span><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- ===================== AKSI CEPAT + LACAK ===================== -->
        <div class="col-xl-4 mb-4">
            <section class="db2-card db2-reveal h-100" style="--d: 440ms">
                <header class="db2-card-head">
                    <div>
                        <h2><i class="fas fa-bolt"></i> <?= lang_line('db2_quick_title', 'Aksi Cepat'); ?></h2>
                        <p><?= lang_line('db2_quick_sub', 'Buat dokumen baru dalam satu klik.'); ?></p>
                    </div>
                </header>
                <?php if (empty($quick_actions)): ?>
                    <p class="db2-muted mb-0"><?= lang_line('db2_quick_none', 'Belum ada aksi yang tersedia untuk akun Anda.'); ?></p>
                <?php else: ?>
                    <div class="db2-actions">
                        <?php foreach ($quick_actions as $qa): ?>
                            <a class="db2-action" href="<?= base_url($qa['url']); ?>">
                                <span class="db2-action-icon"><i class="fas <?= $qa['icon']; ?>"></i></span>
                                <span><?= $e($qa['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($can_trace_so): ?>
                    <button type="button" class="db2-trace-btn" data-toggle="modal" data-target="#db2TraceModal">
                        <span class="db2-trace-icon"><i class="fas fa-route"></i></span>
                        <span>
                            <strong><?= lang_line('db2_trace_title', 'Lacak Sales Order'); ?></strong>
                            <small><?= lang_line('db2_trace_sub', 'Lihat WO, produksi & pengiriman satu SO'); ?></small>
                        </span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- ===================== ALUR PRODUKSI ===================== -->
    <section class="db2-card db2-reveal mb-4" style="--d: 500ms">
        <header class="db2-card-head">
            <div>
                <h2><i class="fas fa-stream"></i> <?= lang_line('db2_flow_title', 'Alur Produksi Bulan Ini'); ?></h2>
                <p><?= lang_line('db2_flow_sub', 'Dari pesanan pelanggan sampai barang terkirim.'); ?></p>
            </div>
        </header>
        <div class="db2-flow">
            <?php foreach ($pipeline as $i => $p):
                $href = check_access($p['menu'], 'view') ? base_url($p['url']) : null; ?>
                <<?= $href ? 'a href="' . $href . '"' : 'div'; ?> class="db2-flow-step" style="--i: <?= $i; ?>">
                    <span class="db2-flow-node"><i class="fas <?= $p['icon']; ?>"></i></span>
                    <span class="db2-flow-value" data-count="<?= (int) $p['value']; ?>"><?= $nf($p['value']); ?></span>
                    <span class="db2-flow-label"><?= $e($p['label']); ?></span>
                </<?= $href ? 'a' : 'div'; ?>>
                <?php if ($i < count($pipeline) - 1): ?><span class="db2-flow-link" aria-hidden="true"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="row">
        <!-- ===================== PROGRES 5 SO TERBARU ===================== -->
        <div class="col-xl-7 mb-4">
            <section class="db2-card db2-reveal h-100" style="--d: 560ms">
                <header class="db2-card-head">
                    <div>
                        <h2><i class="fas fa-chart-line"></i> <?= lang_line('db2_so_title', 'Progres Sales Order Terbaru'); ?></h2>
                        <p><?= lang_line('db2_so_sub', '5 Sales Order terakhir — seberapa jauh sudah diproduksi & dikirim.'); ?></p>
                    </div>
                    <?php if (check_access('sales_contract', 'view')): ?>
                        <a class="db2-link" href="<?= base_url('sales_contract/list_all'); ?>"><?= lang_line('db2_see_all', 'Lihat semua'); ?> <i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                </header>
                <?php if (empty($so_progress)): ?>
                    <div class="db2-empty db2-empty--sm">
                        <div class="db2-empty-art"><i class="fas fa-file-signature"></i></div>
                        <p><?= lang_line('db2_so_empty', 'Belum ada Sales Order.'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="db2-so-list">
                        <?php foreach ($so_progress as $so): ?>
                            <div class="db2-so">
                                <div class="db2-so-top">
                                    <div class="db2-so-id">
                                        <?php if (check_access('sales_contract', 'view')): ?>
                                            <a href="<?= base_url('sales_contract/detail/' . (int) $so->id); ?>"><?= $e($so->sc_number); ?></a>
                                        <?php else: ?><span><?= $e($so->sc_number); ?></span><?php endif; ?>
                                        <small><?= $e($so->nama_customer ?: '-'); ?> · <?= $so->contract_date ? $e(date('d M Y', strtotime($so->contract_date))) : '-'; ?></small>
                                    </div>
                                    <div class="db2-so-meta">
                                        <span class="db2-status db2-status--<?= $status_tone($so->status); ?>"><?= $e($so->status); ?></span>
                                        <span class="db2-so-qty"><?= $nf($so->po_qty); ?> <small>pcs</small></span>
                                    </div>
                                </div>
                                <div class="db2-bars">
                                    <div class="db2-bar">
                                        <span class="db2-bar-label"><?= lang_line('db2_so_produced', 'Diproduksi'); ?></span>
                                        <span class="db2-bar-track"><span class="db2-bar-fill db2-bar-fill--prod" style="--w: <?= (int) $so->pct_produced; ?>%"></span></span>
                                        <span class="db2-bar-val"><?= (int) $so->pct_produced; ?>%</span>
                                    </div>
                                    <div class="db2-bar">
                                        <span class="db2-bar-label"><?= lang_line('db2_so_delivered', 'Terkirim'); ?></span>
                                        <span class="db2-bar-track"><span class="db2-bar-fill db2-bar-fill--ship" style="--w: <?= (int) $so->pct_delivered; ?>%"></span></span>
                                        <span class="db2-bar-val"><?= (int) $so->pct_delivered; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- ===================== DOKUMEN TERBARU ===================== -->
        <div class="col-xl-5 mb-4">
            <section class="db2-card db2-reveal h-100" style="--d: 620ms">
                <header class="db2-card-head">
                    <div>
                        <h2><i class="fas fa-history"></i> <?= lang_line('db2_recent_title', 'Dokumen Terbaru'); ?></h2>
                        <p><?= lang_line('db2_recent_sub', 'Aktivitas terakhir di modul yang bisa Anda akses.'); ?></p>
                    </div>
                </header>
                <?php if (empty($recent_docs)): ?>
                    <div class="db2-empty db2-empty--sm">
                        <div class="db2-empty-art"><i class="fas fa-folder-open"></i></div>
                        <p><?= lang_line('db2_recent_empty', 'Belum ada dokumen.'); ?></p>
                    </div>
                <?php else: ?>
                    <ul class="db2-timeline">
                        <?php foreach ($recent_docs as $d): ?>
                            <li class="db2-tl db2-tone-<?= $d->color; ?>">
                                <span class="db2-tl-dot"><i class="fas <?= $d->icon; ?>"></i></span>
                                <a class="db2-tl-body" href="<?= base_url($d->url); ?>">
                                    <span class="db2-tl-top">
                                        <strong><?= $e($d->number); ?></strong>
                                        <span class="db2-status db2-status--<?= $status_tone($d->status); ?>"><?= $e($d->status); ?></span>
                                    </span>
                                    <span class="db2-tl-sub"><?= $e($d->label); ?> · <?= $d->date ? $e(date('d M Y', strtotime($d->date))) : '-'; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- ===================== PERHATIAN ===================== -->
    <section class="db2-card db2-reveal mb-4" style="--d: 680ms">
        <header class="db2-card-head">
            <div>
                <h2><i class="fas fa-shield-alt"></i> <?= lang_line('db2_watch_title', 'Perhatian'); ?></h2>
                <p><?= lang_line('db2_watch_sub', 'Hal yang sebaiknya segera dicek.'); ?></p>
            </div>
        </header>
        <div class="db2-watch">
            <?php $fg_href = $link('fg_stock', 'fg_stock'); ?>
            <<?= $fg_href ? 'a href="' . $fg_href . '"' : 'div'; ?> class="db2-watch-item <?= $low_stock_total ? 'db2-tone-rose' : 'db2-tone-green'; ?>">
                <span class="db2-watch-icon"><i class="fas <?= $low_stock_total ? 'fa-box-open' : 'fa-check'; ?>"></i></span>
                <span class="db2-watch-text">
                    <strong><?= $low_stock_total ? sprintf(lang_line('db2_watch_fg', '%s barang jadi stoknya habis'), $nf($low_stock_total)) : lang_line('db2_watch_fg_ok', 'Stok barang jadi aman'); ?></strong>
                    <?php if (!empty($low_stock_alerts)): ?>
                        <small><?= $e(implode(', ', array_map(function ($i) { return $i->fg_name ?? $i->fg_code ?? '-'; }, array_slice($low_stock_alerts, 0, 3)))); ?><?= $low_stock_total > 3 ? ', …' : ''; ?></small>
                    <?php endif; ?>
                </span>
            </<?= $fg_href ? 'a' : 'div'; ?>>

            <?php $do_href = $link('fg_delivery', 'fg_delivery'); ?>
            <<?= $do_href ? 'a href="' . $do_href . '"' : 'div'; ?> class="db2-watch-item <?= $kpi['do_draft'] ? 'db2-tone-amber' : 'db2-tone-green'; ?>">
                <span class="db2-watch-icon"><i class="fas <?= $kpi['do_draft'] ? 'fa-truck-loading' : 'fa-check'; ?>"></i></span>
                <span class="db2-watch-text">
                    <strong><?= $kpi['do_draft'] ? sprintf(lang_line('db2_watch_do', '%s pengiriman masih draft'), $nf($kpi['do_draft'])) : lang_line('db2_watch_do_ok', 'Tidak ada pengiriman tertunda'); ?></strong>
                    <small><?= lang_line('db2_watch_do_sub', 'Konfirmasi agar stok & surat jalan sinkron'); ?></small>
                </span>
            </<?= $do_href ? 'a' : 'div'; ?>>

            <?php if ($can_stock_alert): ?>
                <a href="<?= base_url('stock_alert'); ?>" class="db2-watch-item db2-tone-slate" id="db2StockAlert">
                    <span class="db2-watch-icon"><i class="fas fa-layer-group"></i></span>
                    <span class="db2-watch-text">
                        <strong data-loading="<?= $e(lang_line('db2_watch_min_loading', 'Mengecek stok minimum…')); ?>"><?= lang_line('db2_watch_min_loading', 'Mengecek stok minimum…'); ?></strong>
                        <small><?= lang_line('db2_watch_min_sub', 'Material, barang umum & peralatan'); ?></small>
                    </span>
                </a>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($can_trace_so): ?>
<!-- ===================== MODAL LACAK SO (Bootstrap 4) ===================== -->
<div class="modal fade db2-modal" id="db2TraceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-route mr-2"></i><?= lang_line('db2_trace_title', 'Lacak Sales Order'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row mb-3">
                    <div class="col-md-9 mb-2 mb-md-0"><select id="db2TraceSelect" class="form-control" style="width:100%"></select></div>
                    <div class="col-md-3"><button type="button" id="db2TraceGo" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> <?= lang_line('db2_trace_go', 'Lacak'); ?></button></div>
                </div>
                <div id="db2TraceOut">
                    <div class="db2-empty db2-empty--sm">
                        <div class="db2-empty-art"><i class="fas fa-search-location"></i></div>
                        <p><?= lang_line('db2_trace_hint', 'Pilih Sales Order untuk melihat jejaknya.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ========================= DASHBOARD ACROPOLY (db2) ========================= */
.db2 {
    --db2-card: #ffffff; --db2-text: #1e293b; --db2-muted: #64748b; --db2-line: #e2e8f0; --db2-soft: #f8fafc;
    --db2-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .06);
    --db2-radius: 18px;
    color: var(--db2-text); padding-bottom: 1rem;
}
body.dark-mode .db2 {
    --db2-card: #1b2330; --db2-text: #e5e7eb; --db2-muted: #94a3b8; --db2-line: #2c3646; --db2-soft: #151c27;
    --db2-shadow: 0 1px 2px rgba(0,0,0,.3), 0 10px 28px rgba(0,0,0,.35);
}
/* palet nada (dipakai kartu, tugas, timeline) */
.db2 .db2-tone-blue   { --t: #3b82f6; --t-soft: rgba(59,130,246,.12); }
.db2 .db2-tone-orange { --t: #f97316; --t-soft: rgba(249,115,22,.12); }
.db2 .db2-tone-teal   { --t: #14b8a6; --t-soft: rgba(20,184,166,.12); }
.db2 .db2-tone-purple { --t: #8b5cf6; --t-soft: rgba(139,92,246,.12); }
.db2 .db2-tone-green  { --t: #10b981; --t-soft: rgba(16,185,129,.12); }
.db2 .db2-tone-slate  { --t: #64748b; --t-soft: rgba(100,116,139,.14); }
.db2 .db2-tone-amber  { --t: #f59e0b; --t-soft: rgba(245,158,11,.14); }
.db2 .db2-tone-sky    { --t: #0ea5e9; --t-soft: rgba(14,165,233,.12); }
.db2 .db2-tone-rose   { --t: #f43f5e; --t-soft: rgba(244,63,94,.12); }
.db2 .db2-tone-indigo { --t: #6366f1; --t-soft: rgba(99,102,241,.12); }
.db2 .db2-tone-pink   { --t: #ec4899; --t-soft: rgba(236,72,153,.12); }
.db2 .db2-tone-brand  { --t: var(--brand-1); --t-soft: rgba(var(--brand-rgb), .12); }

/* animasi masuk */
.db2-reveal { opacity: 0; transform: translateY(14px); animation: db2Up .6s cubic-bezier(.2,.7,.2,1) forwards; animation-delay: var(--d, 0ms); }
@keyframes db2Up { to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) { .db2-reveal { animation: none; opacity: 1; transform: none; } .db2-bar-fill { transition: none !important; } }

/* ---------- HERO ---------- */
.db2-hero {
    position: relative; overflow: hidden; border-radius: 24px; margin-bottom: 1.5rem;
    padding: 2rem 2.25rem; color: #fff; display: flex; gap: 1.5rem; align-items: center; justify-content: space-between; flex-wrap: wrap;
    background: radial-gradient(120% 140% at 0% 0%, var(--brand-2) 0%, var(--brand-1) 45%, var(--brand-dark) 100%);
    box-shadow: 0 18px 40px rgba(var(--brand-rgb), .28);
}
.db2-hero-mark { position: absolute; right: -40px; top: 50%; width: 260px; transform: translateY(-50%) rotate(-12deg); opacity: .09; filter: grayscale(1) brightness(3); pointer-events: none; }
.db2-hero-orb { position: absolute; border-radius: 50%; filter: blur(2px); pointer-events: none; }
.db2-hero-orb--a { width: 220px; height: 220px; left: -60px; bottom: -110px; background: rgba(255,255,255,.07); }
.db2-hero-orb--b { width: 120px; height: 120px; left: 38%; top: -60px; background: rgba(255,255,255,.06); }
.db2-hero-main { position: relative; z-index: 1; min-width: 260px; }
.db2-eyebrow { display: inline-flex; gap: .45rem; align-items: center; font-size: .8rem; letter-spacing: .04em; text-transform: uppercase; opacity: .8; font-weight: 600; }
.db2-hero-title { font-size: clamp(1.6rem, 2.6vw, 2.3rem); font-weight: 700; margin: .35rem 0 .4rem; color: #fff; letter-spacing: -.02em; }
.db2-hero-title span { background: linear-gradient(90deg, #fff, #cbd5e1); -webkit-background-clip: text; background-clip: text; color: transparent; }
.db2-hero-sub { margin: 0; opacity: .88; font-size: .92rem; display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
.db2-role { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); padding: .15rem .6rem; border-radius: 999px; font-size: .74rem; }
.db2-hero-side { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: flex-end; gap: .75rem; }
.db2-clock { font-size: 2.4rem; font-weight: 700; line-height: 1; letter-spacing: -.03em; font-variant-numeric: tabular-nums; }
.db2-clock small { font-size: .8rem; font-weight: 600; opacity: .7; margin-left: .35rem; letter-spacing: .05em; }
.db2-hero-cta { display: inline-flex; gap: .6rem; align-items: center; padding: .6rem 1rem; border-radius: 14px; color: #fff !important; text-decoration: none !important;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); backdrop-filter: blur(6px); transition: transform .2s, background .2s; font-size: .9rem; }
.db2-hero-cta:hover { background: rgba(255,255,255,.22); transform: translateY(-2px); }
.db2-hero-cta i { color: #fde68a; animation: db2Ring 2.4s ease-in-out infinite; }
.db2-hero-cta.is-clear i { color: #6ee7b7; animation: none; }
@keyframes db2Ring { 0%, 70%, 100% { transform: rotate(0); } 75% { transform: rotate(14deg); } 85% { transform: rotate(-12deg); } }

/* ---------- KPI ---------- */
.db2-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
@media (min-width: 1680px) { .db2-kpis { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
@media (max-width: 991px)  { .db2-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 519px)  { .db2-kpis { grid-template-columns: 1fr; } }
.db2-kpi { position: relative; display: flex; gap: .9rem; align-items: flex-start; padding: 1.15rem 1.1rem; border-radius: var(--db2-radius);
    background: var(--db2-card); box-shadow: var(--db2-shadow); border: 1px solid var(--db2-line); color: var(--db2-text) !important; text-decoration: none !important;
    transition: transform .25s, box-shadow .25s, border-color .25s; overflow: hidden; }
.db2-kpi::after { content: ""; position: absolute; inset: auto -30px -40px auto; width: 110px; height: 110px; border-radius: 50%; background: var(--t-soft); transition: transform .4s; }
a.db2-kpi:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(15,23,42,.1); border-color: var(--t); }
a.db2-kpi:hover::after { transform: scale(1.35); }
.db2-kpi-icon { flex: 0 0 auto; width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center; background: var(--t-soft); color: var(--t); font-size: 1.1rem; position: relative; z-index: 1; }
.db2-kpi-body { min-width: 0; position: relative; z-index: 1; }
.db2-kpi-label { font-size: .74rem; font-weight: 600; color: var(--db2-muted); text-transform: uppercase; letter-spacing: .04em; }
.db2-kpi-label em { font-style: normal; text-transform: none; letter-spacing: 0; opacity: .8; font-weight: 500; }
.db2-kpi-value { font-size: 1.75rem; font-weight: 700; line-height: 1.15; margin: .15rem 0 .2rem; font-variant-numeric: tabular-nums; letter-spacing: -.02em; }
.db2-kpi-sub { font-size: .76rem; color: var(--db2-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db2-kpi-go { position: absolute; right: 1rem; top: 1.1rem; font-size: .8rem; color: var(--db2-muted); opacity: 0; transform: translateX(-6px); transition: .25s; z-index: 1; }
a.db2-kpi:hover .db2-kpi-go { opacity: 1; transform: none; color: var(--t); }

/* ---------- KARTU ---------- */
.db2-card { background: var(--db2-card); border: 1px solid var(--db2-line); border-radius: 22px; box-shadow: var(--db2-shadow); padding: 1.4rem 1.5rem; }
.db2-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.1rem; }
.db2-card-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--db2-text); display: flex; align-items: center; gap: .55rem; }
.db2-card-head h2 i { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; font-size: .8rem; color: #fff; background: linear-gradient(135deg, var(--brand-2), var(--brand-dark)); }
.db2-card-head p { margin: .3rem 0 0; font-size: .82rem; color: var(--db2-muted); }
.db2-muted { color: var(--db2-muted); font-size: .88rem; }
.db2-link { font-size: .82rem; font-weight: 600; color: var(--brand-1); white-space: nowrap; }
body.dark-mode .db2-link { color: #cbd5e1; }
.db2-pill { padding: .3rem .75rem; border-radius: 999px; font-size: .76rem; font-weight: 700; white-space: nowrap; }
.db2-pill--amber { background: rgba(245,158,11,.14); color: #b45309; }
body.dark-mode .db2-pill--amber { color: #fbbf24; }

/* ---------- TUGAS ---------- */
.db2-tasks { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: .85rem; }
.db2-task { border: 1px solid var(--db2-line); border-radius: 16px; padding: .85rem .95rem; background: var(--db2-soft); transition: border-color .2s, transform .2s; }
.db2-task:hover { border-color: var(--t); transform: translateY(-2px); }
.db2-task-main { display: flex; align-items: center; gap: .7rem; text-decoration: none !important; color: var(--db2-text) !important; }
.db2-task-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--t-soft); color: var(--t); flex: 0 0 auto; }
.db2-task-label { flex: 1; font-weight: 600; font-size: .86rem; line-height: 1.25; }
.db2-task-count { min-width: 34px; height: 30px; padding: 0 .55rem; border-radius: 10px; display: grid; place-items: center; font-weight: 700; color: #fff; background: var(--t); font-variant-numeric: tabular-nums; }
.db2-task-items { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .65rem; padding-left: calc(36px + .7rem); }
.db2-chip { font-size: .72rem; padding: .18rem .55rem; border-radius: 8px; background: var(--db2-card); border: 1px solid var(--db2-line); color: var(--db2-muted); font-family: SFMono-Regular, Menlo, monospace; }
.db2-chip--more { font-family: inherit; font-weight: 700; color: var(--t); }

/* ---------- KOSONG ---------- */
.db2-empty { text-align: center; padding: 2rem 1rem; }
.db2-empty--sm { padding: 1.25rem .5rem; }
.db2-empty-art { width: 76px; height: 76px; margin: 0 auto .9rem; border-radius: 24px; display: grid; place-items: center; font-size: 1.8rem;
    color: var(--brand-1); background: linear-gradient(135deg, rgba(var(--brand-rgb), .1), rgba(var(--brand-rgb), .02)); animation: db2Float 4s ease-in-out infinite; }
body.dark-mode .db2-empty-art { color: #cbd5e1; }
.db2-empty h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: .25rem; color: var(--db2-text); }
.db2-empty p { color: var(--db2-muted); font-size: .86rem; margin: 0; }
@keyframes db2Float { 50% { transform: translateY(-6px); } }

/* ---------- AKSI CEPAT ---------- */
.db2-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
.db2-action { display: flex; align-items: center; gap: .6rem; padding: .7rem .75rem; border-radius: 14px; border: 1px solid var(--db2-line); background: var(--db2-soft);
    color: var(--db2-text) !important; text-decoration: none !important; font-size: .83rem; font-weight: 600; transition: .2s; }
.db2-action:hover { background: var(--brand-1); border-color: var(--brand-1); color: #fff !important; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(var(--brand-rgb), .25); }
.db2-action-icon { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: rgba(var(--brand-rgb), .1); color: var(--brand-1); flex: 0 0 auto; transition: .2s; }
body.dark-mode .db2-action-icon { color: #cbd5e1; }
.db2-action:hover .db2-action-icon { background: rgba(255,255,255,.18); color: #fff; }
.db2-trace-btn { margin-top: 1rem; width: 100%; display: flex; align-items: center; gap: .8rem; text-align: left; border: 1px dashed var(--db2-line); background: transparent;
    border-radius: 16px; padding: .85rem 1rem; color: var(--db2-text); transition: .2s; cursor: pointer; }
.db2-trace-btn:hover { border-style: solid; border-color: var(--brand-1); background: var(--db2-soft); }
.db2-trace-btn small { display: block; color: var(--db2-muted); font-size: .76rem; }
.db2-trace-btn > i { margin-left: auto; color: var(--db2-muted); }
.db2-trace-icon { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, var(--brand-2), var(--brand-dark)); flex: 0 0 auto; }

/* ---------- ALUR PRODUKSI ---------- */
.db2-flow { display: flex; align-items: stretch; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; }
.db2-flow-step { flex: 1 1 0; min-width: 140px; display: flex; flex-direction: column; align-items: center; gap: .35rem; padding: 1.1rem .75rem; border-radius: 18px;
    background: var(--db2-soft); border: 1px solid var(--db2-line); color: var(--db2-text) !important; text-decoration: none !important; position: relative; transition: .25s;
    opacity: 0; animation: db2Up .55s cubic-bezier(.2,.7,.2,1) forwards; animation-delay: calc(560ms + var(--i) * 90ms); }
a.db2-flow-step:hover { transform: translateY(-4px); border-color: var(--brand-1); box-shadow: 0 12px 26px rgba(var(--brand-rgb), .16); }
.db2-flow-node { width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: linear-gradient(135deg, var(--brand-2), var(--brand-dark)); box-shadow: 0 0 0 6px rgba(var(--brand-rgb), .1); }
.db2-flow-value { font-size: 1.6rem; font-weight: 700; line-height: 1; margin-top: .35rem; font-variant-numeric: tabular-nums; }
.db2-flow-label { font-size: .78rem; color: var(--db2-muted); text-align: center; font-weight: 600; }
.db2-flow-link { align-self: center; color: var(--db2-muted); opacity: .6; font-size: .85rem; flex: 0 0 auto; }

/* ---------- PROGRES SO ---------- */
.db2-so-list { display: flex; flex-direction: column; gap: .85rem; }
.db2-so { padding: .9rem 1rem; border-radius: 16px; border: 1px solid var(--db2-line); background: var(--db2-soft); }
.db2-so-top { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: .7rem; }
.db2-so-id a, .db2-so-id span { font-weight: 700; color: var(--db2-text); font-size: .92rem; }
.db2-so-id small { display: block; color: var(--db2-muted); font-size: .76rem; margin-top: .1rem; }
.db2-so-meta { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; justify-content: flex-end; }
.db2-so-qty { font-weight: 700; font-variant-numeric: tabular-nums; }
.db2-so-qty small { color: var(--db2-muted); font-weight: 500; }
.db2-bars { display: flex; flex-direction: column; gap: .45rem; }
.db2-bar { display: grid; grid-template-columns: 82px 1fr 42px; align-items: center; gap: .6rem; font-size: .76rem; }
.db2-bar-label { color: var(--db2-muted); font-weight: 600; }
.db2-bar-track { height: 9px; border-radius: 99px; background: var(--db2-line); overflow: hidden; }
.db2-bar-fill { display: block; height: 100%; width: 0; border-radius: 99px; transition: width 1.1s cubic-bezier(.2,.7,.2,1); }
.db2-bar-fill--prod { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
.db2-bar-fill--ship { background: linear-gradient(90deg, #10b981, #34d399); }
.db2.is-ready .db2-bar-fill { width: var(--w); }
.db2-bar-val { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }

/* ---------- STATUS ---------- */
.db2-status { font-size: .68rem; font-weight: 700; padding: .2rem .55rem; border-radius: 999px; white-space: nowrap; }
.db2-status--ok    { background: rgba(16,185,129,.14); color: #047857; }
.db2-status--bad   { background: rgba(244,63,94,.12);  color: #be123c; }
.db2-status--wait  { background: rgba(245,158,11,.15); color: #b45309; }
.db2-status--mid   { background: rgba(14,165,233,.14); color: #0369a1; }
.db2-status--draft { background: rgba(100,116,139,.15); color: #475569; }
.db2-status--run   { background: rgba(99,102,241,.13); color: #4338ca; }
body.dark-mode .db2-status--ok { color: #6ee7b7; } body.dark-mode .db2-status--bad { color: #fda4af; } body.dark-mode .db2-status--wait { color: #fcd34d; }
body.dark-mode .db2-status--mid { color: #7dd3fc; } body.dark-mode .db2-status--draft { color: #cbd5e1; } body.dark-mode .db2-status--run { color: #a5b4fc; }

/* ---------- TIMELINE ---------- */
.db2-timeline { list-style: none; margin: 0; padding: 0; position: relative; }
.db2-timeline::before { content: ""; position: absolute; left: 17px; top: 6px; bottom: 6px; width: 2px; background: var(--db2-line); border-radius: 2px; }
.db2-tl { position: relative; display: flex; gap: .85rem; padding: .35rem 0; }
.db2-tl-dot { position: relative; z-index: 1; width: 36px; height: 36px; flex: 0 0 auto; border-radius: 12px; display: grid; place-items: center; font-size: .8rem;
    background: var(--db2-card); color: var(--t); border: 2px solid var(--t-soft); box-shadow: 0 0 0 3px var(--db2-card); }
.db2-tl-body { flex: 1; min-width: 0; padding: .45rem .7rem; border-radius: 12px; text-decoration: none !important; color: var(--db2-text) !important; transition: background .2s; }
.db2-tl-body:hover { background: var(--db2-soft); }
.db2-tl-top { display: flex; justify-content: space-between; gap: .5rem; align-items: center; }
.db2-tl-top strong { font-size: .86rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db2-tl-sub { display: block; font-size: .75rem; color: var(--db2-muted); margin-top: .1rem; }

/* ---------- PERHATIAN ---------- */
.db2-watch { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .85rem; }
.db2-watch-item { display: flex; gap: .8rem; align-items: center; padding: .9rem 1rem; border-radius: 16px; background: var(--t-soft); border: 1px solid transparent;
    color: var(--db2-text) !important; text-decoration: none !important; transition: .2s; }
a.db2-watch-item:hover { border-color: var(--t); transform: translateY(-2px); }
.db2-watch-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: var(--t); flex: 0 0 auto; }
.db2-watch-text strong { display: block; font-size: .87rem; }
.db2-watch-text small { display: block; font-size: .75rem; color: var(--db2-muted); margin-top: .1rem; }

/* ---------- MODAL ---------- */
.db2-modal .modal-content { border: none; border-radius: 20px; overflow: hidden; }
.db2-modal .modal-header { background: linear-gradient(135deg, var(--brand-2), var(--brand-dark)); color: #fff; border: none; }
.db2-modal .modal-header .close { color: #fff; opacity: .85; text-shadow: none; }
.db2-trace-sec { border: 1px solid var(--db2-line, #e2e8f0); border-radius: 14px; padding: .9rem 1rem; margin-bottom: .8rem; }
.db2-trace-sec h6 { font-weight: 700; font-size: .85rem; margin-bottom: .6rem; display: flex; gap: .45rem; align-items: center; }
.db2-trace-row { display: flex; justify-content: space-between; gap: .75rem; padding: .45rem .6rem; border-radius: 10px; background: rgba(var(--brand-rgb), .05); margin-bottom: .35rem; font-size: .83rem; }

@media (max-width: 767px) {
    .db2-hero { padding: 1.5rem; } .db2-hero-side { align-items: flex-start; } .db2-clock { font-size: 1.9rem; }
    .db2-card { padding: 1.1rem; } .db2-bar { grid-template-columns: 70px 1fr 38px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('.db2');
    if (!root) return;
    var nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Angka naik (count-up) untuk KPI & alur produksi
    root.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        if (reduce || target <= 0) { el.textContent = nf.format(target); return; }
        var start = null, dur = 900 + Math.min(target, 500);
        el.textContent = '0';
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min(1, (ts - start) / dur), eased = 1 - Math.pow(1 - p, 3);
            el.textContent = nf.format(Math.round(target * eased));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });

    // Bar progres SO tumbuh setelah halaman siap
    requestAnimationFrame(function () { setTimeout(function () { root.classList.add('is-ready'); }, 250); });

    // Jam berjalan
    var clock = document.getElementById('db2Clock');
    if (clock) setInterval(function () {
        var d = new Date();
        clock.textContent = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }, 15000);

    // Stok di bawah minimum — dimuat terpisah (hitungannya berat)
    var sa = document.getElementById('db2StockAlert');
    if (sa && window.fetch) {
        fetch(<?= json_encode(base_url('stock_alert/get_alert_count')); ?>, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                var n = parseInt(j && j.alert_count, 10) || 0, s = sa.querySelector('strong');
                s.textContent = n > 0
                    ? <?= json_encode(lang_line('db2_watch_min', '%s item di bawah stok minimum')); ?>.replace('%s', nf.format(n))
                    : <?= json_encode(lang_line('db2_watch_min_ok', 'Semua stok di atas minimum')); ?>;
                sa.classList.remove('db2-tone-slate');
                sa.classList.add(n > 0 ? 'db2-tone-amber' : 'db2-tone-green');
            })
            .catch(function () { sa.querySelector('strong').textContent = <?= json_encode(lang_line('db2_watch_min_fail', 'Gagal memuat stok minimum')); ?>; });
    }

<?php if ($can_trace_so): ?>
    // Lacak Sales Order (Select2 + dashboard/sc_trace)
    var $ = window.jQuery;
    if ($ && $.fn.select2) {
        $('#db2TraceSelect').select2({
            dropdownParent: $('#db2TraceModal'), width: '100%', allowClear: true, minimumInputLength: 1,
            placeholder: <?= json_encode(lang_line('db2_trace_placeholder', 'Ketik nomor Sales Order / nama customer…')); ?>,
            ajax: { url: <?= json_encode(base_url('dashboard/sc_search')); ?>, dataType: 'json', delay: 250, data: function (p) { return { q: p.term }; }, processResults: function (d) { return d; } }
        });
    }
    var esc = function (v) { var d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; };
    var go = document.getElementById('db2TraceGo');
    if (go) go.addEventListener('click', function () {
        var id = $ ? $('#db2TraceSelect').val() : null, out = document.getElementById('db2TraceOut');
        if (!id) { out.innerHTML = '<div class="alert alert-warning mb-0">' + esc(<?= json_encode(lang_line('db2_trace_pick', 'Pilih Sales Order dulu.')); ?>) + '</div>'; return; }
        out.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
        fetch(<?= json_encode(base_url('dashboard/sc_trace/')); ?> + encodeURIComponent(id), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                var sec = function (icon, title, rows, empty) {
                    return '<div class="db2-trace-sec"><h6><i class="fas ' + icon + '"></i>' + esc(title) + '</h6>' + (rows.length ? rows.join('') : '<p class="text-muted small mb-0">' + esc(empty) + '</p>') + '</div>';
                };
                var row = function (a, b) { return '<div class="db2-trace-row"><strong>' + esc(a) + '</strong><span>' + esc(b) + '</span></div>'; };
                var html = '<div class="mb-3"><h5 class="mb-0">' + esc(j.sc && j.sc.sc_number) + '</h5><small class="text-muted">' + esc(j.sc && j.sc.customer_name) + '</small></div>';
                html += sec('fa-dolly', 'Material Request', (j.mrs || []).map(function (m) { return row(m.mr_number, m.request_date || ''); }), <?= json_encode(lang_line('db2_trace_no_mr', 'Belum ada Material Request.')); ?>);
                html += sec('fa-cogs', 'Work Order', (j.work_orders || []).map(function (w) { return row(w.wo_number + ' — ' + (w.product_name || ''), <?= json_encode(lang_line('db2_so_produced', 'Diproduksi')); ?> + ': ' + nf.format(w.produced_qty || 0)); }), <?= json_encode(lang_line('db2_trace_no_wo', 'Belum ada Work Order.')); ?>);
                html += sec('fa-truck', <?= json_encode(lang_line('db2_kpi_do', 'Pengiriman')); ?>, (j.deliveries || []).map(function (d) { return row(d.delivery_number + ' · ' + (d.delivery_date || ''), nf.format(d.total_shipped || 0) + ' pcs'); }), <?= json_encode(lang_line('db2_trace_no_do', 'Belum ada pengiriman.')); ?>);
                out.innerHTML = html;
            })
            .catch(function () { out.innerHTML = '<div class="alert alert-danger mb-0">' + esc(<?= json_encode(lang_line('db2_trace_fail', 'Gagal memuat data.')); ?>) + '</div>'; });
    });
<?php endif; ?>
});
</script>
