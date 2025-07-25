<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Start session
session_start();

// Router class for handling requests
class Router {
    private $routes = [];
    
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }
    
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }
    
    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove leading slash and get clean path
        $path = '/' . trim($path, '/');
        if ($path === '/') $path = '/home';
        
        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
        } else {
            http_response_code(404);
            include '../views/404.php';
        }
    }
}

// Authentication check
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}

function requireRole($role) {
    requireAuth();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        include '../views/403.php';
        exit;
    }
}

// Load controllers
spl_autoload_register(function ($class) {
    $file = '../controllers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
    $file = '../models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize router
$router = new Router();

// Define routes
$router->get('/home', function() {
    requireAuth();
    $controller = new HomeController();
    $controller->index();
});

$router->get('/login', function() {
    if (isset($_SESSION['user_id'])) {
        header('Location: /home');
        exit;
    }
    $controller = new AuthController();
    $controller->login();
});

$router->post('/login', function() {
    $controller = new AuthController();
    $controller->authenticate();
});

$router->get('/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

// Orders routes
$router->get('/orders', function() {
    requireAuth();
    $controller = new OrderController();
    $controller->index();
});

$router->get('/orders/create', function() {
    requireRole('supervisor');
    $controller = new OrderController();
    $controller->create();
});

$router->post('/orders/store', function() {
    requireRole('supervisor');
    $controller = new OrderController();
    $controller->store();
});

$router->get('/orders/view', function() {
    requireAuth();
    $controller = new OrderController();
    $controller->view();
});

// Deliveries routes
$router->get('/deliveries', function() {
    requireAuth();
    $controller = new DeliveryController();
    $controller->index();
});

$router->get('/deliveries/create', function() {
    requireAuth();
    $controller = new DeliveryController();
    $controller->create();
});

$router->post('/deliveries/store', function() {
    requireAuth();
    $controller = new DeliveryController();
    $controller->store();
});

$router->get('/deliveries/evidence', function() {
    requireAuth();
    $controller = new DeliveryController();
    $controller->evidence();
});

$router->post('/deliveries/evidence/store', function() {
    requireAuth();
    $controller = new DeliveryController();
    $controller->storeEvidence();
});

// Reports routes
$router->get('/reports', function() {
    requireAuth();
    $controller = new ReportController();
    $controller->index();
});

$router->post('/reports/generate', function() {
    requireAuth();
    $controller = new ReportController();
    $controller->generate();
});

// Admin routes
$router->get('/admin', function() {
    requireRole('admin');
    $controller = new AdminController();
    $controller->dashboard();
});

$router->get('/admin/users', function() {
    requireRole('admin');
    $controller = new AdminController();
    $controller->users();
});

// API routes for AJAX
$router->post('/api/scan-product', function() {
    requireAuth();
    $controller = new OrderController();
    $controller->scanProduct();
});

$router->post('/api/upload-signature', function() {
    requireAuth();
    $controller = new DeliveryController();
    $controller->uploadSignature();
});

// Run the router
$router->run();
?>