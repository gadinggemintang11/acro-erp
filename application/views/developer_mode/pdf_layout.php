<?php
/**
 * Editor Tata Letak PDF — "Preview Langsung".
 *
 * Kiri: penggeser (slider) yang dikelompokkan per bagian dokumen.
 * Kanan: PDF ASLI hasil Mpdf, dirender PDF.js. Bukan tiruan HTML — jadi apa
 * yang terlihat memang persis yang tercetak (itu sebabnya ada jeda render
 * ~1-2 detik tiap perubahan; kepastian ditukar dengan kecepatan).
 *
 * Klik salah satu pita di atas preview -> panel kiri melompat ke kelompok
 * setelan bagian itu. Sengaja pakai 3 PITA, bukan deteksi elemen per
 * koordinat: tata letaknya mengalir (ubah font -> semua bergeser, bahkan
 * pindah halaman), jadi koordinat elemen tidak pernah bisa dipatok.
 */
$groups = $groups ?? [];
?>
<style>
    .pdfed-wrap { display: flex; gap: 1rem; align-items: flex-start; }
    .pdfed-side { flex: 0 0 400px; max-width: 400px; }
    .pdfed-view { flex: 1 1 auto; min-width: 0; position: sticky; top: 90px; }
    @media (max-width: 991px) { .pdfed-wrap { flex-direction: column; } .pdfed-side { flex: 1 1 auto; max-width: none; } .pdfed-view { position: static; width: 100%; } }
    .pdfed-canvas-box { background: #525659; border-radius: .35rem; padding: 12px; text-align: center; position: relative; min-height: 320px; }
    .pdfed-canvas-box canvas { max-width: 100%; height: auto; box-shadow: 0 2px 12px rgba(0,0,0,.4); background: #fff; }
    /* Lapisan kotak klik PER ELEMEN. Posisinya dihitung dari getTextContent()
       PDF.js, jadi menempel persis di tulisan yang sedang tercetak dan ikut
       bergeser sendiri setiap setelan diubah (user 2026-09-14). */
    .pdfed-hots { position: absolute; pointer-events: none; }
    .pdfed-hot  { position: absolute; cursor: pointer; pointer-events: auto; border-radius: 2px;
                  outline: 1px solid transparent; transition: background .1s, outline-color .1s; }
    .pdfed-hot:hover { background: rgba(78,115,223,.28); outline-color: #4e73df; }
    .pdfed-hot.on    { background: rgba(246,194,62,.40); outline: 2px solid #f6c23e; }
    .pdfed-tip { position: absolute; z-index: 6; pointer-events: none; white-space: nowrap;
                 font-size: .68rem; font-weight: 700; color: #fff; background: #4e73df;
                 padding: 2px 8px; border-radius: 10px; transform: translate(-50%, -130%); display: none; }
    .pdfed-row.flash { animation: pdfedFlash 1s ease-out; }
    @keyframes pdfedFlash { 0%, 60% { background: rgba(246,194,62,.45); } 100% { background: transparent; } }
    .pdfed-busy { position: absolute; inset: 0; background: rgba(0,0,0,.45); color: #fff;
                  display: none; align-items: center; justify-content: center; border-radius: .35rem; z-index: 5; }
    .pdfed-busy.show { display: flex; }
    .pdfed-group { border: 1px solid #e3e6f0; border-radius: .35rem; margin-bottom: .75rem; }
    .pdfed-group.on { border-color: #4e73df; box-shadow: 0 0 0 .18rem rgba(78,115,223,.18); }
    .pdfed-group > .hd { background: #f8f9fc; padding: .5rem .75rem; font-weight: 700; font-size: .85rem; cursor: pointer; }
    .pdfed-group > .bd { padding: .6rem .75rem; }
    .pdfed-row { margin-bottom: .85rem; }
    .pdfed-row label { font-size: .8rem; font-weight: 600; margin-bottom: .15rem; display: block; }
    .pdfed-row .val { font-weight: 700; color: #4e73df; }
    .pdfed-row .hint { font-size: .72rem; color: #858796; line-height: 1.25; }
    .pdfed-row input[type=range] { width: 100%; }
    .pdfed-dirty { display: none; }
    .pdfed-dirty.show { display: inline; }
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-file-pdf text-primary mr-2"></i> <?= lang_line('devmode_pdf_page_title', 'Atur Tampilan Cetakan PDF'); ?></h3>
            <small class="text-muted"><?= lang_line('devmode_pdf_subtitle', 'Geser penggesernya, lihat hasilnya langsung di sebelah kanan. Tidak perlu menyentuh kode.'); ?></small>
        </div>
        <div>
            <a href="<?= base_url('developer_mode') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-language mr-1"></i> <?= lang_line('devmode_assets_go_text', 'Kelola Teks'); ?></a>
            <a href="<?= base_url('developer_mode/assets') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-image mr-1"></i> <?= lang_line('devmode_assets_go_images', 'Kelola Logo & Gambar'); ?></a>
            <a href="<?= base_url('developer_mode/columns') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_assets_go_columns', 'Kelola Kolom'); ?></a>
            <a href="<?= base_url('developer_mode/sidebar_order') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-bars mr-1"></i> <?= lang_line('devmode_assets_go_sidebar_order', 'Atur Urutan Sidebar'); ?></a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success'); ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-1"></i> <?= $this->session->flashdata('error'); ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <?php if (empty($ready)): ?>
        <div class="alert alert-warning"><i class="fas fa-database mr-1"></i>
            <?= lang_line('devmode_pdf_not_ready', 'Tabel penyimpan setelan belum dibuat. Buka halaman <code>/db_migration</code> dan jalankan migrasi dulu. Sampai itu dilakukan, cetakan tetap memakai tampilan bawaan.'); ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <?php foreach ($templates as $i => $t): ?>
            <li class="nav-item">
                <a class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-toggle="tab" href="#tpl-<?= htmlspecialchars($t->key) ?>"><?= htmlspecialchars($t->label) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content">
    <?php foreach ($templates as $i => $t): ?>
        <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tpl-<?= htmlspecialchars($t->key) ?>">
            <?php
                // Peta teks->setelan + label manusiawi tiap setelan, dipakai
                // lapisan klik-per-elemen di preview.
                $hot_labels = [];
                foreach ($t->settings as $sk => $m) { $hot_labels[$sk] = $m['label']; }
            ?>
            <div class="pdfed-wrap" data-tpl="<?= htmlspecialchars($t->key) ?>"
                 data-hotspots="<?= htmlspecialchars(json_encode($t->hotspots ?? []), ENT_QUOTES, 'UTF-8') ?>"
                 data-hotlabels="<?= htmlspecialchars(json_encode($hot_labels), ENT_QUOTES, 'UTF-8') ?>">

                <!-- ============ KIRI: penggeser ============ -->
                <div class="pdfed-side">
                    <form method="POST" action="<?= base_url('developer_mode/save_pdf_layout') ?>" class="pdfed-form">
                        <input type="hidden" name="template_key" value="<?= htmlspecialchars($t->key) ?>">

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body py-2">
                                <div class="small text-muted mb-1"><?= lang_line('devmode_pdf_quick', 'Pengaturan cepat'); ?></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mb-1 pdfed-preset" data-preset="rapat">
                                    <i class="fas fa-compress-alt mr-1"></i> <?= lang_line('devmode_pdf_preset_tight', 'Rapatkan (hemat halaman)'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary mb-1 pdfed-preset" data-preset="besar">
                                    <i class="fas fa-search-plus mr-1"></i> <?= lang_line('devmode_pdf_preset_big', 'Perbesar tulisan'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-1 pdfed-preset" data-preset="bawaan">
                                    <i class="fas fa-undo mr-1"></i> <?= lang_line('devmode_pdf_preset_default', 'Kembali ke bawaan'); ?>
                                </button>
                            </div>
                        </div>

                        <?php foreach ($groups as $gk => $g):
                            $rows = array_filter($t->settings, function ($m) use ($gk) { return ($m['group'] ?? '') === $gk; });
                            if (empty($rows)) continue; ?>
                            <div class="pdfed-group" data-group="<?= htmlspecialchars($gk) ?>">
                                <div class="hd"><i class="fas <?= htmlspecialchars($g['icon']) ?> mr-1 text-primary"></i> <?= htmlspecialchars($g['label']) ?></div>
                                <div class="bd">
                                    <div class="hint mb-2"><?= htmlspecialchars($g['hint']) ?></div>
                                    <?php foreach ($rows as $sk => $meta):
                                        $val = $t->values[$sk] ?? $meta['default']; ?>
                                        <div class="pdfed-row">
                                            <label>
                                                <?= htmlspecialchars($meta['label']) ?>
                                                <span class="val"><span class="js-val"><?= htmlspecialchars($val) ?></span> <?= htmlspecialchars($meta['unit']) ?></span>
                                            </label>
                                            <input type="range" class="custom-range js-range"
                                                   min="<?= $meta['min'] ?>" max="<?= $meta['max'] ?>" step="0.5"
                                                   value="<?= htmlspecialchars($val) ?>"
                                                   data-key="<?= htmlspecialchars($sk) ?>"
                                                   data-default="<?= $meta['default'] ?>">
                                            <input type="hidden" name="settings[<?= htmlspecialchars($sk) ?>]" value="<?= htmlspecialchars($val) ?>" class="js-hidden">
                                            <?php if (!empty($meta['hint'])): ?><div class="hint"><?= htmlspecialchars($meta['hint']) ?></div><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-2 d-flex align-items-center justify-content-between">
                                <span class="small text-warning pdfed-dirty"><i class="fas fa-exclamation-circle mr-1"></i><?= lang_line('devmode_pdf_unsaved', 'Ada perubahan belum disimpan'); ?></span>
                                <div class="ml-auto">
                                    <button type="submit" class="btn btn-primary btn-sm" <?= empty($ready) ? 'disabled' : '' ?>>
                                        <i class="fas fa-save mr-1"></i> <?= lang_line('devmode_pdf_save', 'Simpan'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="<?= base_url('developer_mode/reset_pdf_layout') ?>" class="mt-2"
                          onsubmit="return confirm('<?= htmlspecialchars(lang_line('devmode_pdf_reset_confirm', 'Hapus semua setelan tersimpan untuk template ini?'), ENT_QUOTES) ?>');">
                        <input type="hidden" name="template_key" value="<?= htmlspecialchars($t->key) ?>">
                        <button type="submit" class="btn btn-link btn-sm text-muted p-0"><?= lang_line('devmode_pdf_reset', 'Hapus setelan tersimpan'); ?></button>
                    </form>
                </div>

                <!-- ============ KANAN: preview PDF asli ============ -->
                <div class="pdfed-view">
                    <?php if (empty($t->sample)): ?>
                        <div class="alert alert-secondary mb-0"><i class="fas fa-inbox mr-1"></i>
                            <?= lang_line('devmode_pdf_no_sample', 'Belum ada dokumen yang bisa dijadikan contoh. Buat satu dokumen dulu, lalu buka halaman ini lagi.'); ?></div>
                    <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header py-2 d-flex align-items-center justify-content-between">
                            <div class="small">
                                <span class="badge badge-primary js-pagecount">-</span>
                                <span class="text-muted ml-1 js-pagewarn"></span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-xs btn-outline-secondary js-prev">&laquo;</button>
                                <span class="small mx-1"><span class="js-pageno">1</span></span>
                                <button type="button" class="btn btn-xs btn-outline-secondary js-next">&raquo;</button>
                                <a href="<?= base_url($t->sample) ?>" target="_blank" class="btn btn-xs btn-outline-primary ml-2 no-loader"><i class="fas fa-external-link-alt"></i></a>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="pdfed-canvas-box">
                                <div class="pdfed-busy"><span><i class="fas fa-spinner fa-spin mr-2"></i><?= lang_line('devmode_pdf_rendering', 'Membuat ulang cetakan…'); ?></span></div>
                                <canvas class="js-canvas"></canvas>
                                <div class="pdfed-hots js-hots"></div>
                                <div class="pdfed-tip js-tip"></div>
                            </div>
                            <div class="small text-muted mt-2">
                                <i class="fas fa-hand-pointer mr-1"></i>
                                <?= lang_line('devmode_pdf_hot_help', 'Arahkan kursor ke tulisan mana pun di dokumen untuk melihat setelan yang mengaturnya, lalu klik untuk langsung membukanya.'); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
    if (!window.pdfjsLib) { console.warn('[PDF Layout] PDF.js gagal dimuat — preview dimatikan.'); return; }
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    var PREVIEW_URL = '<?= base_url('developer_mode/preview_pdf/') ?>';

    document.querySelectorAll('.pdfed-wrap').forEach(function (wrap) {
        var tpl      = wrap.dataset.tpl;
        var canvas   = wrap.querySelector('.js-canvas');
        if (!canvas) return;                       // template tanpa dokumen contoh
        var ctx      = canvas.getContext('2d');
        var busy     = wrap.querySelector('.pdfed-busy');
        var elCount  = wrap.querySelector('.js-pagecount');
        var elWarn   = wrap.querySelector('.js-pagewarn');
        var elNo     = wrap.querySelector('.js-pageno');
        var dirty    = wrap.querySelector('.pdfed-dirty');
        var hots     = wrap.querySelector('.js-hots');
        var tip      = wrap.querySelector('.js-tip');
        var HOTSPOTS = JSON.parse(wrap.dataset.hotspots || '[]');
        var HOTLABEL = JSON.parse(wrap.dataset.hotlabels || '{}');
        var doc = null, page = 1, timer = null, basePages = null, token = 0;

        /** Teks PDF dinormalkan sebelum dicocokkan: huruf besar, spasi rapat. */
        function norm(t) { return String(t || '').toUpperCase().replace(/\s+/g, ' ').trim(); }

        /**
         * Perkalian matriks affine 2D — sama persis dengan pdfjsLib.Util.transform().
         * Ditulis sendiri sebagai cadangan: Util memang bagian API publik PDF.js,
         * tapi kalau suatu saat build CDN-nya berubah dan Util hilang, hotspot
         * akan diam-diam tidak muncul sama sekali. Lebih baik jatuh ke sini.
         */
        function mulT(m, t) {
            return [
                m[0] * t[0] + m[2] * t[1],
                m[1] * t[0] + m[3] * t[1],
                m[0] * t[2] + m[2] * t[3],
                m[1] * t[2] + m[3] * t[3],
                m[0] * t[4] + m[2] * t[5] + m[4],
                m[1] * t[4] + m[3] * t[5] + m[5]
            ];
        }

        /** Cari setelan yang mengatur sepotong teks. null kalau tidak ada aturannya. */
        function keyFor(str) {
            var n = norm(str);
            if (!n) return null;
            for (var i = 0; i < HOTSPOTS.length; i++) {
                var r = HOTSPOTS[i];
                if (r.m === 'exact' ? (n === r.t) : (n.indexOf(r.t) !== -1)) return r.k;
            }
            return null;
        }

        /**
         * Bangun kotak klik untuk SETIAP potongan teks di halaman ini.
         * Posisinya diambil dari getTextContent() -> menempel persis di
         * tulisannya, bukan pita persentase tebakan seperti versi lama.
         */
        function buildHotspots(p, vp) {
            if (!hots) return;
            hots.innerHTML = '';
            hots.style.left   = canvas.offsetLeft + 'px';
            hots.style.top    = canvas.offsetTop + 'px';
            hots.style.width  = canvas.offsetWidth + 'px';
            hots.style.height = canvas.offsetHeight + 'px';

            p.getTextContent().then(function (tc) {
                var boxes = [], yTable = Infinity, ySign = Infinity;

                tc.items.forEach(function (it) {
                    if (!it.str || !it.str.trim()) return;
                    var tx = (pdfjsLib.Util && pdfjsLib.Util.transform)
                        ? pdfjsLib.Util.transform(vp.transform, it.transform)
                        : mulT(vp.transform, it.transform);
                    var fh = Math.hypot(tx[2], tx[3]);
                    var w  = it.width * vp.scale;
                    if (w < 2 || fh < 2) return;
                    var b = { x: tx[4], y: tx[5] - fh, w: w, h: fh, key: keyFor(it.str) };
                    if (b.key && b.key.indexOf('col_') === 0) yTable = Math.min(yTable, b.y);
                    if (b.key === 'sig_height')               ySign  = Math.min(ySign,  b.y);
                    boxes.push(b);
                });

                // Teks tanpa aturan (isi tabel, angka, nama) tetap bisa diklik:
                // bagiannya ditentukan dari posisi relatif terhadap baris judul
                // tabel & blok tanda tangan yang BARU SAJA terdeteksi di atas,
                // bukan dari persentase yang dipatok di kode.
                boxes.forEach(function (b) {
                    if (b.key) return;
                    b.key = (b.y < yTable) ? 'font_body' : (b.y < ySign ? 'font_table' : 'sig_height');
                });

                boxes.forEach(function (b) {
                    if (!HOTLABEL[b.key]) return;          // setelan tidak ada di template ini
                    var d = document.createElement('div');
                    d.className = 'pdfed-hot';
                    d.style.left   = (b.x / canvas.width  * 100) + '%';
                    d.style.top    = (b.y / canvas.height * 100) + '%';
                    d.style.width  = (b.w / canvas.width  * 100) + '%';
                    d.style.height = (b.h / canvas.height * 100) + '%';
                    d.dataset.key  = b.key;
                    d.addEventListener('mouseenter', function () {
                        if (!tip) return;
                        tip.textContent = HOTLABEL[b.key];
                        tip.style.left = (canvas.offsetLeft + b.x / canvas.width * canvas.offsetWidth + (b.w / canvas.width * canvas.offsetWidth) / 2) + 'px';
                        tip.style.top  = (canvas.offsetTop  + b.y / canvas.height * canvas.offsetHeight) + 'px';
                        tip.style.display = 'block';
                    });
                    d.addEventListener('mouseleave', function () { if (tip) tip.style.display = 'none'; });
                    d.addEventListener('click', function () { focusSetting(b.key, d); });
                    hots.appendChild(d);
                });
            });
        }

        /** Buka kelompok setelan yang bersangkutan lalu sorot barisnya. */
        function focusSetting(key, hotEl) {
            var range = wrap.querySelector('.js-range[data-key="' + key + '"]');
            if (!range) return;
            var row   = range.closest('.pdfed-row');
            var group = range.closest('.pdfed-group');
            wrap.querySelectorAll('.pdfed-hot').forEach(function (x) { x.classList.remove('on'); });
            if (hotEl) hotEl.classList.add('on');
            wrap.querySelectorAll('.pdfed-group').forEach(function (x) { x.classList.toggle('on', x === group); });
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.remove('flash');
                void row.offsetWidth;              // paksa animasi jalan lagi
                row.classList.add('flash');
            }
        }

        function values() {
            var o = {};
            wrap.querySelectorAll('.js-range').forEach(function (r) { o[r.dataset.key] = r.value; });
            return o;
        }

        function draw() {
            if (!doc) return;
            doc.getPage(page).then(function (p) {
                var box   = canvas.parentElement.clientWidth - 24;
                var vp1   = p.getViewport({ scale: 1 });
                var scale = Math.max(0.2, box / vp1.width);
                var vp    = p.getViewport({ scale: scale });
                canvas.width = vp.width; canvas.height = vp.height;
                p.render({ canvasContext: ctx, viewport: vp }).promise.then(function () {
                    buildHotspots(p, vp);
                });
                elNo.textContent = page + ' / ' + doc.numPages;
            });
        }

        function load() {
            var my = ++token;                      // buang hasil render yang keburu basi
            busy.classList.add('show');
            var q = new URLSearchParams(values()).toString();
            pdfjsLib.getDocument(PREVIEW_URL + tpl + '?' + q).promise.then(function (d) {
                if (my !== token) return;
                doc = d; page = Math.min(page, d.numPages);
                elCount.textContent = d.numPages + ' halaman';
                if (basePages === null) basePages = d.numPages;
                if (d.numPages > basePages) {
                    elWarn.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> bertambah ' + (d.numPages - basePages) + ' halaman</span>';
                } else if (d.numPages < basePages) {
                    elWarn.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> hemat ' + (basePages - d.numPages) + ' halaman</span>';
                } else { elWarn.textContent = ''; }
                draw();
            }).catch(function (e) {
                if (my !== token) return;
                elWarn.innerHTML = '<span class="text-danger">Gagal membuat preview</span>';
                console.error('[PDF Layout]', e);
            }).then(function () { if (my === token) busy.classList.remove('show'); });
        }

        function schedule() { clearTimeout(timer); timer = setTimeout(load, 450); }

        wrap.querySelectorAll('.js-range').forEach(function (r) {
            r.addEventListener('input', function () {
                r.closest('.pdfed-row').querySelector('.js-val').textContent = r.value;
                r.closest('.pdfed-row').querySelector('.js-hidden').value = r.value;
                dirty.classList.add('show');
                schedule();
            });
        });

        wrap.querySelectorAll('.pdfed-preset').forEach(function (b) {
            b.addEventListener('click', function () {
                var mode = b.dataset.preset;
                wrap.querySelectorAll('.js-range').forEach(function (r) {
                    var def = parseFloat(r.dataset.default), min = parseFloat(r.min), max = parseFloat(r.max), v = def;
                    if (mode === 'rapat') v = Math.max(min, def - Math.max(1, def * 0.25));
                    else if (mode === 'besar') v = Math.min(max, def + Math.max(1, def * 0.25));
                    r.value = v;
                    r.closest('.pdfed-row').querySelector('.js-val').textContent = r.value;
                    r.closest('.pdfed-row').querySelector('.js-hidden').value = r.value;
                });
                dirty.classList.add('show');
                schedule();
            });
        });

        // Klik judul kelompok: sorot kelompoknya & lepas sorotan di preview.
        wrap.querySelectorAll('.pdfed-group > .hd').forEach(function (hd) {
            hd.addEventListener('click', function () {
                wrap.querySelectorAll('.pdfed-hot').forEach(function (x) { x.classList.remove('on'); });
                wrap.querySelectorAll('.pdfed-group').forEach(function (x) { x.classList.toggle('on', x === hd.parentElement); });
            });
        });

        wrap.querySelector('.js-prev').addEventListener('click', function () { if (page > 1) { page--; draw(); } });
        wrap.querySelector('.js-next').addEventListener('click', function () { if (doc && page < doc.numPages) { page++; draw(); } });

        load();
    });
})();
</script>
