<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$page_title = "Mi Perfil - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user = getCurrentUser();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nombre_apellidos = trim($_POST['nombre_apellidos']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $referencia_direccion = trim($_POST['referencia_direccion']);
    
    $query = "UPDATE usuarios SET nombre_apellidos = ?, telefono = ?, direccion = ?, referencia_direccion = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $nombre_apellidos);
    $stmt->bindParam(2, $telefono);
    $stmt->bindParam(3, $direccion);
    $stmt->bindParam(4, $referencia_direccion);
    $stmt->bindParam(5, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $nombre_apellidos;
        $_SESSION['success'] = "Perfil actualizado correctamente.";
    } else {
        $_SESSION['error'] = "Error al actualizar el perfil.";
    }
    
    header("Location: profile.php");
    exit();
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Las nuevas contraseñas no coinciden.";
    } else {
        // Verificar contraseña actual
        $query = "SELECT password FROM usuarios WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE usuarios SET password = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $hashed_password);
            $stmt->bindParam(2, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Contraseña cambiada correctamente.";
            } else {
                $_SESSION['error'] = "Error al cambiar la contraseña.";
            }
        } else {
            $_SESSION['error'] = "La contraseña actual es incorrecta.";
        }
    }
    
    header("Location: profile.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container">
    <h1>Mi Perfil</h1>
    
    <div class="profile-container">
        <div class="profile-form">
            <h2>Información Personal</h2>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label for="nombre_apellidos">Nombre y Apellidos *</label>
                    <input type="text" id="nombre_apellidos" name="nombre_apellidos" required 
                           value="<?php echo htmlspecialchars($user['nombre_apellidos']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="text" id="telefono" name="telefono" required 
                           value="<?php echo htmlspecialchars($user['telefono']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="direccion">Dirección *</label>
                    <textarea id="direccion" name="direccion" rows="3" required><?php echo htmlspecialchars($user['direccion']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="referencia_direccion">Referencia de Dirección (opcional)</label>
                    <textarea id="referencia_direccion" name="referencia_direccion" rows="2"><?php echo htmlspecialchars($user['referencia_direccion']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-block">Actualizar Perfil</button>
            </form>
        </div>
        
        <div class="password-form">
            <h2>Cambiar Contraseña</h2>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label for="current_password">Contraseña Actual *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña *</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-block">Cambiar Contraseña</button>
            </form>
        </div>
    </div>
</div>

<style>
.profile-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.profile-form, .password-form {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
include 'includes/footer.php';
?>