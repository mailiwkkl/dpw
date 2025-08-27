<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Galería - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Obtener temáticas
$query_tematicas = "SELECT * FROM tematicas ORDER BY nombre";
$tematicas = $db->query($query_tematicas)->fetchAll(PDO::FETCH_ASSOC);

// Obtener servicios (para usar como categorías)
$servicios = getAllServices();

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $nombre = trim($_POST['nombre']);
        $tematica_id = $_POST['tematica_id'];
        $servicio_id = $_POST['servicio_id'];
        $precio = $_POST['precio'];
        
        try {
            if ($_POST['action'] == 'add') {
                // Agregar nuevo producto
                if (!empty($_FILES['imagen']['name'])) {
                    $imagen_nombre = processAndSaveImage($_FILES['imagen'], UPLOAD_PATH, 'G');
                    
                    if ($imagen_nombre) {
                        $query = "INSERT INTO galeria (nombre, tematica_id, servicio_id, imagen, precio) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(1, $nombre);
                        $stmt->bindParam(2, $tematica_id);
                        $stmt->bindParam(3, $servicio_id);
                        $stmt->bindParam(4, $imagen_nombre);
                        $stmt->bindParam(5, $precio);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Producto agregado correctamente.";
                        } else {
                            $_SESSION['error'] = "Error al agregar el producto.";
                        }
                    }
                } else {
                    $_SESSION['error'] = "Debe seleccionar una imagen.";
                }
            } elseif ($_POST['action'] == 'edit') {
                // Editar producto existente
                $producto_id = $_POST['producto_id'];
                
                if (!empty($_FILES['imagen']['name'])) {
                    $imagen_nombre = processAndSaveImage($_FILES['imagen'], UPLOAD_PATH, 'G');
                    
                    if (!$imagen_nombre) {
                        $_SESSION['error'] = "Error al procesar la imagen.";
                        header("Location: gallery.php");
                        exit();
                    }
                    
                    // Eliminar imagen anterior
                    $query_old = "SELECT imagen FROM galeria WHERE id = ?";
                    $stmt_old = $db->prepare($query_old);
                    $stmt_old->bindParam(1, $producto_id);
                    $stmt_old->execute();
                    $old_image = $stmt_old->fetch(PDO::FETCH_ASSOC)['imagen'];
                    
                    if ($old_image && file_exists(UPLOAD_PATH . $old_image)) {
                        unlink(UPLOAD_PATH . $old_image);
                    }
                    
                    $query = "UPDATE galeria SET nombre = ?, tematica_id = ?, servicio_id = ?, imagen = ?, precio = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $nombre);
                    $stmt->bindParam(2, $tematica_id);
                    $stmt->bindParam(3, $servicio_id);
                    $stmt->bindParam(4, $imagen_nombre);
                    $stmt->bindParam(5, $precio);
                    $stmt->bindParam(6, $producto_id);
                } else {
                    $query = "UPDATE galeria SET nombre = ?, tematica_id = ?, servicio_id = ?, precio = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $nombre);
                    $stmt->bindParam(2, $tematica_id);
                    $stmt->bindParam(3, $servicio_id);
                    $stmt->bindParam(4, $precio);
                    $stmt->bindParam(5, $producto_id);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Producto actualizado correctamente.";
                } else {
                    $_SESSION['error'] = "Error al actualizar el producto.";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Procesar eliminación
if (isset($_GET['delete'])) {
    $producto_id = $_GET['delete'];
    
    try {
        // Obtener nombre de la imagen para eliminarla
        $query = "SELECT imagen FROM galeria WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $producto_id);
        $stmt->execute();
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC)['imagen'];
        
        // Eliminar de la base de datos
        $query = "DELETE FROM galeria WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $producto_id);
        
        if ($stmt->execute()) {
            // Eliminar archivo de imagen
            if ($imagen && file_exists(UPLOAD_PATH . $imagen)) {
                unlink(UPLOAD_PATH . $imagen);
            }
            
            $_SESSION['success'] = "Producto eliminado correctamente.";
        } else {
            $_SESSION['error'] = "Error al eliminar el producto.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: gallery.php");
    exit();
}

// Obtener todos los productos
$query = "SELECT g.*, t.nombre as tematica_nombre, s.servicio as servicio_nombre 
          FROM galeria g 
          LEFT JOIN tematicas t ON g.tematica_id = t.id 
          LEFT JOIN servicios s ON g.servicio_id = s.id 
          ORDER BY g.id DESC";
$productos = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Establecer variable para el header
$is_admin = true;
include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Gestión de Galería</h1>
    
    <div class="admin-form">
        <h2><?php echo isset($_GET['edit']) ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h2>
        
        <?php
        $editing = false;
        $producto_edit = null;
        
        if (isset($_GET['edit'])) {
            $editing = true;
            $producto_id = $_GET['edit'];
            
            $query = "SELECT * FROM galeria WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $producto_id);
            $stmt->execute();
            $producto_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $editing ? 'edit' : 'add'; ?>">
            <?php if ($editing): ?>
            <input type="hidden" name="producto_id" value="<?php echo $producto_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nombre">Nombre del Producto *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo $editing ? htmlspecialchars($producto_edit['nombre']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="tematica_id">Temática *</label>
                <select id="tematica_id" name="tematica_id" required>
                    <option value="">Seleccionar Temática</option>
                    <?php foreach ($tematicas as $tema): ?>
                    <option value="<?php echo $tema['id']; ?>" 
                        <?php echo ($editing && $producto_edit['tematica_id'] == $tema['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tema['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="servicio_id">Servicio (Categoría) *</label>
                <select id="servicio_id" name="servicio_id" required>
                    <option value="">Seleccionar Servicio</option>
                    <?php foreach ($servicios as $serv): ?>
                    <option value="<?php echo $serv['id']; ?>" 
                        <?php echo ($editing && $producto_edit['servicio_id'] == $serv['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($serv['servicio']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="precio">Precio *</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                       value="<?php echo $editing ? $producto_edit['precio'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="imagen">Imagen <?php echo $editing ? '(Dejar vacío para mantener la actual)' : '*'; ?></label>
                <input type="file" id="imagen" name="imagen" accept="image/*" <?php echo $editing ? '' : 'required'; ?>>
                <small>Formatos aceptados: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB</small>
            </div>
            
            <?php if ($editing && $producto_edit['imagen']): ?>
            <div class="form-group">
                <label>Imagen Actual:</label>
                <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $producto_edit['imagen']; ?>" 
                     alt="<?php echo htmlspecialchars($producto_edit['nombre']); ?>" 
                     style="max-width: 200px; height: auto; border-radius: 8px; border: 2px solid var(--gray-light);">
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary btn-block">
                <?php echo $editing ? 'Actualizar Producto' : 'Agregar Producto'; ?>
            </button>
            
            <?php if ($editing): ?>
            <a href="gallery.php" class="btn btn-secondary btn-block">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="admin-list">
        <h2>Productos en Galería</h2>
        
        <?php if (empty($productos)): ?>
        <div class="empty-state">
            <i class="fas fa-images"></i>
            <h3>No hay productos en la galería</h3>
            <p>Comienza agregando tu primer producto.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Temática</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo $producto['id']; ?></td>
                        <td>
                            <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $producto['imagen']; ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                        </td>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($producto['tematica_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($producto['servicio_nombre']); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                        <td>
                            <a href="gallery.php?edit=<?php echo $producto['id']; ?>" class="btn btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="gallery.php?delete=<?php echo $producto['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
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