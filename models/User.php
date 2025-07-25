<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->selectOne('users', 'id = ?', [$id]);
    }
    
    public function findByUsername($username) {
        return $this->db->selectOne('users', 'username = ?', [$username]);
    }
    
    public function create($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->db->insert('users', $data);
    }
    
    public function update($id, $data) {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }
    
    public function getAll($filters = []) {
        $whereClause = '1=1';
        $params = [];
        
        if (isset($filters['role']) && !empty($filters['role'])) {
            $whereClause .= ' AND role = ?';
            $params[] = $filters['role'];
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $whereClause .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        
        return $this->db->select('users', $whereClause, $params, 'id, username, full_name, email, role, status, last_login, created_at');
    }
    
    public function delete($id) {
        return $this->db->delete('users', 'id = ?', [$id]);
    }
    
    public function validateLogin($username, $password) {
        $user = $this->findByUsername($username);
        
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    public function updateLastLogin($id) {
        return $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }
}
?>