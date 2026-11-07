<?php
/**
 * Pilih role untuk user — boleh lebih dari satu (Role Level, klien 2026-09-15).
 * Contoh: Bayu = Logistic - Officer + General Warehouse - Supervisor + ADM - Supervisor.
 *
 * Variabel: $roles (Role_model::get_all_roles, aktif saja), $selected (int[]),
 *           $picker_id (unik per modal).
 */
$selected = array_map('intval', $selected ?? []);
$grouped = [];
foreach ($roles as $r) {
    $k = !empty($r->department_code) ? $r->department_code : '__none__';
    $grouped[$k]['label'] = $k === '__none__' ? 'Belum ada departemen' : ($r->department_name ?? $k);
    $grouped[$k]['roles'][] = $r;
}
?>
<div class="role-picker border rounded" id="<?= $picker_id; ?>">
  <div class="d-flex align-items-center justify-content-between px-2 py-2 border-bottom bg-light" style="gap:.5rem;">
    <input type="text" class="form-control form-control-sm role-picker-search" placeholder="Cari departemen / posisi / role..." style="max-width:260px">
    <span class="small text-muted"><span class="role-picker-count"><?= count($selected); ?></span> role dipilih</span>
  </div>
  <div class="role-picker-list px-2" style="max-height:260px; overflow-y:auto;">
    <?php foreach ($grouped as $gk => $g): ?>
    <div class="role-picker-group pt-2">
      <div class="small font-weight-bold text-uppercase text-muted mb-1"><i class="fas fa-building mr-1"></i><?= htmlspecialchars($g['label']); ?></div>
      <div class="form-row pl-2">
        <?php foreach ($g['roles'] as $r):
          $cid = $picker_id . '_r' . $r->id;
          $auto_name = (!empty($r->department_name) && !empty($r->position_name)) ? $r->department_name . ' - ' . $r->position_name : null;
        ?>
        <div class="col-md-6 role-picker-item">
          <div class="custom-control custom-checkbox mb-1">
            <input type="checkbox" class="custom-control-input" id="<?= $cid; ?>" name="role_ids[]" value="<?= (int) $r->id; ?>" <?= in_array((int) $r->id, $selected, true) ? 'checked' : ''; ?>>
            <label class="custom-control-label small" for="<?= $cid; ?>">
              <?php if (!empty($r->position_name)): ?>
                <span class="font-weight-bold"><?= htmlspecialchars($r->position_name); ?></span>
                <?php if ($r->role_name !== $auto_name): ?><span class="text-muted">· <?= htmlspecialchars($r->role_name); ?></span><?php endif; ?>
              <?php else: ?>
                <?= htmlspecialchars($r->role_name); ?> <span class="text-muted">(posisi belum diatur)</span>
              <?php endif; ?>
            </label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
