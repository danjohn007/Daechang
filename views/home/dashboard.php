<?php 
$pageTitle = 'Dashboard - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div class="text-muted">
                <i class="fas fa-clock"></i> <?= date('d/m/Y H:i:s') ?>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?= $stats['orders_today'] ?></h4>
                        <p class="card-text">Órdenes Hoy</p>
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
                        <h4 class="card-title"><?= $stats['active_deliveries'] ?></h4>
                        <p class="card-text">Entregas Activas</p>
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
                        <h4 class="card-title"><?= $stats['pending_orders'] ?></h4>
                        <p class="card-text">Órdenes Pendientes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <h4 class="card-title"><?= $stats['completed_today'] ?></h4>
                        <p class="card-text">Completadas Hoy</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Orders -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list"></i> Órdenes Recientes</h5>
                <a href="/orders" class="btn btn-sm btn-primary">Ver Todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentOrders)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay órdenes registradas</p>
                    <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor'])): ?>
                    <a href="/orders/create" class="btn btn-primary">Crear Nueva Orden</a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($order['customer']) ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'created' => 'secondary',
                                        'in_progress' => 'warning',
                                        'loading' => 'info',
                                        'loaded' => 'primary',
                                        'in_transit' => 'warning',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusLabels = [
                                        'created' => 'Creada',
                                        'in_progress' => 'En Proceso',
                                        'loading' => 'Cargando',
                                        'loaded' => 'Cargada',
                                        'in_transit' => 'En Tránsito',
                                        'delivered' => 'Entregada',
                                        'cancelled' => 'Cancelada'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $statusColors[$order['status']] ?>">
                                        <?= $statusLabels[$order['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $priorityColors = [
                                        'low' => 'success',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        'urgent' => 'dark'
                                    ];
                                    $priorityLabels = [
                                        'low' => 'Baja',
                                        'medium' => 'Media',
                                        'high' => 'Alta',
                                        'urgent' => 'Urgente'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $priorityColors[$order['priority']] ?>">
                                        <?= $priorityLabels[$order['priority']] ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small><br>
                                    <small class="text-muted">por <?= htmlspecialchars($order['created_by_name']) ?></small>
                                </td>
                                <td>
                                    <a href="/orders/view?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Active Deliveries & Notifications -->
    <div class="col-md-4">
        <!-- Active Deliveries -->
        <div class="card mb-3">
            <div class="card-header">
                <h5><i class="fas fa-shipping-fast"></i> Entregas Activas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($activeDeliveries)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-truck fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No hay entregas activas</p>
                </div>
                <?php else: ?>
                <?php foreach ($activeDeliveries as $delivery): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($delivery['order_number']) ?></strong>
                        <span class="badge bg-<?= $statusColors[$delivery['status']] ?? 'secondary' ?>">
                            <?= ucfirst($delivery['status']) ?>
                        </span>
                    </div>
                    <small class="text-muted">
                        Camión: <?= htmlspecialchars($delivery['truck_plate']) ?><br>
                        Conductor: <?= htmlspecialchars($delivery['driver_name']) ?>
                    </small>
                </div>
                <?php endforeach; ?>
                <a href="/deliveries" class="btn btn-sm btn-primary w-100 mt-2">Ver Todas</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bell"></i> Notificaciones</h5>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No hay notificaciones</p>
                </div>
                <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong class="text-<?= $notification['type'] === 'error' ? 'danger' : $notification['type'] ?>">
                            <?= htmlspecialchars($notification['title']) ?>
                        </strong>
                        <small class="text-muted">
                            <?= date('H:i', strtotime($notification['created_at'])) ?>
                        </small>
                    </div>
                    <small><?= htmlspecialchars($notification['message']) ?></small>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor'])): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="/orders/create" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-plus"></i> Nueva Orden
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/deliveries/create" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-truck"></i> Nueva Entrega
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/reports" class="btn btn-info w-100 mb-2">
                            <i class="fas fa-chart-bar"></i> Generar Reporte
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin" class="btn btn-warning w-100 mb-2">
                            <i class="fas fa-cog"></i> Administración
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../views/layouts/footer.php'; ?>