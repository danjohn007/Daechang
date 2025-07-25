<?php
class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login() {
        include '../views/auth/login.php';
    }
    
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Usuario y contraseña son requeridos';
            header('Location: /login');
            exit;
        }
        
        try {
            $user = $this->db->selectOne('users', 'username = ? AND status = ?', [$username, 'active']);
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time();
                
                // Log security event
                $this->logSecurityEvent($user['id'], 'login_success', 'Usuario inició sesión exitosamente');
                
                $_SESSION['success'] = 'Bienvenido, ' . $user['full_name'];
                header('Location: /home');
                exit;
            } else {
                // Log failed attempt
                if ($user) {
                    $this->logSecurityEvent($user['id'], 'login_failed', 'Intento de inicio de sesión fallido - contraseña incorrecta');
                } else {
                    $this->logSecurityEvent(null, 'login_failed', 'Intento de inicio de sesión fallido - usuario no encontrado: ' . $username);
                }
                
                $_SESSION['error'] = 'Credenciales incorrectas';
                header('Location: /login');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error del sistema. Intente nuevamente.';
            header('Location: /login');
            exit;
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logSecurityEvent($_SESSION['user_id'], 'logout', 'Usuario cerró sesión');
        }
        
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    private function logSecurityEvent($userId, $action, $description) {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            $this->db->insert('security_logs', $data);
        } catch (Exception $e) {
            // Log error but don't break the flow
            error_log('Error logging security event: ' . $e->getMessage());
        }
    }
    
    public static function checkSession() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                session_destroy();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        return isset($_SESSION['user_id']);
    }
}
?>