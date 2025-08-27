<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['factura_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de factura no especificado']);
    exit();
}

$factura_id = $data['factura_id'];
$database = new Database();
$db = $database->getConnection();

// Verificar que la factura pertenece al usuario
if (!isAdmin()) {
    $query = "SELECT id FROM facturas WHERE id = ? AND cliente_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $factura_id);
    $stmt->bindParam(2, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para esta operación']);
        exit();
    }
}

// Actualizar estado de la factura
$query = "UPDATE facturas SET estado = 'En Revisión' WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $factura_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pedido enviado para revisión']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']);
}