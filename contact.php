<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$page_title = "Contacto - " . APP_NAME;

// Procesar formulario de contacto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contact'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $mensaje = trim($_POST['mensaje']);
    
    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        $_SESSION['error'] = "Por favor complete todos los campos obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Por favor ingrese un email válido.";
    } else {
        // Aquí iría el código para enviar el email o guardar en base de datos
        $_SESSION['success'] = "¡Gracias por contactarnos! Te responderemos a la brevedad.";
        
        // Limpiar formulario
        $_POST = array();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <h1>Contacto</h1>
    
    <div class="contact-container">
        <div class="contact-info">
            <h2>Información de Contacto</h2>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3>Dirección</h3>
                    <p>Av. Principal 123, Ciudad</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h3>Teléfono</h3>
                    <p>+1 234 567 8900</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3>Email</h3>
                    <p>info@digitalprintfiesta.com</p>
                </div>
            </div>
            
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h3>Horario de Atención</h3>
                    <p>Lunes a Viernes: 9:00 - 18:00</p>
                    <p>Sábados: 9:00 - 14:00</p>
                </div>
            </div>
        </div>
        
        <div class="contact-form">
            <h2>Envíanos un Mensaje</h2>
            <form method="POST">
                <input type="hidden" name="contact" value="1">
                
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" 
                           value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mensaje">Mensaje *</label>
                    <textarea id="mensaje" name="mensaje" rows="5" required><?php echo isset($_POST['mensaje']) ? htmlspecialchars($_POST['mensaje']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-block">Enviar Mensaje</button>
            </form>
        </div>
    </div>
</div>

<style>
.contact-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-top: 30px;
}

.contact-info {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 20px;
}

.contact-item i {
    font-size: 20px;
    color: var(--primary-color);
    margin-top: 5px;
}

.contact-item h3 {
    margin-bottom: 5px;
    color: var(--primary-dark);
}

.contact-form {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .contact-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
include 'includes/footer.php';
?>