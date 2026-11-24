<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Approval PO -- daftar. SATU view untuk keempat tipe (redesign 2026-11-09).
 * Tanpa filter = antrian yang menunggu keputusan user (get_pending_approvals);
 * dengan filter status/cari = riwayat (_get_pos). Kedua sumber itu dulu beda
 * kolom (antrian tidak punya creator_name/currency_code, jadi "Dibuat Oleh"
 * kosong dan nilainya IDR) -- di sini disamakan. Approval General menampilkan
 * gabungan General/Jasa/Asset; tiap baris dibuka lewat controller approval
 * tipenya sendiri.
 */
$this->load->view('purchase_order/_po_style');

$appr     = $approval_route ?? 'purchase_order_approval';
$types    = $included_types ?? [$po_type ?? 'produksi'];
$multi    = count($types) > 1;
$cur_st   = $filters['status'] ?? '';
$search   = $filters['search'] ?? '';
$filtered = $cur_st !== '' || $search !== '';
$sc       = $status_counts ?? [];
$routes   = [
    'produksi'     => ['purchase_order_approval',         lang_line('pod2_type_produksi', 'Material Produksi'), 'tone-brand'],
    'non_produksi' => ['purchase_order_approval_general', lang_line('pod2_type_general', 'General'),            'tone-slate'],
    'jasa'         => ['purchase_order_approval_jasa',    lang_line('pod2_type_jasa', 'Jasa'),                  'tone-blue'],
    'asset'        => ['purchase_order_approval_asset',   lang_line('pod2_type_asset', 'Asset'),                'tone-amber'],
];
$kpis = [
    ''          => [lang_line('poa2_kpi_queue', 'Menunggu keputusan Anda'), 'fa-hourglass-half', 'tone-amber', (int) ($pending_count ?? 0)],
    'Confirmed' => [lang_line('poa2_kpi_approved', 'Disetujui'),             'fa-check-double',   'tone-green', $sc['Confirmed'] ?? 0],
    'Rejected'  => [lang_line('poa2_kpi_rejected', 'Ditolak'),               'fa-ban',            'tone-rose',  $sc['Rejected'] ?? 0],
    'Cancelled' => [lang_line('poa2_kpi_cancelled', 'Dibatalkan'),           'fa-archive',        'tone-slate', $sc['Cancelled'] ?? 0],
];
$pill = ['Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress', 'Endorsed' => 'pp-status--progress',
         'Confirmed' => 'pp-status--completed', 'Draft' => 'pp-status--draft', 'Closed' => 'pp-status--other',
         'Cancelled' => 'pod-status--bad', 'Rejected' => 'pod-status--bad'];
$tier = [];
if (!empty($can_hod))         $tier[] = 'Head of Dept';
if (!empty($can_endorsement)) $tier[] = 'Endorsement';
if (!empty($can_approval))    $tier[] = 'Approval';
$export_q = http_build_query(array_filter(['status' => $cur_st, 'search' => $search]));
?>
<style>
.poa2 #riwayat-approval-saya { border: 1px solid var(--pp-line) !important; border-radius: var(--pp-radius) !important; box-shadow: var(--pp-shadow) !important; background: var(--pp-card); overflow: hidden; margin-top: 0 !important; }
.poa2 #riwayat-approval-saya .card-header { background: var(--pp-card) !important; border-bottom: 1px solid var(--pp-line); padding: 1rem 1.25rem !important; }
.poa2 #riwayat-approval-saya .card-header h6 { color: var(--pp-text) !important; font-size: .98rem; }
.poa2 #riwayat-approval-saya .table thead th { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--pp-muted); background: var(--pp-soft); border-top: 0; padding: .65rem .9rem; }
.poa2 #riwayat-approval-saya .table td { padding: .6rem .9rem; font-size: .84rem; vertical-align: middle; }
</style>

