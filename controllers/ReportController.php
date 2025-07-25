<?php
class ReportController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        include '../views/reports/index.php';
    }
    
    public function generate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /reports');
            exit;
        }
        
        $reportType = $_POST['report_type'] ?? '';
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $status = $_POST['status'] ?? 'all';
        $format = $_POST['format'] ?? 'excel';
        
        if (empty($reportType) || empty($dateFrom) || empty($dateTo)) {
            $_SESSION['error'] = 'Tipo de reporte y fechas son requeridos';
            header('Location: /reports');
            exit;
        }
        
        try {
            switch ($reportType) {
                case 'orders':
                    $this->generateOrdersReport($dateFrom, $dateTo, $status, $format);
                    break;
                case 'deliveries':
                    $this->generateDeliveriesReport($dateFrom, $dateTo, $status, $format);
                    break;
                case 'security':
                    $this->generateSecurityReport($dateFrom, $dateTo, $format);
                    break;
                case 'performance':
                    $this->generatePerformanceReport($dateFrom, $dateTo, $format);
                    break;
                default:
                    $_SESSION['error'] = 'Tipo de reporte no válido';
                    header('Location: /reports');
                    exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al generar el reporte: ' . $e->getMessage();
            header('Location: /reports');
            exit;
        }
    }
    
    private function generateOrdersReport($dateFrom, $dateTo, $status, $format) {
        $whereClause = 'DATE(o.created_at) BETWEEN ? AND ?';
        $params = [$dateFrom, $dateTo];
        
        if ($status !== 'all') {
            $whereClause .= ' AND o.status = ?';
            $params[] = $status;
        }
        
        $orders = $this->db->query(
            "SELECT o.order_number, o.customer, o.description, o.status, o.priority,
                    o.estimated_delivery, o.created_at, u.full_name as created_by,
                    COUNT(oi.id) as total_items,
                    SUM(CASE WHEN oi.scanned = 1 THEN 1 ELSE 0 END) as scanned_items,
                    SUM(oi.quantity * p.weight_kg) as total_weight
             FROM orders o
             LEFT JOIN users u ON o.created_by = u.id
             LEFT JOIN order_items oi ON o.id = oi.order_id
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE {$whereClause}
             GROUP BY o.id
             ORDER BY o.created_at DESC",
            $params
        )->fetchAll();
        
        if ($format === 'excel') {
            $this->exportOrdersToExcel($orders, $dateFrom, $dateTo);
        } else {
            $this->exportOrdersToPDF($orders, $dateFrom, $dateTo);
        }
    }
    
    private function generateDeliveriesReport($dateFrom, $dateTo, $status, $format) {
        $whereClause = 'DATE(d.created_at) BETWEEN ? AND ?';
        $params = [$dateFrom, $dateTo];
        
        if ($status !== 'all') {
            $whereClause .= ' AND d.status = ?';
            $params[] = $status;
        }
        
        $deliveries = $this->db->query(
            "SELECT d.*, o.order_number, o.customer, o.priority,
                    u.full_name as created_by,
                    de.recipient_name, de.recipient_id,
                    TIMESTAMPDIFF(HOUR, d.entry_time, d.exit_time) as duration_hours
             FROM deliveries d
             LEFT JOIN orders o ON d.order_id = o.id
             LEFT JOIN users u ON d.created_by = u.id
             LEFT JOIN delivery_evidence de ON d.id = de.delivery_id
             WHERE {$whereClause}
             ORDER BY d.created_at DESC",
            $params
        )->fetchAll();
        
        if ($format === 'excel') {
            $this->exportDeliveriesToExcel($deliveries, $dateFrom, $dateTo);
        } else {
            $this->exportDeliveriesToPDF($deliveries, $dateFrom, $dateTo);
        }
    }
    
    private function generateSecurityReport($dateFrom, $dateTo, $format) {
        $securityLogs = $this->db->query(
            "SELECT sl.*, u.full_name as user_name, 
                    d.truck_plate, o.order_number
             FROM security_logs sl
             LEFT JOIN users u ON sl.user_id = u.id
             LEFT JOIN deliveries d ON sl.delivery_id = d.id
             LEFT JOIN orders o ON d.order_id = o.id
             WHERE DATE(sl.created_at) BETWEEN ? AND ?
             ORDER BY sl.created_at DESC",
            [$dateFrom, $dateTo]
        )->fetchAll();
        
        if ($format === 'excel') {
            $this->exportSecurityToExcel($securityLogs, $dateFrom, $dateTo);
        } else {
            $this->exportSecurityToPDF($securityLogs, $dateFrom, $dateTo);
        }
    }
    
    private function generatePerformanceReport($dateFrom, $dateTo, $format) {
        // Get performance metrics
        $metrics = [
            'total_orders' => $this->db->query(
                "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            )->fetch()['count'],
            
            'completed_orders' => $this->db->query(
                "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'delivered'",
                [$dateFrom, $dateTo]
            )->fetch()['count'],
            
            'total_deliveries' => $this->db->query(
                "SELECT COUNT(*) as count FROM deliveries WHERE DATE(created_at) BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            )->fetch()['count'],
            
            'avg_delivery_time' => $this->db->query(
                "SELECT AVG(TIMESTAMPDIFF(HOUR, entry_time, exit_time)) as avg_time 
                 FROM deliveries WHERE DATE(created_at) BETWEEN ? AND ? AND exit_time IS NOT NULL",
                [$dateFrom, $dateTo]
            )->fetch()['avg_time']
        ];
        
        // Get daily statistics
        $dailyStats = $this->db->query(
            "SELECT DATE(created_at) as date,
                    COUNT(*) as orders_count,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_count
             FROM orders 
             WHERE DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date",
            [$dateFrom, $dateTo]
        )->fetchAll();
        
        if ($format === 'excel') {
            $this->exportPerformanceToExcel($metrics, $dailyStats, $dateFrom, $dateTo);
        } else {
            $this->exportPerformanceToPDF($metrics, $dailyStats, $dateFrom, $dateTo);
        }
    }
    
    private function exportOrdersToExcel($orders, $dateFrom, $dateTo) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_ordenes_' . $dateFrom . '_' . $dateTo . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h1>Reporte de Órdenes</h1>';
        echo '<p>Período: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)) . '</p>';
        echo '<p>Generado el: ' . date('d/m/Y H:i:s') . '</p>';
        echo '<p>Usuario: ' . htmlspecialchars($_SESSION['full_name']) . '</p>';
        
        echo '<table border="1">';
        echo '<tr>';
        echo '<th>Número de Orden</th>';
        echo '<th>Cliente</th>';
        echo '<th>Estado</th>';
        echo '<th>Prioridad</th>';
        echo '<th>Items Totales</th>';
        echo '<th>Items Escaneados</th>';
        echo '<th>Progreso (%)</th>';
        echo '<th>Peso Total (kg)</th>';
        echo '<th>Entrega Estimada</th>';
        echo '<th>Creado</th>';
        echo '<th>Creado Por</th>';
        echo '</tr>';
        
        foreach ($orders as $order) {
            $progress = $order['total_items'] > 0 ? 
                round(($order['scanned_items'] / $order['total_items']) * 100) : 0;
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
            echo '<td>' . htmlspecialchars($order['customer']) . '</td>';
            echo '<td>' . htmlspecialchars($order['status']) . '</td>';
            echo '<td>' . htmlspecialchars($order['priority']) . '</td>';
            echo '<td>' . $order['total_items'] . '</td>';
            echo '<td>' . $order['scanned_items'] . '</td>';
            echo '<td>' . $progress . '%</td>';
            echo '<td>' . number_format($order['total_weight'], 2) . '</td>';
            echo '<td>' . ($order['estimated_delivery'] ? date('d/m/Y', strtotime($order['estimated_delivery'])) : '') . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</td>';
            echo '<td>' . htmlspecialchars($order['created_by']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
    
    private function exportDeliveriesToExcel($deliveries, $dateFrom, $dateTo) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_entregas_' . $dateFrom . '_' . $dateTo . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h1>Reporte de Entregas</h1>';
        echo '<p>Período: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)) . '</p>';
        echo '<p>Generado el: ' . date('d/m/Y H:i:s') . '</p>';
        
        echo '<table border="1">';
        echo '<tr>';
        echo '<th>Orden</th>';
        echo '<th>Cliente</th>';
        echo '<th>Camión</th>';
        echo '<th>Conductor</th>';
        echo '<th>Estado</th>';
        echo '<th>Entrada</th>';
        echo '<th>Salida</th>';
        echo '<th>Duración (hrs)</th>';
        echo '<th>Receptor</th>';
        echo '<th>ID Receptor</th>';
        echo '</tr>';
        
        foreach ($deliveries as $delivery) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($delivery['order_number']) . '</td>';
            echo '<td>' . htmlspecialchars($delivery['customer']) . '</td>';
            echo '<td>' . htmlspecialchars($delivery['truck_plate']) . '</td>';
            echo '<td>' . htmlspecialchars($delivery['driver_name']) . '</td>';
            echo '<td>' . htmlspecialchars($delivery['status']) . '</td>';
            echo '<td>' . ($delivery['entry_time'] ? date('d/m/Y H:i', strtotime($delivery['entry_time'])) : '') . '</td>';
            echo '<td>' . ($delivery['exit_time'] ? date('d/m/Y H:i', strtotime($delivery['exit_time'])) : '') . '</td>';
            echo '<td>' . ($delivery['duration_hours'] ? number_format($delivery['duration_hours'], 1) : '') . '</td>';
            echo '<td>' . htmlspecialchars($delivery['recipient_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($delivery['recipient_id'] ?? '') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
    
    private function exportSecurityToExcel($logs, $dateFrom, $dateTo) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_seguridad_' . $dateFrom . '_' . $dateTo . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h1>Reporte de Seguridad</h1>';
        echo '<p>Período: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)) . '</p>';
        
        echo '<table border="1">';
        echo '<tr>';
        echo '<th>Fecha/Hora</th>';
        echo '<th>Usuario</th>';
        echo '<th>Acción</th>';
        echo '<th>Descripción</th>';
        echo '<th>Orden</th>';
        echo '<th>Camión</th>';
        echo '<th>IP</th>';
        echo '</tr>';
        
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . date('d/m/Y H:i:s', strtotime($log['created_at'])) . '</td>';
            echo '<td>' . htmlspecialchars($log['user_name']) . '</td>';
            echo '<td>' . htmlspecialchars($log['action']) . '</td>';
            echo '<td>' . htmlspecialchars($log['description']) . '</td>';
            echo '<td>' . htmlspecialchars($log['order_number'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($log['truck_plate'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($log['ip_address']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
    
    private function exportPerformanceToExcel($metrics, $dailyStats, $dateFrom, $dateTo) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_desempeno_' . $dateFrom . '_' . $dateTo . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h1>Reporte de Desempeño</h1>';
        echo '<p>Período: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)) . '</p>';
        
        echo '<h2>Métricas Generales</h2>';
        echo '<table border="1">';
        echo '<tr><th>Métrica</th><th>Valor</th></tr>';
        echo '<tr><td>Total de Órdenes</td><td>' . $metrics['total_orders'] . '</td></tr>';
        echo '<tr><td>Órdenes Completadas</td><td>' . $metrics['completed_orders'] . '</td></tr>';
        echo '<tr><td>Tasa de Completación</td><td>' . 
            ($metrics['total_orders'] > 0 ? round(($metrics['completed_orders'] / $metrics['total_orders']) * 100, 1) : 0) . 
            '%</td></tr>';
        echo '<tr><td>Total de Entregas</td><td>' . $metrics['total_deliveries'] . '</td></tr>';
        echo '<tr><td>Tiempo Promedio de Entrega (hrs)</td><td>' . 
            ($metrics['avg_delivery_time'] ? number_format($metrics['avg_delivery_time'], 1) : 'N/A') . '</td></tr>';
        echo '</table>';
        
        echo '<h2>Estadísticas Diarias</h2>';
        echo '<table border="1">';
        echo '<tr><th>Fecha</th><th>Órdenes Creadas</th><th>Órdenes Completadas</th><th>Tasa de Completación</th></tr>';
        
        foreach ($dailyStats as $stat) {
            $completion_rate = $stat['orders_count'] > 0 ? 
                round(($stat['completed_count'] / $stat['orders_count']) * 100, 1) : 0;
            
            echo '<tr>';
            echo '<td>' . date('d/m/Y', strtotime($stat['date'])) . '</td>';
            echo '<td>' . $stat['orders_count'] . '</td>';
            echo '<td>' . $stat['completed_count'] . '</td>';
            echo '<td>' . $completion_rate . '%</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
    
    // PDF export methods would be similar but using a PDF library like TCPDF
    private function exportOrdersToPDF($orders, $dateFrom, $dateTo) {
        // For now, redirect to Excel format
        $this->exportOrdersToExcel($orders, $dateFrom, $dateTo);
    }
    
    private function exportDeliveriesToPDF($deliveries, $dateFrom, $dateTo) {
        $this->exportDeliveriesToExcel($deliveries, $dateFrom, $dateTo);
    }
    
    private function exportSecurityToPDF($logs, $dateFrom, $dateTo) {
        $this->exportSecurityToExcel($logs, $dateFrom, $dateTo);
    }
    
    private function exportPerformanceToPDF($metrics, $dailyStats, $dateFrom, $dateTo) {
        $this->exportPerformanceToExcel($metrics, $dailyStats, $dateFrom, $dateTo);
    }
}
?>