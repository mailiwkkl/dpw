<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$page_title = "Catálogo - " . APP_NAME;

$database = new Database();
$db = $database->getConnection();

// Obtener parámetros de filtro
$tema_id = isset($_GET['tema']) ? $_GET['tema'] : '';
$categoria_id = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta con filtros
$query = "SELECT g.*, t.nombre as tematica_nombre, c.nombre as categoria_nombre 
          FROM galeria g 
          LEFT JOIN tematicas t ON g.tematica_id = t.id 
          LEFT JOIN categorias c ON g.categoria_id = c.id 
          WHERE 1=1";

$params = [];

if (!empty($tema_id)) {
    $query .= " AND g.tematica_id = ?";
    $params[] = $tema_id;
}

if (!empty($categoria_id)) {
    $query .= " AND g.categoria_id = ?";
    $params[] = $categoria_id;
}

if (!empty($busqueda)) {
    $query .= " AND (g.nombre LIKE ? OR t.nombre LIKE ? OR c.nombre LIKE ?)";
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
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener temáticas y categorías para los filtros
$query_tematicas = "SELECT * FROM tematicas ORDER BY nombre";
$tematicas = $db->query($query_tematicas)->fetchAll(PDO::FETCH_ASSOC);

$query_categorias = "SELECT * FROM categorias ORDER BY nombre";
$categorias = $db->query($query_categorias)->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h1 style="text-align: center; margin-bottom: 30px;">Catálogo de Productos</h1>

<div class="container">
    <div class="catalog-filters">
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
            <label for="categoria">Filtrar por Categoría</label>
            <select id="categoria" name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="busqueda">Buscar</label>
            <input type="text" id="busqueda" name="busqueda" placeholder="Nombre, temática o categoría..." value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>
        
        <div class="filter-group" style="align-self: flex-end;">
            <button id="btn-filtrar" class="btn">Filtrar</button>
            <button id="btn-limpiar" class="btn btn-secondary">Limpiar</button>
        </div>
    </div>
    
    <?php if (empty($items)): ?>
    <div style="text-align: center; padding: 40px 0;">
        <h3>No se encontraron productos con los filtros seleccionados</h3>
        <p>Intenta con otros criterios de búsqueda</p>
    </div>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($items as $item): ?>
        <div class="gallery-item">
            <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $item['imagen']; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
            <div class="gallery-item-info">
                <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                <p><strong>Temática:</strong> <?php echo htmlspecialchars($item['tematica_nombre']); ?></p>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($item['categoria_nombre']); ?></p>
                <p class="gallery-item-price">$<?php echo number_format($item['precio'], 2); ?></p>
                <div class="item-actions">
                    <button class="btn-view" data-id="<?php echo $item['id']; ?>">Ver Detalles</button>
                    <?php if (isLoggedIn()): ?>
                    <button class="btn-favorite" data-id="<?php echo $item['id']; ?>">
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

<!-- Modal para vista ampliada -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-body">
            <img id="modalImage" src="" alt="">
            <div class="image-info">
                <span id="imageId"></span>
                <span id="imagePrice"></span>
            </div>
            <?php if (isLoggedIn()): ?>
            <button id="addToFavorites" class="btn-favorite">
                <i class="far fa-heart"></i> Agregar a favoritos
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrar al cambiar selects
    document.getElementById('tema').addEventListener('change', aplicarFiltros);
    document.getElementById('categoria').addEventListener('change', aplicarFiltros);
    
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
        const tema = document.getElementById('tema').value;
        const categoria = document.getElementById('categoria').value;
        const busqueda = document.getElementById('busqueda').value;
        
        let url = 'catalog.php?';
        if (tema) url += `tema=${tema}&`;
        if (categoria) url += `categoria=${categoria}&`;
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