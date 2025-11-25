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
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pendapatan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($report_data['total_pendapatan'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Biaya</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($report_data['total_biaya'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <?php
                $laba_class = $report_data['laba_bersih'] >= 0 ? 'success' : 'danger';
                $laba_text = $report_data['laba_bersih'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih';
            ?>
            <div class="card border-left-<?= $laba_class; ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= $laba_class; ?> text-uppercase mb-1"><?= $laba_text; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($report_data['laba_bersih'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Laporan Laba Rugi (Periode: <?= $start_date; ?> s/d <?= $end_date; ?>)</h6>
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
                <table class="table table-sm table-hover" id="reportTable">
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
                        // Fungsi ini akan dipanggil berulang kali untuk
                        // menampilkan akun dan sub-akunnya (children)
                        //
                        // @param array $account Akun yang akan dirender
                        // @param int $level Level indentasi
                        // @param object $CI Instance CodeIgniter
                        // ======================================================
                        function render_profit_loss_row($account, $level, $CI) {
                            
                            // 1. Tentukan padding (indentasi)
                            // Setiap level +1, kita tambahkan 20px padding
                            $padding = $level * 20;
                            $style = "padding-left: {$padding}px;";

                            // 2. Tentukan class (tebal untuk header)
                            $class = $account['is_header'] ? 'font-weight-bold' : 'text-gray-900';

                            // 3. Ambil saldo (sudah dihitung di Model)
                            $balance = $account['balance'];

                            // 4. Render baris (TR)
                            echo "<tr>";
                            // Kolom Nama Akun
                            echo "<td class='{$class}' style='{$style}'>{$account['account_name']}</td>";
                            
                            // Kolom Saldo
                            echo "<td class'text-right'>". number_format($balance, 2) ."</td>";

                            // Kolom Aksi (Drill Down)
                            echo "<td class='text-center'>";
                            // Tampilkan tombol HANYA jika dia akun postable (bukan header)
                            // dan saldonya bukan 0 (opsional, tapi lebih rapi)
                            if ($account['is_header'] == 0 && $balance != 0) {
                                // Buat URL untuk drill-down ke Buku Besar
                                // Kita teruskan juga query string (filter tanggal)
                                $url = site_url('reports/general_ledger/' . $account['id'] . '?' . $_SERVER['QUERY_STRING']);
                                echo "<a href='{$url}' class='btn btn-info btn-sm py-0 px-1' title='Lihat Detail Buku Besar'><i class='fa fa-search'></i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";

                            // 5. Panggil fungsi ini lagi untuk semua anak (CHILDREN)
                            if (isset($account['children'])) {
                                foreach ($account['children'] as $child) {
                                    // Level + 1 untuk indentasi lebih dalam
                                    render_profit_loss_row($child, $level + 1, $CI);
                                }
                            }
                        }
                        ?>

                        <tr class="table-primary">
                            <td class="font-weight-bold" colspan="3">PENDAPATAN</td>
                        </tr>
                        <?php foreach ($report_data['pendapatan'] as $account) : ?>
                            <?php render_profit_loss_row($account, 0, $CI); ?>
                        <?php endforeach; ?>
                        <tr class="table-light">
                            <td class="font-weight-bold text-right">TOTAL PENDAPATAN</td>
                            <td class="font-weight-bold text-right">
                                <?= number_format($report_data['total_pendapatan'], 2); ?>
                            </td>
                            <td></td>
                        </tr>

                        <tr><td colspan="3">&nbsp;</td></tr>

                        <tr class="table-danger">
                            <td class="font-weight-bold" colspan="3">BIAYA</td>
                        </tr>
                        <?php foreach ($report_data['biaya'] as $account) : ?>
                            <?php render_profit_loss_row($account, 0, $CI); ?>
                        <?php endforeach; ?>
                        <tr class="table-light">
                            <td class="font-weight-bold text-right">TOTAL BIAYA</td>
                            <td class="font-weight-bold text-right">
                                <?= number_format($report_data['total_biaya'], 2); ?>
                            </td>
                            <td></td>
                        </tr>
                        
                        <tr><td colspan="3">&nbsp;</td></tr>

                        <?php
                            $laba_row_class = $report_data['laba_bersih'] >= 0 ? 'table-success' : 'table-danger';
                        ?>
                        <tr class="<?= $laba_row_class; ?>">
                            <td class="font-weight-bold text-right" style="font-size: 1.1rem;">
                                <?= $laba_text; ?>
                            </td>
                            <td class="font-weight-bold text-right" style="font-size: 1.1rem;">
                                <?= number_format($report_data['laba_bersih'], 2); ?>
                            </td>
                            <td></td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>