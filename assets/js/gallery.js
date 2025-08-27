// Funciones específicas para la galería
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sistema de favoritos
    initFavorites();
    
    // Inicializar modales
    initModals();
    
    // Inicializar filtros
    initFilters();
});

// Sistema de favoritos mejorado
function initFavorites() {
    document.querySelectorAll('.btn-favorite').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const isActive = this.classList.contains('active');
            
            toggleFavorite(itemId, this, isActive);
        });
    });
}

function toggleFavorite(itemId, button, isActive) {
    // Cambiar estado visual inmediatamente
    if (isActive) {
        button.classList.remove('active');
        button.innerHTML = '<i class="far fa-heart"></i>';
        if (button.textContent) {
            button.innerHTML = '<i class="far fa-heart"></i> Agregar a favoritos';
        }
    } else {
        button.classList.add('active');
        button.innerHTML = '<i class="fas fa-heart"></i>';
        if (button.textContent) {
            button.innerHTML = '<i class="fas fa-heart"></i> En favoritos';
        }
    }
    
    // Llamada AJAX al servidor
    fetch('ajax/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            item_id: itemId,
            action: isActive ? 'remove' : 'add'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Revertir cambios si hay error
            if (isActive) {
                button.classList.add('active');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                if (button.textContent) {
                    button.innerHTML = '<i class="fas fa-heart"></i> En favoritos';
                }
            } else {
                button.classList.remove('active');
                button.innerHTML = '<i class="far fa-heart"></i>';
                if (button.textContent) {
                    button.innerHTML = '<i class="far fa-heart"></i> Agregar a favoritos';
                }
            }
            
            showNotification('Error: ' + data.message, 'error');
        } else {
            showNotification(
                isActive ? 'Producto eliminado de favoritos' : 'Producto agregado a favoritos',
                isActive ? 'info' : 'success'
            );
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revertir cambios en caso de error
        if (isActive) {
            button.classList.add('active');
            button.innerHTML = '<i class="fas fa-heart"></i>';
        } else {
            button.classList.remove('active');
            button.innerHTML = '<i class="far fa-heart"></i>';
        }
        showNotification('Error de conexión', 'error');
    });
}

// Sistema de modales mejorado
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
    
    // Tecla Escape para cerrar modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    
    function openModal(itemId) {
        // Cargar datos del producto
        fetch(`ajax/get_producto.php?id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const producto = data.producto;
                    
                    modalImage.src = `${APP_URL}/assets/uploads/${producto.imagen}`;
                    modalImage.alt = producto.nombre;
                    imageId.textContent = `ID: ${producto.id}`;
                    imagePrice.textContent = `$${parseFloat(producto.precio).toFixed(2)}`;
                    
                    if (addToFavoritesBtn) {
                        addToFavoritesBtn.setAttribute('data-id', producto.id);
                        
                        // Verificar si ya es favorito
                        fetch('ajax/check_favorite.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ item_id: producto.id })
                        })
                        .then(response => response.json())
                        .then(favData => {
                            if (favData.success && favData.is_favorite) {
                                addToFavoritesBtn.classList.add('active');
                                addToFavoritesBtn.innerHTML = '<i class="fas fa-heart"></i> En favoritos';
                            } else {
                                addToFavoritesBtn.classList.remove('active');
                                addToFavoritesBtn.innerHTML = '<i class="far fa-heart"></i> Agregar a favoritos';
                            }
                        });
                        
                        // Event listener para el botón de favoritos en el modal
                        addToFavoritesBtn.onclick = function() {
                            const isActive = this.classList.contains('active');
                            toggleFavorite(producto.id, this, isActive);
                        };
                    }
                    
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    
                    // Incrementar contador de visualizaciones
                    fetch('ajax/increment_views.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ item_id: producto.id })
                    });
                } else {
                    showNotification('Error al cargar el producto', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            });
    }
    
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Sistema de filtros
function initFilters() {
    const filterForm = document.querySelector('.catalog-filters');
    if (!filterForm) return;
    
    const filterBtn = document.getElementById('btn-filtrar');
    const clearBtn = document.getElementById('btn-limpiar');
    
    if (filterBtn) {
        filterBtn.addEventListener('click', aplicarFiltros);
    }
    
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            window.location.href = 'catalog.php';
        });
    }
    
    // Filtrar al cambiar selects
    const temaSelect = document.getElementById('tema');
    const categoriaSelect = document.getElementById('categoria');
    const busquedaInput = document.getElementById('busqueda');
    
    if (temaSelect) {
        temaSelect.addEventListener('change', function() {
            // Cuando cambia la temática, actualizar categorías
            const tematicaId = this.value;
            
            if (tematicaId) {
                fetch(`ajax/get_categorias.php?tematica_id=${tematicaId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let options = '<option value="">Todas las categorías</option>';
                            data.categorias.forEach(cat => {
                                options += `<option value="${cat.id}">${cat.nombre}</option>`;
                            });
                            categoriaSelect.innerHTML = options;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            } else {
                categoriaSelect.innerHTML = '<option value="">Todas las categorías</option>';
            }
        });
    }
    
    // También filtrar al presionar Enter en la búsqueda
    if (busquedaInput) {
        busquedaInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
    }
}

function aplicarFiltros() {
    const tema = document.getElementById('tema')?.value || '';
    const categoria = document.getElementById('categoria')?.value || '';
    const busqueda = document.getElementById('busqueda')?.value || '';
    
    let url = 'catalog.php?';
    if (tema) url += `tema=${tema}&`;
    if (categoria) url += `categoria=${categoria}&`;
    if (busqueda) url += `busqueda=${encodeURIComponent(busqueda)}`;
    
    window.location.href = url;
}

// Función para mostrar notificaciones
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
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
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
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
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
}