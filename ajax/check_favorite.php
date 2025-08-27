<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado', 'is_favorite' => false]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de item no especificado', 'is_favorite' => false]);
    exit();
}

$item_id = $data['item_id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

$query = "SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = ? AND galeria_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->bindParam(2, $item_id);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true, 
    'is_favorite' => ($result['total'] > 0)
]);