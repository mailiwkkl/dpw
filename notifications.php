<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$page_title = "Mis Notificaciones - " . APP_NAME;

$user_id = $_SESSION['user_id'];
$notifications = getUserNotifications($user_id);

include 'includes/header.php';
?>

<div class="container">
    <h1>Mis Notificaciones</h1>
    
    <div class="notifications-actions">
        <button onclick="markAllAsRead()" class="btn">Marcar todas como leídas</button>
        <button onclick="deleteAllRead()" class="btn btn-secondary">Eliminar leídas</button>
    </div>
    
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No tienes notificaciones</h3>
                <p>Las notificaciones sobre tus pedidos aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['leida'] ? 'read' : 'unread'; ?>" 
                     data-id="<?php echo $notification['id']; ?>">
                    <div class="notification-content">
                        <p><?php echo htmlspecialchars($notification['mensaje']); ?></p>
                        <small><?php echo date('d/m/Y H:i', strtotime($notification['fecha_creacion'])); ?></small>
                    </div>
                    <div class="notification-actions">
                        <?php if (!$notification['leida']): ?>
                            <button class="btn-mark-read" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn-delete" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('includes/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_action=mark_as_read&notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                item.querySelector('.btn-mark-read').remove();
            }
            updateNotificationCount();
        }
    });
}

function markAllAsRead() {
    if (!confirm('¿Marcar todas las notificaciones como leídas?')) return;
    
    fetch('includes/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_action=mark_all_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
                const btn = item.querySelector('.btn-mark-read');
                if (btn) btn.remove();
            });
            updateNotificationCount();
        }
    });
}

function deleteNotification(notificationId) {
    if (!confirm('¿Eliminar esta notificación?')) return;
    
    fetch('includes/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_action=delete&notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.remove();
            }
            updateNotificationCount();
            
            // Si no quedan notificaciones, mostrar empty state
            if (document.querySelectorAll('.notification-item').length === 0) {
                document.querySelector('.notifications-list').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No tienes notificaciones</h3>
                        <p>Las notificaciones sobre tus pedidos aparecerán aquí.</p>
                    </div>
                `;
            }
        }
    });
}

function deleteAllRead() {
    if (!confirm('¿Eliminar todas las notificaciones leídas?')) return;
    
    fetch('includes/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_action=delete_all_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item.read').forEach(item => {
                item.remove();
            });
            
            // Si no quedan notificaciones, mostrar empty state
            if (document.querySelectorAll('.notification-item').length === 0) {
                document.querySelector('.notifications-list').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No tienes notificaciones</h3>
                        <p>Las notificaciones sobre tus pedidos aparecerán aquí.</p>
                    </div>
                `;
            }
        }
    });
}

function updateNotificationCount() {
    // Esta función debería actualizar el badge de notificaciones en el header
    // Se implementaría con una llamada AJAX para obtener el conteo actual
    fetch('includes/notifications.php?get_notifications=true&unread_only=true')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.notification-badge');
                if (data.unread_count > 0) {
                    if (!badge) {
                        // Crear badge si no existe
                        const badge = document.createElement('span');
                        badge.className = 'notification-badge';
                        document.querySelector('.user-menu > a').appendChild(badge);
                    }
                    document.querySelector('.notification-badge').textContent = data.unread_count;
                } else if (badge) {
                    badge.remove();
                }
            }
        });
}
</script>

<style>
.notifications-actions {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
}

.notifications-list {
    background: var(--white);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.notification-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid var(--gray-light);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background-color: #f0f9ff;
    border-left: 4px solid var(--primary-color);
}

.notification-content {
    flex: 1;
}

.notification-content p {
    margin: 0 0 5px 0;
}

.notification-content small {
    color: var(--text-color);
    opacity: 0.7;
}

.notification-actions {
    display: flex;
    gap: 10px;
}

.btn-mark-read, .btn-delete {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    color: var(--text-color);
}

.btn-mark-read:hover {
    color: var(--success-color);
}

.btn-delete:hover {
    color: var(--error-color);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-color);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: var(--text-color);
}
</style>

<?php
include 'includes/footer.php';
?>