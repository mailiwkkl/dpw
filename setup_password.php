<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$page_title = "Configurar Contraseña - " . APP_NAME;

// Verificar si hay un usuario para configurar contraseña
if (!isset($_SESSION['setup_user'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario
$query = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $_SESSION['setup_user']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    unset($_SESSION['setup_user']);
    header("Location: login.php");
    exit();
}

// Procesar el formulario de configuración de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password)) {
        $_SESSION['error'] = "La contraseña no puede estar vacía.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
    } else {
        // Actualizar contraseña y activar usuario
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "UPDATE usuarios SET password = ?, estado = 'Activo' WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $hashed_password);
        $stmt->bindParam(2, $_SESSION['setup_user']);
        
        if ($stmt->execute()) {
            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['user_name'] = $user['nombre_apellidos'];
            
            unset($_SESSION['setup_user']);
            
            $_SESSION['success'] = "Contraseña configurada correctamente. Bienvenido!";
            
            // Redirigir según el rol
            if ($user['rol'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Error al configurar la contraseña. Intente nuevamente.";
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Configurar Contraseña</h2>
    <p>Hola <strong><?php echo htmlspecialchars($user['nombre_apellidos']); ?></strong>, es la primera vez que inicias sesión. Por favor, establece una contraseña para tu cuenta.</p>
    
    <form action="" method="POST">
        <input type="hidden" name="setup_password" value="1">
        
        <div class="form-group">
            <label for="password">Nueva Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn btn-block">Establecer Contraseña</button>
    </form>
</div>

<?php
include 'includes/footer.php';
?>