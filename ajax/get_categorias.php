<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_GET['tematica_id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID de la temática']);
    exit();
}

$tematica_id = $_GET['tematica_id'];
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM categorias WHERE tematica_id = ? ORDER BY nombre";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $tematica_id);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'categorias' => $categorias]);