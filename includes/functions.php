<?php
// includes/functions.php

// Verificar si GD está instalado
function gd_is_available() {
    return extension_loaded('gd') && function_exists('gd_info');
}

// Función para redimensionar y guardar imágenes en formato WebP
function processAndSaveImage($file, $targetPath, $prefix = '', $maxWidth = null, $maxHeight = null) {
    // Verificar si GD está disponible
    if (!gd_is_available()) {
        throw new Exception('La extensión GD no está instalada en el servidor. Contacte al administrador.');
    }
    
    // Verificar si el archivo es una imagen válida
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Archivo no válido o no subido correctamente.');
    }
    
    // Obtener la información de la imagen
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('El archivo no es una imagen válida.');
    }
    
    $mimeType = $imageInfo['mime'];
    
    // Crear imagen según el tipo
    switch($mimeType) {
        case 'image/jpeg':
            $sourceImage = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $sourceImage = @imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            throw new Exception('Formato de imagen no soportado: ' . $mimeType);
    }
    
    if (!$sourceImage) {
        throw new Exception('Error al crear la imagen desde el archivo.');
    }
    
    // Obtener dimensiones originales
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);
    
    // Calcular nuevas dimensiones manteniendo la relación de aspecto
    if ($maxWidth && $maxHeight) {
        // Redimensionar para ajustar exactamente al tamaño especificado
        $newWidth = $maxWidth;
        $newHeight = $maxHeight;
    } else if ($maxWidth && $originalWidth > $maxWidth) {
        // Redimensionar por ancho
        $ratio = $maxWidth / $originalWidth;
        $newWidth = $maxWidth;
        $newHeight = $originalHeight * $ratio;
    } else if ($maxHeight && $originalHeight > $maxHeight) {
        // Redimensionar por alto
        $ratio = $maxHeight / $originalHeight;
        $newHeight = $maxHeight;
        $newWidth = $originalWidth * $ratio;
    } else {
        // Mantener dimensiones originales
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
    }
    
    // Crear nueva imagen
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparencia para PNG y GIF
    if($mimeType == 'image/png' || $mimeType == 'image/gif') {
        imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }
    
    // Redimensionar imagen
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Generar nombre de archivo
    $extension = '.webp';
    $filename = $prefix . uniqid() . $extension;
    $fullPath = $targetPath . $filename;
    
    // Crear directorio si no existe
    if (!file_exists($targetPath)) {
        mkdir($targetPath, 0755, true);
    }
    
    // Guardar imagen en formato WebP
    $success = imagewebp($newImage, $fullPath, IMAGE_QUALITY);
    
    // Liberar memoria
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    if (!$success) {
        throw new Exception('Error al guardar la imagen.');
    }
    
    return $filename;
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar si el usuario es administrador
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Función para obtener datos del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, nombre_apellidos, telefono, direccion, referencia_direccion, rol, estado FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para agregar notificación
function addNotification($userId, $message, $link = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO notificaciones (usuario_id, mensaje, enlace) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $userId);
    $stmt->bindParam(2, $message);
    $stmt->bindParam(3, $link);
    
    return $stmt->execute();
}

// Función para obtener usuarios (para admin)
function getUsers($filters = []) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM usuarios WHERE 1=1";
    $params = [];
    
    if (!empty($filters['search'])) {
        $query .= " AND (nombre_apellidos LIKE ? OR telefono LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['estado'])) {
        $query .= " AND estado = ?";
        $params[] = $filters['estado'];
    }
    
    if (!empty($filters['rol'])) {
        $query .= " AND rol = ?";
        $params[] = $filters['rol'];
    }
    
    $query .= " ORDER BY fecha_registro DESC";
    
    $stmt = $db->prepare($query);
    if ($params) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para crear usuario desde admin
function createUserFromAdmin($data) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el teléfono ya existe
    $query = "SELECT id FROM usuarios WHERE telefono = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data['telefono']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Ya existe un usuario con este teléfono.'];
    }
    
    // Insertar nuevo usuario (sin contraseña inicialmente)
    $query = "INSERT INTO usuarios (nombre_apellidos, telefono, direccion, referencia_direccion, rol, estado) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data['nombre_apellidos']);
    $stmt->bindParam(2, $data['telefono']);
    $stmt->bindParam(3, $data['direccion']);
    $stmt->bindParam(4, $data['referencia_direccion']);
    $stmt->bindParam(5, $data['rol']);
    $stmt->bindParam(6, $data['estado']);
    
    if ($stmt->execute()) {
        return ['success' => true, 'user_id' => $db->lastInsertId()];
    } else {
        return ['success' => false, 'message' => 'Error al crear el usuario.'];
    }
}

// Función para obtener todos los servicios
function getAllServices() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM servicios ORDER BY servicio";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener productos por servicio
function getProductsByService($servicio_id = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT g.*, t.nombre as tematica_nombre, s.servicio as servicio_nombre
              FROM galeria g 
              LEFT JOIN tematicas t ON g.tematica_id = t.id 
              LEFT JOIN servicios s ON g.servicio_id = s.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($servicio_id) {
        $query .= " AND g.servicio_id = ?";
        $params[] = $servicio_id;
    }
    
    $query .= " ORDER BY g.nombre";
    
    $stmt = $db->prepare($query);
    if ($params) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener productos destacados por servicio
function getFeaturedProductsByService($servicio_id = null, $limit = 8) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT g.*, t.nombre as tematica_nombre, s.servicio as servicio_nombre
              FROM galeria g 
              LEFT JOIN tematicas t ON g.tematica_id = t.id 
              LEFT JOIN servicios s ON g.servicio_id = s.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($servicio_id) {
        $query .= " AND g.servicio_id = ?";
        $params[] = $servicio_id;
    }
    
    $query .= " ORDER BY g.visualizaciones DESC, RAND() LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener promociones activas
function getActivePromotions() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM promociones WHERE activa = 1 ORDER BY orden";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener todas las promociones
function getAllPromotions() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM promociones ORDER BY orden";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para actualizar estado de promoción
function updatePromotionStatus($promocion_id, $activa) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE promociones SET activa = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $activa, PDO::PARAM_INT);
    $stmt->bindParam(2, $promocion_id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

// Función para obtener productos más vistos
function getMostViewedProducts($limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT g.*, t.nombre as tematica_nombre, s.servicio as servicio_nombre 
              FROM galeria g 
              LEFT JOIN tematicas t ON g.tematica_id = t.id 
              LEFT JOIN servicios s ON g.servicio_id = s.id 
              ORDER BY g.visualizaciones DESC LIMIT ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>