<?php 
$pageTitle = 'Panel de Administración - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-cog"></i> Panel de Administración</h1>
            <div class="text-muted">
                <i class="fas fa-crown"></i> Acceso de Administrador
            </div>
        </div>
    </div>
</div>

<!-- System Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= $stats['total_users'] ?></h4>
                        <p class="card-text">Usuarios Totales</p>
                        <small><?= $stats['active_users'] ?> activos</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= $stats['total_orders'] ?></h4>
                        <p class="card-text">Órdenes Totales</p>
                        <small><?= $stats['pending_orders'] ?> pendientes</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= $stats['total_deliveries'] ?></h4>
                        <p class="card-text">Entregas Totales</p>
                        <small><?= $stats['active_deliveries'] ?> activas</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-truck fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= $stats['security_events_today'] ?></h4>
                        <p class="card-text">Eventos Hoy</p>
                        <small>Uso disco: <?= $stats['disk_usage'] ?>%</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/admin/users" class="btn btn-primary">
                        <i class="fas fa-users"></i> Gestionar Usuarios
                    </a>
                    <a href="/admin/users/create" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Crear Usuario
                    </a>
                    <a href="/admin/system-info" class="btn btn-info">
                        <i class="fas fa-info-circle"></i> Información del Sistema
                    </a>
                    <a href="/reports" class="btn btn-warning">
                        <i class="fas fa-chart-bar"></i> Generar Reportes
                    </a>
                </div>
            </div>
        </div>
        
        <!-- User Statistics -->
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Estadísticas de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php
                    $roleLabels = [
                        'admin' => 'Admins',
                        'supervisor' => 'Supervisores',
                        'operator' => 'Operadores',
                        'security' => 'Seguridad'
                    ];
                    $roleColors = [
                        'admin' => 'danger',
                        'supervisor' => 'warning',
                        'operator' => 'primary',
                        'security' => 'success'
                    ];
                    ?>
                    <?php foreach ($roleLabels as $role => $label): ?>
                    <div class="col-6 mb-2">
                        <div class="bg-<?= $roleColors[$role] ?> text-white rounded p-2">
                            <div class="fs-5"><?= $userStats['by_role'][$role] ?? 0 ?></div>
                            <small><?= $label ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <div class="mb-2">
                        <strong>Activos últimas 24h:</strong> 
                        <span class="badge bg-success"><?= $userStats['active_24h'] ?></span>
                    </div>
                    <div>
                        <strong>Nuevos este mes:</strong> 
                        <span class="badge bg-info"><?= $userStats['new_this_month'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history"></i> Actividad Reciente</h5>
                <small class="text-muted">Últimos 20 eventos</small>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($recentActivity)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay actividad reciente</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($recentActivity as $activity): ?>
                    <div class="timeline-item border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <?php
                                    $actionIcons = [
                                        'login_success' => ['icon' => 'fa-sign-in-alt', 'color' => 'success'],
                                        'login_failed' => ['icon' => 'fa-exclamation-triangle', 'color' => 'danger'],
                                        'logout' => ['icon' => 'fa-sign-out-alt', 'color' => 'secondary'],
                                        'delivery_created' => ['icon' => 'fa-truck', 'color' => 'primary'],
                                        'status_changed' => ['icon' => 'fa-edit', 'color' => 'warning'],
                                        'evidence_recorded' => ['icon' => 'fa-signature', 'color' => 'success'],
                                        'order_created' => ['icon' => 'fa-plus', 'color' => 'info']
                                    ];
                                    $actionInfo = $actionIcons[$activity['action']] ?? ['icon' => 'fa-info', 'color' => 'secondary'];
                                    ?>
                                    <i class="fas <?= $actionInfo['icon'] ?> text-<?= $actionInfo['color'] ?> me-2"></i>
                                    <strong><?= htmlspecialchars($activity['user_name'] ?? 'Sistema') ?></strong>
                                    <span class="ms-2 text-muted"><?= ucfirst(str_replace('_', ' ', $activity['action'])) ?></span>
                                </div>
                                <p class="mb-1 text-muted"><?= htmlspecialchars($activity['description']) ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?= date('d/m/Y H:i:s', strtotime($activity['created_at'])) ?>
                                    <?php if (!empty($activity['ip_address'])): ?>
                                    | <i class="fas fa-globe"></i> <?= htmlspecialchars($activity['ip_address']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Health -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-heartbeat"></i> Estado del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success mb-2">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <strong>Base de Datos</strong><br>
                            <small class="text-success">Conectada</small>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success mb-2">
                                <i class="fas fa-server fa-2x"></i>
                            </div>
                            <strong>Servidor Web</strong><br>
                            <small class="text-success">Funcionando</small>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-<?= $stats['disk_usage'] > 90 ? 'danger' : ($stats['disk_usage'] > 70 ? 'warning' : 'success') ?> mb-2">
                                <i class="fas fa-hdd fa-2x"></i>
                            </div>
                            <strong>Espacio en Disco</strong><br>
                            <small class="text-<?= $stats['disk_usage'] > 90 ? 'danger' : ($stats['disk_usage'] > 70 ? 'warning' : 'success') ?>">
                                <?= $stats['disk_usage'] ?>% usado
                            </small>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success mb-2">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                            <strong>Seguridad</strong><br>
                            <small class="text-success">Normal</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
</style>

<?php include '../views/layouts/footer.php'; ?>