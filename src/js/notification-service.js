// src/js/notification-service.js
//
// Handles IN-APP (foreground) notification display — a toast/banner shown
// while the user already has the app open. This is separate from
// push-notification-service.js, which handles OS-level push notifications
// for when the app is backgrounded or closed. FCM does not automatically
// show a system banner while the app is in the foreground, so this fills
// that gap using the data already polled from notifications.php.

class NotificationService {
    constructor() {
        this.initialized = false;
        this.audio = null;
    }

    async initialize() {
        if (this.initialized) return;

        // Prepare a short notification sound (optional — silently no-ops
        // if the file doesn't exist).
        try {
            this.audio = new Audio('/assets/notification-sound.mp3');
            this.audio.volume = 0.5;
        } catch (e) {
            this.audio = null;
        }

        this._ensureContainer();
        this.initialized = true;
        console.log('In-app notification service initialized');
    }

    _ensureContainer() {
        if (document.getElementById('inapp-notification-container')) return;

        const container = document.createElement('div');
        container.id = 'inapp-notification-container';
        container.style.cssText = `
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: min(92vw, 420px);
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    _showToast({ title, body, onClick }) {
        this._ensureContainer();
        const container = document.getElementById('inapp-notification-container');

        const toast = document.createElement('div');
        toast.style.cssText = `
            pointer-events: auto;
            background: #1f2933;
            color: #fff;
            border-radius: 10px;
            padding: 12px 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            cursor: pointer;
            animation: inapp-toast-in 0.25s ease-out;
        `;
        toast.innerHTML = `
            <div style="font-weight:600; font-size:0.95rem; margin-bottom:2px;">${this._escape(title)}</div>
            <div style="font-size:0.85rem; opacity:0.85;">${this._escape(body)}</div>
        `;

        if (!document.getElementById('inapp-toast-style')) {
            const style = document.createElement('style');
            style.id = 'inapp-toast-style';
            style.textContent = `
                @keyframes inapp-toast-in {
                    from { opacity: 0; transform: translateY(-12px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);
        }

        toast.addEventListener('click', () => {
            if (onClick) onClick();
            toast.remove();
        });

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 6000);

        if (this.audio) {
            this.audio.currentTime = 0;
            this.audio.play().catch(() => {
                // Autoplay may be blocked until the user interacts with the
                // page at least once — safe to ignore.
            });
        }
    }

    _escape(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // Called from request_status.php's checkForNotifications() for each
    // new request_update notification returned by notifications.php.
    async sendRequestStatusNotification(data, userName) {
        if (!this.initialized) await this.initialize();

        const requestType = data?.request_type || 'Service Request';
        const status = data?.status ? String(data.status).replace(/_/g, ' ') : 'updated';
        const statusDisplay = status.charAt(0).toUpperCase() + status.slice(1);

        this._showToast({
            title: `📋 ${requestType} Update`,
            body: `Status changed to: ${statusDisplay}`,
            onClick: () => {
                window.location.href = '/public/user/request_status.php';
            }
        });
    }

    // Generic method for other notification types if needed later
    async sendGenericNotification(title, body, onClick) {
        if (!this.initialized) await this.initialize();
        this._showToast({ title, body, onClick });
    }
}

const notificationService = new NotificationService();
window.NotificationService = notificationService;

export default notificationService;