<div class="container-fluid pp2 pod2 poa2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-clipboard-check mr-2" style="color:var(--brand-1)"></i><?= htmlspecialchars(($po_type_label ?? 'PO') . ' Approval'); ?></h1>
            <div class="small" style="color:var(--pp-muted)">
                <?= lang_line('poa2_sub', 'Periksa PO yang diajukan, lalu setujui, kembalikan ke Draft, atau tolak.'); ?>
                <?php if ($tier): ?> · <?= lang_line('poa2_your_access', 'Akses Anda'); ?>: <b><?= implode(' + ', $tier); ?></b><?php endif; ?>
            </div>
        </div>
        <div class="d-flex flex-wrap" style="gap:.4rem">
            <a href="<?= base_url($appr . '/export_list_excel') . ($export_q ? '?' . $export_q : ''); ?>" class="btn btn-light no-loader" target="_blank"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
            <a href="<?= base_url($appr . '/export_list_pdf') . ($export_q ? '?' . $export_q : ''); ?>" class="btn btn-light no-loader" target="_blank"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        </div>
    </div>

    <?php foreach (['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k => $kp): ?>
            <a href="<?= base_url($appr) . ($k !== '' ? '?status=' . urlencode($k) : ''); ?>" class="pp-kpi <?= $kp[2]; ?><?= ($k === '' ? !$filtered : ($cur_st === $k && $search === '')) ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $kp[1]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $kp[3]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($kp[0]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="GET" action="<?= base_url($appr); ?>" class="pp-toolbar w-100">
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('poa_search_placeholder', 'No PO, Supplier, Dibuat Oleh...')); ?>" value="<?= htmlspecialchars($search); ?>">
                </div>
                <select name="status" class="form-control" style="max-width:200px">
                    <option value=""><?= lang_line('poa_all_status', '-- Semua Status --'); ?></option>
                    <?php foreach (($statuses ?? []) as $s): ?><option value="<?= htmlspecialchars($s); ?>" <?= $cur_st === $s ? 'selected' : ''; ?>><?= htmlspecialchars($s); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('poa_filter_button', 'Filter'); ?></button>
                <?php if ($filtered): ?><a href="<?= base_url($appr); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('poa_reset_button', 'Reset'); ?></a><?php endif; ?>
            </form>
        </div>
        <div class="px-3 pt-3 pb-1 pp-item-code">
            <?php if (!$filtered): ?><i class="fas fa-hourglass-half mr-1"></i><?= lang_line('poa2_showing_queue', 'Menampilkan PO yang menunggu keputusan Anda.'); ?>
            <?php else: ?><i class="fas fa-filter mr-1"></i><?= lang_line('poa2_showing_filter', 'Menampilkan hasil filter (termasuk riwayat).'); ?><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('po_number', 'No. PO'); ?></th>
                    <?php if ($multi): ?><th><?= lang_line('po_kategori', 'Kategori'); ?></th><?php endif; ?>
                    <th><?= lang_line('po_supplier', 'Supplier'); ?></th>
                    <th><?= lang_line('poa_dibuat_oleh', 'Dibuat Oleh'); ?></th>
                    <th class="num"><?= lang_line('poa2_th_total', 'Total (termasuk PPN)'); ?></th>
                    <th><?= lang_line('label_status', 'Status'); ?></th>
                    <th class="text-right"></th>
                </tr></thead>
                <tbody>
                <?php if (empty($pos)): ?>
                    <tr><td colspan="<?= $multi ? 7 : 6; ?>" class="pp-empty"><i class="fas <?= $filtered ? 'fa-inbox' : 'fa-check-circle'; ?>"></i><?= $filtered ? lang_line('poa_no_data', 'Tidak ada data Purchase Order') : lang_line('poa2_queue_empty', 'Tidak ada PO yang menunggu keputusan Anda.'); ?></td></tr>
                <?php else: foreach ($pos as $po):
                    $rt      = $routes[$po->type ?? ($po_type ?? 'produksi')] ?? $routes['produksi'];
                    $url     = base_url($rt[0] . '/detail/' . $po->id);
                    $creator = $po->creator_name ?? ($po->created_by_name ?? null);
                    $pcur    = $po->currency_code ?? 'IDR';
                    $base    = isset($po->subtotal_base) ? $po->subtotal_base : $po->subtotal;
                    $total   = po_ppn_from_header($po, (float) $base)['total'];
                    $todo    = in_array($po->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true); ?>
                    <tr>
                        <td>
                            <a href="<?= $url; ?>" class="pp-list-title"><?= htmlspecialchars($po->po_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($po->po_date)); ?></div>
                        </td>
                        <?php if ($multi): ?><td><span class="pp-tag <?= $rt[2]; ?>"><?= htmlspecialchars($rt[1]); ?></span></td><?php endif; ?>
                        <td><?= htmlspecialchars($po->nama_supplier ?? '-'); ?></td>
                        <td><?= htmlspecialchars($creator ?: '-'); ?></td>
                        <td class="num"><small class="text-muted"><?= htmlspecialchars($pcur); ?></small> <b><?= number_format($total, 2, '.', ','); ?></b></td>
                        <td><span class="pp-status <?= $pill[$po->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($po->status); ?></span></td>
                        <td class="text-right">
                            <a href="<?= $url; ?>" class="btn btn-sm <?= $todo ? 'btn-pp' : 'btn-light'; ?>">
                                <?php if ($todo): ?><i class="fas fa-gavel mr-1"></i><?= lang_line('poa_review_button', 'Review'); ?><?php else: ?><i class="fas fa-eye mr-1"></i><?= lang_line('quality_assurance_detail_view', 'Detail'); ?><?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot"><span><b><?= count($pos ?? []); ?></b> PO</span></div>
    </div>

    <?php
        // Kartu "Riwayat Approval Saya" -- partial bersama semua halaman approval
        // (templates/my_approval_history.php); tampilannya disesuaikan lewat CSS di atas.
        if (isset($my_approval_history)) {
            $this->load->view('templates/my_approval_history', ['history' => $my_approval_history, 'route' => $my_history_route ?? '']);
        }
    ?>
</div>
