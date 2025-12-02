<style>
:root {
    --primary: #4F46E5;
    --primary-dark: #4338CA;
    --success: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --info: #3B82F6;
    --dark: #1F2937;
    --light-bg: #F9FAFB;
    --card-bg: #FFFFFF;
    --text-primary: #111827;
    --text-secondary: #6B7280;
    --border: #E5E7EB;
}

body { background: var(--light-bg); }

.modern-dashboard { 
    padding: 2rem 1rem; 
    max-width: 1400px; 
    margin: 0 auto;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Hero Header */
.hero-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 24px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.2);
    position: relative;
    overflow: hidden;
}

.hero-header::before {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    top: -150px;
    right: -100px;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.hero-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.hero-header p {
    opacity: 0.95;
    font-size: 0.95rem;
    position: relative;
    z-index: 1;
}

/* Modern Card */
.metric-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 1.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border);
    height: 100%;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, var(--primary), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.metric-card:hover {
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
    transform: translateY(-8px);
    border-color: var(--primary);
}

.metric-card:hover::before {
    transform: translateX(100%);
}

/* Stat Layout */
.stat-layout {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.stat-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    position: relative;
}

.stat-icon-wrapper::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 18px;
    padding: 2px;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.3s;
}

.metric-card:hover .stat-icon-wrapper::after {
    opacity: 1;
}

.stat-icon-wrapper.primary { background: linear-gradient(135deg, rgba(79, 70, 229, 0.15), rgba(67, 56, 202, 0.25)); color: var(--primary); }
.stat-icon-wrapper.success { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.25)); color: var(--success); }
.stat-icon-wrapper.warning { background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.25)); color: var(--warning); }
.stat-icon-wrapper.danger { background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.25)); color: var(--danger); }
.stat-icon-wrapper.info { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.25)); color: var(--info); }
.stat-icon-wrapper.dark { background: linear-gradient(135deg, rgba(31, 41, 55, 0.15), rgba(17, 24, 39, 0.25)); color: var(--dark); }

.stat-details { flex: 1; min-width: 0; }

.stat-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-subtitle {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

/* Chart Section */
.chart-section {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid var(--border);
}

.section-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i { color: var(--primary); font-size: 1rem; }

/* Activity Feed */
.activity-feed {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-entry {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
    border-radius: 12px;
    margin-bottom: 0.25rem;
}

.activity-entry:last-child { border-bottom: none; }

.activity-entry:hover {
    background: linear-gradient(90deg, rgba(79, 70, 229, 0.03), transparent);
    padding-left: 1.5rem;
}

.activity-details { flex: 1; }

.activity-heading {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.activity-subtext {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.activity-timestamp {
    font-size: 0.75rem;
    color: var(--text-secondary);
    white-space: nowrap;
    margin-left: 1rem;
    font-weight: 600;
}

/* Alert Badges */
.alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
}

.alert-badge:hover {
    transform: translateX(4px);
}

.alert-badge.warning { 
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05)); 
    color: var(--warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.alert-badge.danger { 
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)); 
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Action Buttons */
.action-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    border-radius: 16px;
    border: 2px solid var(--border);
    background: var(--card-bg);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: var(--text-primary);
    position: relative;
    overflow: hidden;
}

.action-tile::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}

.action-tile:hover {
    border-color: var(--primary);
    background: rgba(79, 70, 229, 0.02);
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
    text-decoration: none;
}

.action-tile:hover::before {
    opacity: 1;
}

.action-tile i {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 0.75rem;
    position: relative;
    z-index: 1;
    transition: all 0.3s;
}

.action-tile:hover i {
    transform: scale(1.15) rotate(5deg);
}

