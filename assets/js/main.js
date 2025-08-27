// Funciones generales para el sitio
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar carruseles
    initCarousels();
    
    // Manejar modales
    initModals();
    
    // Manejar sistema de favoritos
    initFavorites();
    
    // Manejar notificaciones
    initNotifications();
});

// Inicializar todos los carruseles
function initCarousels() {
    const carousels = document.querySelectorAll('.carousel');
    
    carousels.forEach(carousel => {
        const container = carousel.querySelector('.carousel-container');
        const prevBtn = carousel.querySelector('.carousel-btn.prev');
        const nextBtn = carousel.querySelector('.carousel-btn.next');
        const items = carousel.querySelectorAll('.carousel-item');
        let currentPosition = 0;
        
        if (items.length === 0) return;
        
        const itemWidth = items[0].offsetWidth + parseInt(getComputedStyle(items[0]).marginRight);
        
        // Event listener para botón siguiente
        nextBtn.addEventListener('click', () => {
            if (currentPosition < items.length - 1) {
                currentPosition++;
                container.scrollTo({
                    left: currentPosition * itemWidth,
                    behavior: 'smooth'
                });
            }
        });
        
        // Event listener para botón anterior
        prevBtn.addEventListener('click', () => {
            if (currentPosition > 0) {
                currentPosition--;
                container.scrollTo({
                    left: currentPosition * itemWidth,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Inicializar modales
function initModals() {
    const modal = document.getElementById('imageModal');
    if (!modal) return;
    
    const closeBtn = modal.querySelector('.close');
    const modalImage = modal.querySelector('#modalImage');
    const imageId = modal.querySelector('#imageId');
    const imagePrice = modal.querySelector('#imagePrice');
    const addToFavoritesBtn = modal.querySelector('#addToFavorites');
    
    // Botones para abrir modal
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            openModal(itemId);
        });
    });
    
    // Cerrar modal
    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    function openModal(itemId) {
        // Aquí se cargarían los datos reales desde el servidor
        // Por ahora usamos datos de ejemplo
        modalImage.src = `https://via.placeholder.com/800x500/6b46c1/ffffff?text=Imagen+${itemId}`;
        imageId.textContent = `ID: ${itemId}`;
        imagePrice.textContent = `$${(Math.random() * 50 + 10).toFixed(2)}`;
        
        if (addToFavoritesBtn) {
            addToFavoritesBtn.setAttribute('data-id', itemId);
        }
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Sistema de favoritos
function initFavorites() {
    const favoriteButtons = document.querySelectorAll('.btn-favorite');
    
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            toggleFavorite(itemId, this);
        });
    });
    
    // Botón de favoritos en el modal
    const modalFavoriteBtn = document.querySelector('#addToFavorites');
    if (modalFavoriteBtn) {
        modalFavoriteBtn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            toggleFavorite(itemId, this);
        });
    }
}

function toggleFavorite(itemId, button) {
    // Simular llamada al servidor
    const isFavorite = button.classList.contains('active');
    
    if (isFavorite) {
        // Quitar de favoritos
        button.classList.remove('active');
        button.innerHTML = '<i class="far fa-heart"></i>';
        if (button.textContent) {
            button.innerHTML = '<i class="far fa-heart"></i> Agregar a favoritos';
        }
        showNotification('Producto eliminado de favoritos', 'info');
    } else {
        // Agregar a favoritos
        button.classList.add('active');
        button.innerHTML = '<i class="fas fa-heart"></i>';
        if (button.textContent) {
            button.innerHTML = '<i class="fas fa-heart"></i> En favoritos';
        }
        showNotification('Producto agregado a favoritos', 'success');
    }
    
    // Aquí iría la llamada AJAX real al servidor
    /*
    fetch('ajax/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar UI
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    });
    */
}

// Sistema de notificaciones
function initNotifications() {
    // Cargar notificaciones no leídas
    loadUnreadNotifications();
}

function loadUnreadNotifications() {
    // Simular carga de notificaciones
    /*
    fetch('ajax/get_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayNotifications(data.notifications);
        }
    });
    */
}

function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <p>${message}</p>
        </div>
        <button class="notification-close">&times;</button>
    `;
    
    // Estilos para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'error' ? '#f56565' : type === 'success' ? '#48bb78' : '#4299e1'};
        color: white;
        padding: 15px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 300px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Botón para cerrar
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    });
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

// Añadir estilos de animación para notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: 15px;
    }
`;
document.head.appendChild(style);