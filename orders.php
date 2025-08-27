<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$page_title = "Mis Pedidos - " . APP_NAME;

// Solo usuarios logueados pueden ver pedidos
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener facturas del usuario
$query = "SELECT * FROM facturas WHERE cliente_id = ? ORDER BY fecha_solicitud DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $_SESSION['user_id']);
$stmt->execute();
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <h1 style="text-align: center; margin-bottom: 30px;">Mis Pedidos</h1>
    
    <?php if (empty($facturas)): ?>
    <div style="text-align: center; padding: 40px 0;">
        <i class="fas fa-shopping-cart" style="font-size: 60px; color: var(--light-color); margin-bottom: 20px;"></i>
        <h3>No tienes pedidos todavía</h3>
        <p>Realiza tu primer pedido y haz realidad tus ideas.</p>
        <a href="catalog.php" class="btn">Explorar Catálogo</a>
    </div>
    <?php else: ?>
    <div class="orders-list">
        <?php foreach ($facturas as $factura): 
            // Obtener pedidos de esta factura
            $query_pedidos = "SELECT p.*, s.servicio, g.nombre as producto_nombre 
                             FROM pedidos p 
                             LEFT JOIN servicios s ON p.servicio_id = s.id 
                             LEFT JOIN galeria g ON p.galeria_id = g.id 
                             WHERE p.factura_id = ?";
            $stmt_pedidos = $db->prepare($query_pedidos);
            $stmt_pedidos->bindParam(1, $factura['id']);
            $stmt_pedidos->execute();
            $pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
            
            // Determinar clase CSS según estado
            $estado_class = '';
            switch ($factura['estado']) {
                case 'Solicitando': $estado_class = 'estado-solicitando'; break;
                case 'En Revisión': $estado_class = 'estado-revision'; break;
                case 'Rechazada': $estado_class = 'estado-rechazada'; break;
                case 'Pendiente de Pago': $estado_class = 'estado-pendiente'; break;
                case 'En Espera': $estado_class = 'estado-espera'; break;
                case 'En Proceso': $estado_class = 'estado-proceso'; break;
                case 'Terminada': $estado_class = 'estado-terminada'; break;
                case 'Entregada': $estado_class = 'estado-entregada'; break;
            }
        ?>
        <div class="order-card">
            <div class="order-header">
                <div class="order-info">
                    <h3>Pedido #<?php echo $factura['id']; ?></h3>
                    <p>Fecha de solicitud: <?php echo date('d/m/Y', strtotime($factura['fecha_solicitud'])); ?></p>
                    <?php if ($factura['fecha_entrega']): ?>
                    <p>Fecha de entrega solicitada: <?php echo date('d/m/Y', strtotime($factura['fecha_entrega'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="order-status <?php echo $estado_class; ?>">
                    <span><?php echo $factura['estado']; ?></span>
                </div>
            </div>
            
            <div class="order-details">
                <h4>Detalles del Pedido</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Producto/Servicio</th>
                            <th>Detalles</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Descuento</th>
                            <th>Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td>
                                <?php 
                                if ($pedido['servicio_id']) {
                                    echo htmlspecialchars($pedido['servicio']);
                                } else {
                                    echo htmlspecialchars($pedido['producto_nombre']);
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($pedido['detalles']); ?></td>
                            <td>$<?php echo number_format($pedido['precio'], 2); ?></td>
                            <td><?php echo $pedido['copias']; ?></td>
                            <td>$<?php echo number_format($pedido['descuento'], 2); ?></td>
                            <td>$<?php echo number_format($pedido['importe'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold;">Total:</td>
                            <td style="font-weight: bold;">$<?php echo number_format($factura['importe_total'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold;">Pagado:</td>
                            <td style="font-weight: bold;">$<?php echo number_format($factura['pagado'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php if ($factura['detalles']): ?>
                <div class="order-admin-notes">
                    <h4>Notas del Administrador</h4>
                    <p><?php echo nl2br(htmlspecialchars($factura['detalles'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($factura['estado'] == 'Solicitando'): ?>
                <div class="order-actions">
                    <button class="btn" onclick="cerrarPedido(<?php echo $factura['id']; ?>)">Cerrar y Solicitar Pedido</button>
                    <button class="btn btn-secondary" onclick="editarPedido(<?php echo $factura['id']; ?>)">Seguir Editando</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function cerrarPedido(facturaId) {
    if (confirm('¿Estás seguro de que quieres cerrar y solicitar este pedido? No podrás editarlo después.')) {
        // Aquí iría la llamada AJAX para cambiar el estado a "En Revisión"
        fetch('ajax/cerrar_pedido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ factura_id: facturaId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pedido enviado correctamente. Estará en estado "En Revisión".');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error al procesar la solicitud');
            console.error('Error:', error);
        });
    }
}

function editarPedido(facturaId) {
    // Redirigir a la página de edición de pedidos (no implementada en este ejemplo)
    alert('Funcionalidad de edición no implementada en este ejemplo');
}
</script>

<style>
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--light-color);
}

.order-info h3 {
    margin-bottom: 5px;
    color: var(--primary-dark);
}

.order-status {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    color: white;
}

.estado-solicitando { background-color: var(--primary-color); }
.estado-revision { background-color: var(--warning-color); }
.estado-rechazada { background-color: var(--error-color); }
.estado-pendiente { background-color: #ecc94b; }
.estado-espera { background-color: #4299e1; }
.estado-proceso { background-color: #48bb78; }
.estado-terminada { background-color: #38a169; }
.estado-entregada { background-color: #2f855a; }

.order-details {
    padding: 20px;
}

.order-details table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.order-details th,
.order-details td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid var(--gray-light);
}

.order-details th {
    background-color: var(--light-color);
    font-weight: bold;
}

.order-admin-notes {
    background-color: #f7fafc;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.order-admin-notes h4 {
    margin-bottom: 10px;
    color: var(--primary-dark);
}

.order-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .order-details {
        overflow-x: auto;
    }
    
    .order-details table {
        min-width: 600px;
    }
}
</style>

<?php
include 'includes/footer.php';
?>