<?php
/**
 * Utilidades varias para Digital Print Fiesta
 */

/**
 * Sanitizar datos de entrada
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar teléfono (formato básico)
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9\s\-\+\(\)]{10,20}$/', $phone);
}

/**
 * Generar contraseña segura
 */
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Formatear precio
 */
function formatPrice($price, $currency = '$') {
    return $currency . number_format(floatval($price), 2);
}

/**
 * Obtener la fecha en formato legible
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date || $date == '0000-00-00') {
        return '--';
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Obtener la diferencia de tiempo en formato legible
 */
function timeAgo($date) {
    if (empty($date)) {
        return "No disponible";
    }
    
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return "hace " . $diff . " segundos";
    } elseif ($diff < 3600) {
        return "hace " . round($diff / 60) . " minutos";
    } elseif ($diff < 86400) {
        return "hace " . round($diff / 3600) . " horas";
    } elseif ($diff < 2592000) {
        return "hace " . round($diff / 86400) . " días";
    } elseif ($diff < 31536000) {
        return "hace " . round($diff / 2592000) . " meses";
    } else {
        return "hace " . round($diff / 31536000) . " años";
    }
}

/**
 * Redireccionar con mensaje
 */
function redirectWithMessage($url, $type, $message) {
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit();
}

/**
 * Verificar si una imagen es válida
 */
function isValidImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mime_type, $allowed_types);
}

/**
 * Limpiar nombre de archivo
 */
function cleanFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}

/**
 * Obtener la extensión de un archivo
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Crear directorio si no existe
 */
function createDirectoryIfNotExists($path) {
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

/**
 * Log de errores personalizado
 */
function logError($message, $file = '', $line = '') {
    $log_message = date('[Y-m-d H:i:s]') . " ";
    $log_message .= "Error: " . $message;
    
    if (!empty($file)) {
        $log_message .= " in " . $file;
    }
    
    if (!empty($line)) {
        $log_message .= " on line " . $line;
    }
    
    $log_message .= PHP_EOL;
    
    // Guardar en archivo de log
    $log_file = __DIR__ . '/../logs/error.log';
    createDirectoryIfNotExists(dirname($log_file));
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Obtener IP del cliente
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Validar si es una URL válida
 */
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Limitar texto
 */
function limitText($text, $limit = 100, $end = '...') {
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    
    return mb_substr($text, 0, $limit) . $end;
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}