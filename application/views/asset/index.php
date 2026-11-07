<?php
// kategori & metode disimpan sebagai code; peta ini untuk menampilkan labelnya.
$kategori_map = [];
foreach ($kategori_list as $k) { $kategori_map[$k->code] = $k->description; }
$metode_map = [];
foreach ($metode_list as $m) { $metode_map[$m->code] = $m->description; }

/**
 * Render tiga dropdown mapping COA. Dipakai di modal Tambah maupun Edit
 * supaya pilihan akunnya tidak pernah beda antara dua form.
 */
if (!function_exists('coa_mapping_fields')):
function coa_mapping_fields($coa_asset_list, $coa_akum_list, $coa_beban_list, $a = null) {
    $rows = [
        ['coa_asset_id', 'Akun Aktiva Tetap',      $coa_asset_list, 'Didebit saat perolehan aset'],
        ['coa_akum_id',  'Akun Akumulasi Penyusutan', $coa_akum_list,  'Dikredit tiap bulan saat penyusutan'],
        ['coa_beban_id', 'Akun Beban Penyusutan',  $coa_beban_list, 'Didebit tiap bulan saat penyusutan'],
    ];
    foreach ($rows as $r) {
        list($name, $label, $list, $hint) = $r;
        $current = $a ? ($a->{$name} ?? null) : null;

        // Kalau akun yang tersimpan berada di luar rentang yang disarankan,
        // tandai -- biasanya itu salah pilih, dan diamkan saja berarti jurnal
        // penyusutan nanti menghantam akun yang keliru.
        $di_luar_rentang = false;
        if ($current) {
            foreach ($list['lainnya'] as $c) {
                if ($c->id == $current) { $di_luar_rentang = true; break; }
            }
        }
        ?>
        <div class="form-group">
            <label class="mb-1"><?= $label; ?> <small class="text-muted">— <?= $hint; ?></small></label>
            <select name="<?= $name; ?>" class="form-control form-control-sm<?= $di_luar_rentang ? ' border-warning' : ''; ?>">
                <option value="">-- Belum ditentukan --</option>
                <?php if (!empty($list['disarankan'])): ?>
                    <optgroup label="Disarankan">
                        <?php foreach ($list['disarankan'] as $c): ?>
                            <option value="<?= $c->id; ?>" <?= ($current == $c->id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($c->account_code . ' — ' . $c->account_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if (!empty($list['lainnya'])): ?>
                    <optgroup label="Akun lain (jarang dipakai untuk aset)">
                        <?php foreach ($list['lainnya'] as $c): ?>
                            <option value="<?= $c->id; ?>" <?= ($current == $c->id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($c->account_code . ' — ' . $c->account_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
            <?php if ($di_luar_rentang): ?>
                <small class="form-text text-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Akun ini di luar rentang yang lazim untuk aset tetap — pastikan memang disengaja.
                </small>
            <?php endif; ?>
        </div>
        <?php
    }
}
endif;
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-building text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div class="d-flex align-items-center">
            <form class="form-inline mr-2" method="get" action="<?= base_url('master_asset'); ?>">
                <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Cari kode / nama asset..." value="<?= htmlspecialchars($keyword ?? ''); ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            </form>

            <?php if (check_access('master_asset', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Asset
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div class="alert alert-info border-0 shadow-sm">
        <i class="fas fa-info-circle mr-1"></i>
        Mapping akun (COA) bersifat <strong>opsional</strong> di tahap ini — asset tetap bisa dipakai untuk PR &amp; PO tanpa diisi.
        Mapping baru <strong>wajib</strong> saat modul Penyusutan dijalankan.
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Kode Asset</th>
                            <th>Nama Asset</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Umur Manfaat</th>
                            <th>Mapping COA</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = isset($offset) ? ((int)$offset + 1) : 1; ?>
                        <?php if (!empty($assets)): foreach ($assets as $a): ?>
                        <?php
                            $coa_terisi = (int)!empty($a->coa_asset_id) + (int)!empty($a->coa_akum_id) + (int)!empty($a->coa_beban_id);
                        ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td><span class="font-weight-bold text-primary"><?= htmlspecialchars($a->kode_asset); ?></span></td>
                            <td class="text-left"><?= htmlspecialchars($a->nama_asset); ?></td>
                            <td><?= htmlspecialchars($kategori_map[$a->kategori] ?? $a->kategori ?? ''); ?></td>
                            <td><?= htmlspecialchars($a->satuan ?? ''); ?></td>
                            <td>
                                <?= (int) $a->umur_manfaat_bulan; ?> bln
                                <small class="text-muted d-block">(<?= number_format($a->umur_manfaat_bulan / 12, 1); ?> thn)</small>
                            </td>
                            <td>
                                <?php if ($coa_terisi === 3): ?>
                                    <span class="badge badge-success px-2 py-1" title="Siap untuk penyusutan">
                                        <i class="fas fa-check mr-1"></i>Lengkap
                                    </span>
                                <?php elseif ($coa_terisi === 0): ?>
                                    <span class="badge badge-secondary px-2 py-1">Belum diisi</span>
                                <?php else: ?>
                                    <span class="badge badge-warning px-2 py-1" title="Penyusutan akan ditolak sampai ketiganya terisi">
                                        <i class="fas fa-exclamation-triangle mr-1"></i><?= $coa_terisi; ?>/3
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $a->status == 'active' ? 'success' : 'secondary'; ?> px-3 py-1">
                                    <?= $a->status == 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_asset', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $a->id; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (check_access('master_asset', 'delete')): ?>
                                <a href="<?= base_url('master_asset/delete/' . $a->id); ?>" class="btn btn-sm btn-danger btn-delete" data-message="Yakin nonaktifkan asset <?= htmlspecialchars($a->nama_asset); ?>?">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $a->id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                <form method="post" action="<?= base_url('master_asset/update/' . $a->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Asset</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <h6 class="text-primary font-weight-bold border-bottom pb-2 mb-3">Identitas</h6>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label>Kode Asset <span class="text-danger">*</span></label>
                                                    <input type="text" name="kode_asset" class="form-control" value="<?= htmlspecialchars($a->kode_asset); ?>" required>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <label>Nama Asset <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_asset" class="form-control" value="<?= htmlspecialchars($a->nama_asset); ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Kategori</label>
                                                    <select name="kategori" class="form-control">
                                                        <?php foreach ($kategori_list as $k): ?>
                                                            <option value="<?= $k->code; ?>" <?= $k->code == $a->kategori ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($k->description); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Satuan</label>
                                                    <select name="satuan" class="form-control">
                                                        <?php foreach ($satuan_list as $s): ?>
                                                            <option value="<?= $s->code; ?>" <?= $s->code == $a->satuan ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($s->description); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <h6 class="text-primary font-weight-bold border-bottom pb-2 mb-3 mt-4">Penyusutan</h6>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label>Umur Manfaat <small class="text-muted">(bulan)</small></label>
                                                    <input type="number" name="umur_manfaat_bulan" class="form-control" min="1" value="<?= (int) $a->umur_manfaat_bulan; ?>">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>Metode</label>
                                                    <select name="metode_penyusutan" class="form-control">
                                                        <?php foreach ($metode_list as $m): ?>
                                                            <option value="<?= $m->code; ?>" <?= $m->code == $a->metode_penyusutan ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($m->description); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>Nilai Residu <small class="text-muted">(%)</small></label>
                                                    <input type="number" step="0.01" min="0" max="100" name="nilai_residu_persen" class="form-control" value="<?= htmlspecialchars($a->nilai_residu_persen); ?>">
                                                </div>
                                            </div>

                                            <h6 class="text-primary font-weight-bold border-bottom pb-2 mb-3 mt-4">
                                                Mapping Akun (COA) <small class="text-muted font-weight-normal">— opsional</small>
                                            </h6>
                                            <?php coa_mapping_fields($coa_asset_list, $coa_akum_list, $coa_beban_list, $a); ?>

                                            <div class="form-row mt-3">
                                                <div class="form-group col-md-8">
                                                    <label>Keterangan</label>
                                                    <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($a->keterangan ?? ''); ?></textarea>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control">
                                                        <option value="active" <?= $a->status == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                        <option value="inactive" <?= $a->status == 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Update</button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-3">Belum ada data asset.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-end">
                    <?= $pagination ?? ''; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form method="post" action="<?= base_url('master_asset/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Asset</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h6 class="text-primary font-weight-bold border-bottom pb-2 mb-3">Identitas</h6>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode Asset <small class="text-muted">(kosongkan = auto)</small></label>
                            <input type="text" name="kode_asset" class="form-control" placeholder="Contoh: AST-0001">
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Asset <span class="text-danger">*</span></label>
                            <input type="text" name="nama_asset" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Kategori</label>
                            <select name="kategori" class="form-control" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $k): ?>
                                    <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Satuan</label>
                            <select name="satuan" class="form-control" required>
                                <option value="">-- Pilih Satuan --</option>
                                <?php foreach ($satuan_list as $s): ?>
                                    <option value="<?= $s->code; ?>" <?= $s->code == 'UNIT' ? 'selected' : ''; ?>><?= htmlspecialchars($s->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary font-weight-bold border-bottom pb-2 mb-3 mt-4">Penyusutan</h6>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Umur Manfaat <small class="text-muted">(bulan)</small></label>
                            <input type="number" name="umur_manfaat_bulan" class="form-control" min="1" value="48">
                            <small class="form-text text-muted">48=4th · 96=8th · 192=16th · 240=20th</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Metode</label>
                            <select name="metode_penyusutan" class="form-control">
                                <?php foreach ($metode_list as $m): ?>
                                    <option value="<?= $m->code; ?>"><?= htmlspecialchars($m->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nilai Residu <small class="text-muted">(%)</small></label>
                            <input type="number" step="0.01" min="0" max="100" name="nilai_residu_persen" class="form-control" value="0">
                        </div>
                    </div>

                    <h6 class="text-primary font-weight-bold border-bottom pb-2 mb-3 mt-4">
                        Mapping Akun (COA) <small class="text-muted font-weight-normal">— opsional, bisa diisi nanti</small>
                    </h6>
                    <?php coa_mapping_fields($coa_asset_list, $coa_akum_list, $coa_beban_list); ?>

                    <div class="form-group mt-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
