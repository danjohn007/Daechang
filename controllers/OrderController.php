<?php
class OrderController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        
        $whereClause = '1=1';
        $params = [];
        
        if ($status !== 'all') {
            $whereClause .= ' AND o.status = ?';
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $whereClause .= ' AND (o.order_number LIKE ? OR o.customer LIKE ? OR o.description LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        try {
            $orders = $this->db->query(
                "SELECT o.*, u.full_name as created_by_name,
                        COUNT(oi.id) as total_items,
                        SUM(CASE WHEN oi.scanned = 1 THEN 1 ELSE 0 END) as scanned_items
                 FROM orders o 
                 LEFT JOIN users u ON o.created_by = u.id 
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE {$whereClause}
                 GROUP BY o.id
                 ORDER BY o.created_at DESC",
                $params
            )->fetchAll();
        } catch (Exception $e) {
            $orders = [];
            $_SESSION['error'] = 'Error al cargar las órdenes';
        }
        
        include '../views/orders/index.php';
    }
    
    public function create() {
        // Get products for the form
        try {
            $products = $this->db->select('products', 'status = ?', ['active'], 'id, product_code, name, weight_kg');
        } catch (Exception $e) {
            $products = [];
            $_SESSION['error'] = 'Error al cargar los productos';
        }
        
        include '../views/orders/create.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /orders');
            exit;
        }
        
        $orderNumber = trim($_POST['order_number'] ?? '');
        $customer = trim($_POST['customer'] ?? 'Samsung');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $estimatedDelivery = $_POST['estimated_delivery'] ?? null;
        $products = $_POST['products'] ?? [];
        
        if (empty($orderNumber)) {
            $_SESSION['error'] = 'El número de orden es requerido';
            header('Location: /orders/create');
            exit;
        }
        
        try {
            // Check if order number already exists
            $existing = $this->db->selectOne('orders', 'order_number = ?', [$orderNumber]);
            if ($existing) {
                $_SESSION['error'] = 'Ya existe una orden con este número';
                header('Location: /orders/create');
                exit;
            }
            
            // Create order
            $orderData = [
                'order_number' => $orderNumber,
                'customer' => $customer,
                'description' => $description,
                'priority' => $priority,
                'estimated_delivery' => $estimatedDelivery,
                'created_by' => $_SESSION['user_id']
            ];
            
            $orderId = $this->db->insert('orders', $orderData);
            
            // Add products to order
            foreach ($products as $productData) {
                if (!empty($productData['product_id']) && !empty($productData['quantity'])) {
                    $itemData = [
                        'order_id' => $orderId,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'notes' => $productData['notes'] ?? ''
                    ];
                    $this->db->insert('order_items', $itemData);
                }
            }
            
            $_SESSION['success'] = 'Orden creada exitosamente';
            header('Location: /orders/view?id=' . $orderId);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear la orden: ' . $e->getMessage();
            header('Location: /orders/create');
            exit;
        }
    }
    
    public function view() {
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /orders');
            exit;
        }
        
        try {
            $order = $this->db->selectOne(
                'orders o LEFT JOIN users u ON o.created_by = u.id',
                'o.id = ?',
                [$orderId],
                'o.*, u.full_name as created_by_name'
            );
            
            if (!$order) {
                $_SESSION['error'] = 'Orden no encontrada';
                header('Location: /orders');
                exit;
            }
            
            $orderItems = $this->db->query(
                "SELECT oi.*, p.product_code, p.name as product_name, p.weight_kg,
                        u.full_name as scanned_by_name
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.id
                 LEFT JOIN users u ON oi.scanned_by = u.id
                 WHERE oi.order_id = ?
                 ORDER BY oi.id",
                [$orderId]
            )->fetchAll();
            
            // Get delivery info if exists
            $delivery = $this->db->selectOne('deliveries', 'order_id = ?', [$orderId]);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al cargar la orden';
            header('Location: /orders');
            exit;
        }
        
        include '../views/orders/view.php';
    }
    
    public function scanProduct() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        $itemId = $_POST['item_id'] ?? 0;
        $action = $_POST['action'] ?? 'scan'; // scan or unscan
        
        try {
            if ($action === 'scan') {
                $this->db->update('order_items', [
                    'scanned' => 1,
                    'scanned_at' => date('Y-m-d H:i:s'),
                    'scanned_by' => $_SESSION['user_id']
                ], 'id = ?', [$itemId]);
                
                echo json_encode(['success' => true, 'message' => 'Producto escaneado']);
            } else {
                $this->db->update('order_items', [
                    'scanned' => 0,
                    'scanned_at' => null,
                    'scanned_by' => null
                ], 'id = ?', [$itemId]);
                
                echo json_encode(['success' => true, 'message' => 'Escaneo removido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    }
}
?>