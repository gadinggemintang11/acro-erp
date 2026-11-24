<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Gaya halaman detail Purchase Order & Approval PO (redesign 2026-11-09).
 * Dipakai purchase_order/detail.php dan purchase_order_approval/{index,detail}.php,
 * di atas production_process/_pp_style (.pp2). Scope: .pod2 / .pod2-modal.
 */
$this->load->view('production_process/_pp_style');
?>
<style>
.pod2 .pp-hero { overflow: visible; }
.pod2 .pp-hero::after { right: 0; top: 0; width: 190px; height: 190px; border-radius: 0 20px 0 100%; }
.pod2 .pp-hero-top { z-index: 3; }
.pod2 .pp-hero .dropdown-menu { border: 0; border-radius: 12px; box-shadow: 0 18px 40px rgba(15,23,42,.22); padding: .4rem; min-width: 230px; }
.pod2 .pp-hero .dropdown-item { border-radius: 8px; font-size: .84rem; font-weight: 600; padding: .5rem .7rem; display: flex; align-items: center; gap: .6rem; }
.pod2 .pp-hero .dropdown-item i { width: 1rem; text-align: center; opacity: .8; }
.pod-status--bad { background: rgba(244,63,94,.14); color: #be123c; }
body.dark-mode .pod-status--bad { color: #fda4af; }
.pod2 .pp-meta-value { white-space: normal; overflow-wrap: anywhere; }
.pod2 .pp-meta-value.is-total { font-size: 1rem; }

.pod2 .pod-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .6rem; margin-bottom: 1.25rem; }
.pod2 .pod-step { display: flex; gap: .65rem; padding: .8rem .9rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); min-width: 0; }
.pod2 .pod-step-dot { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; font-size: .82rem; background: var(--pp-soft); color: var(--pp-muted); border: 1px solid var(--pp-line); }
.pod2 .pod-step.is-done .pod-step-dot { background: #10b981; color: #fff; border-color: transparent; }
.pod2 .pod-step.is-current { border-color: var(--brand-1); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12), var(--pp-shadow); }
.pod2 .pod-step.is-current .pod-step-dot { background: var(--brand-1); color: #fff; border-color: transparent; }
.pod2 .pod-step.is-partial { border-color: #f59e0b; }
.pod2 .pod-step.is-partial .pod-step-dot { background: #f59e0b; color: #fff; border-color: transparent; }
.pod2 .pod-step.is-rejected { border-color: #f43f5e; }
.pod2 .pod-step.is-rejected .pod-step-dot { background: #f43f5e; color: #fff; border-color: transparent; }
.pod2 .pod-step-name { font-weight: 700; font-size: .84rem; }
.pod2 .pod-step-meta { font-size: .72rem; color: var(--pp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 991.98px) { .pod2 .pod-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.pod2 .pod-note { display: flex; gap: .75rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .86rem; border: 1px solid transparent; }
.pod2 .pod-note > i { margin-top: .2rem; }
.pod2 .pod-note small { opacity: .85; }
.pod2 .pod-note--warn { background: rgba(245,158,11,.1); color: #92400e; border-color: rgba(245,158,11,.3); }
.pod2 .pod-note--bad { background: rgba(244,63,94,.08); color: #9f1239; border-color: rgba(244,63,94,.25); }
.pod2 .pod-note--info { background: rgba(59,130,246,.08); color: #1e40af; border-color: rgba(59,130,246,.22); }
.pod2 .pod-note--admin { background: rgba(var(--brand-rgb), .06); color: var(--pp-text); border-color: rgba(var(--brand-rgb), .25); border-style: dashed; }
body.dark-mode .pod2 .pod-note--warn { color: #fcd34d; } body.dark-mode .pod2 .pod-note--bad { color: #fda4af; } body.dark-mode .pod2 .pod-note--info { color: #93c5fd; }

.pod2 .pod-items td { vertical-align: top; }
.pod2 .pod-items .pod-no { width: 42px; color: var(--pp-muted); font-weight: 700; font-size: .76rem; }
.pod2 .pod-items .pod-meta { display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; margin-top: .25rem; }
.pod2 .pod-items .pod-desc { font-size: .76rem; color: var(--pp-muted); margin-top: .3rem; word-break: break-word; }
.pod2 .pod-items .pod-unit { color: var(--pp-muted); font-size: .78rem; white-space: nowrap; }
.pod2 .pod-items a.pp-chip:hover { border-color: var(--brand-1); color: var(--brand-1); text-decoration: none; }
.pod2 .pod-items td.price-zero .display-val { color: #dc2626; font-weight: 700; }
.pod2 .pod-items tr.price-zero-row td { background: rgba(245,158,11,.06); }
.pod2 .pod-items tr.editing td { background: rgba(var(--brand-rgb), .05) !important; }
.pod2 .pod-items .edit-input, .pod2 .pod-items .new-item-row .form-control { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .82rem; border-radius: 8px; }
.pod2 .pod-items .cell-qty .edit-input { width: 100px; margin-left: auto; }
.pod2 .pod-items .cell-price .edit-input { width: 130px; margin-left: auto; }
.pod2 .pod-items .cell-desc .edit-input { margin-top: .35rem; }
.pod2 .pod-items tfoot td { background: var(--pp-soft); border-top: 1px solid var(--pp-line); padding: .5rem .9rem; font-size: .84rem; }
.pod2 .pod-items tfoot tr.pod-grand td { font-size: 1rem; font-weight: 800; border-top: 2px solid var(--pp-line); }
.pod2 .pod-items tfoot tr.pod-idr td { color: var(--pp-muted); font-size: .8rem; }
.pod2 .pod-prog { height: 5px; border-radius: 99px; background: var(--pp-line); overflow: hidden; margin-top: .3rem; width: 90px; margin-left: auto; }
.pod2 .pod-prog > span { display: block; height: 100%; background: #10b981; }
.pod2 .pod-prog.is-partial > span { background: #f59e0b; }
.pod2 .pp-actions .btn { padding: .25rem .5rem; }
.pod2 .pod-bom { display: none; padding: .9rem 1.25rem; border-bottom: 1px solid var(--pp-line); background: var(--pp-soft); }
.pod2 .pod-bom.is-open { display: block; }
.pod2 .new-item-row td { background: rgba(var(--brand-rgb), .04); }
.pod2 .select2-container--default .select2-selection--single { height: calc(1.5em + .5rem + 2px); border: 1px solid var(--pp-line); border-radius: 8px; background: var(--pp-card); }
.pod2 .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.9; font-size: .82rem; color: var(--pp-text); }
.pod2 .select2-container--default .select2-selection--single .select2-selection__arrow { height: 100%; }

.pod2 .pod-kv { display: grid; grid-template-columns: 130px minmax(0, 1fr); gap: .55rem 1rem; font-size: .85rem; margin: 0; }
.pod2 .pod-kv dt { color: var(--pp-muted); font-weight: 600; font-size: .78rem; }
.pod2 .pod-kv dd { margin: 0; word-break: break-word; }
.pod2 .pod-sep { border-top: 1px dashed var(--pp-line); margin: .9rem 0; }
.pod2 .pod-remarks { font-size: .85rem; white-space: pre-line; background: var(--pp-soft); border: 1px solid var(--pp-line); border-radius: 10px; padding: .65rem .8rem; }

.pod2 .pod-tl { list-style: none; margin: 0; padding: 0; }
.pod2 .pod-tl > li { position: relative; display: flex; gap: .75rem; padding-bottom: 1rem; }
.pod2 .pod-tl > li:not(:last-child)::before { content: ""; position: absolute; left: 15px; top: 34px; bottom: 2px; width: 2px; background: var(--pp-line); }
.pod2 .pod-tl-dot { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; font-size: .78rem; flex: 0 0 auto; color: var(--t); background: var(--t-soft); }
.pod2 .pod-tl-title { font-weight: 700; font-size: .84rem; }
.pod2 .pod-tl-meta { font-size: .74rem; color: var(--pp-muted); }
.pod2 .pod-tl-note { margin-top: .35rem; font-size: .8rem; padding: .45rem .65rem; border-radius: 10px; background: var(--pp-soft); border: 1px solid var(--pp-line); word-break: break-word; }
.pod2 .pod-tl-reply .pod-tl-note { background: rgba(var(--brand-rgb), .06); }

.pod2 .pod-docs { list-style: none; margin: 0; padding: 0; }
.pod2 .pod-docs li { display: flex; align-items: center; gap: .8rem; padding: .7rem 1.25rem; border-top: 1px solid var(--pp-line); }
.pod2 .pod-docs li:first-child { border-top: 0; }
.pod2 .pod-doc-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; font-size: .8rem; color: var(--t); background: var(--t-soft); flex: 0 0 auto; }
.pod2 .pod-doc-title { font-weight: 700; font-size: .85rem; color: var(--pp-text); }
.pod2 a.pod-doc-title:hover { color: var(--brand-1); }
.pod2 .pod-doc-meta { font-size: .74rem; color: var(--pp-muted); }

.pod2 .pod-recv-table td, .pod2 .pod-recv-table th { white-space: nowrap; }
.pod2 .pod-recv-table .form-control { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .82rem; border-radius: 8px; min-width: 90px; }
.pod2 .pod-recv-table tr.is-off td { opacity: .5; }

.modal-content.pp2 { padding-bottom: 0; }
.pod2-modal .select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .3rem .6rem; border: 1px solid var(--pp-line); border-radius: 10px; background: var(--pp-card); }
.pod2-modal .select2-container--default .select2-selection--single .select2-selection__arrow { height: 100%; }
.pod2-modal .select2-container--default .select2-selection--single .select2-selection__rendered { color: var(--pp-text); line-height: 1.6; padding-left: 0; }
.pod2-modal .pod-ppn { border: 1px solid var(--pp-line); border-radius: 12px; padding: .8rem .9rem; background: var(--pp-soft); }
body.dark-mode .select2-dropdown { background: #1b2330; color: #e5e7eb; border-color: #2c3646; }
input[type=number].no-spin::-webkit-inner-spin-button, input[type=number].no-spin::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number].no-spin { -moz-appearance: textfield; }
</style>
