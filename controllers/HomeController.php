<?php
class HomeController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get recent orders
        $recentOrders = $this->getRecentOrders();
        
        // Get active deliveries
        $activeDeliveries = $this->getActiveDeliveries();
        
        // Get pending notifications
        $notifications = $this->getNotifications();
        
        include '../views/home/dashboard.php';
    }
    
    private function getDashboardStats() {
        try {
            $stats = [];
            
            // Total orders today
            $stats['orders_today'] = $this->db->query(
                "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()"
            )->fetch()['count'];
            
            // Active deliveries
            $stats['active_deliveries'] = $this->db->query(
                "SELECT COUNT(*) as count FROM deliveries WHERE status IN ('waiting', 'loading', 'loaded')"
            )->fetch()['count'];
            
            // Pending orders
            $stats['pending_orders'] = $this->db->query(
                "SELECT COUNT(*) as count FROM orders WHERE status IN ('created', 'in_progress')"
            )->fetch()['count'];
            
            // Completed deliveries today
            $stats['completed_today'] = $this->db->query(
                "SELECT COUNT(*) as count FROM deliveries WHERE status = 'delivered' AND DATE(updated_at) = CURDATE()"
            )->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            return [
                'orders_today' => 0,
                'active_deliveries' => 0,
                'pending_orders' => 0,
                'completed_today' => 0
            ];
        }
    }
    
    private function getRecentOrders() {
        try {
            return $this->db->query(
                "SELECT o.*, u.full_name as created_by_name 
                 FROM orders o 
                 LEFT JOIN users u ON o.created_by = u.id 
                 ORDER BY o.created_at DESC 
                 LIMIT 10"
            )->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getActiveDeliveries() {
        try {
            return $this->db->query(
                "SELECT d.*, o.order_number, o.customer 
                 FROM deliveries d 
                 LEFT JOIN orders o ON d.order_id = o.id 
                 WHERE d.status IN ('waiting', 'loading', 'loaded') 
                 ORDER BY d.entry_time DESC"
            )->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getNotifications() {
        try {
            return $this->db->query(
                "SELECT * FROM notifications 
                 WHERE (user_id = ? OR user_id IS NULL) 
                 AND read_status = 0 
                 ORDER BY created_at DESC 
                 LIMIT 5",
                [$_SESSION['user_id']]
            )->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>