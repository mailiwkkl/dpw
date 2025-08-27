<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: invoices.php");
    exit();
}

$factura_id = $_GET['id'];
$page_title = "Detalle de Factura #$factura_id - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Obtener información de la factura
$query = "SELECT f.*, u.nombre_apellidos, u.telefono, u.direccion, u.referencia_direccion 
          FROM facturas f 
          JOIN usuarios u ON f.cliente_id = u.id 
          WHERE f.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $factura_id);
$stmt->execute();
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
    $_SESSION['error'] = "Factura no encontrada.";
    header("Location: invoices.php");
    exit();
}

// Obtener pedidos de esta factura
$query = "SELECT p.*, s.servicio, g.nombre as producto_nombre, g.imagen as producto_imagen 
          FROM pedidos p 
          LEFT JOIN servicios s ON p.servicio_id = s.id 
          LEFT JOIN galeria g ON p.galeria_id = g.id 
          WHERE p.factura_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $factura_id);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Detalle de Factura #<?php echo $factura_id; ?></h1>
    
    <div class="invoice-detail">
        <div class="invoice-header">
            <div class="invoice-info">
                <h2>Información de la Factura</h2>
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($factura['nombre_apellidos']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($factura['telefono']); ?></p>
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($factura['direccion']); ?></p>
                <?php if ($factura['referencia_direccion']): ?>
                <p><strong>Referencia:</strong> <?php echo htmlspecialchars($factura['referencia_direccion']); ?></p>
                <?php endif; ?>
                <p><strong>Fecha de Solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($factura['fecha_solicitud'])); ?></p>
                <?php if ($factura['fecha_entrega']): ?>
                <p><strong>Fecha de Entrega Solicitada:</strong> <?php echo date('d/m/Y', strtotime($factura['fecha_entrega'])); ?></p>
                <?php endif; ?>
                
                <?php
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
                <p><strong>Estado:</strong> <span class="status-badge <?php echo $estado_class; ?>"><?php echo $factura['estado']; ?></span></p>
                
                <p><strong>Importe Total:</strong> $<?php echo number_format($factura['importe_total'], 2); ?></p>
                <p><strong>Pagado:</strong> $<?php echo number_format($factura['pagado'], 2); ?></p>
            </div>
            
            <div class="invoice-actions">
                <a href="invoices.php" class="btn btn-secondary">Volver a Facturas</a>
                <button class="btn" onclick="openStatusModal(<?php echo $factura_id; ?>, '<?php echo $factura['estado']; ?>', `<?php echo htmlspecialchars($factura['detalles']); ?>`)">Cambiar Estado</button>
            </div>
        </div>
        
        <?php if ($factura['detalles']): ?>
        <div class="invoice-notes">
            <h3>Notas del Administrador</h3>
            <p><?php echo nl2br(htmlspecialchars($factura['detalles'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="invoice-items">
            <h3>Items del Pedido</h3>
            
            <?php if (empty($pedidos)): ?>
            <p>No hay items en este pedido.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Producto/Servicio</th>
                            <th>Detalles</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad</th>
                            <th>Descuento</th>
                            <th>Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td>
                                <?php if ($pedido['servicio_id']): ?>
                                <?php echo htmlspecialchars($pedido['servicio']); ?>
                                <?php else: ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if ($pedido['producto_imagen']): ?>
                                    <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $pedido['producto_imagen']; ?>" 
                                         alt="<?php echo htmlspecialchars($pedido['producto_nombre']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($pedido['producto_nombre']); ?>
                                </div>
                                <?php endif; ?>
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
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeStatusModal()">&times;</span>
        <h2>Cambiar Estado de Factura</h2>
        <form method="POST" action="invoices.php">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="factura_id" id="modal_factura_id">
            
            <div class="form-group">
                <label for="modal_estado">Nuevo Estado</label>
                <select id="modal_estado" name="estado" required>
                    <option value="Solicitando">Solicitando</option>
                    <option value="En Revisión">En Revisión</option>
                    <option value="Rechazada">Rechazada</option>
                    <option value="Pendiente de Pago">Pendiente de Pago</option>
                    <option value="En Espera">En Espera</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Terminada">Terminada</option>
                    <option value="Entregada">Entregada</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modal_detalles">Detalles (opcional)</label>
                <textarea id="modal_detalles" name="detalles" rows="4" placeholder="Información adicional para el cliente..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-block">Actualizar Estado</button>
        </form>
    </div>
</div>

<script>
function openStatusModal(facturaId, currentStatus, currentDetails) {
    document.getElementById('modal_factura_id').value = facturaId;
    document.getElementById('modal_estado').value = currentStatus;
    document.getElementById('modal_detalles').value = currentDetails || '';
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('statusModal');
    if (event.target === modal) {
        closeStatusModal();
    }
}
</script>

<style>
.invoice-detail {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 20px;
}

.invoice-info {
    flex: 1;
    min-width: 300px;
}

.invoice-actions {
    display: flex;
    gap: 10px;
}

.invoice-notes {
    background-color: #f7fafc;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.invoice-items {
    margin-top: 20px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    color: white;
}

.estado-solicitando { background-color: var(--primary-color); }
.estado-revision { background-color: var(--warning-color); }
.estado-rechazada { background-color: var(--error-color); }
.estado-pendiente { background-color: #ecc94b; color: var(--text-color); }
.estado-espera { background-color: #4299e1; }
.estado-proceso { background-color: #48bb78; }
.estado-terminada { background-color: #38a169; }
.estado-entregada { background-color: #2f855a; }

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: var(--white);
    margin: 10% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.modal .close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

@media (max-width: 768px) {
    .invoice-header {
        flex-direction: column;
    }
    
    .invoice-actions {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<?php
include '../includes/footer.php';
?>