<?php
/**
 * Kartu "Item Payment Request" -- dipakai bersama prf/create & prf/edit (2026-11-06).
 *
 * Jenis baris (data-kind):
 *   manual : item ketik sendiri (Payment Request MANUAL, jurnal manual via Jurnal Umum)
 *   po     : ditarik dari PO -- qty maks = barang yang sudah datang & belum ditagih,
 *            harga dikunci ke harga PO (tanpa PPN)
 *   ap     : ditarik dari AP Invoice -- nominal maks = sisa AP yang belum dibayar
 *   ppn    : baris PPN otomatis per PO (dihitung ulang server)
 * Baris po/ap menjadikan Payment Request ini HUTANG: AP Invoice + jurnal dibuat
 * otomatis saat approve, jurnal bayar saat Record Payment (Ap_auto_model).
 *
 * Variabel: $details (array object, boleh kosong), $satuan_list, $line_po_meta, $line_ap_meta
 */
$details      = $details ?? [];
$line_po_meta = $line_po_meta ?? [];
$line_ap_meta = $line_ap_meta ?? [];

$unit_options = function ($selected) use ($satuan_list) {
    $h = '<option value="">-</option>';
    foreach (($satuan_list ?? []) as $s) {
        $h .= '<option value="' . htmlspecialchars($s->code) . '"' . ((string) $s->code === (string) $selected ? ' selected' : '') . '>'
            . htmlspecialchars($s->description) . '</option>';
    }
    return $h;
};
$fmt_in = function ($v) { return number_format((float) $v, 2, ',', ''); };
?>
<div class="card mb-4" id="prf-items-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
        <h5 class="m-0"><?= lang_line('prf_section_items', 'Item Payment Request'); ?></h5>
        <div class="d-flex flex-wrap" style="gap:.4rem">
            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#pullPoModal">
                <i class="fas fa-download"></i> <?= lang_line('prf_btn_pull_po', 'Tarik dari PO'); ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#pullApModal">
                <i class="fas fa-file-invoice-dollar"></i> <?= lang_line('prf_btn_pull_ap', 'Tarik dari AP Invoice'); ?>
            </button>
        </div>
    </div>

    <div class="card-body">
        <div id="prfHutangInfo" class="alert alert-info py-2 small" style="display:none">
            <i class="fas fa-link mr-1"></i>
            <?= lang_line('prf_hutang_info', '<strong>Payment Request hutang</strong> — terhubung ke PO / AP Invoice. Saat di-approve, sistem otomatis mencatat AP Invoice + jurnal hutang (kecuali baris yang ditarik dari AP Invoice, karena sudah dijurnal). Saat Record Payment, jurnal pembayaran dibuat otomatis. Harga mengikuti PO, qty maksimal = barang yang sudah datang dan belum ditagih, dan tidak bisa dicampur item manual.'); ?>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="detail-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;"><?= lang_line('prf_th_item_no', 'No'); ?></th>
                        <th><?= lang_line('prf_th_item_desc', 'Deskripsi Item'); ?> *</th>
                        <th style="width: 12%;"><?= lang_line('prf_th_qty', 'Qty'); ?> *</th>
                        <th style="width: 12%;"><?= lang_line('prf_th_unit', 'Unit'); ?></th>
                        <th style="width: 18%;"><?= lang_line('prf_th_unit_price', 'Harga Satuan'); ?> *</th>
                        <th style="width: 18%;"><?= lang_line('prf_th_subtotal', 'Subtotal'); ?></th>
                        <th style="width: 7%;"><?= lang_line('prf_th_action_col', 'Aksi'); ?></th>
                    </tr>
                </thead>
                <tbody id="detail-body">
                    <?php $idx = 0; foreach ($details as $d):
                        $kind = ($d->line_type ?? 'item') === 'ppn' ? 'ppn'
                              : (!empty($d->po_detail_id) ? 'po' : (!empty($d->ap_invoice_header_id) ? 'ap' : 'manual'));
                        $pm = $kind === 'po' ? ($line_po_meta[(int) $d->po_detail_id] ?? null) : null;
                        $am = $kind === 'ap' ? ($line_ap_meta[(int) $d->ap_invoice_header_id] ?? null) : null;
                        $max = $kind === 'po' ? ($pm['max'] ?? (float) $d->qty) : ($kind === 'ap' ? ($am['max'] ?? (float) $d->unit_price) : '');
                        $ro_price = in_array($kind, ['po', 'ppn'], true);
                        $ro_qty   = in_array($kind, ['ap', 'ppn'], true);
                    ?>
                    <tr class="detail-row<?= $kind === 'ppn' ? ' table-light' : ''; ?>" data-idx="<?= $idx; ?>" data-kind="<?= $kind; ?>"
                        data-po-id="<?= (int) ($d->po_header_id ?? 0); ?>"
                        data-po-detail-id="<?= (int) ($d->po_detail_id ?? 0); ?>"
                        data-ap-id="<?= (int) ($d->ap_invoice_header_id ?? 0); ?>"
                        data-max="<?= htmlspecialchars((string) $max); ?>"
                        data-local-price="<?= htmlspecialchars((string) ($pm['local_price'] ?? $d->unit_price)); ?>">
                        <td class="row-number"><?= $idx + 1; ?></td>
                        <td>
                            <input type="text" name="details[<?= $idx; ?>][description]" class="form-control desc-input" value="<?= htmlspecialchars($d->description); ?>" <?= $kind === 'ppn' ? 'readonly' : 'required'; ?>>
                            <input type="hidden" name="details[<?= $idx; ?>][po_header_id]" value="<?= htmlspecialchars((string) ($d->po_header_id ?? '')); ?>">
                            <input type="hidden" name="details[<?= $idx; ?>][po_detail_id]" value="<?= htmlspecialchars((string) ($d->po_detail_id ?? '')); ?>">
                            <input type="hidden" name="details[<?= $idx; ?>][ap_invoice_header_id]" value="<?= htmlspecialchars((string) ($d->ap_invoice_header_id ?? '')); ?>">
                            <input type="hidden" name="details[<?= $idx; ?>][line_type]" value="<?= $kind === 'ppn' ? 'ppn' : 'item'; ?>">
                            <small class="text-muted row-hint">
                                <?php if ($kind === 'po'): ?>
                                    <i class="fas fa-file-invoice"></i> PO <?= htmlspecialchars($d->source_po_number ?? ($pm['po_number'] ?? '')); ?> · <?= lang_line('prf_hint_max_qty', 'maks'); ?> <?= rtrim(rtrim(number_format((float) $max, 2, ',', '.'), '0'), ','); ?>
                                <?php elseif ($kind === 'ap'): ?>
                                    <i class="fas fa-file-invoice-dollar"></i> AP <?= htmlspecialchars($d->source_ap_number ?? ($am['invoice_no'] ?? '')); ?> · <?= lang_line('prf_hint_max_amount', 'sisa'); ?> Rp <?= number_format((float) $max, 2, ',', '.'); ?>
                                <?php elseif ($kind === 'ppn'): ?>
                                    <i class="fas fa-percent"></i> <?= lang_line('prf_hint_ppn_auto', 'PPN otomatis dari PO'); ?>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td><input type="text" name="details[<?= $idx; ?>][qty]" class="form-control qty-input" inputmode="decimal" value="<?= $fmt_in($d->qty); ?>" <?= $ro_qty ? 'readonly' : 'required'; ?>></td>
                        <td>
                            <?php if ($kind === 'ppn' || $kind === 'ap'): ?>
                                <input type="hidden" name="details[<?= $idx; ?>][unit]" value=""><span class="text-muted">-</span>
                            <?php else: ?>
                                <select name="details[<?= $idx; ?>][unit]" class="form-control unit-select"><?= $unit_options($d->unit ?? ''); ?></select>
                            <?php endif; ?>
                        </td>
                        <td><input type="text" name="details[<?= $idx; ?>][unit_price]" class="form-control price-input" inputmode="decimal" value="<?= $fmt_in($d->unit_price); ?>" <?= $ro_price ? 'readonly' : 'required'; ?>></td>
                        <td class="subtotal-cell text-right"><?= number_format((float) $d->qty * (float) $d->unit_price, 2, ',', '.'); ?></td>
                        <td><?php if ($kind !== 'ppn'): ?><button type="button" class="btn btn-sm btn-danger" onclick="deleteRow(this)"><i class="fas fa-trash"></i></button><?php endif; ?></td>
                    </tr>
                    <?php $idx++; endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light font-weight-bold">
                        <td colspan="5" class="text-right"><?= lang_line('prf_total', 'TOTAL'); ?>:</td>
                        <td class="text-right">Rp <span id="total-amount">0,00</span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-2" id="manualAddWrap">
            <button type="button" class="btn btn-sm btn-success" onclick="addRow()">
                <i class="fas fa-plus"></i> <?= lang_line('prf_btn_add_item', 'Tambah Item'); ?>
            </button>
        </div>
        <select id="prfUnitTemplate" class="d-none" tabindex="-1" aria-hidden="true"><?= $unit_options(''); ?></select>
    </div>
</div>
