<?php
/**
 * Pemetaan Akun Barang -- akun DEBIT jurnal hutang otomatis (2026-11-06).
 *
 * Dipakai saat Payment Request dari PO di-approve (AP Invoice + jurnal dibuat
 * otomatis) dan sebagai saran akun di AP Invoice berbasis PO / GRN.
 * Urutan pencarian akun: pengecualian per barang -> akun di Master Aset
 * (khusus aset) -> per kategori -> default per jenis barang.
 */
$map      = $item_map ?? ['default' => [], 'category' => [], 'item' => []];
$types    = $item_types ?? [];
$cats     = $item_categories ?? [];
$can_edit = !empty($can_edit);

$acc_options = function ($selected) use ($accounts) {
    $h = '<option value="">' . lang_line('sa_map_none', '-- belum dipetakan --') . '</option>';
    foreach (($accounts ?? []) as $a) {
        $h .= '<option value="' . (int) $a->id . '"' . ((int) $selected === (int) $a->id ? ' selected' : '') . '>'
            . htmlspecialchars($a->account_code . ' - ' . $a->account_name) . '</option>';
    }
    return $h;
};
?>
<div class="row" id="item-map">
    <div class="col-lg-12">
        <div class="card modern-card mb-4">
            <div class="modern-card-header">
                <h6 class="modern-card-title"><i class="fas fa-boxes mr-2"></i><?= lang_line('sa_map_title', 'Pemetaan Akun Barang (Jurnal Hutang Otomatis)'); ?></h6>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <i class="fas fa-lightbulb"></i>
                    <p><?= lang_line('sa_map_info', 'Saat <strong>Payment Request dari PO</strong> di-approve, sistem otomatis membuat AP Invoice + jurnal hutang: <strong>Dr akun di bawah</strong> (Persediaan / Beban / Aset) + Dr PPN Masukan, <strong>Kr Utang Usaha</strong>. Akun dicari berurutan: <strong>pengecualian per barang</strong> → akun di Master Aset (khusus aset) → <strong>per kategori</strong> → <strong>default per jenis</strong>. Kalau tidak ketemu, approval Payment Request ditolak sampai akunnya diisi di sini. Pembuat Payment Request tidak perlu memilih akun.'); ?></p>
                </div>

                <form action="<?= base_url('settingakun/save_item_map'); ?>" method="POST">
                    <ul class="nav nav-tabs" role="tablist">
                        <?php $first = true; foreach ($types as $t => $meta): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $first ? 'active' : ''; ?>" data-toggle="tab" href="#map-<?= $t; ?>" role="tab"><?= htmlspecialchars($meta['label']); ?></a>
                            </li>
                        <?php $first = false; endforeach; ?>
                    </ul>
                    <div class="tab-content border border-top-0 p-3 mb-3">
                        <?php $first = true; foreach ($types as $t => $meta):
                            $def = $map['default'][$t . '|'] ?? null; ?>
                            <div class="tab-pane fade <?= $first ? 'show active' : ''; ?>" id="map-<?= $t; ?>" role="tabpanel">
                                <div class="form-group row align-items-center">
                                    <label class="col-md-4 col-form-label font-weight-bold">
                                        <?= lang_line('sa_map_default', 'Default'); ?> <?= htmlspecialchars($meta['label']); ?>
                                        <small class="d-block text-muted font-weight-normal"><?= lang_line('sa_map_default_hint', 'Dipakai kalau kategori barang belum dipetakan.'); ?></small>
                                    </label>
                                    <div class="col-md-8">
                                        <select name="map_default[<?= $t; ?>]" class="form-control form-control-sm select-akun-map" <?= $can_edit ? '' : 'disabled'; ?>><?= $acc_options($def->account_id ?? null); ?></select>
                                    </div>
                                </div>
                                <?php if ($t === 'asset'): ?>
                                    <p class="small text-muted mb-2"><i class="fas fa-info-circle"></i> <?= lang_line('sa_map_asset_hint', 'Aset yang di Master Aset sudah punya "Akun Aset" memakai akun tersebut lebih dulu.'); ?></p>
                                <?php endif; ?>
                                <?php if (empty($cats[$t])): ?>
                                    <p class="small text-muted mb-0"><?= lang_line('sa_map_no_category', 'Belum ada kategori untuk jenis ini (Common Code).'); ?></p>
                                <?php else: ?>
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="thead-light"><tr>
                                            <th style="width:35%"><?= lang_line('sa_map_th_category', 'Kategori'); ?></th>
                                            <th><?= lang_line('sa_map_th_account', 'Akun Debit'); ?></th>
                                        </tr></thead>
                                        <tbody>
                                        <?php foreach ($cats[$t] as $code => $label):
                                            $row = $map['category'][$t . '|' . $code] ?? null; ?>
                                            <tr>
                                                <td class="align-middle"><strong><?= htmlspecialchars($code); ?></strong><?= $label !== $code ? ' <small class="text-muted">' . htmlspecialchars($label) . '</small>' : ''; ?></td>
                                                <td><select name="map_category[<?= $t; ?>][<?= htmlspecialchars($code); ?>]" class="form-control form-control-sm select-akun-map" <?= $can_edit ? '' : 'disabled'; ?>><?= $acc_options($row->account_id ?? null); ?></select></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php $first = false; endforeach; ?>
                    </div>
                    <?php if ($can_edit): ?>
                        <div class="text-right">
                            <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> <?= lang_line('sa_map_btn_save', 'Simpan Pemetaan'); ?></button>
                        </div>
                    <?php endif; ?>
                </form>

                <hr>
                <h6 class="font-weight-bold"><i class="fas fa-thumbtack mr-1"></i> <?= lang_line('sa_map_override_title', 'Pengecualian per Barang'); ?></h6>
                <p class="small text-muted"><?= lang_line('sa_map_override_hint', 'Untuk barang yang akunnya beda dari kategorinya (mis. satu jenis foam masuk akun lain).'); ?></p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light"><tr>
                            <th><?= lang_line('sa_map_th_type', 'Jenis'); ?></th>
                            <th><?= lang_line('sa_map_th_item', 'Barang'); ?></th>
                            <th><?= lang_line('sa_map_th_account', 'Akun Debit'); ?></th>
                            <?php if ($can_edit): ?><th style="width:5%"></th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($map['item'])): ?>
                            <tr><td colspan="4" class="text-center text-muted small"><?= lang_line('sa_map_override_empty', 'Belum ada pengecualian.'); ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($map['item'] as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($types[$r->item_type]['label'] ?? $r->item_type); ?></td>
                                <td><?= htmlspecialchars($r->item_code . ' - ' . $r->item_name); ?></td>
                                <td><?= htmlspecialchars(trim(($r->account_code ?? '?') . ' - ' . ($r->account_name ?? ''))); ?></td>
                                <?php if ($can_edit): ?>
                                <td>
                                    <form method="POST" action="<?= base_url('settingakun/delete_item_override/' . (int) $r->id); ?>" onsubmit="return confirm('Hapus pengecualian ini?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($can_edit): ?>
                <form action="<?= base_url('settingakun/add_item_override'); ?>" method="POST" class="form-row align-items-end">
                    <div class="col-md-2 form-group">
                        <label class="small"><?= lang_line('sa_map_th_type', 'Jenis'); ?></label>
                        <select name="item_type" id="ovType" class="form-control form-control-sm">
                            <?php foreach ($types as $t => $meta): ?><option value="<?= $t; ?>"><?= htmlspecialchars($meta['label']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="small"><?= lang_line('sa_map_th_item', 'Barang'); ?></label>
                        <select name="item_id" id="ovItem" class="form-control form-control-sm" required></select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="small"><?= lang_line('sa_map_th_account', 'Akun Debit'); ?></label>
                        <select name="account_id" class="form-control form-control-sm select-akun-map" required><?= $acc_options(null); ?></select>
                    </div>
                    <div class="col-md-2 form-group">
                        <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-plus"></i> <?= lang_line('sa_map_btn_add', 'Tambah'); ?></button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    if (!$ || !$.fn.select2) return;
    $('.select-akun-map').select2({ width: '100%', allowClear: true, placeholder: <?= json_encode(lang_line('sa_map_none', '-- belum dipetakan --')); ?> });
    var $item = $('#ovItem');
    if ($item.length) {
        $item.select2({
            width: '100%', minimumInputLength: 0,
            placeholder: <?= json_encode(lang_line('sa_map_search_item', 'Cari kode / nama barang...')); ?>,
            ajax: {
                url: '<?= base_url('settingakun/search_items'); ?>', dataType: 'json', delay: 250,
                data: function (p) { return { type: $('#ovType').val(), q: p.term || '' }; },
                processResults: function (d) { return d; }
            }
        });
        $('#ovType').on('change', function () { $item.val(null).trigger('change'); });
    }
    if (location.hash === '#item-map') { var el = document.getElementById('item-map'); if (el) el.scrollIntoView(); }
});
</script>
