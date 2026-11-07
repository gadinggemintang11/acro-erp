<?php
/**
 * Pemilihan item di dalam modal "Buat PO" — dipakai bersama oleh 8 view PR
 * (4 varian × halaman detail + halaman approval).
 *
 * Butuh dari view pemanggil:
 *   $details     array  baris PR (hasil Purchase_request_model::get_details_by_id)
 *   $po_progress array  pr_detail_id => ['ordered'=>float, 'pos'=>[...]] (boleh kosong)
 *
 * "Sisa" = qty PR dikurangi yang sudah masuk PO hidup. Angka di sini cuma
 * ancang-ancang untuk user; saat disimpan, Po_model::pull_pr_items() menghitung
 * ulang sisanya dari DB, jadi qty di form ini tidak bisa dipakai over-order.
 */
$details     = $details     ?? [];
$po_progress = $po_progress ?? [];
$eps         = 0.005;
?>
<div class="form-group">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="mb-0">Item yang dimasukkan ke PO <span class="text-danger">*</span></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="poItemCheckAll" checked>
            <label class="custom-control-label small text-muted" for="poItemCheckAll">pilih semua</label>
        </div>
    </div>

    <div class="table-responsive border rounded" style="max-height:38vh; overflow-y:auto;">
        <table class="table table-sm table-hover mb-0 small">
            <thead class="thead-light" style="position:sticky; top:0; z-index:2;">
                <tr class="text-center">
                    <th style="width:34px;"></th>
                    <th class="text-left">Item</th>
                    <th style="width:70px;">Qty PR</th>
                    <th style="width:82px;">Sudah PO</th>
                    <th style="width:70px;">Sisa</th>
                    <th style="width:104px;">Qty ke PO</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($details)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">PR ini belum punya item.</td></tr>
            <?php else: ?>
                <?php foreach ($details as $d): ?>
                    <?php
                    $ordered   = (float) ($po_progress[$d->id]['ordered'] ?? 0);
                    $remaining = (float) $d->qty - $ordered;
                    $habis     = $remaining <= $eps;

                    $po_titles = [];
                    foreach (($po_progress[$d->id]['pos'] ?? []) as $p) {
                        $po_titles[] = $p['po_number'] . ' (' . number_format($p['qty'], 2)
                            . (in_array($p['status'], ['Cancelled', 'Rejected'], true) ? ' — ' . $p['status'] . ', dilepas' : '')
                            . ')';
                    }
                    ?>
                    <tr class="<?= $habis ? 'bg-light text-muted' : '' ?>">
                        <td class="text-center align-middle">
                            <input type="checkbox" class="po-item-check" name="pr_detail_ids[]"
                                   value="<?= (int) $d->id ?>" <?= $habis ? 'disabled' : 'checked' ?>>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($d->item_code ?? '-') ?></code>
                            <?= htmlspecialchars($d->item_name ?? '-') ?>
                            <?php if (!empty($d->description)): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($d->description) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right align-middle"><?= number_format((float) $d->qty, 2) ?></td>
                        <td class="text-right align-middle <?= $ordered > 0 ? 'text-warning font-weight-bold' : '' ?>"
                            <?= $po_titles ? 'title="' . htmlspecialchars(implode(', ', $po_titles)) . '"' : '' ?>>
                            <?= number_format($ordered, 2) ?>
                        </td>
                        <td class="text-right align-middle font-weight-bold"><?= number_format(max(0, $remaining), 2) ?></td>
                        <td class="align-middle">
                            <?php if ($habis): ?>
                                <span class="badge badge-secondary">habis</span>
                            <?php else: ?>
                                <input type="number" class="form-control form-control-sm text-right po-item-qty"
                                       name="pull_qty[<?= (int) $d->id ?>]" step="0.01" min="0.01"
                                       max="<?= $remaining ?>" value="<?= $remaining ?>">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <small class="text-muted d-block mt-1">
        Kolom <strong>Sisa</strong> = qty PR dikurangi yang sudah masuk PO lain. Qty boleh dikurangi
        untuk memesan sebagian &mdash; sisanya tetap bisa ditarik ke PO berikutnya, termasuk dari
        halaman PO lewat tombol <em>Tarik dari PR</em>.
    </small>
</div>

<script>
$(function () {
    // Tombol submit baru aktif kalau supplier sudah dipilih DAN minimal satu item
    // dicentang. Handler supplier bawaan halaman tetap jalan; kita jadwalkan
    // evaluasi di setTimeout supaya keputusan kita yang terakhir dipakai.
    function refreshCreatePoState() {
        var $checked  = $('#createPoModal .po-item-check:checked');
        var hasSupp   = !!$('#supplierSelect').val();

        $('#createPoModal .po-item-check').each(function () {
            $(this).closest('tr').find('.po-item-qty').prop('disabled', !this.checked);
        });

        $('#submitCreatePo').prop('disabled', !(hasSupp && $checked.length > 0));
    }

    $(document).on('change', '#supplierSelect, #createPoModal .po-item-check', function () {
        setTimeout(refreshCreatePoState, 0);
    });

    $('#poItemCheckAll').on('change', function () {
        $('#createPoModal .po-item-check:not(:disabled)').prop('checked', this.checked);
        refreshCreatePoState();
    });

    // Jaga qty tidak melebihi sisa di sisi klien juga (server tetap yang final).
    $(document).on('change', '#createPoModal .po-item-qty', function () {
        var max = parseFloat($(this).attr('max')) || 0;
        var val = parseFloat(this.value) || 0;
        if (val > max || val <= 0) this.value = max;
    });

    $('#createPoModal').on('shown.bs.modal', refreshCreatePoState);

    $('#createPoForm').on('submit', function (e) {
        if ($('#createPoModal .po-item-check:checked').length === 0) {
            e.preventDefault();
            alert('Pilih minimal satu item untuk dimasukkan ke PO.');
        }
    });

    refreshCreatePoState();
});
</script>
