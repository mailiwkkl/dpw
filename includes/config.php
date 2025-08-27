<?php
// includes/config.php
session_start();

// Configuración básica primero
define('APP_NAME', 'Digital Print Fiesta');
define('APP_URL', 'http://localhost/dpw');

// Luego incluir archivos esenciales en el orden correcto
require_once 'database.php';
require_once 'functions.php'; // Aquí está isLoggedIn()
require_once 'auth.php';
require_once 'notifications.php';

// Ahora el resto de configuraciones que dependen de las funciones
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('IMAGE_PATH', __DIR__ . '/../assets/images/');
define('LOG_PATH', __DIR__ . '/../logs/');
define('ERROR_LOG', LOG_PATH . 'error.log');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'digital_print_fiesta');
define('DB_USER', 'root');
define('DB_PASS', '');

// Paleta de colores
define('COLOR_PRIMARY', '#6b46c1');
define('COLOR_SECONDARY', '#805ad5');
define('COLOR_ACCENT', '#9f7aea');
define('COLOR_LIGHT', '#e9d8fd');
define('COLOR_DARK', '#322659');
define('COLOR_DARKER', '#211a36');
define('COLOR_TEXT', '#2d3748');
define('COLOR_BACKGROUND', '#f8fafc');
define('COLOR_WHITE', '#ffffff');
define('COLOR_GRAY_LIGHT', '#e2e8f0');
define('COLOR_SUCCESS', '#48bb78');
define('COLOR_ERROR', '#f56565');
define('COLOR_WARNING', '#ed8936');

// Otras configuraciones
define('MAX_IMAGE_WIDTH', 1200);
define('MAX_IMAGE_HEIGHT', 300);
define('IMAGE_QUALITY', 80);

// Crear directorio de logs si no existe
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Función para logging
function log_message($message, $level = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents(ERROR_LOG, $log_entry, FILE_APPEND | LOCK_EX);
}
?>