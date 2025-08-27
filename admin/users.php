<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Gestión de Usuarios - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'toggle_status':
                // Cambiar estado del usuario
                $query = "SELECT estado FROM usuarios WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $new_status = $user['estado'] == 'Activo' ? 'Desactivado' : 'Activo';
                
                $query = "UPDATE usuarios SET estado = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $new_status);
                $stmt->bindParam(2, $user_id);
                $stmt->execute();
                
                $_SESSION['success'] = "Estado del usuario actualizado correctamente.";
                break;
                
            case 'change_role':
                // Cambiar rol del usuario
                $new_role = $_POST['role'];
                
                $query = "UPDATE usuarios SET rol = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $new_role);
                $stmt->bindParam(2, $user_id);
                $stmt->execute();
                
                $_SESSION['success'] = "Rol del usuario actualizado correctamente.";
                break;
                
            case 'delete_user':
                // Eliminar usuario (solo si no tiene facturas asociadas)
                $query = "SELECT COUNT(*) as total FROM facturas WHERE cliente_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $user_id);
                $stmt->execute();
                $facturas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                if ($facturas > 0) {
                    $_SESSION['error'] = "No se puede eliminar el usuario porque tiene facturas asociadas.";
                } else {
                    $query = "DELETE FROM usuarios WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $user_id);
                    $stmt->execute();
                    
                    $_SESSION['success'] = "Usuario eliminado correctamente.";
                }
                break;
        }
    }
}

// Procesar creación de usuario desde admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $nombre_apellidos = trim($_POST['nombre_apellidos']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $referencia_direccion = trim($_POST['referencia_direccion']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    
    // Validaciones básicas
    if (empty($nombre_apellidos) || empty($telefono) || empty($direccion)) {
        $_SESSION['error'] = "Todos los campos obligatorios deben ser completados.";
        header("Location: users.php");
        exit();
    }
    
    $userData = [
        'nombre_apellidos' => $nombre_apellidos,
        'telefono' => $telefono,
        'direccion' => $direccion,
        'referencia_direccion' => $referencia_direccion,
        'rol' => $rol,
        'estado' => $estado
    ];
    
    $result = createUserFromAdmin($userData);
    
    if ($result['success']) {
        $_SESSION['success'] = "Usuario creado correctamente.";
        
        // Si es cliente, agregar notificación
        if ($rol == 'cliente') {
            addNotification($result['user_id'], "Bienvenido a Digital Print Fiesta. Su cuenta ha sido creada por el administrador. Para activarla, inicie sesión con su teléfono y deje la contraseña en blanco para configurarla.");
        }
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header("Location: users.php");
    exit();
}

// ... resto del código ...

// Formulario para agregar usuario
?>
<div class="admin-form">
    <h2>Agregar Nuevo Usuario</h2>
    <form method="POST">
        <input type="hidden" name="create_user" value="1">
        
        <div class="form-group">
            <label for="nombre_apellidos">Nombre y Apellidos *</label>
            <input type="text" id="nombre_apellidos" name="nombre_apellidos" required>
        </div>
        
        <div class="form-group">
            <label for="telefono">Teléfono *</label>
            <input type="text" id="telefono" name="telefono" required>
        </div>
        
        <div class="form-group">
            <label for="direccion">Dirección *</label>
            <textarea id="direccion" name="direccion" rows="3" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="referencia_direccion">Referencia de Dirección (opcional)</label>
            <textarea id="referencia_direccion" name="referencia_direccion" rows="2"></textarea>
        </div>
        
        <div class="form-group">
            <label for="rol">Rol *</label>
            <select id="rol" name="rol" required>
                <option value="cliente">Cliente</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="estado">Estado *</label>
            <select id="estado" name="estado" required>
                <option value="Activo">Activo</option>
                <option value="Desactivado">Desactivado</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-block">Crear Usuario</button>
    </form>
</div>
<?php


// Obtener todos los usuarios
$query = "SELECT * FROM usuarios ORDER BY fecha_registro DESC";
$users = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Gestión de Usuarios</h1>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre y Apellidos</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['nombre_apellidos']); ?></td>
                    <td><?php echo htmlspecialchars($user['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($user['direccion']); ?></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="change_role">
                            <select name="role" onchange="this.form.submit()">
                                <option value="cliente" <?php echo $user['rol'] == 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                                <option value="admin" <?php echo $user['rol'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <button type="submit" class="status-btn <?php echo $user['estado'] == 'Activo' ? 'active' : 'inactive'; ?>">
                                <?php echo $user['estado']; ?>
                            </button>
                        </form>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este usuario?');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="delete_user">
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.admin-container {
    padding: 20px;
}

.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--white);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

.inline-form {
    display: inline;
}

.status-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
}

.status-btn.active {
    background-color: var(--success-color);
    color: white;
}

.status-btn.inactive {
    background-color: var(--error-color);
    color: white;
}

.btn-danger {
    background-color: var(--error-color);
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.btn-danger:hover {
    background-color: #e53e3e;
}

select {
    padding: 6px;
    border: 1px solid var(--gray-light);
    border-radius: 6px;
}
</style>

<?php
include '../includes/footer.php';
?>