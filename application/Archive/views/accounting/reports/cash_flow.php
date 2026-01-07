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
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_date">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date; ?>">
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
            <h6 class="m-0 font-weight-bold text-primary">Laporan Arus Kas (Periode: <?= $start_date; ?> s/d <?= $end_date; ?>)</h6>
            <?php
            $queryString = http_build_query([
                'start_date' => $start_date,
                'end_date' => $end_date,
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
                <table class="table table-sm" id="cashFlowTable">
                    
                    <tr class="table-primary">
                        <td class="font-weight-bold" colspan="3">ARUS KAS DARI AKTIVITAS OPERASI (CFO)</td>
                    </tr>
                    <?php
                        // Kita akan kelompokkan berdasarkan 'group_name'
                        $cfo_groups = [];
                        foreach ($report_data['cfo'] as $item) {
                            $cfo_groups[$item['group_name']][] = $item;
                        }

                        // Tampilkan per grup
                        foreach ($cfo_groups as $group_name => $items) :
                            $group_total = 0;
                            foreach ($items as $item) {
                                $group_total += $item['cash_impact'];
                            }
                    ?>
                        <tr class="font-weight-bold">
                            <td style="padding-left: 20px;"><?= $group_name; ?></td>
                            <td class="text-right"><?= number_format($group_total, 2); ?></td>
                            <td class="text-center" width="5%">
                                </td>
                        </tr>
                        <?php 
                            // Tampilkan rincian per akun jika perlu
                            foreach ($items as $item) : 
                        ?>
                            <tr>
                                <td style="padding-left: 40px;"><?= $item['account_name']; ?> (<?= $item['account_code']; ?>)</td>
                                <td class="text-right">(<?= number_format($item['cash_impact'], 2); ?>)</td>
                                <td class="text-center">
                                    <?php
                                        // TRACEABILITY: Link ke General Ledger dari akun "Lawan"
                                        $queryString = "start_date={$start_date}&end_date={$end_date}";
                                        $url = site_url('reports/general_ledger/' . $item['account_id'] . '?' . $queryString);
                                    ?>
                                    <a href="<?= $url; ?>" class="btn btn-info btn-sm py-0 px-1" title="Lihat Detail Buku Besar (Akun Lawan)"><i class="fa fa-search"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; // end item ?>
                    <?php endforeach; // end group ?>
                    
                    <tr class="table-light">
                        <td class="font-weight-bold text-right">Total Arus Kas dari Aktivitas Operasi</td>
                        <td class="font-weight-bold text-right"><?= number_format($report_data['total_cfo'], 2); ?></td>
                        <td></td>
                    </tr>
                    <tr><td colspan="3">&nbsp;</td></tr>


                    <tr class="table-info">
                        <td class="font-weight-bold" colspan="3">ARUS KAS DARI AKTIVITAS INVESTASI (CFI)</td>
                    </tr>
                    <?php foreach ($report_data['cfi'] as $item) : ?>
                         <tr>
                            <td style="padding-left: 20px;"><?= $item['account_name']; ?> (<?= $item['account_code']; ?>)</td>
                            <td class="text-right"><?= number_format($item['cash_impact'], 2); ?></td>
                            <td class="text-center">
                                <?php
                                    $queryString = "start_date={$start_date}&end_date={$end_date}";
                                    $url = site_url('reports/general_ledger/' . $item['account_id'] . '?' . $queryString);
                                ?>
                                <a href="<?= $url; ?>" class="btn btn-info btn-sm py-0 px-1" title="Lihat Detail Buku Besar (Akun Lawan)"><i class="fa fa-search"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light">
                        <td class="font-weight-bold text-right">Total Arus Kas dari Aktivitas Investasi</td>
                        <td class="font-weight-bold text-right"><?= number_format($report_data['total_cfi'], 2); ?></td>
                        <td></td>
                    </tr>
                    <tr><td colspan="3">&nbsp;</td></tr>


                    <tr class="table-warning">
                        <td class="font-weight-bold" colspan="3">ARUS KAS DARI AKTIVITAS PENDANAAN (CFF)</td>
                    </tr>
                     <?php foreach ($report_data['cff'] as $item) : ?>
                         <tr>
                            <td style="padding-left: 20px;"><?= $item['account_name']; ?> (<?= $item['account_code']; ?>)</td>
                            <td class="text-right"><?= number_format($item['cash_impact'], 2); ?></td>
                            <td class="text-center">
                                <?php
                                    $queryString = "start_date={$start_date}&end_date={$end_date}";
                                    $url = site_url('reports/general_ledger/' . $item['account_id'] . '?' . $queryString);
                                ?>
                                <a href="<?= $url; ?>" class="btn btn-info btn-sm py-0 px-1" title="Lihat Detail Buku Besar (Akun Lawan)"><i class="fa fa-search"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light">
                        <td class="font-weight-bold text-right">Total Arus Kas dari Aktivitas Pendanaan</td>
                        <td class="font-weight-bold text-right"><?= number_format($report_data['total_cff'], 2); ?></td>
                        <td></td>
                    </tr>
                    <tr><td colspan="3">&nbsp;</td></tr>


                    <tr class="table-success">
                        <td class="font-weight-bold text-right">Peningkatan/(Penurunan) Bersih Kas</td>
                        <td class="font-weight-bold text-right"><?= number_format($report_data['peningkatan_kas'], 2); ?></td>
                        <td></td>
                    </tr>
                    <tr class="table-light">
                        <td class="font-weight-bold text-right">Saldo Kas di Awal Periode</td>
                        <td class="font-weight-bold text-right"><?= number_format($report_data['saldo_awal_kas'], 2); ?></td>
                        <td></td>
                    </tr>
                    <tr class="table-success">
                        <td class="font-weight-bold text-right">Saldo Kas di Akhir Periode</td>
                        <td class="font-weight-bold text-right"><?= number_format($report_data['saldo_akhir_kas'], 2); ?></td>
                        <td></td>
                    </tr>

                </table>
            </div>
        </div>
    </div>
    </div>