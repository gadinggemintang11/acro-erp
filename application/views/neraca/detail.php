<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('nrc_detail_card_header', 'Detail Ledger Akun') ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?= lang_line('nrc_th_date', 'Tanggal') ?></th>
                                    <th><?= lang_line('nrc_th_journal_invoice', 'No. Jurnal / Invoice') ?></th>
                                    <th><?= lang_line('nrc_th_partner', 'Partner') ?></th>
                                    <th><?= lang_line('nrc_th_remarks', 'Keterangan') ?></th>
                                    <th class="text-right"><?= lang_line('nrc_th_debit', 'Debit') ?></th>
                                    <th class="text-right"><?= lang_line('nrc_th_credit', 'Kredit') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail as $row): ?>
                                <tr>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                    <td>
                                        <?= $row['no_jurnal'] ?><br>
                                        <small><?= $row['no_invoice_ar'] ?? $row['no_invoice_ap'] ?></small>
                                    </td>
                                    <td><?= $row['customer_name'] ?? $row['supplier_name'] ?></td>
                                    <td><?= $row['keterangan'] ?></td>
                                    <td class="text-right"><?= number_format($row['debit'], 2) ?></td>
                                    <td class="text-right"><?= number_format($row['kredit'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
