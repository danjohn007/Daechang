    </main>

    <!-- Footer -->
    <footer class="bg-light py-3 mt-5">
        <div class="container text-center">
            <p class="mb-0 text-muted">
                © <?= date('Y') ?> <?= APP_NAME ?> - v<?= APP_VERSION ?>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/js/app.js"></script>
    
    <!-- Session timeout check -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        // Auto-logout after session timeout
        let sessionTimeout = <?= SESSION_TIMEOUT * 1000 ?>; // Convert to milliseconds
        let warningTime = sessionTimeout - (5 * 60 * 1000); // 5 minutes before timeout
        
        setTimeout(function() {
            alert('Su sesión expirará en 5 minutos. Guarde su trabajo.');
        }, warningTime);
        
        setTimeout(function() {
            alert('Su sesión ha expirado. Será redirigido al login.');
            window.location.href = '/logout';
        }, sessionTimeout);
    </script>
    <?php endif; ?>
</body>
</html>