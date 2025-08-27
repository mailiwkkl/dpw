<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

$page_title = "Iniciar Sesión - " . APP_NAME;

// Si el usuario ya está logueado, redirigir según su rol
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Iniciar Sesión</h2>
    <form action="" method="POST">
        <input type="hidden" name="login" value="1">
        
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password">
            <small>Si es su primer inicio, deje este campo vacío para configurar una contraseña</small>
        </div>
        
        <button type="submit" class="btn btn-block">Iniciar Sesión</button>
        
        <p style="text-align: center; margin-top: 20px;">
            ¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>
        </p>
    </form>
</div>

<?php
include 'includes/footer.php';
?>