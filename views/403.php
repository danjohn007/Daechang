<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <div class="error-page">
                    <i class="fas fa-ban fa-5x text-danger mb-4"></i>
                    <h1 class="display-1 fw-bold text-muted">403</h1>
                    <h2 class="mb-4">Acceso Denegado</h2>
                    <p class="lead mb-4">
                        No tiene permisos suficientes para acceder a esta página.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/home" class="btn btn-primary">
                            <i class="fas fa-home"></i> Ir al Dashboard
                        </a>
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver Atrás
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>