<?php 
$pageTitle = 'Ver Orden ' . $order['order_number'] . ' - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-eye"></i> Orden: <?= htmlspecialchars($order['order_number']) ?></h1>
            <div>
                <a href="/orders" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Órdenes
                </a>
                <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor']) && 
                          $order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                <a href="/orders/edit?id=<?= $order['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Order Information -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Información de la Orden</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Número de Orden:</strong></td>
                                <td><?= htmlspecialchars($order['order_number']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cliente:</strong></td>
                                <td><?= htmlspecialchars($order['customer']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
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
                                    <span class="badge bg-<?= $statusColors[$order['status']] ?> fs-6">
                                        <?= $statusLabels[$order['status']] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Prioridad:</strong></td>
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
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Entrega Estimada:</strong></td>
                                <td>
                                    <?php if ($order['estimated_delivery']): ?>
                                        <?= date('d/m/Y', strtotime($order['estimated_delivery'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No definida</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Creado:</strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Creado por:</strong></td>
                                <td><?= htmlspecialchars($order['created_by_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Última actualización:</strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($order['description'])): ?>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <strong>Descripción:</strong><br>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($order['description'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Summary -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> Resumen</h5>
            </div>
            <div class="card-body">
                <?php
                $totalItems = count($orderItems);
                $scannedItems = count(array_filter($orderItems, function($item) { return $item['scanned']; }));
                $progress = $totalItems > 0 ? round(($scannedItems / $totalItems) * 100) : 0;
                $totalWeight = array_sum(array_map(function($item) { 
                    return ($item['quantity'] * $item['weight_kg']); 
                }, $orderItems));
                ?>
                
                <div class="mb-3">
                    <strong>Progreso de Escaneo:</strong><br>
                    <div class="progress mt-2" style="height: 25px;">
                        <div class="progress-bar bg-<?= $progress === 100 ? 'success' : ($progress > 50 ? 'warning' : 'danger') ?>" 
                             style="width: <?= $progress ?>%">
                            <?= $progress ?>%
                        </div>
                    </div>
                    <small class="text-muted"><?= $scannedItems ?> de <?= $totalItems ?> productos escaneados</small>
                </div>
                
                <div class="mb-3">
                    <strong>Total de Productos:</strong><br>
                    <span class="fs-4 text-primary"><?= $totalItems ?></span>
                </div>
                
                <div class="mb-3">
                    <strong>Peso Total:</strong><br>
                    <span class="fs-5 text-success"><?= number_format($totalWeight, 2) ?> kg</span>
                </div>
                
                <?php if ($delivery): ?>
                <hr>
                <div class="mb-3">
                    <strong>Estado de Entrega:</strong><br>
                    <span class="badge bg-info"><?= ucfirst($delivery['status']) ?></span>
                </div>
                
                <div class="text-center">
                    <a href="/deliveries/view?id=<?= $delivery['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-truck"></i> Ver Entrega
                    </a>
                </div>
                <?php else: ?>
                <hr>
                <div class="text-center">
                    <a href="/deliveries/create?order_id=<?= $order['id'] ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Crear Entrega
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Products Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-boxes"></i> Productos (<?= count($orderItems) ?>)</h5>
                <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor', 'operator']) && 
                          $order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                <div>
                    <button class="btn btn-sm btn-success" onclick="scanAllProducts()">
                        <i class="fas fa-qrcode"></i> Marcar Todos
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="unscanAllProducts()">
                        <i class="fas fa-undo"></i> Desmarcar Todos
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($orderItems)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay productos en esta orden</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Peso Unit.</th>
                                <th>Peso Total</th>
                                <th>Estado</th>
                                <th>Escaneado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                            <tr class="<?= $item['scanned'] ? 'table-success' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($item['product_code']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($item['product_name']) ?>
                                    <?php if (!empty($item['notes'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($item['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['weight_kg'], 2) ?> kg</td>
                                <td><?= number_format($item['quantity'] * $item['weight_kg'], 2) ?> kg</td>
                                <td>
                                    <?php if ($item['scanned']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Escaneado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['scanned'] && $item['scanned_at']): ?>
                                        <small class="text-success">
                                            <?= date('d/m/Y H:i', strtotime($item['scanned_at'])) ?><br>
                                            por <?= htmlspecialchars($item['scanned_by_name']) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">No escaneado</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (in_array($_SESSION['user_role'], ['admin', 'supervisor', 'operator']) && 
                                              $order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                    <?php if ($item['scanned']): ?>
                                        <button class="btn btn-sm btn-outline-warning scan-product-btn" 
                                                data-item-id="<?= $item['id'] ?>" 
                                                data-action="unscan"
                                                title="Remover escaneo">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-success scan-product-btn" 
                                                data-item-id="<?= $item['id'] ?>" 
                                                data-action="scan"
                                                title="Marcar como escaneado">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="2"><strong>TOTALES</strong></td>
                                <td><strong><?= array_sum(array_column($orderItems, 'quantity')) ?></strong></td>
                                <td>-</td>
                                <td><strong><?= number_format($totalWeight, 2) ?> kg</strong></td>
                                <td colspan="3">
                                    <strong><?= $scannedItems ?>/<?= $totalItems ?> productos escaneados</strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function scanAllProducts() {
    if (confirm('¿Marcar todos los productos como escaneados?')) {
        const buttons = document.querySelectorAll('.scan-product-btn[data-action="scan"]');
        buttons.forEach(button => {
            button.click();
        });
    }
}

function unscanAllProducts() {
    if (confirm('¿Desmarcar todos los productos escaneados?')) {
        const buttons = document.querySelectorAll('.scan-product-btn[data-action="unscan"]');
        buttons.forEach(button => {
            button.click();
        });
    }
}
</script>

<?php include '../views/layouts/footer.php'; ?>