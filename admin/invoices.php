<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Facturas - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $factura_id = $_POST['factura_id'];
        $nuevo_estado = $_POST['estado'];
        $detalles = trim($_POST['detalles']);
        
        // Obtener información actual de la factura
        $query = "SELECT f.*, u.telefono, u.nombre_apellidos 
                 FROM facturas f 
                 JOIN usuarios u ON f.cliente_id = u.id 
                 WHERE f.id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $factura_id);
        $stmt->execute();
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Actualizar factura
        $query = "UPDATE facturas SET estado = ?, detalles = ?, fecha_actualizacion = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $nuevo_estado);
        $stmt->bindParam(2, $detalles);
        $stmt->bindParam(3, $factura_id);
        
        if ($stmt->execute()) {
            // Enviar notificación al cliente
            $mensaje = "El estado de tu pedido #{$factura_id} ha cambiado a: {$nuevo_estado}";
            if (!empty($detalles)) {
                $mensaje .= ". Detalles: {$detalles}";
            }
            
            addNotification($factura['cliente_id'], $mensaje, "orders.php");
            
            $_SESSION['success'] = "Estado actualizado correctamente.";
        } else {
            $_SESSION['error'] = "Error al actualizar el estado.";
        }
    }
}

// Obtener parámetros de filtro
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';

// Construir consulta con filtros
$query = "SELECT f.*, u.nombre_apellidos as cliente_nombre 
          FROM facturas f 
          JOIN usuarios u ON f.cliente_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($estado)) {
    $query .= " AND f.estado = ?";
    $params[] = $estado;
}

if (!empty($cliente)) {
    $query .= " AND (u.nombre_apellidos LIKE ? OR u.telefono LIKE ?)";
    $search_term = "%$cliente%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY f.fecha_solicitud DESC";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Gestión de Facturas</h1>
    
    <div class="filters">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="estado">Filtrar por Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="Solicitando" <?php echo $estado == 'Solicitando' ? 'selected' : ''; ?>>Solicitando</option>
                    <option value="En Revisión" <?php echo $estado == 'En Revisión' ? 'selected' : ''; ?>>En Revisión</option>
                    <option value="Rechazada" <?php echo $estado == 'Rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                    <option value="Pendiente de Pago" <?php echo $estado == 'Pendiente de Pago' ? 'selected' : ''; ?>>Pendiente de Pago</option>
                    <option value="En Espera" <?php echo $estado == 'En Espera' ? 'selected' : ''; ?>>En Espera</option>
                    <option value="En Proceso" <?php echo $estado == 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="Terminada" <?php echo $estado == 'Terminada' ? 'selected' : ''; ?>>Terminada</option>
                    <option value="Entregada" <?php echo $estado == 'Entregada' ? 'selected' : ''; ?>>Entregada</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cliente">Buscar Cliente</label>
                <input type="text" id="cliente" name="cliente" placeholder="Nombre o teléfono" value="<?php echo htmlspecialchars($cliente); ?>">
            </div>
            
            <button type="submit" class="btn">Filtrar</button>
            <a href="invoices.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Facturas</h2>
        
        <?php if (empty($facturas)): ?>
        <p>No hay facturas con los filtros seleccionados.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha Solicitud</th>
                        <th>Fecha Entrega</th>
                        <th>Importe Total</th>
                        <th>Pagado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $factura): 
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
                    <tr>
                        <td><?php echo $factura['id']; ?></td>
                        <td><?php echo htmlspecialchars($factura['cliente_nombre']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($factura['fecha_solicitud'])); ?></td>
                        <td><?php echo $factura['fecha_entrega'] ? date('d/m/Y', strtotime($factura['fecha_entrega'])) : '--'; ?></td>
                        <td>$<?php echo number_format($factura['importe_total'], 2); ?></td>
                        <td>$<?php echo number_format($factura['pagado'], 2); ?></td>
                        <td>
                            <span class="status-badge <?php echo $estado_class; ?>">
                                <?php echo $factura['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="invoice_detail.php?id=<?php echo $factura['id']; ?>" class="btn">Ver Detalle</a>
                            <button class="btn" onclick="openStatusModal(<?php echo $factura['id']; ?>, '<?php echo $factura['estado']; ?>', `<?php echo htmlspecialchars($factura['detalles']); ?>`)">Cambiar Estado</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeStatusModal()">&times;</span>
        <h2>Cambiar Estado de Factura</h2>
        <form method="POST">
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
.filters {
    background: var(--white);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
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
</style>

<?php
include '../includes/footer.php';
?>