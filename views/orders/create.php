<?php 
$pageTitle = 'Crear Orden - ' . APP_NAME;
include '../views/layouts/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-plus"></i> Crear Nueva Orden</h1>
            <a href="/orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Órdenes
            </a>
        </div>
    </div>
</div>

<form method="POST" action="/orders/store" id="orderForm">
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
                            <div class="mb-3">
                                <label for="order_number" class="form-label">
                                    <i class="fas fa-hashtag"></i> Número de Orden *
                                </label>
                                <input type="text" class="form-control" id="order_number" name="order_number" 
                                       placeholder="ej: ORD-2024-001" required>
                                <div class="form-text">Debe ser único en el sistema</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer" class="form-label">
                                    <i class="fas fa-building"></i> Cliente
                                </label>
                                <input type="text" class="form-control" id="customer" name="customer" 
                                       value="Samsung" placeholder="Samsung">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">
                                    <i class="fas fa-flag"></i> Prioridad
                                </label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Baja</option>
                                    <option value="medium" selected>Media</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estimated_delivery" class="form-label">
                                    <i class="fas fa-calendar"></i> Entrega Estimada
                                </label>
                                <input type="date" class="form-control" id="estimated_delivery" name="estimated_delivery"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-file-text"></i> Descripción
                        </label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Descripción detallada de la orden..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clipboard-list"></i> Resumen</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Fecha de Creación:</strong><br>
                        <span class="text-muted"><?= date('d/m/Y H:i:s') ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Creado por:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Estado Inicial:</strong><br>
                        <span class="badge bg-secondary">Creada</span>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <strong>Total de Productos:</strong><br>
                        <span id="product-count" class="text-primary fs-4">0</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Peso Total Estimado:</strong><br>
                        <span id="total-weight" class="text-success">0 kg</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-boxes"></i> Productos</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addProductRow()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                </div>
                <div class="card-body">
                    <div id="products-container">
                        <!-- Product rows will be added here -->
                    </div>
                    
                    <div class="text-center py-4" id="no-products-message">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay productos agregados</p>
                        <button type="button" class="btn btn-primary" onclick="addProductRow()">
                            <i class="fas fa-plus"></i> Agregar Primer Producto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <a href="/orders" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Orden
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Product Row Template -->
<template id="product-row-template">
    <div class="product-row border rounded p-3 mb-3" data-row-index="">
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Producto *</label>
                <select class="form-select product-select" name="products[][product_id]" required onchange="updateProductInfo(this)">
                    <option value="">Seleccione un producto...</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" 
                            data-weight="<?= $product['weight_kg'] ?>"
                            data-code="<?= htmlspecialchars($product['product_code']) ?>"
                            data-name="<?= htmlspecialchars($product['name']) ?>">
                        [<?= htmlspecialchars($product['product_code']) ?>] <?= htmlspecialchars($product['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Cantidad *</label>
                <input type="number" class="form-control quantity-input" 
                       name="products[][quantity]" min="1" value="1" required
                       onchange="updateTotals()">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Peso Unit. (kg)</label>
                <input type="text" class="form-control weight-display" readonly>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Notas</label>
                <input type="text" class="form-control" name="products[][notes]" 
                       placeholder="Notas adicionales...">
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger d-block" onclick="removeProductRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
let productRowIndex = 0;

function addProductRow() {
    const template = document.getElementById('product-row-template');
    const container = document.getElementById('products-container');
    const noProductsMessage = document.getElementById('no-products-message');
    
    // Clone template
    const clone = template.content.cloneNode(true);
    
    // Set unique index
    const productRow = clone.querySelector('.product-row');
    productRow.setAttribute('data-row-index', productRowIndex++);
    
    // Add to container
    container.appendChild(clone);
    
    // Hide no products message
    noProductsMessage.style.display = 'none';
    
    updateTotals();
}

function removeProductRow(button) {
    const row = button.closest('.product-row');
    row.remove();
    
    // Show no products message if no rows left
    const container = document.getElementById('products-container');
    const noProductsMessage = document.getElementById('no-products-message');
    
    if (container.children.length === 0) {
        noProductsMessage.style.display = 'block';
    }
    
    updateTotals();
}

function updateProductInfo(select) {
    const row = select.closest('.product-row');
    const weightDisplay = row.querySelector('.weight-display');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const weight = selectedOption.getAttribute('data-weight') || '0';
        weightDisplay.value = weight + ' kg';
    } else {
        weightDisplay.value = '';
    }
    
    updateTotals();
}

function updateTotals() {
    const productRows = document.querySelectorAll('.product-row');
    let totalProducts = 0;
    let totalWeight = 0;
    
    productRows.forEach(row => {
        const select = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        
        if (select.value && quantityInput.value) {
            totalProducts += parseInt(quantityInput.value) || 0;
            
            const selectedOption = select.options[select.selectedIndex];
            const weight = parseFloat(selectedOption.getAttribute('data-weight')) || 0;
            const quantity = parseInt(quantityInput.value) || 0;
            
            totalWeight += weight * quantity;
        }
    });
    
    document.getElementById('product-count').textContent = totalProducts;
    document.getElementById('total-weight').textContent = totalWeight.toFixed(2) + ' kg';
}

// Add first product row on page load
document.addEventListener('DOMContentLoaded', function() {
    // Generate unique order number suggestion
    const today = new Date();
    const dateStr = today.getFullYear() + 
                   (today.getMonth() + 1).toString().padStart(2, '0') + 
                   today.getDate().toString().padStart(2, '0');
    const timeStr = today.getHours().toString().padStart(2, '0') + 
                   today.getMinutes().toString().padStart(2, '0');
    
    document.getElementById('order_number').value = `ORD-${dateStr}-${timeStr}`;
});

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const productRows = document.querySelectorAll('.product-row');
    
    if (productRows.length === 0) {
        e.preventDefault();
        alert('Debe agregar al menos un producto a la orden.');
        return false;
    }
    
    let hasValidProducts = false;
    productRows.forEach(row => {
        const select = row.querySelector('.product-select');
        const quantity = row.querySelector('.quantity-input');
        
        if (select.value && quantity.value && parseInt(quantity.value) > 0) {
            hasValidProducts = true;
        }
    });
    
    if (!hasValidProducts) {
        e.preventDefault();
        alert('Debe tener al menos un producto válido con cantidad mayor a 0.');
        return false;
    }
});
</script>

<?php include '../views/layouts/footer.php'; ?>