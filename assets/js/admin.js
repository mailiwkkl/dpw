// Funciones específicas para el panel de administración
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    initTooltips();
    
    // Inicializar confirmaciones de eliminación
    initDeleteConfirmations();
    
    // Inicializar formularios dinámicos
    initDynamicForms();
    
    // Inicializar búsquedas en tiempo real
    initLiveSearch();
});

// Tooltips para elementos de administración
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = this.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'admin-tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.position = 'absolute';
    tooltip.style.background = 'rgba(0, 0, 0, 0.8)';
    tooltip.style.color = 'white';
    tooltip.style.padding = '8px 12px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '10000';
    tooltip.style.pointerEvents = 'none';
    
    document.body.appendChild(tooltip);
    
    const rect = this.getBoundingClientRect();
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
    
    this.tooltipElement = tooltip;
}

function hideTooltip() {
    if (this.tooltipElement) {
        document.body.removeChild(this.tooltipElement);
        this.tooltipElement = null;
    }
}

// Confirmaciones para acciones de eliminación
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('a.btn-danger, button.btn-danger');
    
    deleteButtons.forEach(button => {
        // Solo si no tiene ya un evento de confirmación
        if (!button.hasAttribute('data-confirmation-set')) {
            button.addEventListener('click', function(e) {
                const message = this.getAttribute('data-confirm') || '¿Estás seguro de que quieres eliminar este elemento?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
            
            button.setAttribute('data-confirmation-set', 'true');
        }
    });
}

// Formularios dinámicos (para dependencias entre selects)
function initDynamicForms() {
    // Dependencia entre temática y categoría
    const tematicaSelect = document.getElementById('tematica_id');
    const categoriaSelect = document.getElementById('categoria_id');
    
    if (tematicaSelect && categoriaSelect) {
        tematicaSelect.addEventListener('change', function() {
            const tematicaId = this.value;
            
            if (tematicaId) {
                // Mostrar loading
                categoriaSelect.disabled = true;
                const originalHTML = categoriaSelect.innerHTML;
                categoriaSelect.innerHTML = '<option value="">Cargando categorías...</option>';
                
                fetch(`../ajax/get_categorias.php?tematica_id=${tematicaId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let options = '<option value="">Seleccionar Categoría</option>';
                            data.categorias.forEach(cat => {
                                options += `<option value="${cat.id}">${cat.nombre}</option>`;
                            });
                            categoriaSelect.innerHTML = options;
                        } else {
                            categoriaSelect.innerHTML = originalHTML;
                            alert('Error al cargar las categorías');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        categoriaSelect.innerHTML = originalHTML;
                        alert('Error de conexión');
                    })
                    .finally(() => {
                        categoriaSelect.disabled = false;
                    });
            } else {
                categoriaSelect.innerHTML = '<option value="">Seleccionar Categoría</option>';
            }
        });
    }
}

// Búsqueda en tiempo real para tablas
function initLiveSearch() {
    const searchInputs = document.querySelectorAll('input[data-live-search]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const tableId = this.getAttribute('data-live-search');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    if (rowText.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    });
}

// Función para actualizar estadísticas en tiempo real
function updateAdminStats() {
    fetch('../ajax/get_admin_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contadores en el dashboard
                if (document.getElementById('stat-users')) {
                    document.getElementById('stat-users').textContent = data.stats.total_usuarios;
                }
                if (document.getElementById('stat-invoices')) {
                    document.getElementById('stat-invoices').textContent = data.stats.total_facturas;
                }
                if (document.getElementById('stat-pending')) {
                    document.getElementById('stat-pending').textContent = data.stats.pendientes_pago;
                }
                if (document.getElementById('stat-gallery')) {
                    document.getElementById('stat-gallery').textContent = data.stats.total_galeria;
                }
            }
        })
        .catch(error => {
            console.error('Error al actualizar estadísticas:', error);
        });
}

// Actualizar estadísticas cada 5 minutos si estamos en el dashboard
if (window.location.pathname.includes('admin/index.php')) {
    updateAdminStats();
    setInterval(updateAdminStats, 300000); // 5 minutos
}

// Modal functions for admin
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modales al hacer clic fuera
document.addEventListener('click', function(e) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});

// Exportar datos
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Limpiar texto y evitar comas problemáticas
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Descargar archivo
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename || 'export.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Notificaciones para admin
function showAdminNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `admin-notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <p>${message}</p>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        background: type === 'error' ? '#f56565' : type === 'success' ? '#48bb78' : '#4299e1',
        color: 'white',
        padding: '15px 20px',
        borderRadius: '6px',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        minWidth: '300px',
        zIndex: '10000',
        animation: 'slideIn 0.3s ease'
    });
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Añadir estilos para notificaciones admin
const adminStyles = document.createElement('style');
adminStyles.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .admin-notification .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: 15px;
    }
    
    .admin-tooltip {
        position: absolute;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 10000;
        pointer-events: none;
        max-width: 200px;
        word-wrap: break-word;
    }
`;
document.head.appendChild(adminStyles);