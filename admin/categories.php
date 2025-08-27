<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Categorías - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Obtener todas las temáticas
$query_tematicas = "SELECT * FROM tematicas ORDER BY nombre";
$tematicas = $db->query($query_tematicas)->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario de agregar/editar categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $nombre = trim($_POST['nombre']);
        $tematica_id = $_POST['tematica_id'];
        
        if ($_POST['action'] == 'add') {
            // Agregar nueva categoría
            $query = "INSERT INTO categorias (nombre, tematica_id) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $nombre);
            $stmt->bindParam(2, $tematica_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Categoría agregada correctamente.";
            } else {
                $_SESSION['error'] = "Error al agregar la categoría.";
            }
        } elseif ($_POST['action'] == 'edit') {
            // Editar categoría existente
            $categoria_id = $_POST['categoria_id'];
            
            $query = "UPDATE categorias SET nombre = ?, tematica_id = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $nombre);
            $stmt->bindParam(2, $tematica_id);
            $stmt->bindParam(3, $categoria_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Categoría actualizada correctamente.";
            } else {
                $_SESSION['error'] = "Error al actualizar la categoría.";
            }
        }
    }
}

// Procesar eliminación
if (isset($_GET['delete'])) {
    $categoria_id = $_GET['delete'];
    
    // Verificar si hay productos asociados
    $query = "SELECT COUNT(*) as total FROM galeria WHERE categoria_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $categoria_id);
    $stmt->execute();
    $productos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($productos > 0) {
        $_SESSION['error'] = "No se puede eliminar la categoría porque tiene productos asociados.";
    } else {
        // Eliminar de la base de datos
        $query = "DELETE FROM categorias WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $categoria_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Categoría eliminada correctamente.";
        } else {
            $_SESSION['error'] = "Error al eliminar la categoría.";
        }
    }
    
    header("Location: categories.php");
    exit();
}

// Obtener todas las categorías con información de temática
$query = "SELECT c.*, t.nombre as tematica_nombre 
          FROM categorias c 
          LEFT JOIN tematicas t ON c.tematica_id = t.id 
          ORDER BY t.nombre, c.nombre";
$categorias = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Gestión de Categorías</h1>
    
    <div class="admin-form">
        <h2><?php echo isset($_GET['edit']) ? 'Editar Categoría' : 'Agregar Nueva Categoría'; ?></h2>
        
        <?php
        $editing = false;
        $categoria_edit = null;
        
        if (isset($_GET['edit'])) {
            $editing = true;
            $categoria_id = $_GET['edit'];
            
            $query = "SELECT * FROM categorias WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $categoria_id);
            $stmt->execute();
            $categoria_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $editing ? 'edit' : 'add'; ?>">
            <?php if ($editing): ?>
            <input type="hidden" name="categoria_id" value="<?php echo $categoria_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nombre">Nombre de la Categoría *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo $editing ? htmlspecialchars($categoria_edit['nombre']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="tematica_id">Temática *</label>
                <select id="tematica_id" name="tematica_id" required>
                    <option value="">Seleccionar Temática</option>
                    <?php foreach ($tematicas as $tema): ?>
                    <option value="<?php echo $tema['id']; ?>" 
                        <?php echo ($editing && $categoria_edit['tematica_id'] == $tema['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tema['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-block">
                <?php echo $editing ? 'Actualizar Categoría' : 'Agregar Categoría'; ?>
            </button>
            
            <?php if ($editing): ?>
            <a href="categories.php" class="btn btn-secondary btn-block">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Categorías Existentes</h2>
        
        <?php if (empty($categorias)): ?>
        <p>No hay categorías registradas.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Temática</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td><?php echo $categoria['id']; ?></td>
                        <td><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($categoria['tematica_nombre']); ?></td>
                        <td>
                            <a href="categories.php?edit=<?php echo $categoria['id']; ?>" class="btn">Editar</a>
                            <a href="categories.php?delete=<?php echo $categoria['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar esta categoría?');">Eliminar</a>
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

.admin-form {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

.btn-danger {
    background-color: var(--error-color);
    color: white;
}

.btn-danger:hover {
    background-color: #e53e3e;
}

.btn-block {
    display: block;
    width: 100%;
    margin-bottom: 10px;
}
</style>

<?php
include '../includes/footer.php';
?>