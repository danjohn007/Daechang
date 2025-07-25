<?php
class AdminController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function dashboard() {
        // Get system statistics
        $stats = $this->getSystemStats();
        
        // Get recent activity
        $recentActivity = $this->getRecentActivity();
        
        // Get user statistics
        $userStats = $this->getUserStats();
        
        include '../views/admin/dashboard.php';
    }
    
    public function users() {
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? 'all';
        $status = $_GET['status'] ?? 'all';
        
        $whereClause = '1=1';
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= ' AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($role !== 'all') {
            $whereClause .= ' AND role = ?';
            $params[] = $role;
        }
        
        if ($status !== 'all') {
            $whereClause .= ' AND status = ?';
            $params[] = $status;
        }
        
        try {
            $users = $this->db->select(
                'users', 
                $whereClause, 
                $params, 
                'id, username, full_name, email, role, status, last_login, created_at'
            );
        } catch (Exception $e) {
            $users = [];
            $_SESSION['error'] = 'Error al cargar los usuarios';
        }
        
        include '../views/admin/users.php';
    }
    
    public function createUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'operator';
            
            if (empty($username) || empty($password) || empty($fullName) || empty($email)) {
                $_SESSION['error'] = 'Todos los campos son requeridos';
                header('Location: /admin/users');
                exit;
            }
            
            try {
                // Check if username or email already exists
                $existing = $this->db->selectOne('users', 'username = ? OR email = ?', [$username, $email]);
                if ($existing) {
                    $_SESSION['error'] = 'El usuario o email ya existe';
                    header('Location: /admin/users');
                    exit;
                }
                
                $userData = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'full_name' => $fullName,
                    'email' => $email,
                    'role' => $role,
                    'status' => 'active'
                ];
                
                $this->db->insert('users', $userData);
                
                $_SESSION['success'] = 'Usuario creado exitosamente';
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al crear el usuario: ' . $e->getMessage();
            }
            
            header('Location: /admin/users');
            exit;
        }
        
        include '../views/admin/create_user.php';
    }
    
    public function editUser() {
        $userId = $_GET['id'] ?? 0;
        
        if (!$userId) {
            $_SESSION['error'] = 'Usuario no encontrado';
            header('Location: /admin/users');
            exit;
        }
        
        try {
            $user = $this->db->selectOne('users', 'id = ?', [$userId]);
            if (!$user) {
                $_SESSION['error'] = 'Usuario no encontrado';
                header('Location: /admin/users');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al cargar el usuario';
            header('Location: /admin/users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'operator';
            $status = $_POST['status'] ?? 'active';
            $password = $_POST['password'] ?? '';
            
            if (empty($fullName) || empty($email)) {
                $_SESSION['error'] = 'Nombre completo y email son requeridos';
                header('Location: /admin/users/edit?id=' . $userId);
                exit;
            }
            
            try {
                // Check if email already exists for another user
                $existing = $this->db->selectOne('users', 'email = ? AND id != ?', [$email, $userId]);
                if ($existing) {
                    $_SESSION['error'] = 'El email ya está en uso';
                    header('Location: /admin/users/edit?id=' . $userId);
                    exit;
                }
                
                $updateData = [
                    'full_name' => $fullName,
                    'email' => $email,
                    'role' => $role,
                    'status' => $status
                ];
                
                if (!empty($password)) {
                    $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $this->db->update('users', $updateData, 'id = ?', [$userId]);
                
                $_SESSION['success'] = 'Usuario actualizado exitosamente';
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al actualizar el usuario: ' . $e->getMessage();
            }
            
            header('Location: /admin/users');
            exit;
        }
        
        include '../views/admin/edit_user.php';
    }
    
    public function deleteUser() {
        $userId = $_GET['id'] ?? 0;
        
        if (!$userId) {
            $_SESSION['error'] = 'Usuario no encontrado';
            header('Location: /admin/users');
            exit;
        }
        
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['error'] = 'No puede eliminar su propio usuario';
            header('Location: /admin/users');
            exit;
        }
        
        try {
            $user = $this->db->selectOne('users', 'id = ?', [$userId]);
            if (!$user) {
                $_SESSION['error'] = 'Usuario no encontrado';
                header('Location: /admin/users');
                exit;
            }
            
            // Check if user has related records
            $hasOrders = $this->db->selectOne('orders', 'created_by = ?', [$userId]);
            $hasDeliveries = $this->db->selectOne('deliveries', 'created_by = ?', [$userId]);
            
            if ($hasOrders || $hasDeliveries) {
                // Just deactivate instead of deleting
                $this->db->update('users', ['status' => 'inactive'], 'id = ?', [$userId]);
                $_SESSION['success'] = 'Usuario desactivado (tiene registros asociados)';
            } else {
                $this->db->delete('users', 'id = ?', [$userId]);
                $_SESSION['success'] = 'Usuario eliminado exitosamente';
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al eliminar el usuario: ' . $e->getMessage();
        }
        
        header('Location: /admin/users');
        exit;
    }
    
    public function systemInfo() {
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->db->query("SELECT VERSION() as version")->fetch()['version'],
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'disk_space' => $this->formatBytes(disk_free_space('.')),
            'app_version' => APP_VERSION
        ];
        
        include '../views/admin/system_info.php';
    }
    
    private function getSystemStats() {
        try {
            return [
                'total_users' => $this->db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
                'active_users' => $this->db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch()['count'],
                'total_orders' => $this->db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'],
                'pending_orders' => $this->db->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('created', 'in_progress')")->fetch()['count'],
                'total_deliveries' => $this->db->query("SELECT COUNT(*) as count FROM deliveries")->fetch()['count'],
                'active_deliveries' => $this->db->query("SELECT COUNT(*) as count FROM deliveries WHERE status IN ('waiting', 'loading', 'loaded')")->fetch()['count'],
                'security_events_today' => $this->db->query("SELECT COUNT(*) as count FROM security_logs WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
                'disk_usage' => $this->getDiskUsage()
            ];
        } catch (Exception $e) {
            return array_fill_keys([
                'total_users', 'active_users', 'total_orders', 'pending_orders',
                'total_deliveries', 'active_deliveries', 'security_events_today', 'disk_usage'
            ], 0);
        }
    }
    
    private function getRecentActivity() {
        try {
            return $this->db->query(
                "SELECT sl.*, u.full_name as user_name
                 FROM security_logs sl
                 LEFT JOIN users u ON sl.user_id = u.id
                 ORDER BY sl.created_at DESC
                 LIMIT 20"
            )->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getUserStats() {
        try {
            $stats = [];
            
            // Users by role
            $roleStats = $this->db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();
            $stats['by_role'] = [];
            foreach ($roleStats as $stat) {
                $stats['by_role'][$stat['role']] = $stat['count'];
            }
            
            // Active users in last 24 hours
            $stats['active_24h'] = $this->db->query(
                "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            )->fetch()['count'];
            
            // New users this month
            $stats['new_this_month'] = $this->db->query(
                "SELECT COUNT(*) as count FROM users 
                 WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
            )->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            return ['by_role' => [], 'active_24h' => 0, 'new_this_month' => 0];
        }
    }
    
    private function getDiskUsage() {
        $bytes = disk_total_space('.') - disk_free_space('.');
        $total = disk_total_space('.');
        return round(($bytes / $total) * 100, 1);
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
?>