// DAECHANG Shipping Control System - Main JavaScript File

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Initialize signature pad if present
    initSignaturePad();
    
    // Initialize file upload areas
    initFileUpload();
    
    // Initialize barcode scanning
    initBarcodeScanning();
}

// Signature Pad Functionality
function initSignaturePad() {
    const signaturePads = document.querySelectorAll('.signature-pad canvas');
    
    signaturePads.forEach(function(canvas) {
        let isDrawing = false;
        const ctx = canvas.getContext('2d');
        
        // Set canvas size
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        
        // Drawing event listeners
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        // Touch events for mobile
        canvas.addEventListener('touchstart', handleTouch);
        canvas.addEventListener('touchmove', handleTouch);
        canvas.addEventListener('touchend', stopDrawing);
        
        function startDrawing(e) {
            isDrawing = true;
            ctx.beginPath();
            ctx.moveTo(getEventX(e), getEventY(e));
        }
        
        function draw(e) {
            if (!isDrawing) return;
            
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
            
            ctx.lineTo(getEventX(e), getEventY(e));
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(getEventX(e), getEventY(e));
        }
        
        function stopDrawing() {
            isDrawing = false;
            ctx.beginPath();
            
            // Save signature data
            const signatureData = canvas.toDataURL();
            const hiddenInput = canvas.parentElement.querySelector('input[type="hidden"]');
            if (hiddenInput) {
                hiddenInput.value = signatureData;
            }
        }
        
        function handleTouch(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                            e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }
        
        function getEventX(e) {
            const rect = canvas.getBoundingClientRect();
            return e.clientX - rect.left;
        }
        
        function getEventY(e) {
            const rect = canvas.getBoundingClientRect();
            return e.clientY - rect.top;
        }
        
        // Clear button functionality
        const clearButton = canvas.parentElement.querySelector('.clear-signature');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const hiddenInput = canvas.parentElement.querySelector('input[type="hidden"]');
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            });
        }
    });
}

// File Upload Functionality
function initFileUpload() {
    const uploadAreas = document.querySelectorAll('.file-upload-area');
    
    uploadAreas.forEach(function(area) {
        const input = area.querySelector('input[type="file"]');
        
        // Click to upload
        area.addEventListener('click', function() {
            input.click();
        });
        
        // Drag and drop
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function() {
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            area.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileSelect(files, area);
            }
        });
        
        // File input change
        input.addEventListener('change', function() {
            handleFileSelect(this.files, area);
        });
    });
}

function handleFileSelect(files, area) {
    const fileList = area.querySelector('.file-list');
    if (!fileList) return;
    
    fileList.innerHTML = '';
    
    Array.from(files).forEach(function(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item d-flex justify-content-between align-items-center p-2 border-bottom';
        fileItem.innerHTML = `
            <div>
                <i class="fas fa-file"></i> ${file.name}
                <small class="text-muted">(${formatFileSize(file.size)})</small>
            </div>
            <button type="button" class="btn btn-sm btn-danger remove-file">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        fileList.appendChild(fileItem);
        
        // Remove file functionality
        fileItem.querySelector('.remove-file').addEventListener('click', function() {
            fileItem.remove();
        });
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Barcode Scanning
function initBarcodeScanning() {
    const scanButtons = document.querySelectorAll('.scan-product-btn');
    
    scanButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const action = this.dataset.action || 'scan';
            
            // Show loading
            const originalContent = this.innerHTML;
            this.innerHTML = '<span class="loading-spinner"></span> Procesando...';
            this.disabled = true;
            
            // Send AJAX request
            fetch('/api/scan-product', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    // Update UI
                    location.reload();
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Error de conexión');
            })
            .finally(() => {
                this.innerHTML = originalContent;
                this.disabled = false;
            });
        });
    });
}

// Utility Functions
function showAlert(type, message) {
    const alertContainer = document.querySelector('.alert-container') || document.querySelector('main');
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.insertBefore(alert, alertContainer.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX') + ' ' + date.toLocaleTimeString('es-MX');
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// AJAX form submission
function submitFormAjax(form, successCallback) {
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (successCallback) successCallback(data);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error de conexión');
    });
}

// Real-time clock
function updateClock() {
    const clockElements = document.querySelectorAll('.real-time-clock');
    clockElements.forEach(function(element) {
        element.textContent = new Date().toLocaleString('es-MX');
    });
}

// Update clock every second
setInterval(updateClock, 1000);

// Export functions for use in other scripts
window.DaechangApp = {
    showAlert,
    formatDate,
    confirmAction,
    submitFormAjax
};