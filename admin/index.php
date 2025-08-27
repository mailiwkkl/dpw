<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Verificar si es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$page_title = "Panel de Administración - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$query_users = "SELECT COUNT(*) as total FROM usuarios";
$total_users = $db->query($query_users)->fetch(PDO::FETCH_ASSOC)['total'];

$query_invoices = "SELECT COUNT(*) as total FROM facturas";
$total_invoices = $db->query($query_invoices)->fetch(PDO::FETCH_ASSOC)['total'];

$query_pending = "SELECT COUNT(*) as total FROM facturas WHERE estado = 'Pendiente de Pago'";
$total_pending = $db->query($query_pending)->fetch(PDO::FETCH_ASSOC)['total'];

$query_galeria = "SELECT COUNT(*) as total FROM galeria";
$total_galeria = $db->query($query_galeria)->fetch(PDO::FETCH_ASSOC)['total'];

include '../includes/header.php';
?>

<div class="admin-container">
    <h1>Panel de Administración</h1>
    
    <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_users; ?></h3>
                <p>Usuarios Registrados</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_invoices; ?></h3>
                <p>Facturas</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_pending; ?></h3>
                <p>Pendientes de Pago</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-images"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_galeria; ?></h3>
                <p>Productos en Galería</p>
            </div>
        </div>
    </div>
    
    <div class="admin-actions">
        <h2>Acciones Rápidas</h2>
        <div class="action-grid">
            <a href="users.php" class="action-card">
                <i class="fas fa-users-cog"></i>
                <h3>Gestión de Usuarios</h3>
                <p>Administrar usuarios y permisos</p>
            </a>
            
            <a href="gallery.php" class="action-card">
                <i class="fas fa-images"></i>
                <h3>Galería de Productos</h3>
                <p>Gestionar productos y categorías</p>
            </a>
            
            <a href="invoices.php" class="action-card">
                <i class="fas fa-file-invoice-dollar"></i>
                <h3>Facturas y Pedidos</h3>
                <p>Ver y gestionar pedidos</p>
            </a>
            
            <a href="themes.php" class="action-card">
                <i class="fas fa-palette"></i>
                <h3>Temáticas</h3>
                <p>Gestionar temáticas y categorías</p>
            </a>
            
            <a href="services.php" class="action-card">
                <i class="fas fa-concierge-bell"></i>
                <h3>Servicios</h3>
                <p>Gestionar servicios y precios</p>
            </a>
            
            <a href="../index.php" class="action-card">
                <i class="fas fa-store"></i>
                <h3>Ver Tienda</h3>
                <p>Ir a la vista de cliente</p>
            </a>
        </div>
    </div>
</div>

<style>
.admin-container {
    padding: 20px;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--white);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: var(--light-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--primary-color);
}

.stat-info h3 {
    font-size: 28px;
    margin-bottom: 5px;
    color: var(--primary-dark);
}

.stat-info p {
    color: var(--text-color);
    margin: 0;
}

.admin-actions h2 {
    margin-bottom: 20px;
    color: var(--primary-dark);
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.action-card {
    background: var(--white);
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: var(--text-color);
    transition: transform 0.3s, box-shadow 0.3s;
    display: block;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    color: var(--text-color);
}

.action-card i {
    font-size: 40px;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.action-card h3 {
    margin-bottom: 10px;
    color: var(--primary-dark);
}

.action-card p {
    color: var(--text-color);
    margin: 0;
}
</style>

<?php
include '../includes/footer.php';
?>