<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-language text-primary mr-2"></i>
                <?= lang_line('devmode_page_title', 'Developer Mode — Editor Bahasa'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('devmode_subtitle', 'Ubah teks/label yang tampil di aplikasi (sidebar, tombol, pesan, dll) tanpa perlu ubah kode.'); ?></small>
        </div>
        <div>
            <a href="<?= base_url('developer_mode/assets') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-image mr-1"></i> <?= lang_line('devmode_assets_go_images', 'Kelola Logo & Gambar'); ?></a>
            <a href="<?= base_url('developer_mode/columns') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_assets_go_columns', 'Kelola Kolom List'); ?></a>
            <a href="<?= base_url('developer_mode/sidebar_order') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-bars mr-1"></i> <?= lang_line('devmode_assets_go_sidebar_order', 'Atur Urutan Sidebar'); ?></a>
            <a href="<?= base_url('developer_mode/pdf_layout') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('devmode_assets_go_pdf', 'Atur PDF'); ?>
            </a>
        </div>
    </div>

    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        <?= lang_line('devmode_info', 'Daftar di bawah otomatis kelengkapin sendiri seiring halaman-halaman dipakai. Kalau teks yang mau diubah belum muncul di sini, coba buka dulu halaman yang menampilkan teks itu, lalu cari lagi di sini.'); ?>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm" style="min-width:220px;"
                           placeholder="<?= htmlspecialchars(lang_line('devmode_search_placeholder', 'Cari key atau teks...')); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="module" class="form-control form-control-sm">
                        <option value=""><?= lang_line('devmode_all_modules', 'Semua Modul'); ?></option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-check mr-2 mb-1">
                    <input type="checkbox" class="form-check-input" name="customized_only" id="customizedOnly" value="1" <?= !empty($filters['customized_only']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="customizedOnly"><?= lang_line('devmode_customized_only', 'Hanya yang sudah diubah'); ?></label>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> <?= lang_line('common_filter', 'Filter'); ?></button>
                <a href="<?= base_url('developer_mode') ?>" class="btn btn-sm btn-secondary mb-1"><?= lang_line('btn_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('devmode_th_key', 'Key'); ?></th>
                            <th><?= lang_line('devmode_th_indonesian', 'Indonesia'); ?></th>
                            <th><?= lang_line('devmode_th_english', 'English'); ?></th>
                            <th><?= lang_line('devmode_th_chinese', 'Chinese'); ?></th>
                            <th width="90"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('devmode_no_data', 'Belum ada key yang tercatat / cocok dengan filter.'); ?></td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <code class="small"><?= htmlspecialchars($r->lang_key) ?></code>
                                </td>
                                <?php foreach ($languages as $lg): $entry = $r->$lg; ?>
                                <td class="small" style="max-width:220px;">
                                    <?php if ($entry->is_custom): ?><span class="badge badge-warning mr-1" title="<?= lang_line('devmode_badge_custom_title', 'Sudah diubah dari default'); ?>"><?= lang_line('devmode_badge_custom', 'Custom'); ?></span><?php endif; ?>
                                    <span style="white-space:normal;word-break:break-word;"><?= htmlspecialchars($entry->live_value ?? '-') ?></span>
                                </td>
                                <?php endforeach; ?>
                                <td>
                                    <?php if ($can_edit): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-key"
                                            data-key="<?= htmlspecialchars($r->lang_key) ?>"
                                            data-indonesian="<?= htmlspecialchars($r->indonesian->live_value ?? '') ?>"
                                            data-english="<?= htmlspecialchars($r->english->live_value ?? '') ?>"
                                            data-chinese="<?= htmlspecialchars($r->chinese->live_value ?? '') ?>"
                                            data-custom-indonesian="<?= $r->indonesian->is_custom ? '1' : '0' ?>"
                                            data-custom-english="<?= $r->english->is_custom ? '1' : '0' ?>"
                                            data-custom-chinese="<?= $r->chinese->is_custom ? '1' : '0' ?>"
                                            title="<?= lang_line('devmode_btn_edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php
                $qs = [
                    'search'          => $filters['search'] ?? '',
                    'module'          => $filters['module'] ?? '',
                    'customized_only' => !empty($filters['customized_only']) ? 1 : '',
                ];
                for ($p = 1; $p <= $total_pages; $p++):
                    $qs['page'] = $p;
                    $url = base_url('developer_mode?' . http_build_query(array_filter($qs, function ($v) { return $v !== ''; })));
            ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $url ?>"><?= $p ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal: Edit 1 key (3 bahasa) -->
<div class="modal fade" id="editKeyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-language mr-2"></i> <?= lang_line('devmode_modal_title', 'Edit Teks'); ?>: <code id="editKeyLabel" class="text-white"></code></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="editKeyAlert"></div>
                <?php
                    $lang_labels = ['indonesian' => 'Bahasa Indonesia', 'english' => 'English', 'chinese' => '中文'];
                    foreach ($languages as $lg):
                ?>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small">
                        <?= $lang_labels[$lg] ?>
                        <span class="badge badge-warning ml-1 d-none" id="badge_<?= $lg ?>"><?= lang_line('devmode_badge_custom', 'Custom'); ?></span>
                    </label>
                    <div class="input-group">
                        <textarea class="form-control form-control-sm" id="field_<?= $lg ?>" rows="2"></textarea>
                    </div>
                    <div class="mt-1">
                        <button type="button" class="btn btn-sm btn-primary btn-save-lang" data-lang="<?= $lg ?>">
                            <i class="fas fa-save mr-1"></i> <?= lang_line('devmode_btn_save', 'Simpan'); ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-reset-lang" data-lang="<?= $lg ?>">
                            <i class="fas fa-undo mr-1"></i> <?= lang_line('devmode_btn_reset_default', 'Reset ke Default'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
const I18N_DEVMODE = {
    save_ok:  <?= json_encode(lang_line('devmode_save_ok', 'Tersimpan.')); ?>,
    save_err: <?= json_encode(lang_line('devmode_save_err', 'Gagal menyimpan.')); ?>
};

$(document).ready(function() {
    $('.btn-edit-key').on('click', function() {
        const btn = $(this);
        const key = btn.data('key');
        $('#editKeyLabel').text(key);

        ['indonesian', 'english', 'chinese'].forEach(function(lg) {
            $('#field_' + lg).val(btn.data(lg)).data('key', key);
            $('#badge_' + lg).toggleClass('d-none', btn.data('custom-' + lg) != 1);
        });

        $('#editKeyAlert').empty();
        $('#editKeyModal').modal('show');
    });

    function showAlert(type, msg) {
        $('#editKeyAlert').html('<div class="alert alert-' + type + ' py-2 small mb-2">' + msg + '</div>');
    }

    $('.btn-save-lang').on('click', function() {
        const lg = $(this).data('lang');
        const key = $('#editKeyLabel').text();
        const value = $('#field_' + lg).val();

        $.post('<?= base_url("developer_mode/save") ?>', { lang_key: key, language: lg, value: value }, function(res) {
            if (res.success) {
                showAlert('success', I18N_DEVMODE.save_ok);
                $('#badge_' + lg).toggleClass('d-none', !res.value);
            } else {
                showAlert('danger', res.message || I18N_DEVMODE.save_err);
            }
        }, 'json').fail(function() {
            showAlert('danger', I18N_DEVMODE.save_err);
        });
    });

    $('.btn-reset-lang').on('click', function() {
        const lg = $(this).data('lang');
        const key = $('#editKeyLabel').text();

        $.post('<?= base_url("developer_mode/reset") ?>', { lang_key: key, language: lg }, function(res) {
            if (res.success) {
                $('#field_' + lg).val(res.value || '');
                $('#badge_' + lg).addClass('d-none');
                showAlert('success', res.message);
            } else {
                showAlert('danger', res.message || I18N_DEVMODE.save_err);
            }
        }, 'json').fail(function() {
            showAlert('danger', I18N_DEVMODE.save_err);
        });
    });

    // Reload halaman begitu modal ditutup, biar tabel di belakang ikut
    // update (simpler drpd sinkronisasi DOM manual per cell).
    $('#editKeyModal').on('hidden.bs.modal', function() {
        location.reload();
    });
});
</script>
