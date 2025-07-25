# Sistema de Control de Embarques - DAECHANG

Sistema web desarrollado en PHP puro + MySQL para gestionar el control de embarques en la maquiladora Samsung DAECHANG.

## 🎯 Características Principales

### ✅ Funcionalidades Implementadas

**🔐 Sistema de Autenticación**
- Login seguro con hash de contraseñas
- Control de roles: Admin, Supervisor, Operador, Seguridad
- Gestión de sesiones con timeout automático
- Registro de eventos de seguridad

**📦 Gestión de Órdenes**
- Crear, editar y visualizar órdenes de embarque
- Catálogo de productos integrado
- Sistema de escaneo/marcado de productos
- Seguimiento de progreso en tiempo real
- Filtros y búsqueda avanzada

**🚛 Control de Entregas**
- Registro de camiones y conductores
- Control de entrada y salida
- Estados de entrega en tiempo real
- Validación de seguridad

**📊 Sistema de Reportes**
- Generación de reportes en Excel
- Reportes de órdenes, entregas, seguridad y desempeño
- Filtros por fechas y estados
- Estadísticas operacionales

**👥 Panel de Administración**
- Gestión completa de usuarios
- Estadísticas del sistema
- Monitor de actividad en tiempo real
- Información del sistema

**🎨 Interfaz de Usuario**
- Diseño responsivo con Bootstrap 5
- Navegación intuitiva
- Dashboard con métricas en tiempo real
- Notificaciones y alertas

### 🛠️ Stack Tecnológico

- **Backend:** PHP 7.4+ (sin frameworks)
- **Base de Datos:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5
- **JavaScript:** Vanilla JS con funciones avanzadas
- **Arquitectura:** MVC personalizado

## 📋 Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache/Nginx
- Extensiones PHP: PDO, PDO_MySQL, GD
- Mínimo 512MB RAM
- 100MB espacio en disco

## 🚀 Instalación

### 1. Clonar el Repositorio
```bash
git clone https://github.com/danjohn007/Daechang.git
cd Daechang
```

### 2. Configurar la Base de Datos
```bash
# Ejecutar el script de configuración
php setup.php
```

### 3. Configurar el Servidor Web

**Apache:**
```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/Daechang/public"
    ServerName daechang.local
    
    <Directory "/path/to/Daechang/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name daechang.local;
    root /path/to/Daechang/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. Configurar Permisos
```bash
chmod -R 755 /path/to/Daechang
chmod -R 777 /path/to/Daechang/uploads
```

### 5. Acceder al Sistema
- URL: `http://daechang.local` (o tu dominio configurado)
- Usuario: `admin`
- Contraseña: `admin123`

## 📖 Guía de Uso

### Roles de Usuario

**👑 Administrador**
- Acceso completo al sistema
- Gestión de usuarios
- Configuración del sistema
- Todos los permisos

**👨‍💼 Supervisor**
- Crear y editar órdenes
- Gestionar entregas
- Generar reportes
- Supervisar operaciones

**👷 Operador**
- Ver órdenes asignadas
- Escanear productos
- Registrar evidencias
- Operaciones básicas

**🛡️ Seguridad**
- Control de acceso
- Validar documentos
- Registro de entrada/salida
- Eventos de seguridad

### Flujo de Trabajo Típico

1. **Crear Orden** (Supervisor)
   - Registrar nueva orden de embarque
   - Agregar productos del catálogo
   - Definir prioridad y fecha de entrega

2. **Procesar Orden** (Operador)
   - Escanear productos conforme se cargan
   - Verificar cantidades y peso
   - Actualizar progreso en tiempo real

3. **Registrar Entrega** (Seguridad/Operador)
   - Registrar datos del camión y conductor
   - Controlar entrada y salida
   - Validar documentación

4. **Evidencia de Entrega** (Operador)
   - Capturar firma digital del receptor
   - Tomar fotografías de evidencia
   - Registrar notas de entrega

5. **Generar Reportes** (Supervisor/Admin)
   - Crear reportes de desempeño
   - Exportar datos en Excel
   - Analizar métricas operacionales

## 🔧 Configuración Avanzada

### Variables de Configuración (`config/config.php`)
```php
// Base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'daechang_shipping');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// Seguridad
define('SESSION_TIMEOUT', 3600); // 1 hora
define('HASH_ALGO', 'sha256');

// Archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
```

### Estructura de Directorios
```
Daechang/
├── config/          # Configuración
├── controllers/     # Controladores MVC
├── models/         # Modelos de datos
├── views/          # Vistas/Templates
├── public/         # Archivos públicos
│   ├── css/       # Estilos
│   ├── js/        # JavaScript
│   └── index.php  # Punto de entrada
├── uploads/        # Archivos subidos
├── database/       # Scripts SQL
└── setup.php      # Script de instalación
```

## 🔐 Seguridad

- Contraseñas hasheadas con `password_hash()`
- Protección contra SQL injection con PDO
- Validación de sesiones y roles
- Registro completo de eventos de seguridad
- Sanitización de datos de entrada
- Control de tamaño de archivos

## 📊 Base de Datos

### Tablas Principales
- `users` - Usuarios del sistema
- `orders` - Órdenes de embarque
- `products` - Catálogo de productos
- `order_items` - Items de cada orden
- `deliveries` - Registro de entregas
- `delivery_evidence` - Evidencias de entrega
- `security_logs` - Eventos de seguridad

## 🤝 Contribuciones

Este es un proyecto específico para Samsung DAECHANG. Para contribuir:

1. Fork el proyecto
2. Crear rama para nueva característica
3. Commit los cambios
4. Push a la rama
5. Abrir Pull Request

## 📞 Soporte

Para soporte técnico o preguntas:
- Email: admin@daechang.com
- Sistema de tickets interno

## 📄 Licencia

Proyecto propietario para Samsung DAECHANG. Todos los derechos reservados.

---

**Desarrollado para Samsung DAECHANG - Sistema de Control de Embarques v1.0.0**
