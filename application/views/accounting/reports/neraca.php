<?php $CI =& get_instance(); ?>

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
                    <div class="form-group col-md-8">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Tampilkan</button>
                        <a href="<?= current_url(); ?>" class="btn btn-secondary"><i class="fa fa-redo"></i> Reset</a>
                        <?php
                        $queryString = http_build_query([
                            'as_of_date' => $as_of_date,
                        ]);
                        ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-download"></i> Ekspor
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=pdf'; ?>" target="_blank"><i class="fa fa-file-pdf"></i> PDF</a>
                                <a class="dropdown-item" href="<?= current_url() . '?' . $queryString . '&export=excel'; ?>"><i class="fa fa-file-excel"></i> Excel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
        $total_aset = $report_data['total_aset'];
        $total_liabilitas = $report_data['total_liabilitas'];
        $total_ekuitas = $report_data['total_ekuitas']; // Ini sudah termasuk Laba Tahun Berjalan
        $total_liabilitas_ekuitas = $total_liabilitas + $total_ekuitas;
        
        // Cek Balance
        $is_balance = (round($total_aset, 2) == round($total_liabilitas_ekuitas, 2));
        $balance_class = $is_balance ? 'success' : 'danger';
        $balance_text = $is_balance ? 'BALANCE' : 'NOT BALANCE';
        $balance_icon = $is_balance ? 'check-circle' : 'times-circle';
    ?>
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Aset</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_aset, 2); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-archive fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Liabilitas & Ekuitas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_liabilitas_ekuitas, 2); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-university fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-<?= $balance_class; ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= $balance_class; ?> text-uppercase mb-1">Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $balance_text; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-<?= $balance_icon; ?> fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">ASET (Per Tanggal: <?= $as_of_date; ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="asetTable">
                            <thead>
                                <tr>
                                    <th>Nama Akun</th>
                                    <th class="text-right">Saldo</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // ======================================================
                                // FUNGSI REKURSIF UNTUK MERENDER TREE
                                // ======================================================
                                // Kita definisikan sekali, kita pakai di kedua tabel
                                //
                                // @param array $account Akun yang akan dirender
                                // @param int $level Level indentasi
                                // @param object $CI Instance CodeIgniter
                                // ======================================================
                                function render_balance_sheet_row($account, $level, $CI, $as_of_date) {
                                    $padding = $level * 20;
                                    $style = "padding-left: {$padding}px;";
                                    $class = $account['is_header'] ? 'font-weight-bold' : 'text-gray-900';
                                    $balance = $account['balance'];

                                    echo "<tr>";
                                    echo "<td class='{$class}' style='{$style}'>{$account['account_name']}</td>";
                                    echo "<td class'text-right'>". number_format($balance, 2) ."</td>";
                                    echo "<td class='text-center'>";
                                    if ($account['is_header'] == 0 && $balance != 0) {
                                        // Buat URL untuk drill-down ke Buku Besar
                                        // PENTING: Untuk Neraca, start_date adalah awal tahun
                                        $startOfYear = date('Y-01-01', strtotime($as_of_date));
                                        $queryString = "start_date={$startOfYear}&end_date={$as_of_date}";
                                        $url = site_url('reports/general_ledger/' . $account['id'] . '?' . $queryString);
                                        echo "<a href='{$url}' class='btn btn-info btn-sm py-0 px-1' title='Lihat Detail Buku Besar'><i class='fa fa-search'></i></a>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";

                                    if (isset($account['children'])) {
                                        foreach ($account['children'] as $child) {
                                            render_balance_sheet_row($child, $level + 1, $CI, $as_of_date);
                                        }
                                    }
                                }
                                ?>

                                <?php foreach ($report_data['aset'] as $account) : ?>
                                    <?php render_balance_sheet_row($account, 0, $CI, $as_of_date); ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <td class="font-weight-bold text-right">TOTAL ASET</td>
                                    <td class="font-weight-bold text-right">
                                        <?= number_format($report_data['total_aset'], 2); ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">LIABILITAS & EKUITAS</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="liabilitasEkuitasTable">
                            <thead>
                                <tr>
                                    <th>Nama Akun</th>
                                    <th class="text-right">Saldo</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-info">
                                    <td class="font-weight-bold" colspan="3">LIABILITAS</td>
                                </tr>
                                <?php foreach ($report_data['liabilitas'] as $account) : ?>
                                    <?php render_balance_sheet_row($account, 0, $CI, $as_of_date); ?>
                                <?php endforeach; ?>
                                <tr class="table-light">
                                    <td class="font-weight-bold text-right">TOTAL LIABILITAS</td>
                                    <td class="font-weight-bold text-right">
                                        <?= number_format($report_data['total_liabilitas'], 2); ?>
                                    </td>
                                    <td></td>
                                </tr>

                                <tr><td colspan="3">&nbsp;</td></tr>
                                
                                <tr class="table-info">
                                    <td class="font-weight-bold" colspan="3">EKUITAS</td>
                                </tr>
                                <?php foreach ($report_data['ekuitas'] as $account) : ?>
                                    <?php render_balance_sheet_row($account, 0, $CI, $as_of_date); ?>
                                <?php endforeach; ?>
                                
                                <tr>
                                    <td class="font-weight-bold" style="padding-left: 20px;">Laba Tahun Berjalan</td>
                                    <td class="text-right"><?= number_format($report_data['laba_tahun_berjalan'], 2); ?></td>
                                    <td class="text-center">
                                        <?php
                                            // Link ke Laporan Laba Rugi
                                            $startOfYear = date('Y-01-01', strtotime($as_of_date));
                                            $queryString = "start_date={$startOfYear}&end_date={$as_of_date}";
                                            $url = site_url('reports/profit_loss?' . $queryString);
                                            echo "<a href='{$url}' class='btn btn-info btn-sm py-0 px-1' title='Lihat Rincian Laba Rugi'><i class='fa fa-search'></i></a>";
                                        ?>
                                    </td>
                                </tr>

                                <tr class="table-light">
                                    <td class="font-weight-bold text-right">TOTAL EKUITAS</td>
                                    <td class="font-weight-bold text-right">
                                        <?= number_format($report_data['total_ekuitas'], 2); ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <td class="font-weight-bold text-right">TOTAL LIABILITAS & EKUITAS</td>
                                    <td class="font-weight-bold text-right">
                                        <?= number_format($total_liabilitas_ekuitas, 2); ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>