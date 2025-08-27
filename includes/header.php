<?php
// includes/header.php

// Incluir config primero para tener las funciones disponibles
require_once 'config.php';

if (!isset($page_title)) {
    $page_title = APP_NAME;
}

// Obtener número de notificaciones no leídas
$unread_count = 0;
if (isLoggedIn()) {
    $unread_count = getUnreadNotificationsCount($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <?php if (isset($is_admin) && $is_admin): ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php">Digital-Print-Fiesta</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="catalog.php">Catálogo</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="favorites.php">Favoritos</a></li>
                        <li><a href="orders.php">Mis Pedidos</a></li>
                        <li class="user-menu">
                            <a href="#">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul>
                                <li>
                                    <a href="notifications.php">
                                        <i class="fas fa-bell"></i> Notificaciones
                                        <?php if ($unread_count > 0): ?>
                                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li><a href="./profile.php"><i class="fas fa-user-circle"></i> Perfil</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><a href="index.php"><i class="fas fa-cog"></i> Panel Admin</a></li>
                                <?php endif; ?>
                                <li><a href="./logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Notificación en tiempo real (para AJAX) -->
        <div id="ajax-notification" class="alert" style="display: none;"></div>

<style>
.notification-badge {
    background-color: var(--error-color);
    color: white;
    border-radius: 50%;
    padding: 3px 7px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-left: 5px;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.user-menu {
    position: relative;
}

.user-menu ul {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--white);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    border-radius: 12px;
    padding: 10px;
    min-width: 220px;
    flex-direction: column;
    z-index: 1000;
    border: 1px solid var(--gray-light);
    margin-top: 10px;
}

.user-menu:hover ul {
    display: flex;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-menu ul li {
    margin: 5px 0;
    list-style: none;
}

.user-menu ul li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    border-radius: 8px;
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.3s ease;
}

.user-menu ul li a:hover {
    background-color: var(--light-color);
    color: var(--primary-color);
}

.alert {
    padding: 15px 20px;
    margin: 0 20px 20px;
    border-radius: 10px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.alert-error {
    background-color: #fed7d7;
    color: #c53030;
    border-left: 4px solid #c53030;
}

.alert-success {
    background-color: #c6f6d5;
    color: #2f855a;
    border-left: 4px solid #2f855a;
}

.alert i {
    font-size: 1.2em;
}

#ajax-notification {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 10000;
    max-width: 350px;
    animation: slideInRight 0.3s ease;
    cursor: pointer;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    header .container {
        flex-direction: column;
        gap: 15px;
        padding: 15px;
    }
    
    nav ul {
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    
    .user-menu ul {
        right: auto;
        left: 0;
        min-width: 200px;
    }
    
    #ajax-notification {
        left: 20px;
        right: 20px;
        max-width: none;
    }
    
    .alert {
        margin: 0 10px 15px;
        padding: 12px 15px;
    }
}

@media (max-width: 480px) {
    nav ul {
        gap: 8px;
    }
    
    nav ul li a {
        font-size: 0.9rem;
        padding: 8px 12px;
    }
    
    .user-menu ul {
        min-width: 180px;
    }
}
</style>

<script>

// Menú desplegable del usuario
document.addEventListener('DOMContentLoaded', function() {
    const userMenu = document.querySelector('.user-menu');
    const userMenuToggle = userMenu.querySelector('> a');
    const userDropdown = userMenu.querySelector('ul');
    
    let menuTimeout;
    
    userMenuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isVisible = userDropdown.style.display === 'flex';
        
        // Cerrar todos los menús primero
        document.querySelectorAll('.user-menu ul').forEach(menu => {
            menu.style.display = 'none';
        });
        
        // Abrir/cerrar este menú
        userDropdown.style.display = isVisible ? 'none' : 'flex';
        
        if (!isVisible) {
            userDropdown.style.animation = 'slideDown 0.3s ease';
        }
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!userMenu.contains(e.target)) {
            userDropdown.style.display = 'none';
        }
    });
    
    // Prevenir que se cierre al pasar el mouse sobre el menú
    userDropdown.addEventListener('mouseenter', function() {
        clearTimeout(menuTimeout);
    });
    
    userDropdown.addEventListener('mouseleave', function() {
        menuTimeout = setTimeout(() => {
            userDropdown.style.display = 'none';
        }, 300);
    });
    
    // Mantener abierto al hacer clic en los items del menú
    userDropdown.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.stopPropagation();
            // El menú se cerrará automáticamente al navegar
        });
    });
});

// Función para mostrar notificaciones AJAX
function showNotification(message, type = 'info') {
    const notification = document.getElementById('ajax-notification');
    notification.innerHTML = `
        <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    notification.className = `alert alert-${type}`;
    notification.style.display = 'flex';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

// Cerrar notificación al hacer clic
document.getElementById('ajax-notification').addEventListener('click', function() {
    this.style.display = 'none';
});

// Cerrar alerts automáticamente después de 8 segundos
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.id || alert.id !== 'ajax-notification') {
                alert.style.display = 'none';
            }
        });
    }, 8000);
});
</script>