@php($notificationPrefix = request()->routeIs('hr.*') ? 'hr' : 'admin')
<header class="mb-4 admin-topbar">
    <a href="#" class="burger-btn d-block d-xl-none"><i class="bi bi-justify fs-3"></i></a>

    <div class="ms-auto d-flex align-items-center gap-3">
        <div class="dropdown notification-dropdown">
            <button
                class="btn topbar-icon position-relative"
                type="button"
                id="notificationBell"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false"
                title="Notifications"
            >
                <i class="bi bi-bell"></i>
                <span class="notification-dot d-none" id="notificationDot"></span>
                <span class="notification-count d-none" id="notificationCount">0</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end notification-menu shadow-lg border-0" aria-labelledby="notificationBell">
                <div class="notification-menu-header">
                    <div>
                        <strong>Notifications</strong>
                        <small id="notificationSummary">No unread notifications</small>
                    </div>
                    <div class="notification-header-actions">
                        <button type="button" class="btn btn-link btn-sm" id="markAllNotificationsRead">Mark all read</button>
                        <button type="button" class="btn btn-link btn-sm text-danger" id="clearAllNotifications">Clear all</button>
                    </div>
                </div>

                <div class="notification-list" id="notificationList">
                    <div class="notification-loading">
                        <span class="spinner-border spinner-border-sm"></span>
                        <span>Loading notifications...</span>
                    </div>
                </div>
                <div class="notification-menu-footer d-none" id="notificationFooter">
                    <button type="button" class="btn btn-link btn-sm" id="toggleAllNotifications">See All</button>
                </div>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn admin-profile dropdown-toggle" data-bs-toggle="dropdown">
                <span class="profile-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
                <span class="d-none d-md-inline text-start">
                    <strong class="d-block">{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->role === 'admin' ? 'System Admin' : 'Recruitment Team' }}</small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('notificationList');
    const dot = document.getElementById('notificationDot');
    const count = document.getElementById('notificationCount');
    const summary = document.getElementById('notificationSummary');
    const markAllButton = document.getElementById('markAllNotificationsRead');
    const indexUrl = @json(route($notificationPrefix . '.notifications.index'));
    const readAllUrl = @json(route($notificationPrefix . '.notifications.read-all'));
    const clearAllUrl = @json(route($notificationPrefix . '.notifications.clear-all'));
    const bell = document.getElementById('notificationBell');
    const clearAllButton = document.getElementById('clearAllNotifications');
    const notificationFooter = document.getElementById('notificationFooter');
    const toggleAllButton = document.getElementById('toggleAllNotifications');
    const pollIntervalMs = 3000;
    const desktopNotificationIcon = @json(asset('assets/images/logo/rs8-logo.png'));
    const desktopNotificationStorageKey = 'rs8-recruitment-desktop-notified-v1';
    let lastUnreadCount = 0;
    let requestInProgress = false;
    let pollTimer = null;
    let expanded = false;
    let notificationBaselineReady = false;

    function getDesktopNotifiedIds() {
        try {
            const stored = JSON.parse(localStorage.getItem(desktopNotificationStorageKey) || '[]');
            return new Set(Array.isArray(stored) ? stored.map(String) : []);
        } catch (error) {
            return new Set();
        }
    }

    function saveDesktopNotifiedIds(ids) {
        try {
            localStorage.setItem(desktopNotificationStorageKey, JSON.stringify(Array.from(ids).slice(-150)));
        } catch (error) {
            // Local storage can be unavailable in strict/private browser modes.
        }
    }

    function desktopNotificationsSupported() {
        return 'Notification' in window;
    }

    async function requestDesktopNotificationPermission() {
        if (!desktopNotificationsSupported()) return false;
        if (Notification.permission === 'granted') return true;
        if (Notification.permission === 'denied') return false;

        try {
            return (await Notification.requestPermission()) === 'granted';
        } catch (error) {
            return false;
        }
    }

    function showDesktopNotification(item) {
        if (!desktopNotificationsSupported() || Notification.permission !== 'granted') return;

        try {
            const notice = new Notification(item.title || 'RS8 Recruitment Update', {
                body: item.message || 'You have a new recruitment notification.',
                icon: desktopNotificationIcon,
                badge: desktopNotificationIcon,
                tag: `rs8-recruitment-${item.id}`,
                renotify: true
            });

            notice.onclick = function () {
                window.focus();
                if (item.open_url) window.location.href = item.open_url;
                notice.close();
            };
        } catch (error) {
            // Keep the in-app bell working even when the OS blocks native toasts.
        }
    }

    function processDesktopNotifications(items) {
        const unreadItems = (items || []).filter(item => !item.is_read && item.id);
        const notifiedIds = getDesktopNotifiedIds();

        // On the first poll, remember current unread notifications without replaying
        // old alerts. Only notifications that arrive after the page is running will pop.
        if (!notificationBaselineReady) {
            unreadItems.forEach(item => notifiedIds.add(String(item.id)));
            saveDesktopNotifiedIds(notifiedIds);
            notificationBaselineReady = true;
            return;
        }

        unreadItems.slice().reverse().forEach(item => {
            const id = String(item.id);
            if (notifiedIds.has(id)) return;

            // Save before showing to reduce duplicate native notifications across tabs.
            notifiedIds.add(id);
            saveDesktopNotifiedIds(notifiedIds);
            showDesktopNotification(item);
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
        })[character]);
    }

    function updateBadge(unreadCount) {
        const total = Number(unreadCount || 0);

        if (total > lastUnreadCount) {
            bell.classList.remove('notification-bell-new');
            void bell.offsetWidth;
            bell.classList.add('notification-bell-new');
        }

        lastUnreadCount = total;
        dot.classList.toggle('d-none', total === 0);
        count.classList.toggle('d-none', total === 0);
        count.textContent = total > 99 ? '99+' : total;
        summary.textContent = total === 0
            ? 'No unread notifications'
            : `${total} unread notification${total === 1 ? '' : 's'}`;
        markAllButton.disabled = total === 0;
    }

    function renderNotifications(items, totalCount = 0) {
        if (!items.length) {
            list.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-bell-slash"></i>
                    <strong>No notifications yet</strong>
                    <small>New applicants, interview updates, and hiring-stage changes will appear here.</small>
                </div>`;
            notificationFooter.classList.add('d-none');
            return;
        }

        notificationFooter.classList.toggle('d-none', Number(totalCount) < 5);
        toggleAllButton.textContent = expanded ? 'Show Less' : 'See All';

        list.innerHTML = items.map(item => `
            <a href="${item.open_url}" class="notification-item ${item.is_read ? '' : 'unread'}">
                <span class="notification-item-icon text-${escapeHtml(item.color)} bg-${escapeHtml(item.color)}-subtle">
                    <i class="bi ${escapeHtml(item.icon)}"></i>
                </span>
                <span class="notification-item-content">
                    <strong>${escapeHtml(item.title)}</strong>
                    <span>${escapeHtml(item.message)}</span>
                    <small>${escapeHtml(item.created_at)}</small>
                </span>
                ${item.is_read ? '' : '<span class="notification-unread-mark"></span>'}
            </a>`).join('');
    }

    async function loadNotifications() {
        if (requestInProgress) return;
        requestInProgress = true;

        try {
            const separator = indexUrl.includes('?') ? '&' : '?';
            const response = await fetch(`${indexUrl}${separator}expanded=${expanded ? 1 : 0}&_=${Date.now()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) throw new Error('Unable to load notifications.');
            const data = await response.json();
            updateBadge(data.unread_count);
            renderNotifications(data.notifications || [], data.total_count || 0);
            processDesktopNotifications(data.notifications || []);
        } catch (error) {
            list.innerHTML = '<div class="notification-empty text-danger"><i class="bi bi-exclamation-circle"></i><strong>Unable to load notifications</strong><small>We will try again automatically.</small></div>';
        } finally {
            requestInProgress = false;
        }
    }

    function startPolling() {
        window.clearInterval(pollTimer);
        pollTimer = window.setInterval(loadNotifications, pollIntervalMs);
    }

    markAllButton.addEventListener('click', async function () {
        try {
            const response = await fetch(readAllUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({})
            });
            if (!response.ok) throw new Error('Unable to update notifications.');
            await loadNotifications();
        } catch (error) {
            if (typeof showToast === 'function') showToast('error', error.message);
        }
    });


    clearAllButton.addEventListener('click', async function () {
        const confirmed = typeof Swal === 'undefined' || (await Swal.fire({
            title: 'Clear all notifications?',
            text: 'This will permanently remove all notifications from your account.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, clear all',
            cancelButtonText: 'Cancel'
        })).isConfirmed;

        if (!confirmed) return;

        try {
            const response = await fetch(clearAllUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Unable to clear notifications.');
            expanded = false;
            await loadNotifications();
            if (typeof showToast === 'function') showToast('success', 'All notifications cleared.');
        } catch (error) {
            if (typeof showToast === 'function') showToast('error', error.message);
        }
    });

    toggleAllButton.addEventListener('click', async function () {
        expanded = !expanded;
        await loadNotifications();
    });

    bell.addEventListener('click', async function () {
        const permissionWasDefault = desktopNotificationsSupported() && Notification.permission === 'default';
        const granted = await requestDesktopNotificationPermission();

        if (permissionWasDefault && granted && typeof showToast === 'function') {
            showToast('success', 'Desktop notifications are now enabled.');
        } else if (desktopNotificationsSupported() && Notification.permission === 'denied' && typeof showToast === 'function') {
            showToast('warning', 'Chrome notifications are blocked for this site. Enable Notifications in Site settings.');
        }
    });

    bell.addEventListener('show.bs.dropdown', loadNotifications);

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) loadNotifications();
    });

    window.addEventListener('focus', loadNotifications);

    loadNotifications();
    startPolling();
});
</script>
@endpush
