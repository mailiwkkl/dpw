<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$page_title = "Mis Favoritos - " . APP_NAME;

// Solo usuarios logueados pueden ver favoritos
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener favoritos del usuario
$query = "SELECT g.*, t.nombre as tematica_nombre, c.nombre as categoria_nombre 
          FROM favoritos f 
          JOIN galeria g ON f.galeria_id = g.id 
          LEFT JOIN tematicas t ON g.tematica_id = t.id 
          LEFT JOIN categorias c ON g.categoria_id = c.id 
          WHERE f.usuario_id = ? 
          ORDER BY f.fecha_agregado DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $_SESSION['user_id']);
$stmt->execute();
$favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <h1 style="text-align: center; margin-bottom: 30px;">Mis Favoritos</h1>
    
    <?php if (empty($favoritos)): ?>
    <div style="text-align: center; padding: 40px 0;">
        <i class="far fa-heart" style="font-size: 60px; color: var(--light-color); margin-bottom: 20px;"></i>
        <h3>No tienes favoritos todavía</h3>
        <p>Explora nuestro catálogo y guarda tus productos favoritos para acceder a ellos fácilmente.</p>
        <a href="catalog.php" class="btn">Explorar Catálogo</a>
    </div>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($favoritos as $item): ?>
        <div class="gallery-item">
            <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $item['imagen']; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
            <div class="gallery-item-info">
                <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                <p><strong>Temática:</strong> <?php echo htmlspecialchars($item['tematica_nombre']); ?></p>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($item['categoria_nombre']); ?></p>
                <p class="gallery-item-price">$<?php echo number_format($item['precio'], 2); ?></p>
                <div class="item-actions">
                    <button class="btn-view" data-id="<?php echo $item['id']; ?>">Ver Detalles</button>
                    <button class="btn-favorite active" data-id="<?php echo $item['id']; ?>">
                        <i class="fas fa-heart"></i>
                    </button>
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
            <button id="addToFavorites" class="btn-favorite">
                <i class="far fa-heart"></i> Agregar a favoritos
            </button>
        </div>
    </div>
</div>

<?php
$scripts = '
<script src="' . APP_URL . '/assets/js/gallery.js"></script>
';
include 'includes/footer.php';
?>