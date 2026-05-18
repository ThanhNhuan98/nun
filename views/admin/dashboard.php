<?php
$stats = $stats ?? [
    'total_users' => 0, 'blocked_users' => 0, 'open_disputes' => 0,
    'pending_cod_settlements' => 0, 'pending_online_payments' => 0,
    'total_orders' => 0, 'total_revenue' => 0
];

$growth = [
    'revenue' => $stats['growth_revenue'] ?? '0%', 
    'orders' => $stats['growth_orders'] ?? '0%', 
    'users' => $stats['growth_users'] ?? '0%'
];
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
                <li class="task-item task-item--warning">
                    <div class="task-item__left"><span class="material-symbols-outlined">currency_exchange</span> Yêu cầu đối soát COD</div>
                    <span class="task-item__badge"><?= app_money($stats['pending_cod_settlements'] ?? 0, '') ?></span>
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
                backgroundColor: '#93c5fd', // Màu xanh nhạt (Tailwind blue-300)
                hoverBackgroundColor: '#2563eb', // Đổi màu khi hover
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