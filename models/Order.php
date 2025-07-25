<?php
class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        return $this->db->insert('orders', $data);
    }
    
    public function findById($id) {
        return $this->db->selectOne('orders', 'id = ?', [$id]);
    }
    
    public function findByOrderNumber($orderNumber) {
        return $this->db->selectOne('orders', 'order_number = ?', [$orderNumber]);
    }
    
    public function getAll($filters = []) {
        $whereClause = '1=1';
        $params = [];
        
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $whereClause .= ' AND o.status = ?';
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $whereClause .= ' AND (o.order_number LIKE ? OR o.customer LIKE ? OR o.description LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $whereClause .= ' AND DATE(o.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $whereClause .= ' AND DATE(o.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        
        $sql = "SELECT o.*, u.full_name as created_by_name,
                       COUNT(oi.id) as total_items,
                       SUM(CASE WHEN oi.scanned = 1 THEN 1 ELSE 0 END) as scanned_items
                FROM orders o 
                LEFT JOIN users u ON o.created_by = u.id 
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE {$whereClause}
                GROUP BY o.id
                ORDER BY o.created_at DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    public function update($id, $data) {
        return $this->db->update('orders', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        // Delete related items first
        $this->db->delete('order_items', 'order_id = ?', [$id]);
        return $this->db->delete('orders', 'id = ?', [$id]);
    }
    
    public function getItems($orderId) {
        $sql = "SELECT oi.*, p.product_code, p.name as product_name, p.weight_kg,
                       u.full_name as scanned_by_name
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON oi.scanned_by = u.id
                WHERE oi.order_id = ?
                ORDER BY oi.id";
        
        return $this->db->query($sql, [$orderId])->fetchAll();
    }
    
    public function addItem($orderId, $data) {
        $data['order_id'] = $orderId;
        return $this->db->insert('order_items', $data);
    }
    
    public function updateItem($itemId, $data) {
        return $this->db->update('order_items', $data, 'id = ?', [$itemId]);
    }
    
    public function deleteItem($itemId) {
        return $this->db->delete('order_items', 'id = ?', [$itemId]);
    }
    
    public function getStatistics() {
        $stats = [];
        
        // Total orders
        $stats['total'] = $this->db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
        
        // Orders by status
        $statusStats = $this->db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status")->fetchAll();
        $stats['by_status'] = [];
        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = $stat['count'];
        }
        
        // Orders today
        $stats['today'] = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
        
        // Orders this week
        $stats['this_week'] = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetch()['count'];
        
        // Orders this month
        $stats['this_month'] = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())")->fetch()['count'];
        
        return $stats;
    }
    
    public function updateStatus($id, $status) {
        return $this->update($id, ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    public function getRecentOrders($limit = 10) {
        $sql = "SELECT o.*, u.full_name as created_by_name 
                FROM orders o 
                LEFT JOIN users u ON o.created_by = u.id 
                ORDER BY o.created_at DESC 
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }
}
?>