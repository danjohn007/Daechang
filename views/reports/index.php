<?php 
$pageTitle = 'Reportes - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-bar"></i> Generador de Reportes</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-file-export"></i> Configurar Reporte</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/reports/generate">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="report_type" class="form-label">
                                    <i class="fas fa-clipboard-list"></i> Tipo de Reporte *
                                </label>
                                <select class="form-select" id="report_type" name="report_type" required>
                                    <option value="">Seleccione un tipo...</option>
                                    <option value="orders">Reporte de Órdenes</option>
                                    <option value="deliveries">Reporte de Entregas</option>
                                    <option value="security">Reporte de Seguridad</option>
                                    <option value="performance">Reporte de Desempeño</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="format" class="form-label">
                                    <i class="fas fa-file"></i> Formato
                                </label>
                                <select class="form-select" id="format" name="format">
                                    <option value="excel">Excel (.xls)</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_from" class="form-label">
                                    <i class="fas fa-calendar"></i> Fecha Desde *
                                </label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?= date('Y-m-01') ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_to" class="form-label">
                                    <i class="fas fa-calendar"></i> Fecha Hasta *
                                </label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="status-filter" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-filter"></i> Filtrar por Estado
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all">Todos los estados</option>
                                    <option value="created">Creada</option>
                                    <option value="in_progress">En Proceso</option>
                                    <option value="delivered">Entregada</option>
                                    <option value="cancelled">Cancelada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-download"></i> Generar y Descargar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Información</h5>
            </div>
            <div class="card-body">
                <h6>Tipos de Reportes Disponibles:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-box text-primary"></i> <strong>Órdenes:</strong> Lista detallada de órdenes con progreso y estado</li>
                    <li><i class="fas fa-truck text-success"></i> <strong>Entregas:</strong> Información de entregas, tiempos y conductores</li>
                    <li><i class="fas fa-shield-alt text-warning"></i> <strong>Seguridad:</strong> Log de eventos y actividades del sistema</li>
                    <li><i class="fas fa-chart-line text-info"></i> <strong>Desempeño:</strong> Métricas y estadísticas operacionales</li>
                </ul>
                
                <hr>
                
                <h6>Formatos Disponibles:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-file-excel text-success"></i> <strong>Excel:</strong> Para análisis y edición de datos</li>
                    <li><i class="fas fa-file-pdf text-danger"></i> <strong>PDF:</strong> Para impresión y archivo oficial</li>
                </ul>
                
                <hr>
                
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Consejo:</strong> Use fechas específicas para reportes más precisos y tiempos de generación más rápidos.
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Estadísticas Rápidas</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $db = Database::getInstance();
                    $quickStats = [
                        'orders_today' => $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
                        'deliveries_today' => $db->query("SELECT COUNT(*) as count FROM deliveries WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
                        'completed_today' => $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(updated_at) = CURDATE() AND status = 'delivered'")->fetch()['count'],
                        'pending_orders' => $db->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('created', 'in_progress')")->fetch()['count']
                    ];
                } catch (Exception $e) {
                    $quickStats = ['orders_today' => 0, 'deliveries_today' => 0, 'completed_today' => 0, 'pending_orders' => 0];
                }
                ?>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="bg-primary text-white rounded p-2 mb-2">
                            <div class="fs-4"><?= $quickStats['orders_today'] ?></div>
                            <small>Órdenes Hoy</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-success text-white rounded p-2 mb-2">
                            <div class="fs-4"><?= $quickStats['deliveries_today'] ?></div>
                            <small>Entregas Hoy</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-info text-white rounded p-2 mb-2">
                            <div class="fs-4"><?= $quickStats['completed_today'] ?></div>
                            <small>Completadas</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-warning text-white rounded p-2">
                            <div class="fs-4"><?= $quickStats['pending_orders'] ?></div>
                            <small>Pendientes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('report_type').addEventListener('change', function() {
    const statusFilter = document.getElementById('status-filter');
    
    if (this.value === 'orders' || this.value === 'deliveries') {
        statusFilter.style.display = 'block';
        
        // Update status options based on report type
        const statusSelect = document.getElementById('status');
        statusSelect.innerHTML = '<option value="all">Todos los estados</option>';
        
        if (this.value === 'orders') {
            statusSelect.innerHTML += `
                <option value="created">Creada</option>
                <option value="in_progress">En Proceso</option>
                <option value="loading">Cargando</option>
                <option value="loaded">Cargada</option>
                <option value="in_transit">En Tránsito</option>
                <option value="delivered">Entregada</option>
                <option value="cancelled">Cancelada</option>
            `;
        } else if (this.value === 'deliveries') {
            statusSelect.innerHTML += `
                <option value="waiting">Esperando</option>
                <option value="loading">Cargando</option>
                <option value="loaded">Cargado</option>
                <option value="departed">Salió</option>
                <option value="delivered">Entregado</option>
            `;
        }
    } else {
        statusFilter.style.display = 'none';
    }
});

// Validate date range
document.getElementById('date_to').addEventListener('change', function() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = this.value;
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        alert('La fecha "Hasta" debe ser posterior a la fecha "Desde"');
        this.value = dateFrom;
    }
});

document.getElementById('date_from').addEventListener('change', function() {
    const dateFrom = this.value;
    const dateTo = document.getElementById('date_to').value;
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        document.getElementById('date_to').value = dateFrom;
    }
});
</script>

<?php include '../views/layouts/footer.php'; ?>