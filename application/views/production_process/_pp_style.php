<?php
/**
 * Gaya halaman Proses Produksi (redesign 2026-11-08, scope .pp2).
 * Bahasa visual sama dengan dashboard (.db2): kartu putih radius besar,
 * warna brand dari var(--brand-*) di templates/header.php, dark-mode aware.
 * Nada per tahap: cutting amber, sewing indigo, assembling sky, finishing green.
 */
?>
<style>
.pp2 {
    --pp-card: #ffffff; --pp-text: #1e293b; --pp-muted: #64748b; --pp-line: #e2e8f0; --pp-soft: #f8fafc;
    --pp-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .06);
    --pp-radius: 16px;
    color: var(--pp-text); padding-bottom: 1.5rem;
}
body.dark-mode .pp2 {
    --pp-card: #1b2330; --pp-text: #e5e7eb; --pp-muted: #94a3b8; --pp-line: #2c3646; --pp-soft: #151c27;
    --pp-shadow: 0 1px 2px rgba(0,0,0,.3), 0 10px 28px rgba(0,0,0,.35);
}
.pp2 .tone-cutting    { --t: #f59e0b; --t-soft: rgba(245,158,11,.14); }
.pp2 .tone-sewing     { --t: #6366f1; --t-soft: rgba(99,102,241,.13); }
.pp2 .tone-assembling { --t: #0ea5e9; --t-soft: rgba(14,165,233,.13); }
.pp2 .tone-finishing  { --t: #10b981; --t-soft: rgba(16,185,129,.13); }
.pp2 .tone-brand      { --t: var(--brand-1); --t-soft: rgba(var(--brand-rgb), .12); }
.pp2 .tone-slate      { --t: #64748b; --t-soft: rgba(100,116,139,.14); }
.pp2 .tone-amber      { --t: #f59e0b; --t-soft: rgba(245,158,11,.14); }
.pp2 .tone-blue       { --t: #3b82f6; --t-soft: rgba(59,130,246,.13); }
.pp2 .tone-green      { --t: #10b981; --t-soft: rgba(16,185,129,.13); }
.pp2 .tone-rose       { --t: #f43f5e; --t-soft: rgba(244,63,94,.12); }

/* ---------- kartu ---------- */
.pp-card { background: var(--pp-card); border: 1px solid var(--pp-line); border-radius: var(--pp-radius); box-shadow: var(--pp-shadow); margin-bottom: 1.25rem; }
.pp-card-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--pp-line); flex-wrap: wrap; }
.pp-card-head h2 { font-size: .98rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: .6rem; color: var(--pp-text); }
.pp-card-head h2 .pp-ico { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; font-size: .82rem; color: var(--t, var(--brand-1)); background: var(--t-soft, rgba(var(--brand-rgb), .12)); flex: 0 0 auto; }
.pp-card-head .pp-sub { font-size: .78rem; color: var(--pp-muted); font-weight: 500; }
.pp-card-body { padding: 1.15rem 1.25rem; }
.pp-card-body.p-0 { padding: 0; }

/* ---------- hero ---------- */
.pp-hero {
    position: relative; overflow: hidden; border-radius: 20px; margin-bottom: 1.25rem; padding: 1.5rem 1.75rem; color: #fff;
    background: radial-gradient(120% 140% at 0% 0%, var(--brand-2) 0%, var(--brand-1) 45%, var(--brand-dark) 100%);
    box-shadow: 0 16px 36px rgba(var(--brand-rgb), .26);
}
.pp-hero::after { content: ""; position: absolute; right: -60px; top: -60px; width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.06); pointer-events: none; }
.pp-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; position: relative; z-index: 1; }
.pp-hero-id { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.pp-hero-icon { width: 54px; height: 54px; border-radius: 16px; display: grid; place-items: center; font-size: 1.35rem; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); flex: 0 0 auto; }
.pp-hero-kicker { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; opacity: .75; font-weight: 600; }
.pp-hero-title { font-size: 1.45rem; font-weight: 800; margin: .1rem 0 .15rem; line-height: 1.2; color: #fff; word-break: break-word; }
.pp-hero-subline { font-size: .85rem; opacity: .85; }
.pp-hero-subline a { color: #fff; text-decoration: underline dotted; }
.pp-hero-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.pp-hero-actions .btn { border-radius: 10px; font-weight: 600; font-size: .82rem; padding: .45rem .85rem; }
.pp-btn-glass { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25); }
.pp-btn-glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.pp-btn-light { background: #fff; color: var(--brand-dark); border: 1px solid #fff; }
.pp-btn-light:hover { background: #f1f5f9; color: var(--brand-dark); }
.pp-hero-meta { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem; margin-top: 1.25rem; }
.pp-meta { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); border-radius: 12px; padding: .65rem .85rem; min-width: 0; }
.pp-meta-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; opacity: .7; font-weight: 600; }
.pp-meta-value { font-size: .92rem; font-weight: 700; margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pp-meta-value small { font-weight: 500; opacity: .75; }

/* ---------- status pill ---------- */
.pp-status { display: inline-flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 700; padding: .28rem .7rem; border-radius: 999px; letter-spacing: .02em; white-space: nowrap; }
.pp-status::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.pp-status--draft { background: rgba(245,158,11,.16); color: #b45309; }
.pp-status--progress { background: rgba(59,130,246,.15); color: #1d4ed8; }
.pp-status--completed { background: rgba(16,185,129,.15); color: #047857; }
.pp-status--other { background: rgba(100,116,139,.15); color: #475569; }
.pp-hero .pp-status { background: rgba(255,255,255,.95); }
body.dark-mode .pp-status--draft { color: #fbbf24; } body.dark-mode .pp-status--progress { color: #93c5fd; }
body.dark-mode .pp-status--completed { color: #6ee7b7; } body.dark-mode .pp-status--other { color: #cbd5e1; }
body.dark-mode .pp-hero .pp-status { background: rgba(15,23,42,.65); }

/* ---------- stepper tahap ---------- */
.pp-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1.25rem; counter-reset: ppstep; }
.pp-step { position: relative; display: flex; gap: .75rem; align-items: flex-start; padding: .9rem 1rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); color: var(--pp-text); text-decoration: none !important; transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; min-width: 0; }
.pp-step:hover { transform: translateY(-2px); color: var(--pp-text); border-color: var(--t); }
.pp-step.is-active { border-color: var(--t); box-shadow: 0 0 0 3px var(--t-soft), var(--pp-shadow); }
.pp-step-dot { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-weight: 800; font-size: .95rem; color: var(--t); background: var(--t-soft); flex: 0 0 auto; }
.pp-step.is-active .pp-step-dot { color: #fff; background: var(--t); }
.pp-step.is-done .pp-step-dot { color: #fff; background: #10b981; }
.pp-step-body { min-width: 0; flex: 1; }
.pp-step-name { font-weight: 700; font-size: .92rem; display: flex; align-items: center; gap: .4rem; }
.pp-step-name i { color: var(--t); font-size: .8rem; }
.pp-step-time { font-size: .74rem; color: var(--pp-muted); margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pp-step-time.is-empty { color: #d97706; font-weight: 600; }
.pp-step-stats { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
.pp-chip { display: inline-flex; align-items: center; gap: .25rem; font-size: .68rem; font-weight: 600; padding: .12rem .5rem; border-radius: 999px; background: var(--pp-soft); color: var(--pp-muted); border: 1px solid var(--pp-line); white-space: nowrap; }
.pp-chip--warn { background: rgba(245,158,11,.12); color: #b45309; border-color: transparent; }
.pp-chip--ok { background: rgba(16,185,129,.12); color: #047857; border-color: transparent; }
body.dark-mode .pp-chip--warn { color: #fbbf24; } body.dark-mode .pp-chip--ok { color: #6ee7b7; }
.pp-step-arrow { position: absolute; right: -.62rem; top: 50%; transform: translateY(-50%); z-index: 2; width: 1.1rem; height: 1.1rem; border-radius: 50%; background: var(--pp-card); border: 1px solid var(--pp-line); display: grid; place-items: center; font-size: .55rem; color: var(--pp-muted); }
@media (max-width: 991.98px) { .pp-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } .pp-step-arrow { display: none; } }
@media (max-width: 575.98px) { .pp-steps { grid-template-columns: 1fr; } }

/* ---------- waktu proses ---------- */
.pp-time { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; gap: 1rem; align-items: end; }
.pp-time-view { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; }
.pp-time-item .pp-label { margin-bottom: .1rem; }
.pp-time-value { font-size: 1.05rem; font-weight: 700; }
.pp-time-value.is-empty { color: var(--pp-muted); font-weight: 500; font-style: italic; font-size: .92rem; }
.pp-time-duration { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: 10px; background: var(--t-soft); color: var(--t); font-weight: 700; font-size: .82rem; }
.pp-time-note { font-size: .76rem; color: var(--pp-muted); margin-top: .75rem; display: flex; gap: .4rem; align-items: flex-start; }
@media (max-width: 767.98px) { .pp-time { grid-template-columns: 1fr; } }

/* ---------- form ---------- */
.pp2 .pp-label { font-size: .74rem; font-weight: 700; color: var(--pp-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: .35rem; display: block; }
.pp2 .form-control, .pp2 .custom-select { border-radius: 10px; border-color: var(--pp-line); background-color: var(--pp-card); color: var(--pp-text); font-size: .88rem; }
.pp2 .form-control:focus { border-color: var(--brand-2); box-shadow: 0 0 0 .18rem rgba(var(--brand-rgb), .15); }
.pp2 .form-control[readonly] { background: var(--pp-soft); }
.pp2 .form-group { margin-bottom: .9rem; }
.pp2 .pp-hint { font-size: .75rem; color: var(--pp-muted); margin-top: .3rem; display: block; }
.pp2 .pp-hint b { color: var(--pp-text); }
.pp2 .btn { border-radius: 10px; font-weight: 600; }
.pp2 .btn-pp, .pp-modal .btn-pp { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
.pp2 .btn-pp:hover, .pp-modal .btn-pp:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
.pp2 .btn-tone { background: var(--t); border-color: var(--t); color: #fff; }
.pp2 .btn-tone:hover { filter: brightness(.93); color: #fff; }
.pp2 .select2-container--bootstrap4 .select2-selection, .pp2 .select2-container--default .select2-selection--single { border-radius: 10px; border-color: var(--pp-line); min-height: calc(1.5em + .75rem + 2px); }

/* segmented tab (form input / output) */
.pp-seg { display: inline-flex; padding: 3px; border-radius: 11px; background: var(--pp-soft); border: 1px solid var(--pp-line); gap: 2px; flex-wrap: wrap; }
.pp-seg .nav-link { border: 0 !important; border-radius: 8px !important; padding: .35rem .8rem; font-size: .78rem; font-weight: 700; color: var(--pp-muted); background: transparent; }
.pp-seg .nav-link.active { background: var(--pp-card) !important; color: var(--pp-text) !important; box-shadow: 0 1px 3px rgba(15,23,42,.12); }
.pp-seg .nav-link i { margin-right: .3rem; }

/* kunci form kalau waktu mulai tahap belum diisi */
.pp-locked { display: flex; gap: .85rem; align-items: center; padding: 1rem 1.1rem; border-radius: 12px; background: rgba(245,158,11,.09); border: 1px dashed rgba(245,158,11,.55); color: #92400e; font-size: .85rem; }
.pp-locked i { font-size: 1.25rem; color: #f59e0b; }
body.dark-mode .pp-locked { color: #fcd34d; }
.pp-readonly { display: flex; gap: .7rem; align-items: center; padding: .85rem 1rem; border-radius: 12px; background: var(--pp-soft); border: 1px solid var(--pp-line); color: var(--pp-muted); font-size: .84rem; }

/* ---------- tabel ---------- */
.pp-table { width: 100%; margin: 0; font-size: .84rem; color: var(--pp-text); }
.pp-table thead th { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--pp-muted); font-weight: 700; background: var(--pp-soft); border-bottom: 1px solid var(--pp-line); border-top: 0; padding: .65rem .9rem; white-space: nowrap; }
.pp-table td { padding: .7rem .9rem; border-top: 1px solid var(--pp-line); vertical-align: middle; }
.pp-table tbody tr:hover td { background: rgba(var(--brand-rgb), .03); }
.pp-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.pp-item-name { font-weight: 600; }
.pp-item-code { font-size: .72rem; color: var(--pp-muted); }
.pp-empty { text-align: center; padding: 2rem 1rem !important; color: var(--pp-muted); }
.pp-empty i { font-size: 1.6rem; opacity: .35; display: block; margin-bottom: .4rem; }
.pp-tag { display: inline-block; font-size: .68rem; font-weight: 700; padding: .15rem .55rem; border-radius: 6px; background: var(--t-soft); color: var(--t); text-transform: capitalize; white-space: nowrap; }
.pp-actions { display: inline-flex; gap: .3rem; flex-wrap: nowrap; justify-content: flex-end; }
.pp-actions .btn { padding: .22rem .5rem; font-size: .72rem; border-radius: 8px; }
.pp-foot { display: flex; justify-content: space-between; gap: 1rem; padding: .7rem 1rem; border-top: 1px solid var(--pp-line); background: var(--pp-soft); font-size: .78rem; color: var(--pp-muted); border-radius: 0 0 var(--pp-radius) var(--pp-radius); flex-wrap: wrap; }
.pp-foot b { color: var(--pp-text); }

/* ---------- flash ---------- */
.pp-flash { display: flex; gap: .7rem; align-items: flex-start; padding: .8rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .87rem; border: 1px solid transparent; }
.pp-flash i { margin-top: .15rem; }
.pp-flash--success { background: rgba(16,185,129,.1); color: #065f46; border-color: rgba(16,185,129,.25); }
.pp-flash--error { background: rgba(244,63,94,.09); color: #9f1239; border-color: rgba(244,63,94,.25); }
.pp-flash--warning { background: rgba(245,158,11,.1); color: #92400e; border-color: rgba(245,158,11,.3); }
body.dark-mode .pp-flash--success { color: #6ee7b7; } body.dark-mode .pp-flash--error { color: #fda4af; } body.dark-mode .pp-flash--warning { color: #fcd34d; }
.pp-flash .close { margin-left: auto; color: inherit; opacity: .6; font-size: 1.2rem; line-height: 1; }

/* ---------- modal ---------- */
.pp-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.25); }
.pp-modal .modal-header { background: linear-gradient(135deg, var(--brand-2), var(--brand-dark)); color: #fff; border: 0; padding: 1rem 1.35rem; }
.pp-modal .modal-header .close { color: #fff; opacity: .8; text-shadow: none; }
.pp-modal .modal-title { font-weight: 700; font-size: 1.02rem; }
.pp-modal .modal-body { padding: 1.35rem; }
.pp-modal .modal-footer { border-top: 1px solid #e2e8f0; padding: .85rem 1.35rem; }
.pp-modal .pp-label { font-size: .74rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .35rem; display: block; }
.pp-modal .pp-sect { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin: 1.1rem 0 .6rem; }
.pp-kv { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; }
.pp-kv > div { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: .65rem .85rem; }
.pp-kv .v { font-weight: 700; font-size: .98rem; }
body.dark-mode .pp-kv > div { background: #151c27; border-color: #2c3646; }

/* ---------- daftar produksi ---------- */
.pp-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; margin-bottom: 1.25rem; }
.pp-kpi { display: flex; align-items: center; gap: .85rem; padding: 1rem 1.1rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); color: var(--pp-text); text-decoration: none !important; transition: border-color .15s, transform .15s; }
.pp-kpi:hover { transform: translateY(-2px); color: var(--pp-text); border-color: var(--t); }
.pp-kpi.is-active { border-color: var(--t); box-shadow: 0 0 0 3px var(--t-soft), var(--pp-shadow); }
.pp-kpi-ico { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; color: var(--t); background: var(--t-soft); font-size: 1rem; flex: 0 0 auto; }
.pp-kpi-num { font-size: 1.45rem; font-weight: 800; line-height: 1; }
.pp-kpi-label { font-size: .74rem; color: var(--pp-muted); font-weight: 600; margin-top: .2rem; }
@media (max-width: 767.98px) { .pp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.pp-mini { display: flex; align-items: center; gap: 0; }
.pp-mini-dot { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-size: .62rem; font-weight: 800; color: var(--pp-muted); background: var(--pp-soft); border: 1px solid var(--pp-line); position: relative; }
.pp-mini-dot.has-data { color: var(--t); background: var(--t-soft); border-color: transparent; }
.pp-mini-dot.is-done { color: #fff; background: #10b981; border-color: transparent; }
.pp-mini-line { width: 10px; height: 2px; background: var(--pp-line); }
.pp-list-title { font-weight: 700; color: var(--pp-text); }
.pp-list-title:hover { color: var(--brand-1); }
.pp-toolbar { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }
.pp-search { position: relative; flex: 1 1 260px; }
.pp-search i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--pp-muted); font-size: .8rem; }
.pp-search .form-control { padding-left: 2.2rem; }
/* select2 di dark mode (tema bootstrap4 bawaannya terang) */
body.dark-mode .pp2 .select2-container--bootstrap4 .select2-selection { background: #1b2330; border-color: #2c3646; }
body.dark-mode .pp2 .select2-container--bootstrap4 .select2-selection__rendered { color: #e5e7eb; }
body.dark-mode .select2-container--bootstrap4 .select2-dropdown { background: #1b2330; border-color: #2c3646; color: #e5e7eb; }
body.dark-mode .select2-container--bootstrap4 .select2-search__field { background: #151c27; color: #e5e7eb; border-color: #2c3646; }
</style>
