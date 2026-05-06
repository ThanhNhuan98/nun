<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Dispute
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Dịch mã lỗi khiếu nại sang tiếng Việt
     */
    public static function getIssueTypeLabel(string $type): string
    {
        $types = [
            'driver_attitude' => 'Thái độ tài xế không tốt',
            'damaged_goods'   => 'Hàng hóa bị hư hỏng',
            'late_delivery'   => 'Giao hàng quá trễ',
            'lost_goods'      => 'Thất lạc hàng hóa',
            'wrong_status'    => 'Cập nhật sai trạng thái',
            'overcharged'     => 'Thu sai cước phí',
            'other'           => 'Lý do khác'
        ];
        return $types[$type] ?? $type;
    }

    /**
     * Dịch trạng thái khiếu nại sang tiếng Việt
     */
    public static function getStatusLabel(string $status): string
    {
        $statuses = [
            'open'       => 'Mới tạo',
            'processing' => 'Đang xử lý',
            'resolved'   => 'Đã giải quyết',
            'closed'     => 'Đã đóng'
        ];
        return $statuses[$status] ?? $status;
    }

    public function countAll(string $statusFilter = '', string $search = ''): int
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($statusFilter !== '') {
            $whereClause .= " AND od.status = ?";
            $params[] = $statusFilter;
        }
        
        if ($search !== '') {
            $whereClause .= " AND (o.tracking_code LIKE ? OR u.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql = "
            SELECT COUNT(od.id) 
            FROM order_disputes od
            LEFT JOIN orders o ON od.order_id = o.id
            LEFT JOIN users u ON od.reported_by = u.id
            $whereClause
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAll(int $limit, int $offset, string $statusFilter = '', string $search = ''): array
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($statusFilter !== '') {
            $whereClause .= " AND od.status = ?";
            $params[] = $statusFilter;
        }
        
        if ($search !== '') {
            $whereClause .= " AND (o.tracking_code LIKE ? OR u.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql = "
            SELECT 
                od.id, od.issue_type, od.status, od.created_at,
                o.tracking_code,
                u.name AS reporter_name, u.phone AS reporter_phone, u.role AS reporter_role
            FROM order_disputes od
            LEFT JOIN orders o ON od.order_id = o.id
            LEFT JOIN users u ON od.reported_by = u.id
            $whereClause
            ORDER BY od.status DESC, od.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                od.*,
                o.tracking_code, o.status AS order_status,
                u.name AS reporter_name, u.phone AS reporter_phone, u.email AS reporter_email, u.role AS reporter_role, u.avatar AS reporter_avatar, u.no_show_count AS reporter_no_show_count
            FROM order_disputes od
            LEFT JOIN orders o ON od.order_id = o.id
            LEFT JOIN users u ON od.reported_by = u.id
            WHERE od.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, string $resolutionNote, int $resolvedBy): bool
    {
        $stmt = $this->db->prepare("UPDATE order_disputes SET status = ?, resolution_note = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $resolutionNote, $resolvedBy, $id]);
    }

    public function getOrderDispute(int $id)
    {
        $stmt = $this->db->prepare("SELECT order_id FROM order_disputes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(int $orderId, int $reporterId, string $issueType): bool
    {
        $stmt = $this->db->prepare("INSERT INTO order_disputes (order_id, reported_by, issue_type, status, created_at) VALUES (?, ?, ?, 'open', NOW())");
        return $stmt->execute([$orderId, $reporterId, $issueType]);
    }

    public function withdrawByOrderId(int $orderId): bool
    {
        $stmt = $this->db->prepare("UPDATE order_disputes SET status = 'closed', resolution_note = 'Khách hàng tự rút khiếu nại', resolved_at = NOW() WHERE order_id = ? AND status = 'open'");
        return $stmt->execute([$orderId]);
    }
}