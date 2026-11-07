<style>
.modern-card {
    border-radius: 10px;
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}
.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08) !important;
}
/* Elegant minimal color palette */
.gradient-card-1 {
    background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
    color: white;
}
.gradient-card-2 {
    background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-dark) 100%);
    color: white;
}
.gradient-card-3 {
    background: linear-gradient(135deg, var(--brand-2) 0%, var(--brand-dark) 100%);
    color: white;
}
.gradient-card-4 {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
}
.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.15);
}
.modern-table {
    border-radius: 8px;
    overflow: hidden;
}
.modern-table thead {
    background: #F9FAFB;
    color: #374151;
    border-bottom: 2px solid #E5E7EB;
}
.modern-table thead th {
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 10px;
}
.modern-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #F3F4F6;
}
.modern-table tbody tr:hover {
    background: #F9FAFB;
}
.page-header {
    background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
    color: white;
    padding: 20px 24px;
    border-radius: 10px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.chart-container {
    border-radius: 10px;
    background: white;
    padding: 20px;
}
.badge-modern {
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
}
.card-header-elegant {
    background: #F9FAFB;
    border-bottom: 2px solid #E5E7EB;
    padding: 14px 20px;
}
.card-header-elegant h6 {
    color: #374151;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-card {
    animation: fadeInUp 0.5s ease-out;
}
.animate-card:nth-child(1) { animation-delay: 0.05s; }
.animate-card:nth-child(2) { animation-delay: 0.1s; }
.animate-card:nth-child(3) { animation-delay: 0.15s; }
.animate-card:nth-child(4) { animation-delay: 0.2s; }
</style>

<div class="container-fluid">
    
    <!-- Minimal Page Heading -->
    <div class="page-header animate-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="h3 mb-1 font-weight-bold">
                    <i class="fas fa-shield-alt"></i> <?= lang_line('dashboard_bc_title', 'Dashboard Bea Cukai'); ?>
                </h1>
                <p class="mb-0" style="opacity: 0.7; font-size: 0.875rem;">
                    <i class="fas fa-user mr-1"></i> <?= $username; ?> &nbsp;•&nbsp; 
                    <i class="fas fa-calendar-alt mr-1"></i> <?= date('d M Y'); ?>
                </p>
            </div>
            <div class="stat-icon">
                <i class="fas fa-file-contract fa-lg"></i>
            </div>
        </div>
    </div>

    <!-- Elegant KPI Cards -->
    <div class="row">

        <!-- BC Masuk Bulan Ini -->
        <div class="col-xl-3 col-md-6 mb-4 animate-card">
            <div class="card modern-card gradient-card-1 shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <?= lang_line('dashboard_bc_incoming_month', 'BC Masuk'); ?>
                            </div>
                            <div class="h2 mb-0 font-weight-bold"><?= number_format($bc_grn_count); ?></div>
                            <div class="text-xs mt-1" style="opacity: 0.6; font-size: 0.7rem;">
                                Bulan Ini
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-down fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BC Keluar Bulan Ini -->
        <div class="col-xl-3 col-md-6 mb-4 animate-card">
            <div class="card modern-card gradient-card-2 shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <?= lang_line('dashboard_bc_outgoing_month', 'BC Keluar'); ?>
                            </div>
                            <div class="h2 mb-0 font-weight-bold"><?= number_format($bc_out_count); ?></div>
                            <div class="text-xs mt-1" style="opacity: 0.6; font-size: 0.7rem;">
                                Bulan Ini
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-up fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Dokumen BC -->
        <div class="col-xl-3 col-md-6 mb-4 animate-card">
            <div class="card modern-card gradient-card-3 shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <?= lang_line('dashboard_bc_total_docs', 'Total Dokumen'); ?>
                            </div>
                            <div class="h2 mb-0 font-weight-bold"><?= number_format($total_bc_documents); ?></div>
                            <div class="text-xs mt-1" style="opacity: 0.6; font-size: 0.7rem;">
                                Semua
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Verification -->
        <div class="col-xl-3 col-md-6 mb-4 animate-card">
            <div class="card modern-card gradient-card-4 shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-white-50 text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <?= lang_line('dashboard_bc_pending', 'Pending'); ?>
                            </div>
                            <div class="h2 mb-0 font-weight-bold"><?= number_format($pending_verification); ?></div>
                            <div class="text-xs mt-1" style="opacity: 0.6; font-size: 0.7rem;">
                                Verifikasi
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <!-- Dokumen BC Masuk Terbaru -->
        <div class="col-lg-6 mb-4">
            <div class="card modern-card shadow mb-4">
                <div class="card-header card-header-elegant">
                    <h6 class="m-0">
                        <i class="fas fa-arrow-down text-muted mr-2"></i><?= lang_line('dashboard_bc_recent_incoming', 'Dokumen BC Masuk Terbaru'); ?>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm modern-table mb-0">
                            <thead>
                                <tr>
                                    <th class="border-0">No</th>
                                    <th class="border-0">GRN</th>
                                    <th class="border-0">Tanggal</th>
                                    <th class="border-0">BC Type</th>
                                    <th class="border-0">BC No.</th>
                                    <th class="border-0">Supplier</th>
                                    <th class="border-0">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_bc_incoming)): $no = 1; ?>
                                    <?php foreach ($recent_bc_incoming as $grn): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $no++; ?></td>
                                        <td><strong style="color: #374151; font-size: 0.85rem;"><?= htmlspecialchars($grn->grn_number); ?></strong></td>
                                        <td class="small text-muted"><?= date('d/m/Y', strtotime($grn->grn_date)); ?></td>
                                        <td><span class="badge badge-modern" style="background: var(--brand-soft); color: var(--brand-1);"><?= htmlspecialchars($grn->bc_type_name ?: $grn->bc_type_code); ?></span></td>
                                        <td class="small text-muted"><?= htmlspecialchars($grn->bc_number); ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($grn->nama_supplier); ?></td>
                                        <td>
                                            <?php
                                            $badgeStyle = 'background: #F3F4F6; color: #6B7280;';
                                            if ($grn->status == 'Confirmed') $badgeStyle = 'background: #D1FAE5; color: #065F46;';
                                            if ($grn->status == 'Draft') $badgeStyle = 'background: #FEF3C7; color: #92400E;';
                                            ?>
                                            <span class="badge badge-modern" style="<?= $badgeStyle; ?>"><?= $grn->status; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4" style="color: #9CA3AF;">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: 0.3;"></i>
                                            <span style="font-size: 0.875rem;">Tidak ada data</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumen BC Keluar Terbaru -->
        <div class="col-lg-6 mb-4">
            <div class="card modern-card shadow mb-4">
                <div class="card-header card-header-elegant">
                    <h6 class="m-0">
                        <i class="fas fa-arrow-up text-muted mr-2"></i><?= lang_line('dashboard_bc_recent_outgoing', 'Dokumen BC Keluar Terbaru'); ?>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm modern-table mb-0">
                            <thead>
                                <tr>
                                    <th class="border-0">No</th>
                                    <th class="border-0">Doc No.</th>
                                    <th class="border-0">Tanggal</th>
                                    <th class="border-0">BC Type</th>
                                    <th class="border-0">BC No.</th>
                                    <th class="border-0">Tipe</th>
                                    <th class="border-0">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_bc_outgoing)): $no = 1; ?>
                                    <?php foreach ($recent_bc_outgoing as $doc): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $no++; ?></td>
                                        <td><strong style="color: #374151; font-size: 0.85rem;"><?= htmlspecialchars($doc->doc_number); ?></strong></td>
                                        <td class="small text-muted"><?= date('d/m/Y', strtotime($doc->doc_date)); ?></td>
                                        <td><span class="badge badge-modern" style="background: var(--brand-soft); color: var(--brand-1);"><?= htmlspecialchars($doc->bc_type_name ?: $doc->bc_type_code); ?></span></td>
                                        <td class="small text-muted"><?= htmlspecialchars($doc->bc_number); ?></td>
                                        <td>
                                            <?php if ($doc->doc_type == 'SUBKON'): ?>
                                                <span class="badge badge-modern" style="background: #F3E8FF; color: #6B21A8;">Subkon</span>
                                            <?php else: ?>
                                                <span class="badge badge-modern" style="background: #fce4ec; color: #ad1457;">Delivery</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeStyle = 'background: #F3F4F6; color: #6B7280;';
                                            if ($doc->status == 'Confirmed') $badgeStyle = 'background: #D1FAE5; color: #065F46;';
                                            if ($doc->status == 'Draft') $badgeStyle = 'background: #FEF3C7; color: #92400E;';
                                            ?>
                                            <span class="badge badge-modern" style="<?= $badgeStyle; ?>"><?= $doc->status; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4" style="color: #9CA3AF;">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: 0.3;"></i>
                                            <span style="font-size: 0.875rem;">Tidak ada data</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- BC Statistics Chart -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card modern-card shadow mb-4">
                <div class="card-header card-header-elegant">
                    <h6 class="m-0">
                        <i class="fas fa-chart-bar text-muted mr-2"></i><?= lang_line('dashboard_bc_statistics', 'Statistik Dokumen BC per Tipe'); ?>
                    </h6>
                </div>
                <div class="card-body chart-container">
                    <canvas id="bcChart" height="60"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // BC Statistics Chart - Elegant & Minimal
    var bcData = <?= json_encode($bc_statistics); ?>;
    
    var labels = bcData.map(function(item) { return item.type; });
    var incomingData = bcData.map(function(item) { return item.incoming; });
    var outgoingData = bcData.map(function(item) { return item.outgoing; });
    
    var ctx = document.getElementById('bcChart').getContext('2d');
    
    var bcChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '<?= lang_line("dashboard_bc_chart_incoming", "Dokumen Masuk"); ?>',
                data: incomingData,
                backgroundColor: '#6B7280',
                borderColor: '#4B5563',
                borderWidth: 0,
                borderRadius: 6,
                barThickness: 32
            }, {
                label: '<?= lang_line("dashboard_bc_chart_outgoing", "Dokumen Keluar"); ?>',
                data: outgoingData,
                backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155',
                borderColor: '#7C3AED',
                borderWidth: 0,
                borderRadius: 6,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                duration: 1200,
                easing: 'easeInOutCubic'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#6B7280',
                        font: {
                            size: 11,
                            family: "'Segoe UI', 'Nunito', sans-serif"
                        }
                    },
                    grid: {
                        color: '#F3F4F6',
                        drawBorder: false,
                        borderDash: [3, 3]
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#374151',
                        font: {
                            size: 11,
                            family: "'Segoe UI', 'Nunito', sans-serif",
                            weight: '600'
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: '#374151',
                        font: {
                            size: 12,
                            family: "'Segoe UI', 'Nunito', sans-serif",
                            weight: '500'
                        },
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8
                    }
                },
                tooltip: {
                    backgroundColor: '#1F2937',
                    titleColor: '#F9FAFB',
                    bodyColor: '#F9FAFB',
                    titleFont: {
                        size: 13,
                        family: "'Segoe UI', 'Nunito', sans-serif",
                        weight: '600'
                    },
                    bodyFont: {
                        size: 12,
                        family: "'Segoe UI', 'Nunito', sans-serif"
                    },
                    padding: 10,
                    cornerRadius: 6,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' dokumen';
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
    
    // Subtle number animation
    $('.h2').each(function() {
        var $this = $(this);
        var countTo = parseInt($this.text().replace(/,/g, ''));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum).toLocaleString());
            },
            complete: function() {
                $this.text(this.countNum.toLocaleString());
            }
        });
    });
});
</script>
