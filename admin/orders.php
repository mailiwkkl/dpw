<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Pedidos - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

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
    <h1>Gestión de Pedidos</h1>
    
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
            <a href="orders.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Pedidos</h2>
        
        <?php if (empty($facturas)): ?>
        <p>No hay pedidos con los filtros seleccionados.</p>
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
                            <a href="invoices.php?factura=<?php echo $factura['id']; ?>" class="btn">Gestionar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-container {
    padding: 20px;
}

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

.admin-list {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid var(--gray-light);
}

.admin-table th {
    background-color: var(--primary-color);
    color: var(--white);
    font-weight: 600;
}

.admin-table tr:hover {
    background-color: #f8f9fa;
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

.btn {
    display: inline-block;
    padding: 8px 16px;
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    margin-right: 5px;
}

.btn:hover {
    background-color: var(--primary-light);
}

.btn-secondary {
    background-color: var(--gray-light);
    color: var(--text-color);
}

.btn-secondary:hover {
    background-color: #d2d6dc;
}
</style>

<?php
include '../includes/footer.php';
?>