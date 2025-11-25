<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Periode Laporan</h6>
        </div>
        <div class="card-body">
            <form action="<?= current_url(); ?>" method="GET">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="as_of_date">Per Tanggal (As of Date)</label>
                        <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="<?= $as_of_date; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Tampilkan</button>
                        <a href="<?= current_url(); ?>" class="btn btn-secondary"><i class="fa fa-redo"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Neraca Saldo (Per Tanggal: <?= $as_of_date; ?>)</h6>
            <?php
            $queryString = http_build_query([
                'as_of_date' => $as_of_date,
            ]);
            ?>
            <div class="btn-group">
                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-download"></i> Ekspor
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=pdf'; ?>" target="_blank"><i class="fa fa-file-pdf"></i> PDF</a>
                    <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=excel'; ?>"><i class="fa fa-file-excel"></i> Excel</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="trialBalanceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr class="text-center">
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th width="15%">Debit</th>
                            <th width="1s5%">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $total_debit = 0;
                            $total_kredit = 0;
                        ?>
                        <?php foreach ($report_data as $account) : ?>
                            <?php
                                $total_debit += $account['debit'];
                                $total_kredit += $account['kredit'];
                            ?>
                            <tr>
                                <td><?= $account['account_code']; ?></td>
                                <td><?= $account['account_name']; ?></td>
                                <td class="text-right"><?= number_format($account['debit'], 2); ?></td>
                                <td class="text-right"><?= number_format($account['kredit'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php
                            $is_balance = (round($total_debit, 2) == round($total_kredit, 2));
                            $balance_class = $is_balance ? 'table-success' : 'table-danger';
                        ?>
                        <tr class="font-weight-bold text-dark">
                            <td colspan="2" class="text-right">TOTAL</td>
                            <td class="text-right"><?= number_format($total_debit, 2); ?></td>
                            <td class="text-right"><?= number_format($total_kredit, 2); ?></td>
                        </tr>
                        <tr class="<?= $balance_class; ?>">
                            <td colspan="2" class="text-center font-weight-bold">
                                STATUS
                            </td>
                            <td colspan="2" class="text-center font-weight-bold">
                                <?= $is_balance ? 'BALANCE' : 'NOT BALANCE'; ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    </div>