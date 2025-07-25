<?php 
$pageTitle = 'Entregas - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-shipping-fast"></i> Control de Entregas</h1>
            <a href="/deliveries/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Entrega
            </a>
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
                            <option value="waiting" <?= ($status ?? '') === 'waiting' ? 'selected' : '' ?>>Esperando</option>
                            <option value="loading" <?= ($status ?? '') === 'loading' ? 'selected' : '' ?>>Cargando</option>
                            <option value="loaded" <?= ($status ?? '') === 'loaded' ? 'selected' : '' ?>>Cargado</option>
                            <option value="departed" <?= ($status ?? '') === 'departed' ? 'selected' : '' ?>>Salió</option>
                            <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Entregado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Placa, conductor, orden..."
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

<!-- Deliveries Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Lista de Entregas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($deliveries)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-truck fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron entregas</h4>
                    <p class="text-muted">No hay entregas que coincidan con los filtros seleccionados.</p>
                    <a href="/deliveries/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Nueva Entrega
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Cliente</th>
                                <th>Camión</th>
                                <th>Conductor</th>
                                <th>Estado</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Duración</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($delivery['order_number']) ?></strong>
                                    <?php
                                    $priorityColors = [
                                        'low' => 'success',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        'urgent' => 'dark'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $priorityColors[$delivery['priority']] ?> ms-1">
                                        <?= ucfirst($delivery['priority']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($delivery['customer']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($delivery['truck_plate']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($delivery['driver_name']) ?>
                                    <?php if (!empty($delivery['driver_phone'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($delivery['driver_phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'waiting' => 'secondary',
                                        'loading' => 'warning',
                                        'loaded' => 'info',
                                        'departed' => 'primary',
                                        'delivered' => 'success'
                                    ];
                                    $statusLabels = [
                                        'waiting' => 'Esperando',
                                        'loading' => 'Cargando',
                                        'loaded' => 'Cargado',
                                        'departed' => 'Salió',
                                        'delivered' => 'Entregado'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $statusColors[$delivery['status']] ?>">
                                        <?= $statusLabels[$delivery['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($delivery['entry_time']): ?>
                                        <?= date('d/m/Y H:i', strtotime($delivery['entry_time'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($delivery['exit_time']): ?>
                                        <?= date('d/m/Y H:i', strtotime($delivery['exit_time'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($delivery['entry_time'] && $delivery['exit_time']): ?>
                                        <?php
                                        $duration = (strtotime($delivery['exit_time']) - strtotime($delivery['entry_time'])) / 3600;
                                        echo number_format($duration, 1) . ' hrs';
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/deliveries/view?id=<?= $delivery['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           data-bs-toggle="tooltip" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($delivery['status'] !== 'delivered'): ?>
                                        <a href="/deliveries/evidence?id=<?= $delivery['id'] ?>" 
                                           class="btn btn-sm btn-outline-success"
                                           data-bs-toggle="tooltip" title="Registrar Evidencia">
                                            <i class="fas fa-signature"></i>
                                        </a>
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

<?php include '../views/layouts/footer.php'; ?>