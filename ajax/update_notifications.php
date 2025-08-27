<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit();
    }
    
    switch ($data['action']) {
        case 'mark_as_read':
            if (!isset($data['notification_id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de notificación no especificado']);
                exit();
            }
            
            $success = markNotificationAsRead($data['notification_id'], $user_id);
            echo json_encode(['success' => $success]);
            break;
            
        case 'mark_all_read':
            $success = markAllNotificationsAsRead($user_id);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete':
            if (!isset($data['notification_id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de notificación no especificado']);
                exit();
            }
            
            $success = deleteNotification($data['notification_id'], $user_id);
            echo json_encode(['success' => $success]);
            break;
            
        case 'delete_all_read':
            $success = deleteAllReadNotifications($user_id);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}