<?php
class DeliveryController {
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
            $whereClause .= ' AND d.status = ?';
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $whereClause .= ' AND (d.truck_plate LIKE ? OR d.driver_name LIKE ? OR o.order_number LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        try {
            $deliveries = $this->db->query(
                "SELECT d.*, o.order_number, o.customer, o.priority,
                        u.full_name as created_by_name
                 FROM deliveries d 
                 LEFT JOIN orders o ON d.order_id = o.id 
                 LEFT JOIN users u ON d.created_by = u.id
                 WHERE {$whereClause}
                 ORDER BY d.created_at DESC",
                $params
            )->fetchAll();
        } catch (Exception $e) {
            $deliveries = [];
            $_SESSION['error'] = 'Error al cargar las entregas';
        }
        
        include '../views/deliveries/index.php';
    }
    
    public function create() {
        // Get pending orders
        try {
            $orders = $this->db->query(
                "SELECT o.id, o.order_number, o.customer, o.description, o.priority
                 FROM orders o 
                 LEFT JOIN deliveries d ON o.id = d.order_id
                 WHERE o.status IN ('created', 'in_progress', 'loading') 
                 AND d.id IS NULL
                 ORDER BY o.priority DESC, o.created_at ASC"
            )->fetchAll();
        } catch (Exception $e) {
            $orders = [];
            $_SESSION['error'] = 'Error al cargar las órdenes disponibles';
        }
        
        include '../views/deliveries/create.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /deliveries');
            exit;
        }
        
        $orderId = $_POST['order_id'] ?? 0;
        $truckPlate = trim($_POST['truck_plate'] ?? '');
        $driverName = trim($_POST['driver_name'] ?? '');
        $driverLicense = trim($_POST['driver_license'] ?? '');
        $driverPhone = trim($_POST['driver_phone'] ?? '');
        $companionName = trim($_POST['companion_name'] ?? '');
        $securityNotes = trim($_POST['security_notes'] ?? '');
        
        if (empty($orderId) || empty($truckPlate) || empty($driverName)) {
            $_SESSION['error'] = 'Orden, placa del camión y conductor son requeridos';
            header('Location: /deliveries/create');
            exit;
        }
        
        try {
            // Check if order exists and is available
            $order = $this->db->selectOne('orders', 'id = ?', [$orderId]);
            if (!$order) {
                $_SESSION['error'] = 'Orden no encontrada';
                header('Location: /deliveries/create');
                exit;
            }
            
            // Check if delivery already exists for this order
            $existingDelivery = $this->db->selectOne('deliveries', 'order_id = ?', [$orderId]);
            if ($existingDelivery) {
                $_SESSION['error'] = 'Ya existe una entrega para esta orden';
                header('Location: /deliveries/create');
                exit;
            }
            
            // Create delivery
            $deliveryData = [
                'order_id' => $orderId,
                'truck_plate' => strtoupper($truckPlate),
                'driver_name' => $driverName,
                'driver_license' => $driverLicense,
                'driver_phone' => $driverPhone,
                'companion_name' => $companionName,
                'entry_time' => date('Y-m-d H:i:s'),
                'status' => 'waiting',
                'security_notes' => $securityNotes,
                'created_by' => $_SESSION['user_id']
            ];
            
            $deliveryId = $this->db->insert('deliveries', $deliveryData);
            
            // Update order status
            $this->db->update('orders', ['status' => 'in_progress'], 'id = ?', [$orderId]);
            
            // Log security event
            $this->logSecurityEvent($deliveryId, 'delivery_created', 
                "Entrega creada para orden {$order['order_number']} - Camión: {$truckPlate}");
            
            $_SESSION['success'] = 'Entrega registrada exitosamente';
            header('Location: /deliveries/view?id=' . $deliveryId);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear la entrega: ' . $e->getMessage();
            header('Location: /deliveries/create');
            exit;
        }
    }
    
    public function view() {
        $deliveryId = $_GET['id'] ?? 0;
        
        if (!$deliveryId) {
            $_SESSION['error'] = 'Entrega no encontrada';
            header('Location: /deliveries');
            exit;
        }
        
        try {
            $delivery = $this->db->selectOne(
                'deliveries d 
                 LEFT JOIN orders o ON d.order_id = o.id 
                 LEFT JOIN users u ON d.created_by = u.id',
                'd.id = ?',
                [$deliveryId],
                'd.*, o.order_number, o.customer, o.description as order_description, 
                 o.priority, u.full_name as created_by_name'
            );
            
            if (!$delivery) {
                $_SESSION['error'] = 'Entrega no encontrada';
                header('Location: /deliveries');
                exit;
            }
            
            // Get order items
            $orderItems = $this->db->query(
                "SELECT oi.*, p.product_code, p.name as product_name, p.weight_kg
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?
                 ORDER BY oi.id",
                [$delivery['order_id']]
            )->fetchAll();
            
            // Get delivery evidence
            $evidence = $this->db->selectOne('delivery_evidence', 'delivery_id = ?', [$deliveryId]);
            
            // Get photos
            $photos = [];
            if ($evidence) {
                $photos = $this->db->select('delivery_photos', 'delivery_evidence_id = ?', [$evidence['id']]);
            }
            
            // Get security logs
            $securityLogs = $this->db->query(
                "SELECT sl.*, u.full_name as user_name
                 FROM security_logs sl
                 LEFT JOIN users u ON sl.user_id = u.id
                 WHERE sl.delivery_id = ?
                 ORDER BY sl.created_at DESC",
                [$deliveryId]
            )->fetchAll();
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al cargar la entrega';
            header('Location: /deliveries');
            exit;
        }
        
        include '../views/deliveries/view.php';
    }
    
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /deliveries');
            exit;
        }
        
        $deliveryId = $_POST['delivery_id'] ?? 0;
        $newStatus = $_POST['status'] ?? '';
        
        if (!$deliveryId || !$newStatus) {
            $_SESSION['error'] = 'Datos incompletos';
            header('Location: /deliveries');
            exit;
        }
        
        try {
            $delivery = $this->db->selectOne('deliveries', 'id = ?', [$deliveryId]);
            if (!$delivery) {
                $_SESSION['error'] = 'Entrega no encontrada';
                header('Location: /deliveries');
                exit;
            }
            
            $updateData = ['status' => $newStatus];
            
            // Set exit time if departing
            if ($newStatus === 'departed') {
                $updateData['exit_time'] = date('Y-m-d H:i:s');
                
                // Update order status
                $this->db->update('orders', ['status' => 'in_transit'], 'id = ?', [$delivery['order_id']]);
            } elseif ($newStatus === 'delivered') {
                // Update order status
                $this->db->update('orders', ['status' => 'delivered'], 'id = ?', [$delivery['order_id']]);
            }
            
            $this->db->update('deliveries', $updateData, 'id = ?', [$deliveryId]);
            
            // Log security event
            $this->logSecurityEvent($deliveryId, 'status_changed', 
                "Estado de entrega cambiado a: {$newStatus}");
            
            $_SESSION['success'] = 'Estado actualizado exitosamente';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al actualizar el estado';
        }
        
        header('Location: /deliveries/view?id=' . $deliveryId);
        exit;
    }
    
    public function evidence() {
        $deliveryId = $_GET['id'] ?? 0;
        
        if (!$deliveryId) {
            $_SESSION['error'] = 'Entrega no encontrada';
            header('Location: /deliveries');
            exit;
        }
        
        try {
            $delivery = $this->db->selectOne(
                'deliveries d LEFT JOIN orders o ON d.order_id = o.id',
                'd.id = ?',
                [$deliveryId],
                'd.*, o.order_number, o.customer'
            );
            
            if (!$delivery) {
                $_SESSION['error'] = 'Entrega no encontrada';
                header('Location: /deliveries');
                exit;
            }
            
            // Get existing evidence
            $evidence = $this->db->selectOne('delivery_evidence', 'delivery_id = ?', [$deliveryId]);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al cargar la entrega';
            header('Location: /deliveries');
            exit;
        }
        
        include '../views/deliveries/evidence.php';
    }
    
    public function storeEvidence() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /deliveries');
            exit;
        }
        
        $deliveryId = $_POST['delivery_id'] ?? 0;
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $recipientId = trim($_POST['recipient_id'] ?? '');
        $deliveryNotes = trim($_POST['delivery_notes'] ?? '');
        $signatureData = $_POST['signature_data'] ?? '';
        
        if (!$deliveryId || !$recipientName) {
            $_SESSION['error'] = 'ID de entrega y nombre del receptor son requeridos';
            header('Location: /deliveries/evidence?id=' . $deliveryId);
            exit;
        }
        
        try {
            // Check if delivery exists
            $delivery = $this->db->selectOne('deliveries', 'id = ?', [$deliveryId]);
            if (!$delivery) {
                $_SESSION['error'] = 'Entrega no encontrada';
                header('Location: /deliveries');
                exit;
            }
            
            $evidenceData = [
                'delivery_id' => $deliveryId,
                'recipient_name' => $recipientName,
                'recipient_id' => $recipientId,
                'delivery_notes' => $deliveryNotes,
                'created_by' => $_SESSION['user_id']
            ];
            
            // Save signature if provided
            if (!empty($signatureData)) {
                $signaturePath = $this->saveSignature($signatureData, $deliveryId);
                if ($signaturePath) {
                    $evidenceData['signature_path'] = $signaturePath;
                }
            }
            
            // Check if evidence already exists
            $existingEvidence = $this->db->selectOne('delivery_evidence', 'delivery_id = ?', [$deliveryId]);
            
            if ($existingEvidence) {
                // Update existing evidence
                $this->db->update('delivery_evidence', $evidenceData, 'delivery_id = ?', [$deliveryId]);
                $evidenceId = $existingEvidence['id'];
            } else {
                // Create new evidence
                $evidenceId = $this->db->insert('delivery_evidence', $evidenceData);
            }
            
            // Handle file uploads
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $this->handlePhotoUploads($_FILES['photos'], $evidenceId);
            }
            
            // Update delivery status
            $this->db->update('deliveries', ['status' => 'delivered'], 'id = ?', [$deliveryId]);
            $this->db->update('orders', ['status' => 'delivered'], 'id = ?', [$delivery['order_id']]);
            
            // Log security event
            $this->logSecurityEvent($deliveryId, 'evidence_recorded', 
                "Evidencia de entrega registrada - Receptor: {$recipientName}");
            
            $_SESSION['success'] = 'Evidencia de entrega registrada exitosamente';
            header('Location: /deliveries/view?id=' . $deliveryId);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al registrar la evidencia: ' . $e->getMessage();
            header('Location: /deliveries/evidence?id=' . $deliveryId);
            exit;
        }
    }
    
    public function uploadSignature() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        $signatureData = $_POST['signature_data'] ?? '';
        $deliveryId = $_POST['delivery_id'] ?? 0;
        
        if (empty($signatureData) || !$deliveryId) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        
        try {
            $signaturePath = $this->saveSignature($signatureData, $deliveryId);
            
            if ($signaturePath) {
                echo json_encode(['success' => true, 'path' => $signaturePath]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar la firma']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error del servidor']);
        }
    }
    
    private function saveSignature($signatureData, $deliveryId) {
        // Remove data URL prefix
        $data = str_replace('data:image/png;base64,', '', $signatureData);
        $data = base64_decode($data);
        
        if (!$data) {
            return false;
        }
        
        // Create signatures directory if it doesn't exist
        $uploadDir = UPLOAD_PATH . 'signatures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'signature_' . $deliveryId . '_' . time() . '.png';
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $data)) {
            return 'signatures/' . $filename;
        }
        
        return false;
    }
    
    private function handlePhotoUploads($files, $evidenceId) {
        $uploadDir = UPLOAD_PATH . 'photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $size = $files['size'][$i];
                
                // Validate file
                if ($size > MAX_FILE_SIZE) {
                    continue;
                }
                
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    continue;
                }
                
                // Generate unique filename
                $filename = 'photo_' . $evidenceId . '_' . time() . '_' . $i . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($tmpName, $filepath)) {
                    // Save to database
                    $photoData = [
                        'delivery_evidence_id' => $evidenceId,
                        'photo_path' => 'photos/' . $filename,
                        'photo_type' => 'delivery',
                        'description' => 'Foto de evidencia de entrega'
                    ];
                    $this->db->insert('delivery_photos', $photoData);
                }
            }
        }
    }
    
    private function logSecurityEvent($deliveryId, $action, $description) {
        try {
            $data = [
                'delivery_id' => $deliveryId,
                'user_id' => $_SESSION['user_id'],
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            $this->db->insert('security_logs', $data);
        } catch (Exception $e) {
            error_log('Error logging security event: ' . $e->getMessage());
        }
    }
}
?>