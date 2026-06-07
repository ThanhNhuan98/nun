<?php
$stats = $stats ?? [
    'total_users' => 0, 'blocked_users' => 0, 'open_disputes' => 0, 'pending_online_payments' => 0,
    'total_orders' => 0, 'total_revenue' => 0
];

$growth = [
    'revenue' => $stats['growth_revenue'] ?? '0%', 
    'orders' => $stats['growth_orders'] ?? '0%', 
    'users' => $stats['growth_users'] ?? '0%'
];

// Trích xuất danh sách tài xế đang hoạt động từ $stats nếu có
$activeDrivers = $activeDrivers ?? ($stats['active_drivers'] ?? []);

// Phân trang cho danh sách tài xế
$driverPage = isset($_GET['dpage']) ? max(1, (int)$_GET['dpage']) : 1;
$driverPerPage = 5; // Số lượng tài xế mỗi trang
$totalActiveDrivers = count($activeDrivers);
$totalDriverPages = max(1, ceil($totalActiveDrivers / $driverPerPage));
$paginatedDrivers = array_slice($activeDrivers, ($driverPage - 1) * $driverPerPage, $driverPerPage);
?>

<?php require_once __DIR__ . '/../layouts/user_header.php'; ?>

<div class="admin-container">
    <div class="admin-page-header">
        <h2 class="admin-page-header__title"><?= app_e($pageTitle ?? 'Tổng quan hệ thống') ?></h2>
        <p class="admin-page-header__desc">Theo dõi và quản lý các hoạt động chính của NUN Express.</p>
    </div>

    <div class="dash-stats-3">
        <div class="stat-card">
            <div class="icon-wrap green"><span class="material-symbols-outlined">payments</span></div>
            <div>
                <div class="label">Tổng doanh thu phí Ship</div>
                <div class="value"><?= app_money($stats['total_revenue'] ?? 0, ' đ') ?></div>
                <div class="trend up"><span class="material-symbols-outlined" style="font-size: 16px;">trending_up</span> <?= $growth['revenue'] ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="icon-wrap blue"><span class="material-symbols-outlined">inbox</span></div>
            <div>
                <div class="label">Tổng vận đơn</div>
                <div class="value"><?= app_money($stats['total_orders'] ?? 0, '') ?></div>
                <div class="trend up"><span class="material-symbols-outlined" style="font-size: 16px;">trending_up</span> <?= $growth['orders'] ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="icon-wrap orange"><span class="material-symbols-outlined">group</span></div>
            <div>
                <div class="label">Người dùng (Khách & Tài xế)</div>
                <div class="value"><?= app_money($stats['total_users'] ?? 0, '') ?></div>
                <div class="trend up"><span class="material-symbols-outlined" style="font-size: 16px;">trending_up</span> <?= $growth['users'] ?></div>
            </div>
        </div>
    </div>

    <div class="grid-layout-2-1">
        <div class="card-v2">
            <div class="form-header-flex">
                <h3 style="color: var(--text-main); font-size: 18px; border:none; margin:0;"><span class="material-symbols-outlined" style="display:none;">bar_chart</span> Biểu đồ tăng trưởng</h3>
                <select id="chartFilter" class="toolbar-filter-select">
                    <option value="7days">7 ngày qua</option>
                    <option value="month">Tháng này</option>
                    <option value="year">Năm nay</option>
                </select>
            </div>
            <div class="chart-box">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="card-v2">
            <div class="form-header-flex" style="margin-bottom: 25px;">
                <h3 style="color: var(--danger); font-size: 18px;">
                    <span class="material-symbols-outlined">error</span> Cần xử lý ngay
                </h3>
            </div>
            
            <ul class="task-list">
                <li class="task-item task-item--danger">
                    <div class="task-item__left"><span class="material-symbols-outlined">gavel</span> Khiếu nại đang mở</div>
                    <span class="task-item__badge"><?= app_money($stats['open_disputes'] ?? 0, '') ?></span>
                </li>
                <li class="task-item task-item--info">
                    <div class="task-item__left"><span class="material-symbols-outlined">credit_card</span> Đơn chờ thanh toán Online</div>
                    <span class="task-item__badge"><?= app_money($stats['pending_online_payments'] ?? 0, '') ?></span>
                </li>
                <li class="task-item task-item--secondary">
                    <div class="task-item__left"><span class="material-symbols-outlined">block</span> Người dùng bị khóa</div>
                    <span class="task-item__badge"><?= app_money($stats['blocked_users'] ?? 0, '') ?></span>
                </li>
            </ul>
            <a href="/admin/tasks" class="btn-link-action">Xem chi tiết công việc →</a>
        </div>
    </div>

    <div class="card-v2" style="margin-top: 25px;">
        <div class="form-header-flex" style="margin-bottom: 20px;">
            <h3 style="color: var(--text-main); font-size: 18px; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="vertical-align: middle;">two_wheeler</span> Hoạt động của Tài xế hiện tại
                <?php $activeDriverCount = is_array($activeDrivers) ? count($activeDrivers) : 0; ?>
                <span style="background: #e0e7ff; color: #2563eb; font-size: 13px; font-weight: 700; padding: 2px 10px; border-radius: 12px;">
                    <?= $activeDriverCount ?> Đang hoạt động
                </span>
            </h3>
        </div>
        
        <?php if (empty($activeDrivers)): ?>
            <div class="empty-state-box" style="padding: 30px; text-align: center; color: var(--text-muted);">
                <span class="material-symbols-outlined" style="font-size: 40px; color: #cbd5e1;">explore_off</span>
                <p style="margin-top: 10px;">Hiện không có tài xế nào đang thực hiện đơn hàng.</p>
            </div>
        <?php else: ?>
            <div class="active-drivers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <?php foreach ($paginatedDrivers as $driver): ?>
                    <div class="driver-active-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 15px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.05)';">
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; color: #0f172a; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 20px; color: #64748b;">person</span>
                                    <?= app_e($driver['name']) ?>
                                </h4>
                                <div style="color: #64748b; font-size: 13px; display: flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">call</span> <?= app_e($driver['phone']) ?>
                                </div>
                            </div>
                            <span style="background: #f1f5f9; color: #334155; padding: 6px 10px; border-radius: 6px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #e2e8f0;">
                                <span class="material-symbols-outlined" style="font-size:16px;">directions_bike</span>
                                <?= app_e($driver['license_plate'] ?: 'Chưa cập nhật') ?>
                            </span>
                        </div>

                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 12px; display: grid; grid-template-columns: 1fr 1px 1fr; gap: 10px; align-items: center;">
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Số đơn đang chạy</div>
                                <div style="font-size: 16px; font-weight: 800; color: #d97706;"><?= $driver['active_orders'] ?> đơn</div>
                            </div>
                            <div style="width: 1px; background: #e2e8f0; height: 30px; margin: 0 auto;"></div>
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Số lần vi phạm</div>
                                <div style="font-size: 16px; font-weight: 800; color: <?= ($driver['violation_count'] > 0) ? '#ef4444' : '#64748b' ?>;"><?= $driver['violation_count'] ?> lần</div>
                            </div>
                            <div style="grid-column: 1 / -1; height: 1px; background: #e2e8f0;"></div>
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Thu nhập (Đơn đang chạy)</div>
                                <div style="font-size: 15px; font-weight: 800; color: #10b981;"><?= app_money(app_calculate_driver_earnings((float)($driver['total_active_fee'] ?? 0))) ?></div>
                            </div>
                            <div style="width: 1px; background: #e2e8f0; height: 30px; margin: 0 auto;"></div>
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Tổng thu nhập (Ví)</div>
                                <div style="font-size: 15px; font-weight: 800; color: #3b82f6;"><?= app_money((float)($driver['balance'] ?? 0)) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($driver['tracking_codes'])): ?>
                            <div style="background: #fff; padding: 2px 0;">
                                <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px;">Chi tiết trạng thái đơn:</div>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <?php 
                                    $codes = explode(',', $driver['tracking_codes']);
                                    foreach ($codes as $codeStr): 
                                        $parts = explode(':', $codeStr);
                                        if (count($parts) < 2) continue;
                                        $code = $parts[0];
                                        $status = $parts[1];
                                        $statusLabel = \App\Models\Order::getStatusLabel($status);
                                        $statusColor = \App\Models\Order::getStatusColor($status);
                                    ?>
                                        <a href="/admin/orders?search=<?= urlencode($code) ?>" style="background: #f8fafc; color: #334155; font-size: 12px; font-weight: 600; padding: 6px 10px; border-radius: 6px; text-decoration: none; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; transition: 0.2s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#f8fafc';">
                                            <span><?= app_e($code) ?></span>
                                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: <?= $statusColor ?>;"></span> <span style="color: <?= $statusColor ?>; font-size: 11px;"><?= app_e($statusLabel) ?></span>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: auto; padding-top: 15px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                            <?php if (!empty($driver['current_lat']) && !empty($driver['current_lng'])): ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= $driver['current_lat'] ?>,<?= $driver['current_lng'] ?>" target="_blank" style="flex: 1; text-align: center; background: #eff6ff; color: #2563eb; padding: 10px; font-size: 13px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#dbeafe';" onmouseout="this.style.background='#eff6ff';">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">my_location</span> Vị trí GPS
                                </a>
                            <?php else: ?>
                                <div style="flex: 1; text-align: center; background: #f1f5f9; color: #94a3b8; padding: 10px; font-size: 13px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 5px;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">location_off</span> Mất tín hiệu
                                </div>
                            <?php endif; ?>
                            
                            <a href="/admin/orders?search=<?= urlencode($driver['name']) ?>" style="flex: 1; text-align: center; background: #2563eb; color: #fff; padding: 10px; font-size: 13px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none; transition: 0.2s;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                                Chi tiết đơn <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalDriverPages > 1): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding: 0 12px;">
                    <span style="font-size: 13px; color: var(--text-muted);">
                        Hiển thị <?= count($paginatedDrivers) ?> / <?= $totalActiveDrivers ?> tài xế
                    </span>
                    <div style="display: flex; gap: 5px;">
                        <?php if ($driverPage > 1): ?>
                            <a href="?dpage=<?= $driverPage - 1 ?>" style="padding: 4px 10px; background: #f1f5f9; color: #334155; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;">&laquo; Trước</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalDriverPages; $i++): ?>
                            <a href="?dpage=<?= $i ?>" style="padding: 4px 10px; background: <?= $i === $driverPage ? '#2563eb' : '#f1f5f9' ?>; color: <?= $i === $driverPage ? '#fff' : '#334155' ?>; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($driverPage < $totalDriverPages): ?>
                            <a href="?dpage=<?= $driverPage + 1 ?>" style="padding: 4px 10px; background: #f1f5f9; color: #334155; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;">Sau &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');

        // Lưu trữ toàn bộ dữ liệu từ PHP vào object JS
        const chartData = {
            '7days': {
                labels: <?= json_encode($stats['chart_labels'] ?? []) ?>,
                data: <?= json_encode($stats['chart_revenues'] ?? []) ?>
            },
            'month': {
                labels: <?= json_encode($stats['chart_month_labels'] ?? []) ?>,
                data: <?= json_encode($stats['chart_month_revenues'] ?? []) ?>
            },
            'year': {
                labels: <?= json_encode($stats['chart_year_labels'] ?? []) ?>,
                data: <?= json_encode($stats['chart_year_revenues'] ?? []) ?>
            }
        };
        
        const data = {
            labels: chartData['7days'].labels,
            datasets: [{
                label: 'Số lượng / Doanh thu',
                data: chartData['7days'].data,
                backgroundColor: '#93c5fd', 
                hoverBackgroundColor: '#2563eb', 
                borderRadius: 4,
                barPercentage: 0.6
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } 
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: "'Inter', sans-serif" }, color: '#64748b' }
                    },
                    y: {
                        border: { display: false },
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: "'Inter', sans-serif" }, color: '#64748b' }
                    }
                }
            }
        };

        const revenueChart = new Chart(ctx, config);

        // Lắng nghe sự kiện thay đổi bộ lọc
        document.getElementById('chartFilter').addEventListener('change', function(e) {
            const selectedPeriod = e.target.value;
            if (chartData[selectedPeriod]) {
                revenueChart.data.labels = chartData[selectedPeriod].labels;
                revenueChart.data.datasets[0].data = chartData[selectedPeriod].data;
                revenueChart.update(); 
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/user_footer.php'; ?>
