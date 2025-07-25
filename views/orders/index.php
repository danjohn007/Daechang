<?php 
$pageTitle = 'Órdenes de Embarque - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-box"></i> Órdenes de Embarque</h1>
            <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor'])): ?>
            <a href="/orders/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Orden
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>Todos</option>
                            <option value="created" <?= ($status ?? '') === 'created' ? 'selected' : '' ?>>Creada</option>
                            <option value="in_progress" <?= ($status ?? '') === 'in_progress' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="loading" <?= ($status ?? '') === 'loading' ? 'selected' : '' ?>>Cargando</option>
                            <option value="loaded" <?= ($status ?? '') === 'loaded' ? 'selected' : '' ?>>Cargada</option>
                            <option value="in_transit" <?= ($status ?? '') === 'in_transit' ? 'selected' : '' ?>>En Tránsito</option>
                            <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Entregada</option>
                            <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Número de orden, cliente o descripción..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Lista de Órdenes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron órdenes</h4>
                    <p class="text-muted">No hay órdenes que coincidan con los filtros seleccionados.</p>
                    <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor'])): ?>
                    <a href="/orders/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Nueva Orden
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número de Orden</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Progreso</th>
                                <th>Entrega Est.</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                    <?php if (!empty($order['description'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(substr($order['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
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
                                    <?php 
                                    $progress = $order['total_items'] > 0 ? 
                                        round(($order['scanned_items'] / $order['total_items']) * 100) : 0;
                                    $progressColor = $progress === 100 ? 'success' : ($progress > 50 ? 'warning' : 'danger');
                                    ?>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-<?= $progressColor ?>" 
                                             style="width: <?= $progress ?>%"
                                             data-bs-toggle="tooltip" 
                                             title="<?= $order['scanned_items'] ?> de <?= $order['total_items'] ?> productos escaneados">
                                            <?= $progress ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $order['scanned_items'] ?>/<?= $order['total_items'] ?> productos
                                    </small>
                                </td>
                                <td>
                                    <?php if ($order['estimated_delivery']): ?>
                                        <?= date('d/m/Y', strtotime($order['estimated_delivery'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No definida</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
                                    <small class="text-muted">por <?= htmlspecialchars($order['created_by_name']) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/orders/view?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           data-bs-toggle="tooltip" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor']) && 
                                                  $order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                        <a href="/orders/edit?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-outline-warning"
                                           data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="tooltip" title="Eliminar"
                                                onclick="confirmDelete(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
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
</div>

<script>
function confirmDelete(orderId, orderNumber) {
    if (confirm(`¿Está seguro de que desea eliminar la orden ${orderNumber}?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = `/orders/delete?id=${orderId}`;
    }
}
</script>

<?php include '../views/layouts/footer.php'; ?>