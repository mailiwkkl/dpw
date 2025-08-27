<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Servicios - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Procesar formulario de agregar/editar servicio
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $servicio = trim($_POST['servicio']);
        $precio = $_POST['precio'];
        
        if ($_POST['action'] == 'add') {
            // Agregar nuevo servicio
            $query = "INSERT INTO servicios (servicio, precio) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $servicio);
            $stmt->bindParam(2, $precio);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Servicio agregado correctamente.";
            } else {
                $_SESSION['error'] = "Error al agregar el servicio.";
            }
        } elseif ($_POST['action'] == 'edit') {
            // Editar servicio existente
            $servicio_id = $_POST['servicio_id'];
            
            $query = "UPDATE servicios SET servicio = ?, precio = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $servicio);
            $stmt->bindParam(2, $precio);
            $stmt->bindParam(3, $servicio_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Servicio actualizado correctamente.";
            } else {
                $_SESSION['error'] = "Error al actualizar el servicio.";
            }
        }
    }
}

// Procesar eliminación
if (isset($_GET['delete'])) {
    $servicio_id = $_GET['delete'];
    
    // Verificar si hay pedidos asociados
    $query = "SELECT COUNT(*) as total FROM pedidos WHERE servicio_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $servicio_id);
    $stmt->execute();
    $pedidos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($pedidos > 0) {
        $_SESSION['error'] = "No se puede eliminar el servicio porque tiene pedidos asociados.";
    } else {
        // Eliminar de la base de datos
        $query = "DELETE FROM servicios WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $servicio_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Servicio eliminado correctamente.";
        } else {
            $_SESSION['error'] = "Error al eliminar el servicio.";
        }
    }
    
    header("Location: services.php");
    exit();
}

// Obtener todos los servicios
$query = "SELECT * FROM servicios ORDER BY servicio";
$servicios = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Gestión de Servicios</h1>
    
    <div class="admin-form">
        <h2><?php echo isset($_GET['edit']) ? 'Editar Servicio' : 'Agregar Nuevo Servicio'; ?></h2>
        
        <?php
        $editing = false;
        $servicio_edit = null;
        
        if (isset($_GET['edit'])) {
            $editing = true;
            $servicio_id = $_GET['edit'];
            
            $query = "SELECT * FROM servicios WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $servicio_id);
            $stmt->execute();
            $servicio_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $editing ? 'edit' : 'add'; ?>">
            <?php if ($editing): ?>
            <input type="hidden" name="servicio_id" value="<?php echo $servicio_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="servicio">Nombre del Servicio *</label>
                <input type="text" id="servicio" name="servicio" required 
                       value="<?php echo $editing ? htmlspecialchars($servicio_edit['servicio']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="precio">Precio *</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                       value="<?php echo $editing ? $servicio_edit['precio'] : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-block">
                <?php echo $editing ? 'Actualizar Servicio' : 'Agregar Servicio'; ?>
            </button>
            
            <?php if ($editing): ?>
            <a href="services.php" class="btn btn-secondary btn-block">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Servicios Existentes</h2>
        
        <?php if (empty($servicios)): ?>
        <p>No hay servicios registrados.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios as $servicio): ?>
                    <tr>
                        <td><?php echo $servicio['id']; ?></td>
                        <td><?php echo htmlspecialchars($servicio['servicio']); ?></td>
                        <td>$<?php echo number_format($servicio['precio'], 2); ?></td>
                        <td>
                            <a href="services.php?edit=<?php echo $servicio['id']; ?>" class="btn">Editar</a>
                            <a href="services.php?delete=<?php echo $servicio['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este servicio?');">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
include '../includes/footer.php';
?>