<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Chooser "Pemasukan Barang" -- redesign 2026-11-09 (user: "ini juga"), gaya .pp2.
 * Tile & statistiknya disiapkan Goods_receiving::index(). Kartu panduan di
 * bawah menjelaskan jenis barang -> menu penerimaannya (Jasa & Aset diterima
 * dari halaman PO masing-masing, bukan dari tile di atas).
 */
$this->load->view('production_process/_pp_style');
?>
<style>
.gr2 .gr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
.gr2 .gr-tile { position: relative; display: flex; flex-direction: column; gap: .8rem; padding: 1.2rem 1.25rem 1.1rem; border-radius: var(--pp-radius); background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); color: var(--pp-text); text-decoration: none !important; transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease; overflow: hidden; }
.gr2 .gr-tile::before { content: ""; position: absolute; left: 0; top: 0; right: 0; height: 3px; background: var(--t); opacity: .9; }
.gr2 .gr-tile:hover { transform: translateY(-3px); border-color: var(--t); color: var(--pp-text); box-shadow: 0 0 0 3px var(--t-soft), var(--pp-shadow); }
.gr2 .gr-head { display: flex; align-items: center; gap: .85rem; }
.gr2 .gr-ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; font-size: 1.15rem; color: var(--t); background: var(--t-soft); flex: 0 0 auto; }
.gr2 .gr-title { font-weight: 800; font-size: 1rem; line-height: 1.2; }
.gr2 .gr-sub { font-size: .76rem; font-weight: 700; color: var(--t); text-transform: uppercase; letter-spacing: .04em; margin-top: .15rem; }
.gr2 .gr-desc { font-size: .8rem; color: var(--pp-muted); line-height: 1.45; flex: 1; }
.gr2 .gr-stats { display: flex; gap: .5rem; flex-wrap: wrap; }
.gr2 .gr-stat { flex: 1 1 0; min-width: 0; padding: .55rem .7rem; border-radius: 12px; background: var(--pp-soft); border: 1px solid var(--pp-line); }
.gr2 .gr-stat b { display: block; font-size: 1.2rem; font-weight: 800; line-height: 1.1; }
.gr2 .gr-stat span { display: block; font-size: .68rem; line-height: 1.3; margin-top: .2rem; color: var(--pp-muted); font-weight: 600; }
.gr2 .gr-stat.is-hot { background: rgba(245,158,11,.1); border-color: rgba(245,158,11,.3); }
.gr2 .gr-stat.is-hot b { color: #b45309; }
body.dark-mode .gr2 .gr-stat.is-hot b { color: #fbbf24; }
.gr2 .gr-cta { display: flex; align-items: center; justify-content: space-between; font-size: .82rem; font-weight: 700; color: var(--t); padding-top: .2rem; }
.gr2 .gr-cta i { transition: transform .15s ease; }
.gr2 .gr-tile:hover .gr-cta i { transform: translateX(4px); }
.gr2 .gr-guide { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .75rem; }
.gr2 .gr-guide-item { display: flex; gap: .7rem; align-items: flex-start; padding: .75rem .85rem; border-radius: 12px; background: var(--pp-soft); border: 1px solid var(--pp-line); font-size: .8rem; }
.gr2 .gr-guide-item .gr-dot { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; font-size: .78rem; color: var(--t); background: var(--t-soft); flex: 0 0 auto; }
.gr2 .gr-guide-item b { display: block; font-size: .82rem; margin-bottom: .1rem; }
.gr2 .gr-guide-item span { color: var(--pp-muted); }
.gr2 .gr-guide-item a { font-weight: 700; }
</style>

<div class="container-fluid pp2 gr2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-truck-loading mr-2" style="color:var(--brand-1)"></i><?= lang_line('gc_receiving_title', 'Pemasukan Barang'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('gr2_sub', 'Pilih jenis penerimaan. Angka di tiap kartu menunjukkan pekerjaan yang masih menunggu.'); ?></div>
        </div>
    </div>

    <div class="gr-grid">
        <?php foreach ($tiles as $t): $w = (int) ($t['stats']['waiting'] ?? 0); $d = (int) ($t['stats']['draft'] ?? 0); ?>
        <a href="<?= base_url($t['url']); ?>" class="gr-tile <?= $t['tone']; ?>">
            <div class="gr-head">
                <span class="gr-ico"><i class="fas <?= $t['icon']; ?>"></i></span>
                <div style="min-width:0">
                    <div class="gr-title"><?= htmlspecialchars($t['title']); ?></div>
                    <div class="gr-sub"><?= htmlspecialchars($t['subtitle']); ?></div>
                </div>
            </div>
            <div class="gr-desc"><?= htmlspecialchars($t['desc']); ?></div>
            <div class="gr-stats">
                <div class="gr-stat<?= $w ? ' is-hot' : ''; ?>"><b><?= number_format($w); ?></b><span><?= lang_line('gr2_stat_waiting', 'PO menunggu diterima'); ?></span></div>
                <div class="gr-stat"><b><?= number_format($d); ?></b><span><?= lang_line('gr2_stat_draft', 'Dokumen Draft'); ?></span></div>
            </div>
            <div class="gr-cta"><span><?= lang_line('gr2_open', 'Buka'); ?></span><i class="fas fa-arrow-right"></i></div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-route"></i></span><span><?= lang_line('gr2_guide', 'Barang apa masuk lewat mana?'); ?>
            <span class="d-block pp-sub"><?= lang_line('gr2_guide_sub', 'Penerimaan selalu berdasarkan PO yang sudah disetujui.'); ?></span></span></h2></div>
        <div class="pp-card-body">
            <div class="gr-guide">
                <div class="gr-guide-item tone-green"><span class="gr-dot"><i class="fas fa-boxes"></i></span><div><b><?= lang_line('gr2_g_material', 'Material produksi'); ?></b><span><?= lang_line('gr2_g_material_d', 'Receiving Material Form (GRN) → Quality Assurance → hanya qty PASS yang masuk stok.'); ?></span></div></div>
                <div class="gr-guide-item tone-blue"><span class="gr-dot"><i class="fas fa-dolly-flatbed"></i></span><div><b><?= lang_line('gr2_g_general', 'Mesin & inventaris'); ?></b><span><?= lang_line('gr2_g_general_d', 'Receiving Item Form sesuai gudangnya (General / Production), tanpa QA.'); ?></span></div></div>
                <div class="gr-guide-item tone-slate"><span class="gr-dot"><i class="fas fa-hands-helping"></i></span><div><b><?= lang_line('gr2_g_jasa', 'Jasa'); ?></b><span><?= lang_line('gr2_g_jasa_d', 'Tidak lewat gudang — tandai "Selesaikan Jasa" di halaman PO Jasa.'); ?></span>
                    <?php if (!empty($can_po_general)): ?> <a href="<?= base_url('purchase_order_general'); ?>"><?= lang_line('gr2_g_open_po', 'Buka daftar PO'); ?> →</a><?php endif; ?></div></div>
                <div class="gr-guide-item tone-amber"><span class="gr-dot"><i class="fas fa-building"></i></span><div><b><?= lang_line('gr2_g_asset', 'Aset dari PO Asset'); ?></b><span><?= lang_line('gr2_g_asset_d', 'Dicatat per unit lewat "Terima Aset" di halaman PO Asset (masuk Asset Register).'); ?></span>
                    <?php if (!empty($can_po_general)): ?> <a href="<?= base_url('purchase_order_general'); ?>"><?= lang_line('gr2_g_open_po', 'Buka daftar PO'); ?> →</a><?php endif; ?></div></div>
            </div>
        </div>
    </div>
</div>
