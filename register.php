<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

$page_title = "Registrarse - " . APP_NAME;

// Si el usuario ya está logueado, redirigir a inicio
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Crear Cuenta</h2>
    <form action="" method="POST">
        <input type="hidden" name="register" value="1">
        
        <div class="form-group">
            <label for="nombre_apellidos">Nombre y Apellidos *</label>
            <input type="text" id="nombre_apellidos" name="nombre_apellidos" required>
        </div>
        
        <div class="form-group">
            <label for="telefono">Teléfono *</label>
            <input type="text" id="telefono" name="telefono" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña *</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña *</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div class="form-group">
            <label for="direccion">Dirección *</label>
            <textarea id="direccion" name="direccion" rows="3" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="referencia_direccion">Referencia de Dirección (opcional)</label>
            <textarea id="referencia_direccion" name="referencia_direccion" rows="2"></textarea>
        </div>
        
        <button type="submit" class="btn btn-block">Registrarse</button>
        
        <p style="text-align: center; margin-top: 20px;">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </form>
</div>

<?php
include 'includes/footer.php';
?>