<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class DashboardModel
{
    protected PDO $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Lấy các số liệu thống kê 
    public function getStats(): array
    {
        $sql = "
            SELECT 
                (SELECT COUNT(id) FROM users) as total_users,
                (SELECT SUM(IF(is_blocked = 1, 1, 0)) FROM users) as blocked_users,
                (SELECT COUNT(id) FROM order_disputes WHERE status = 'open') as open_disputes,
                (SELECT COUNT(id) FROM orders WHERE status = 'awaiting_payment' AND is_archived = 0) as pending_online,
                (SELECT COUNT(id) FROM orders WHERE is_archived = 0) as total_orders,
                (
                    SELECT SUM(fin.shipping_fee) 
                    FROM orders o 
                    LEFT JOIN order_finances fin ON fin.order_id = o.id 
                    WHERE o.status = 'completed' AND o.is_archived = 0
                ) as total_revenue
        ";
        
        $stmt = $this->db->query($sql);
        $overview = $stmt->fetch(PDO::FETCH_ASSOC);

        $userStats = [
            'total' => $overview['total_users'] ?? 0,
            'blocked' => $overview['blocked_users'] ?? 0
        ];
        $openDisputes = $overview['open_disputes'] ?? 0;
        $pendingOnline = $overview['pending_online'] ?? 0;
        $totalOrders = $overview['total_orders'] ?? 0;
        $totalRevenue = $overview['total_revenue'] ?? 0;

        $stmt = $this->db->query("
            SELECT DATE(o.updated_at) as date, SUM(fin.shipping_fee) as daily_revenue
            FROM orders o
            JOIN order_finances fin ON fin.order_id = o.id
            WHERE o.status = 'completed' AND o.is_archived = 0 
              AND o.updated_at >= DATE(NOW() - INTERVAL 6 DAY)
            GROUP BY DATE(o.updated_at)
            ORDER BY date ASC
        ");
        $chartDataRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chartLabels = [];
        $chartRevenues = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateStr = date('Y-m-d', strtotime("-$i days"));
            $chartLabels[] = date('d/m', strtotime("-$i days"));
            $chartRevenues[$dateStr] = 0;
        }
        if ($chartDataRows) {
            foreach ($chartDataRows as $row) {
                $dateStr = $row['date'];
                if (isset($chartRevenues[$dateStr])) {
                    $chartRevenues[$dateStr] = (int) $row['daily_revenue'];
                }
            }
        }

        $stmtMonth = $this->db->query("
            SELECT DAY(o.updated_at) as day, SUM(fin.shipping_fee) as daily_revenue
            FROM orders o
            JOIN order_finances fin ON fin.order_id = o.id
            WHERE o.status = 'completed' AND o.is_archived = 0 
              AND o.updated_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
            GROUP BY DAY(o.updated_at)
            ORDER BY day ASC
        ");
        $monthDataRows = $stmtMonth->fetchAll(PDO::FETCH_ASSOC);

        $daysInMonth = date('t');
        $chartMonthLabels = [];
        $chartMonthRevenues = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartMonthLabels[] = $d . '/' . date('m');
            $chartMonthRevenues[$d] = 0;
        }
        if ($monthDataRows) {
            foreach ($monthDataRows as $row) {
                $chartMonthRevenues[(int)$row['day']] = (int)$row['daily_revenue'];
            }
        }

        $stmtYear = $this->db->query("
            SELECT MONTH(o.updated_at) as month, SUM(fin.shipping_fee) as monthly_revenue
            FROM orders o
            JOIN order_finances fin ON fin.order_id = o.id
            WHERE o.status = 'completed' AND o.is_archived = 0 
              AND o.updated_at >= DATE_FORMAT(NOW() ,'%Y-01-01')
            GROUP BY MONTH(o.updated_at)
            ORDER BY month ASC
        ");
        $yearDataRows = $stmtYear->fetchAll(PDO::FETCH_ASSOC);

        $chartYearLabels = ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'];
        $chartYearRevenues = array_fill(1, 12, 0);
        if ($yearDataRows) {
            foreach ($yearDataRows as $row) {
                $chartYearRevenues[(int)$row['month']] = (int)$row['monthly_revenue'];
            }
        }

        // Lấy danh sách tài xế đang hoạt động và mã đơn họ đang chạy
        $stmtDrivers = $this->db->query("
            SELECT 
                u.name, dp.license_plate, u.phone, dp.current_lat, dp.current_lng, dp.balance, u.violation_count,
                COUNT(o.id) as active_orders,
                GROUP_CONCAT(CONCAT(o.tracking_code, ':', o.status) SEPARATOR ',') as tracking_codes,
                SUM(fin.shipping_fee) as total_active_fee
            FROM users u
            JOIN driver_profiles dp ON u.id = dp.user_id
            JOIN order_deliveries od ON u.id = od.driver_id
            JOIN orders o ON od.order_id = o.id
            LEFT JOIN order_finances fin ON fin.order_id = o.id
            WHERE o.status IN ('accepted', 'picking_up', 'in_transit', 'shipping', 'returning') AND o.is_archived = 0
            GROUP BY u.id
            ORDER BY active_orders DESC
        ");
        $activeDrivers = $stmtDrivers->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_users' => (int) ($userStats['total'] ?? 0),
            'blocked_users' => (int) ($userStats['blocked'] ?? 0),
            'open_disputes' => (int) $openDisputes,
            'pending_online_payments' => (int) $pendingOnline,
            'total_orders' => (int) $totalOrders,
            'total_revenue' => (int) $totalRevenue,
            'chart_labels' => $chartLabels,
            'chart_revenues' => array_values($chartRevenues),
            'chart_month_labels' => $chartMonthLabels,
            'chart_month_revenues' => array_values($chartMonthRevenues),
            'chart_year_labels' => $chartYearLabels,
            'chart_year_revenues' => array_values($chartYearRevenues),
            'active_drivers' => $activeDrivers,
        ];
    }
}
