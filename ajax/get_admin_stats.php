<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$stats = [];

// Total de usuarios
$query = "SELECT COUNT(*) as total FROM usuarios";
$stats['total_usuarios'] = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

// Total de facturas
$query = "SELECT COUNT(*) as total FROM facturas";
$stats['total_facturas'] = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

// Pendientes de pago
$query = "SELECT COUNT(*) as total FROM facturas WHERE estado = 'Pendiente de Pago'";
$stats['pendientes_pago'] = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

// Total en galería
$query = "SELECT COUNT(*) as total FROM galeria";
$stats['total_galeria'] = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

// Ventas del mes actual
$query = "SELECT COALESCE(SUM(importe_total), 0) as total 
          FROM facturas 
          WHERE estado = 'Entregada' 
          AND MONTH(fecha_solicitud) = MONTH(CURRENT_DATE()) 
          AND YEAR(fecha_solicitud) = YEAR(CURRENT_DATE())";
$stats['ventas_mes_actual'] = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

// Pedidos en proceso
$query = "SELECT COUNT(*) as total FROM facturas WHERE estado IN ('En Proceso', 'En Espera')";
$stats['en_proceso'] = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'last_updated' => date('Y-m-d H:i:s')
]);