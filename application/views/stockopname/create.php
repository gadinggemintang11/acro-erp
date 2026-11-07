<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('so_header_card'); ?></h6>
        </div>
        <form action="<?= base_url('stockopname/create'); ?>" method="POST">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('so_label_date'); ?> *</label>
                            <input type="date" name="opname_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('so_label_item_type'); ?> *</label>
                            <select name="item_type" class="form-control" required>
                                <option value=""><?= lang_line('so_opt_select_type'); ?></option>
                                <option value="finished_goods"><?= lang_line('so_type_fg'); ?></option>
                                <option value="material"><?= lang_line('so_type_material'); ?></option>
                                <option value="semifinished"><?= lang_line('so_type_semifinished'); ?></option>
                                <option value="scrap"><?= lang_line('so_type_scrap'); ?></option>
                                <option value="inventory"><?= lang_line('so_type_inventory'); ?></option>
                                <option value="machine"><?= lang_line('so_type_machine'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('so_label_notes'); ?> *</label>
                            <input type="text" name="notes" class="form-control" placeholder="<?= htmlspecialchars(lang_line('so_notes_placeholder')); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="<?= base_url('stockopname'); ?>" class="btn btn-secondary"><?= lang_line('so_btn_cancel'); ?></a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= lang_line('so_btn_create_session'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
