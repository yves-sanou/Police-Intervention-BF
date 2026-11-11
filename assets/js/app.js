document.addEventListener('DOMContentLoaded', function () {
    pollNotifications();
    window.setInterval(pollNotifications, 5000);
});

async function pollNotifications() {
    const badge = document.getElementById('notificationBadge');

    try {
        const baseUrl = (window.APP_BASE_URL || '').replace(/\/$/, '');
        const response = await fetch(
            baseUrl + '/api/notifications.php?t=' + Date.now(),
            {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }
        );

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const data = await response.json();

        if (!data || data.success !== true) {
            throw new Error(data?.message || 'Réponse API notifications invalide');
        }

        updateNotificationBadge(Number(data.unread) || 0);
    } catch (error) {
        console.error('Erreur récupération notifications:', error);
        // Ne pas masquer le dernier compteur valide en cas de panne réseau.
    }
}

function updateNotificationBadge(count) {
    let badge = document.getElementById('notificationBadge');

    if (!badge) {
        const topActions = document.querySelector('.top-actions');
        if (!topActions) return;

        badge = document.createElement('span');
        badge.id = 'notificationBadge';
        badge.className = 'notification-badge';
        badge.setAttribute('aria-live', 'polite');
        badge.setAttribute('aria-label', 'Notifications non lues');
        topActions.prepend(badge);
    }

    const safeCount = Math.max(0, Number(count) || 0);

    if (safeCount > 0) {
        badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
        badge.style.display = 'inline-flex';
    } else {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

async function loadSosAlerts() {
    try {
        const baseUrl = (window.APP_BASE_URL || '').replace(/\/$/, '');
        const response = await fetch(
            baseUrl + '/api/sos_active.php?t=' + Date.now(),
            {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }
        );

        if (!response.ok) return [];

        const data = await response.json();
        return data.success && Array.isArray(data.alerts) ? data.alerts : [];
    } catch (error) {
        console.error('Erreur SOS:', error);
        return [];
    }
}

function initMap(elementId, latitude = 12.3714, longitude = -1.5197, zoom = 6) {
    const element = document.getElementById(elementId);
    if (!element) return null;

    if (typeof L === 'undefined') {
        console.error("Leaflet n'est pas chargé.");
        return null;
    }

    const map = L.map(elementId).setView([latitude, longitude], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    window.setTimeout(function () {
        map.invalidateSize();
    }, 300);

    return map;
}
