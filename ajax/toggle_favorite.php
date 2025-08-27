<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión']);
    exit();
}

// Verificar que se recibieron los datos necesarios
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['item_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$item_id = $data['item_id'];
$action = $data['action'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

if ($action === 'add') {
    // Agregar a favoritos
    $query = "INSERT INTO favoritos (usuario_id, galeria_id) VALUES (?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $item_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar a favoritos']);
    }
} elseif ($action === 'remove') {
    // Eliminar de favoritos
    $query = "DELETE FROM favoritos WHERE usuario_id = ? AND galeria_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $item_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar de favoritos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}