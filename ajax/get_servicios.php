<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['servicio'])) {
    echo json_encode(['success' => false, 'message' => 'ID de categoría no especificado']);
    exit();
}

$categoria_id = $_GET['servicio'];
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM servicios WHERE servicio = ? ORDER BY servicio";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $servicio);
$stmt->execute();

$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'servicios' => $servicios
]);