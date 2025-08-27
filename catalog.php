<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$page_title = "Catálogo - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Obtener parámetros de filtro
$servicio_id = isset($_GET['servicio']) ? $_GET['servicio'] : '';
$tema_id = isset($_GET['tema']) ? $_GET['tema'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Obtener todos los servicios (para usar como categorías)
$servicios = getAllServices();

// Obtener todas las temáticas
$query_tematicas = "SELECT * FROM tematicas ORDER BY nombre";
$tematicas = $db->query($query_tematicas)->fetchAll(PDO::FETCH_ASSOC);

// Construir consulta con filtros
$query = "SELECT g.*, t.nombre as tematica_nombre, s.servicio as servicio_nombre 
          FROM galeria g 
          LEFT JOIN tematicas t ON g.tematica_id = t.id 
          LEFT JOIN servicios s ON g.servicio_id = s.id 
          WHERE 1=1";

$params = [];

if (!empty($servicio_id)) {
    $query .= " AND g.servicio_id = ?";
    $params[] = $servicio_id;
}

if (!empty($tema_id)) {
    $query .= " AND g.tematica_id = ?";
    $params[] = $tema_id;
}

if (!empty($busqueda)) {
    $query .= " AND (g.nombre LIKE ? OR t.nombre LIKE ? OR s.servicio LIKE ?)";
    $search_term = "%$busqueda%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY g.nombre";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <h1>Catálogo de Productos</h1>
    
    <div class="catalog-filters">
        <div class="filter-group">
            <label for="servicio">Filtrar por Servicio</label>
            <select id="servicio" name="servicio">
                <option value="">Todos los servicios</option>
                <?php foreach ($servicios as $serv): ?>
                <option value="<?php echo $serv['id']; ?>" <?php echo $servicio_id == $serv['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($serv['servicio']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="tema">Filtrar por Temática</label>
            <select id="tema" name="tema">
                <option value="">Todas las temáticas</option>
                <?php foreach ($tematicas as $tema): ?>
                <option value="<?php echo $tema['id']; ?>" <?php echo $tema_id == $tema['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($tema['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="busqueda">Buscar</label>
            <input type="text" id="busqueda" name="busqueda" placeholder="Nombre, temática o servicio..." value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>
        
        <div class="filter-group" style="align-self: flex-end;">
            <button id="btn-filtrar" class="btn">Filtrar</button>
            <button id="btn-limpiar" class="btn btn-secondary">Limpiar</button>
        </div>
    </div>
    
    <?php if (empty($productos)): ?>
    <div style="text-align: center; padding: 40px 0;">
        <h3>No se encontraron productos con los filtros seleccionados</h3>
        <p>Intenta con otros criterios de búsqueda</p>
    </div>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($productos as $producto): ?>
        <div class="gallery-item">
            <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
            <div class="gallery-item-info">
                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                <p><strong>Servicio:</strong> <?php echo htmlspecialchars($producto['servicio_nombre']); ?></p>
                <p><strong>Temática:</strong> <?php echo htmlspecialchars($producto['tematica_nombre']); ?></p>
                <p class="gallery-item-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                <div class="item-actions">
                    <button class="btn-view" data-id="<?php echo $producto['id']; ?>">Ver Detalles</button>
                    <?php if (isLoggedIn()): ?>
                    <button class="btn-favorite" data-id="<?php echo $producto['id']; ?>">
                        <i class="far fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrar al cambiar selects
    document.getElementById('servicio').addEventListener('change', aplicarFiltros);
    document.getElementById('tema').addEventListener('change', aplicarFiltros);
    
    // Filtrar al hacer clic en el botón
    document.getElementById('btn-filtrar').addEventListener('click', aplicarFiltros);
    
    // Limpiar filtros
    document.getElementById('btn-limpiar').addEventListener('click', function() {
        window.location.href = 'catalog.php';
    });
    
    // También filtrar al presionar Enter en la búsqueda
    document.getElementById('busqueda').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            aplicarFiltros();
        }
    });
    
    function aplicarFiltros() {
        const servicio = document.getElementById('servicio').value;
        const tema = document.getElementById('tema').value;
        const busqueda = document.getElementById('busqueda').value;
        
        let url = 'catalog.php?';
        if (servicio) url += `servicio=${servicio}&`;
        if (tema) url += `tema=${tema}&`;
        if (busqueda) url += `busqueda=${encodeURIComponent(busqueda)}`;
        
        window.location.href = url;
    }
});
</script>

<?php
$scripts = '
<script src="' . APP_URL . '/assets/js/gallery.js"></script>
';
include 'includes/footer.php';
?>