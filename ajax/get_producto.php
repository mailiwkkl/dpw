<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no especificado']);
    exit();
}

$producto_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

$query = "SELECT g.*, t.nombre as tematica_nombre, c.nombre as categoria_nombre 
          FROM galeria g 
          LEFT JOIN tematicas t ON g.tematica_id = t.id 
          LEFT JOIN categorias c ON g.categoria_id = c.id 
          WHERE g.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $producto_id);
$stmt->execute();

$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if ($producto) {
    echo json_encode(['success' => true, 'producto' => $producto]);
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
}