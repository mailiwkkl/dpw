<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de item no especificado']);
    exit();
}

$item_id = $data['item_id'];
$database = new Database();
$db = $database->getConnection();

$query = "UPDATE galeria SET visualizaciones = visualizaciones + 1 WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $item_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al incrementar visualizaciones']);
}