<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:.5rem;">
    <h3 class="text-gray-800 mb-0"><i class="fas fa-sitemap mr-2 text-primary"></i>Template Role — PDF ROLE LEVEL</h3>
    <div>
      <?php if (!empty($locked)): ?>
      <span class="badge badge-dark p-2 mr-1"><i class="fas fa-lock mr-1"></i> Role Access dikunci</span>
      <?php endif; ?>
      <?php if ($missing > 0 && empty($locked) && check_access('roleaccess', 'add')): ?>
      <form method="post" action="<?= base_url('roleaccess/create_template_roles'); ?>" class="d-inline" onsubmit="return confirm('Buat <?= (int) $missing; ?> role yang belum ada, lengkap dengan hak akses sesuai PDF?');">
        <input type="hidden" name="all" value="1">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magic mr-1"></i> Buat semua role yang belum ada (<?= (int) $missing; ?>)</button>
      </form>
      <?php endif; ?>
      <?php if ($mapped > 0 && empty($locked) && check_access('roleaccess', 'edit')): ?>
      <form method="post" action="<?= base_url('roleaccess/apply_template_all'); ?>" class="d-inline" onsubmit="return confirm('Terapkan template ke <?= (int) $mapped; ?> role? Hak dari PDF ditambahkan; yang dicabut hanya aksi PDF yang tidak diberikan ke posisi itu. Hak lain (termasuk Lihat) tidak disentuh.');">
        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-sync-alt mr-1"></i> Terapkan template ke <?= (int) $mapped; ?> role</button>
      </form>
      <?php endif; ?>
      <a href="<?= base_url('roleaccess'); ?>" class="btn btn-secondary btn-sm btn-load"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
    </div>
  </div>

  <div class="card border-left-info shadow-sm mb-4">
    <div class="card-body small">
      <div class="font-weight-bold mb-2"><i class="fas fa-info-circle text-info mr-1"></i> Cara kerja template</div>
      <ul class="mb-0 pl-3">
        <li>Setiap baris = satu posisi di satu departemen, persis seperti di PDF. <strong>Buat role</strong> membuat role baru lengkap dengan hak aksesnya. Role yang sudah ada tidak diubah dari halaman ini — buka <em>Kelola Hak Akses</em> lalu pakai tombol <strong>Isi dari Template PDF</strong>.</li>
        <li>Template hanya mengatur menu dokumen yang ada di PDF (Permintaan Barang, Payment Request, Reimburse Request, Material Request, Sales Order, PO, Receiving, Return, QA, Import/Export, dll). Menu lain tetap diatur manual. Monitoring hanya menambah hak <em>View</em>.</li>
        <li><strong>Submit</strong> dipisah dari Edit. Admin/Clerk tidak submit; yang submit Supervisor. Departemen tanpa Supervisor: Admin/Clerk ikut boleh submit <span class="badge badge-warning">aturan poin 4</span>.</li>
        <li><strong>Akses tambahan di luar PDF</strong> (ditambahkan 2026-09-16): tiap departemen juga mendapat menu kerja hariannya — master data, stok, jurnal, work order, dll — karena PDF hanya mengatur dokumen. Daftarnya tampil di tiap kartu departemen di bawah. Director & Asst. Director tidak ikut (sudah View semua lewat Monitoring), dan Admin/Clerk tidak dapat hak hapus.</li>
        <li><strong>Own Dept vs All Dept</strong> berlaku untuk Permintaan Barang, Payment Request, Reimburse Request & Material Request: daftar, detail/cetak, buat (pilih departemen dari role), edit, submit, hapus, approve, dan notifikasi dibatasi departemen dokumen. Supervisor bisa melihat & submit draft Admin/Clerk departemennya; HOD hanya approve departemennya. Dokumen lain di PDF dipakai satu departemen saja atau memang All Dept, jadi tidak perlu dibatasi.</li>
      </ul>
    </div>
  </div>

  <?php foreach ($departments as $dept => $def): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2" style="gap:.5rem;">
      <div>
        <i class="fas fa-building text-primary mr-1"></i>
        <strong><?= htmlspecialchars($dept_names[$dept] ?? $dept); ?></strong>
        <span class="text-muted small">(<?= htmlspecialchars($dept); ?>)</span>
        <span class="text-muted small ml-2">PDF: <?= htmlspecialchars($def['pdf_name']); ?></span>
      </div>
      <?php if (!isset($dept_names[$dept])): ?>
        <span class="badge badge-danger">Kode departemen belum ada di master Department</span>
      <?php endif; ?>
    </div>
    <?php if (!empty($extras[$dept])): ?>
    <div class="card-body py-2 border-bottom bg-light small">
      <span class="font-weight-bold text-muted mr-1">Akses tambahan (di luar PDF):</span>
      <?php
        $level_label = ['manage' => ['Kelola + hapus', 'success'], 'use' => ['Buat & ubah', 'primary'], 'view' => ['Lihat', 'secondary']];
        foreach (['manage', 'use', 'view'] as $lvl):
            if (empty($extras[$dept][$lvl])) continue;
            [$lbl, $color] = $level_label[$lvl];
      ?>
        <div class="mt-1">
          <span class="badge badge-<?= $color ?>"><?= $lbl ?></span>
          <?php foreach ($extras[$dept][$lvl] as $mk): ?>
            <span class="text-dark"><?= htmlspecialchars($menu_names[$mk] ?? $mk); ?></span><span class="text-muted">,</span>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="thead-light">
            <tr>
              <th style="width:15%">Posisi</th>
              <th>Aturan di PDF</th>
              <th style="width:22%">Role di sistem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows[$dept] as $row): ?>
            <tr>
              <td class="font-weight-bold"><?= htmlspecialchars($pos_names[$row['position']] ?? $row['position']); ?></td>
              <td>
                <table class="table table-borderless table-sm small mb-0">
                  <thead><tr class="text-muted"><th class="py-0" style="width:28%">Aksi</th><th class="py-0">Own Dept</th><th class="py-0">All Dept</th></tr></thead>
                  <tbody>
                  <?php foreach ($row['summary'] as $a): ?>
                    <tr>
                      <td class="py-0"><?= htmlspecialchars($a['action']); ?><?php if ($a['fallback']): ?> <span class="badge badge-warning" title="Departemen ini tidak punya Supervisor">poin 4</span><?php endif; ?></td>
                      <td class="py-0"><?= htmlspecialchars($a['own']); ?></td>
                      <td class="py-0"><?= htmlspecialchars($a['all']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
                <?php foreach ($row['notes'] as $n): ?>
                  <div class="small text-warning mt-1"><i class="fas fa-exclamation-triangle mr-1"></i><?= htmlspecialchars($n); ?></div>
                <?php endforeach; ?>
              </td>
              <td>
                <?php if ($row['role']): ?>
                  <div class="font-weight-bold small"><?= htmlspecialchars($row['role']->role_name); ?></div>
                  <div class="small text-muted mb-1"><?= (int) $row['role']->user_count; ?> user</div>
                  <?php if (empty($locked)): ?>
                  <a href="<?= base_url('roleaccess/manage_permission/' . $row['role']->id . '?template=1'); ?>" class="btn btn-outline-primary btn-sm btn-load">
                    <i class="fas fa-key mr-1"></i> Kelola & terapkan
                  </a>
                  <?php endif; ?>
                <?php elseif (empty($locked) && check_access('roleaccess', 'add') && isset($dept_names[$dept])): ?>
                  <span class="badge badge-light border mb-1">Belum ada</span><br>
                  <form method="post" action="<?= base_url('roleaccess/create_template_roles'); ?>" class="d-inline">
                    <input type="hidden" name="department_code" value="<?= htmlspecialchars($dept); ?>">
                    <input type="hidden" name="position_code" value="<?= htmlspecialchars($row['position']); ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Buat role</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-light border">Belum ada</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