.action-tile span {
    font-size: 0.8rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

/* Custom Buttons */
.btn-modern {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-modern:hover {
    background: var(--primary-dark);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

.btn-outline-modern {
    background: white;
    color: var(--text-primary);
    border: 2px solid var(--border);
    padding: 0.625rem 1.25rem;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-outline-modern:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(79, 70, 229, 0.05);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 4rem;
    opacity: 0.2;
    margin-bottom: 1rem;
    display: block;
    background: linear-gradient(135deg, var(--primary), var(--info));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.empty-state p {
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-dashboard { padding: 1rem 0.5rem; }
    .hero-header { padding: 2rem 1.5rem; }
    .hero-header h1 { font-size: 1.5rem; }
    .stat-number { font-size: 1.75rem; }
    .chart-section { padding: 1.5rem; }
}
</style>

<div class="modern-dashboard">
    
    <!-- Hero Header -->
    <div class="hero-header">
        <h1><i class="fas fa-chart-line me-2"></i><?= lang_line('dashboard_title', 'Dashboard'); ?></h1>
        <p><i class="fas fa-user me-2"></i><?= html_escape($username ?? 'User'); ?> • <?= html_escape($role_name ?? 'Role'); ?> • <i class="fas fa-calendar ms-3 me-2"></i><?= date('d F Y'); ?></p>
    </div>

    <!-- Primary KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_po_this_month', 'PO Bulan Ini'); ?></div>
                        <div class="stat-number"><?= number_format($new_po_count ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper success">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_active_wo', 'WO Aktif'); ?></div>
                        <div class="stat-number"><?= number_format($active_wo_count ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper warning">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_shipments', 'Pengiriman'); ?></div>
                        <div class="stat-number"><?= number_format($shipments_today_count ?? 0); ?></div>
                        <div class="stat-subtitle"><?= lang_line('dashboard_today', 'Hari ini'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper info">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_sales_contract', 'Sales Contract'); ?></div>
                        <div class="stat-number"><?= number_format($sales_contract_count ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper success">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_cash_balance', 'Cash Balance'); ?></div>
                        <div class="stat-number" style="font-size: 1.5rem;">Rp <?= number_format($cash_balance ?? 0, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper warning">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_receivables', 'Receivables'); ?></div>
                        <div class="stat-number" style="font-size: 1.5rem;">Rp <?= number_format($total_receivables ?? 0, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="metric-card">
                <div class="stat-layout">
                    <div class="stat-icon-wrapper dark">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-label"><?= lang_line('dashboard_inventory', 'Inventory'); ?></div>
                        <div class="stat-number" style="font-size: 1.5rem;"><?= number_format($inventory_fg_qty ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-subtitle"><?= lang_line('dashboard_fg_units', 'FG Units'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart & Activity -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="chart-section">
                <div class="section-header">
                    <h6 class="section-title mb-0">
                        <i class="fas fa-chart-bar"></i> <?= lang_line('dashboard_sc_performance', 'Sales Contract Performance'); ?>
                    </h6>
                    <button class="btn btn-outline-modern btn-sm" data-bs-toggle="modal" data-bs-target="#scTraceModal">
                        <i class="fas fa-search"></i> <?= lang_line('dashboard_trace', 'Trace'); ?>
                    </button>
                </div>
                <canvas id="scChart" height="90"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-section" style="height: 100%;">
                <h6 class="section-title">
                    <i class="fas fa-history"></i> <?= lang_line('dashboard_recent_activity', 'Recent Activity'); ?>
                </h6>
                <ul class="activity-feed">
                    <?php if (!empty($recent_pos) && count($recent_pos) > 0): ?>
                        <?php foreach (array_slice($recent_pos, 0, 5) as $po): ?>
                        <li class="activity-entry">
                            <div class="activity-details">
                                <div class="activity-heading"><?= html_escape($po->po_number ?? '-'); ?></div>
                                <div class="activity-subtext"><?= html_escape($po->nama_supplier ?? 'Supplier'); ?></div>
                            </div>
                            <div class="activity-timestamp"><?= date('d/m', strtotime($po->po_date ?? 'now')); ?></div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p><?= lang_line('dashboard_no_activity', 'No recent activity'); ?></p>
                        </div>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alerts & Actions -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="chart-section">
                <h6 class="section-title">
                    <i class="fas fa-bell"></i> <?= lang_line('dashboard_alerts', 'Alerts'); ?>
                </h6>
                
                <?php if (($pending_do_count ?? 0) > 0 || !empty($low_stock_alerts)): ?>
                    <?php if (($pending_do_count ?? 0) > 0): ?>
                    <div class="alert-badge danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $pending_do_count; ?> <?= lang_line('dashboard_pending_do', 'Pending DO'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($low_stock_alerts)): ?>
                        <?php foreach (array_slice($low_stock_alerts, 0, 3) as $item): ?>
                        <div class="alert-badge warning">
                            <i class="fas fa-box-open"></i>
                            <span><?= html_escape($item->fg_code ?? $item->fg_name ?? 'Item'); ?> - <?= lang_line('dashboard_low_stock', 'Low Stock'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?= base_url('fg_delivery'); ?>" class="btn btn-outline-modern btn-sm me-2">
                            <i class="fas fa-truck"></i> <?= lang_line('dashboard_view_deliveries', 'View Deliveries'); ?>
                        </a>
                        <a href="<?= base_url('fg_stock'); ?>" class="btn btn-outline-modern btn-sm">
                            <i class="fas fa-boxes"></i> <?= lang_line('dashboard_view_stock', 'View Stock'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p style="color: var(--success); font-size: 1rem;"><?= lang_line('dashboard_all_operational', 'All Systems Operational!'); ?></p>
                        <p style="font-size: 0.875rem;"><?= lang_line('dashboard_no_alerts', 'No alerts at this moment'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="chart-section">
                <h6 class="section-title">
                    <i class="fas fa-rocket"></i> <?= lang_line('dashboard_quick_actions', 'Quick Actions'); ?>
                </h6>
                <div class="row g-3">
                    <div class="col-4">
                        <a href="<?= base_url('purchase_order'); ?>" class="action-tile">
                            <i class="fas fa-plus-circle"></i>
                            <span><?= lang_line('dashboard_new_po', 'New PO'); ?></span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?= base_url('work_order/create'); ?>" class="action-tile">
                            <i class="fas fa-tasks"></i>
                            <span><?= lang_line('dashboard_new_wo', 'New WO'); ?></span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?= base_url('sales_contract'); ?>" class="action-tile">
                            <i class="fas fa-file-signature"></i>
                            <span><?= lang_line('dashboard_new_sc', 'New SC'); ?></span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?= base_url('fg_delivery/create'); ?>" class="action-tile">
                            <i class="fas fa-shipping-fast"></i>
                            <span><?= lang_line('dashboard_new_do', 'New DO'); ?></span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?= base_url('material_request'); ?>" class="action-tile">
                            <i class="fas fa-clipboard-list"></i>
                            <span><?= lang_line('dashboard_new_mr', 'New MR'); ?></span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?= base_url('reports'); ?>" class="action-tile">
                            <i class="fas fa-chart-pie"></i>
                            <span><?= lang_line('dashboard_reports', 'Reports'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- SC Trace Modal -->
<div class="modal fade" id="scTraceModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title"><i class="fas fa-route me-2"></i><?= lang_line('dashboard_sc_trace', 'Sales Contract Trace'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div class="row g-3 mb-3">
                    <div class="col-md-9">
                        <select id="sc_select_trace" class="form-control" style="border-radius: 12px; border: 2px solid var(--border); padding: 0.75rem;"></select>
                    </div>
                    <div class="col-md-3">
                        <button id="btnLoadTrace" class="btn btn-modern w-100">
                            <i class="fas fa-search me-2"></i><?= lang_line('dashboard_trace', 'Trace'); ?>
                        </button>
                    </div>
                </div>
                <hr style="border-color: var(--border);">
                <div id="traceContent">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p><?= lang_line('dashboard_select_sc_trace', 'Select a Sales Contract to trace'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const nf = new Intl.NumberFormat('id-ID');

    // Chart
    fetch('<?= base_url('dashboard/sc_chart_data'); ?>')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('scChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.sc_number),
                    datasets: [
                        { 
                            label: 'Ordered', 
                            data: data.map(d => d.po_qty), 
                            backgroundColor: 'rgba(79, 70, 229, 0.8)',
                            borderRadius: 8,
                            borderSkipped: false
                        },
                        { 
                            label: 'Produced', 
                            data: data.map(d => d.produced_qty), 
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderRadius: 8,
                            borderSkipped: false
                        },
                        { 
                            label: 'Delivered', 
                            data: data.map(d => d.delivered_qty), 
                            backgroundColor: 'rgba(245, 158, 11, 0.8)',
                            borderRadius: 8,
                            borderSkipped: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { 
                            position: 'top',
                            labels: { 
                                font: { size: 12, weight: '600' },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(31, 41, 55, 0.95)',
                            padding: 12,
                            borderRadius: 10,
                            titleFont: { size: 13, weight: '600' },
                            bodyFont: { size: 12 },
                            callbacks: { 
                                label: ctx => ctx.dataset.label + ': ' + nf.format(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.03)' },
                            ticks: { 
                                font: { size: 11 },
                                callback: v => nf.format(v)
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }).catch(e => console.error('Chart error:', e));

    // Select2
    try {
        if (window.jQuery && jQuery().select2) {
            $('#sc_select_trace').select2({
                placeholder: 'Search Sales Contract...',
                allowClear: true,
                ajax: {
                    url: '<?= base_url('dashboard/sc_search'); ?>',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term }),
                    processResults: data => data,
                    cache: true
                },
                minimumInputLength: 1
            });
        }
    } catch(e) { console.warn('Select2 not available'); }

    // Trace
    document.getElementById('btnLoadTrace').addEventListener('click', function(){
        const scId = document.getElementById('sc_select_trace').value;
        if (!scId) { 
            alert('Select a Sales Contract first'); 
            return; 
        }
        
        const target = document.getElementById('traceContent');
        target.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div></div>';
        
        fetch('<?= base_url('dashboard/sc_trace/'); ?>' + scId)
            .then(r => r.json())
            .then(json => {
                let html = '<div class="chart-section mb-3"><h6 class="mb-2">' + (json.sc.sc_number || '') + '</h6><small class="text-muted">' + (json.sc.customer_name || '') + '</small></div>';

                html += '<div class="chart-section mb-3"><h6 class="section-title">Material Requests</h6>';
                if (json.mrs && json.mrs.length > 0) {
                    json.mrs.forEach(mr => {
                        html += '<div class="alert-badge info mb-2 d-block" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05)); color: var(--info); border: 1px solid rgba(59, 130, 246, 0.2);"><strong>' + (mr.mr_number||'') + '</strong> - ' + (mr.request_date||'') + '</div>';
                    });
                } else {
                    html += '<p class="text-muted small">No MRs found</p>';
                }
                html += '</div>';

                html += '<div class="chart-section mb-3"><h6 class="section-title">Work Orders</h6>';
                if (json.work_orders && json.work_orders.length > 0) {
                    json.work_orders.forEach(wo => {
                        html += '<div class="alert-badge" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); display: block;"><strong>' + (wo.wo_number||'') + '</strong> - ' + (wo.product_name||'') + ' (Produced: ' + nf.format(wo.produced_qty||0) + ')</div>';
                    });
                } else {
                    html += '<p class="text-muted small">No WOs found</p>';
                }
                html += '</div>';

                html += '<div class="chart-section"><h6 class="section-title">Deliveries</h6>';
                if (json.deliveries && json.deliveries.length > 0) {
                    html += '<div class="table-responsive"><table class="table table-sm table-hover"><thead style="background: var(--light-bg);"><tr><th>DO Number</th><th>Date</th><th>Qty</th></tr></thead><tbody>';
                    json.deliveries.forEach(d => {
                        html += '<tr><td>' + (d.delivery_number||'') + '</td><td>' + (d.delivery_date||'') + '</td><td>' + nf.format(d.total_shipped||0) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                } else {
                    html += '<p class="text-muted small">No deliveries found</p>';
                }
                html += '</div>';

                target.innerHTML = html;
            })
            .catch(() => {
                target.innerHTML = '<div class="alert alert-danger" style="border-radius: 12px;">Error loading data</div>';
            });
    });
});
</script>
