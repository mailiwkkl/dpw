<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Temáticas - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Procesar formulario de agregar/editar temática
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $nombre = trim($_POST['nombre']);
        
        try {
            if ($_POST['action'] == 'add') {
                // Agregar nueva temática
                if (!empty($_FILES['imagen']['name'])) {
                    $imagen_nombre = processAndSaveImage($_FILES['imagen'], IMAGE_PATH, 'T', 26, 26);
                    
                    if ($imagen_nombre) {
                        $query = "INSERT INTO tematicas (nombre, imagen) VALUES (?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $nombre);
                        $stmt->bindParam(2, $imagen_nombre);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Temática agregada correctamente.";
                        } else {
                            $_SESSION['error'] = "Error al agregar la temática.";
                        }
                    }
                } else {
                    $_SESSION['error'] = "Debe seleccionar una imagen.";
                }
            } elseif ($_POST['action'] == 'edit') {
                // Editar temática existente
                $tematica_id = $_POST['tematica_id'];
                
                if (!empty($_FILES['imagen']['name'])) {
                    $imagen_nombre = processAndSaveImage($_FILES['imagen'], IMAGE_PATH, 'T', 26, 26);
                    
                    if ($imagen_nombre) {
                        $query = "UPDATE tematicas SET nombre = ?, imagen = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $nombre);
                        $stmt->bindParam(2, $imagen_nombre);
                        $stmt->bindParam(3, $tematica_id);
                    } else {
                        $_SESSION['error'] = "Error al procesar la imagen.";
                        header("Location: themes.php");
                        exit();
                    }
                } else {
                    $query = "UPDATE tematicas SET nombre = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $nombre);
                    $stmt->bindParam(2, $tematica_id);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Temática actualizada correctamente.";
                } else {
                    $_SESSION['error'] = "Error al actualizar la temática.";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}


// Procesar eliminación
if (isset($_GET['delete'])) {
    $tematica_id = $_GET['delete'];
    
    // Verificar si hay categorías asociadas
    $query = "SELECT COUNT(*) as total FROM categorias WHERE tematica_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $tematica_id);
    $stmt->execute();
    $categorias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($categorias > 0) {
        $_SESSION['error'] = "No se puede eliminar la temática porque tiene categorías asociadas.";
    } else {
        // Obtener nombre de la imagen para eliminarla
        $query = "SELECT imagen FROM tematicas WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $tematica_id);
        $stmt->execute();
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC)['imagen'];
        
        // Eliminar de la base de datos
        $query = "DELETE FROM tematicas WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $tematica_id);
        
        if ($stmt->execute()) {
            // Eliminar archivo de imagen
            if ($imagen && file_exists(IMAGE_PATH . $imagen)) {
                unlink(IMAGE_PATH . $imagen);
            }
            
            $_SESSION['success'] = "Temática eliminada correctamente.";
        } else {
            $_SESSION['error'] = "Error al eliminar la temática.";
        }
    }
    
    header("Location: themes.php");
    exit();
}

// Obtener todas las temáticas
$query = "SELECT * FROM tematicas ORDER BY nombre";
$tematicas = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Gestión de Temáticas</h1>
    
    <div class="admin-form">
        <h2><?php echo isset($_GET['edit']) ? 'Editar Temática' : 'Agregar Nueva Temática'; ?></h2>
        
        <?php
        $editing = false;
        $tematica_edit = null;
        
        if (isset($_GET['edit'])) {
            $editing = true;
            $tematica_id = $_GET['edit'];
            
            $query = "SELECT * FROM tematicas WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $tematica_id);
            $stmt->execute();
            $tematica_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $editing ? 'edit' : 'add'; ?>">
            <?php if ($editing): ?>
            <input type="hidden" name="tematica_id" value="<?php echo $tematica_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nombre">Nombre de la Temática *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo $editing ? htmlspecialchars($tematica_edit['nombre']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="imagen">Imagen <?php echo $editing ? '(Dejar vacío para mantener la actual)' : '*'; ?></label>
                <input type="file" id="imagen" name="imagen" accept="image/*" <?php echo $editing ? '' : 'required'; ?>>
                <small>La imagen se redimensionará a 26x26 px y se convertirá a formato WebP</small>
            </div>
            
            <?php if ($editing && $tematica_edit['imagen']): ?>
            <div class="form-group">
                <label>Imagen Actual:</label>
                <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $tematica_edit['imagen']; ?>" 
                     alt="<?php echo htmlspecialchars($tematica_edit['nombre']); ?>" 
                     style="width: 26px; height: 26px; object-fit: cover; border-radius: 50%;">
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-block">
                <?php echo $editing ? 'Actualizar Temática' : 'Agregar Temática'; ?>
            </button>
            
            <?php if ($editing): ?>
            <a href="themes.php" class="btn btn-secondary btn-block">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Temáticas Existentes</h2>
        
        <?php if (empty($tematicas)): ?>
        <p>No hay temáticas registradas.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tematicas as $tematica): ?>
                    <tr>
                        <td><?php echo $tematica['id']; ?></td>
                        <td>
                            <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $tematica['imagen']; ?>" 
                                 alt="<?php echo htmlspecialchars($tematica['nombre']); ?>" 
                                 style="width: 26px; height: 26px; object-fit: cover; border-radius: 50%;">
                        </td>
                        <td><?php echo htmlspecialchars($tematica['nombre']); ?></td>
                        <td>
                            <a href="themes.php?edit=<?php echo $tematica['id']; ?>" class="btn">Editar</a>
                            <a href="themes.php?delete=<?php echo $tematica['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar esta temática?');">Eliminar</a>
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