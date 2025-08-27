<?php
/**
 * Sistema de notificaciones para Digital Print Fiesta
 * 
 * Funciones para gestionar las notificaciones del sistema
 */

// Función para obtener notificaciones del usuario
function getUserNotifications($user_id, $unread_only = false) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM notificaciones WHERE usuario_id = ?";
    if ($unread_only) {
        $query .= " AND leida = FALSE";
    }
    $query .= " ORDER BY fecha_creacion DESC LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para marcar notificación como leída
function markNotificationAsRead($notification_id, $user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notificaciones SET leida = TRUE WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $notification_id);
    $stmt->bindParam(2, $user_id);
    
    return $stmt->execute();
}

// Función para marcar todas las notificaciones como leídas
function markAllNotificationsAsRead($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notificaciones SET leida = TRUE WHERE usuario_id = ? AND leida = FALSE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    
    return $stmt->execute();
}

// Función para obtener el conteo de notificaciones no leídas
function getUnreadNotificationsCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM notificaciones WHERE usuario_id = ? AND leida = FALSE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Función para eliminar notificación
function deleteNotification($notification_id, $user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $notification_id);
    $stmt->bindParam(2, $user_id);
    
    return $stmt->execute();
}

// Función para eliminar todas las notificaciones leídas
function deleteAllReadNotifications($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM notificaciones WHERE usuario_id = ? AND leida = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    
    return $stmt->execute();
}

// Procesar acciones de notificaciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_action'])) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['notification_action'];
    
    switch ($action) {
        case 'mark_as_read':
            if (isset($_POST['notification_id'])) {
                $success = markNotificationAsRead($_POST['notification_id'], $user_id);
                echo json_encode(['success' => $success]);
            }
            break;
            
        case 'mark_all_read':
            $success = markAllNotificationsAsRead($user_id);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete':
            if (isset($_POST['notification_id'])) {
                $success = deleteNotification($_POST['notification_id'], $user_id);
                echo json_encode(['success' => $success]);
            }
            break;
            
        case 'delete_all_read':
            $success = deleteAllReadNotifications($user_id);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    exit();
}

// AJAX para obtener notificaciones
if (isset($_GET['get_notifications']) && isLoggedIn()) {
    header('Content-Type: application/json');
    
    $user_id = $_SESSION['user_id'];
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] == 'true';
    
    $notifications = getUserNotifications($user_id, $unread_only);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => getUnreadNotificationsCount($user_id)
    ]);
    exit();
}