<?php
require_once 'includes/config.php';

$page_title = "Inicio - " . APP_NAME;

// Obtener datos para la página principal
$database = new Database();
$db = $database->getConnection();

// Obtener promociones activas
$promotions = getActivePromotions();

// Obtener productos más vistos (si no hay, obtener aleatorios)
$most_viewed = getMostViewedProducts(10);
if (empty($most_viewed)) {
    // Si no hay productos vistos, obtener aleatorios
    $query = "SELECT g.*, t.nombre as tematica_nombre, s.servicio as servicio_nombre 
              FROM galeria g 
              LEFT JOIN tematicas t ON g.tematica_id = t.id 
              LEFT JOIN servicios s ON g.servicio_id = s.id 
              ORDER BY RAND() LIMIT 10";
    $most_viewed = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener todos los servicios (para usar como categorías)
$servicios = getAllServices();

// Para cada servicio, obtener algunos productos
foreach ($servicios as &$servicio) {
    $servicio['items'] = getFeaturedProductsByService($servicio['id'], 8);
}
unset($servicio); // Romper la referencia

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-slider">
        <?php if (!empty($promotions)): ?>
            <?php foreach ($promotions as $promo): ?>
            <div class="hero-slide">
                <div class="hero-content">
                    <h2><?php echo htmlspecialchars($promo['titulo']); ?></h2>
                    <p><?php echo htmlspecialchars($promo['descripcion']); ?></p>
                    <a href="catalog.php" class="btn btn-primary btn-large">Ver Catálogo</a>
                </div>
                <div class="hero-image">
                    <img src="<?php echo APP_URL; ?>/assets/images/<?php echo $promo['imagen']; ?>" alt="<?php echo htmlspecialchars($promo['titulo']); ?>">
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Promoción por defecto si no hay promociones activas -->
            <div class="hero-slide">
                <div class="hero-content">
                    <h2>Bienvenido a Digital Print Fiesta</h2>
                    <p>Descubre nuestros servicios de impresión de calidad</p>
                    <a href="catalog.php" class="btn btn-primary btn-large">Ver Catálogo</a>
                </div>
                <div class="hero-image">
                    <img src="<?php echo APP_URL; ?>/assets/images/default-promo.webp" alt="Digital Print Fiesta">
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Productos Más Populares -->
<section class="netflix-section">
    <div class="section-header">
        <h2>Más Populares</h2>
        <a href="catalog.php" class="see-all">Ver todos</a>
    </div>
    <div class="netflix-carousel">
        <button class="carousel-btn prev"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel-container">
            <?php foreach ($most_viewed as $item): ?>
            <div class="carousel-item">
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $item['imagen']; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                        <div class="product-overlay">
                            <button class="btn-view" data-id="<?php echo $item['id']; ?>">Ver Detalles</button>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn-favorite" data-id="<?php echo $item['id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($item['servicio_nombre']); ?></p>
                        <p class="product-price">$<?php echo number_format($item['precio'], 2); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-btn next"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>

<!-- Productos por Servicio (Estilo Netflix) -->
<?php foreach ($servicios as $servicio): ?>
<section class="netflix-section">
    <div class="section-header">
        <div class="service-title">
            <div class="service-icon">
                <i class="fas fa-concierge-bell"></i>
            </div>
            <h2><?php echo htmlspecialchars($servicio['servicio']); ?></h2>
        </div>
        <a href="catalog.php?servicio=<?php echo $servicio['id']; ?>" class="see-all">Ver todos</a>
    </div>
    
    <?php if (!empty($servicio['items'])): ?>
    <div class="netflix-carousel">
        <button class="carousel-btn prev"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel-container">
            <?php foreach ($servicio['items'] as $item): ?>
            <div class="carousel-item">
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $item['imagen']; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                        <div class="product-overlay">
                            <button class="btn-view" data-id="<?php echo $item['id']; ?>">Ver Detalles</button>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn-favorite" data-id="<?php echo $item['id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($item['tematica_nombre']); ?></p>
                        <p class="product-price">$<?php echo number_format($item['precio'], 2); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-btn next"><i class="fas fa-chevron-right"></i></button>
    </div>
    <?php else: ?>
    <p class="no-items">No hay productos para este servicio todavía.</p>
    <?php endif; ?>
</section>
<?php endforeach; ?>

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

<style>
.service-title {
    display: flex;
    align-items: center;
    gap: 15px;
}

.service-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.service-icon i {
    font-size: 1.2rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar carruseles
    initCarousels();
    
    function initCarousels() {
        const carousels = document.querySelectorAll('.netflix-carousel');
        
        carousels.forEach(carousel => {
            const container = carousel.querySelector('.carousel-container');
            const prevBtn = carousel.querySelector('.carousel-btn.prev');
            const nextBtn = carousel.querySelector('.carousel-btn.next');
            const items = carousel.querySelectorAll('.carousel-item');
            
            if (items.length === 0) return;
            
            const itemWidth = items[0].offsetWidth + 15; // 15px gap
            
            nextBtn.addEventListener('click', () => {
                container.scrollBy({ left: itemWidth * 3, behavior: 'smooth' });
            });
            
            prevBtn.addEventListener('click', () => {
                container.scrollBy({ left: -itemWidth * 3, behavior: 'smooth' });
            });
        });
    }
});
</script>

<?php
$scripts = '
<script src="' . APP_URL . '/assets/js/gallery.js"></script>
';
include 'includes/footer.php';
?>