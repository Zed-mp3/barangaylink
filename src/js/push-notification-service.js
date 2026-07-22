class PushNotificationService {
    constructor() {
        this.initialized = false;
        this.initializing = false;
        this.isNative = false;
        this.lastToken = null;
    }

    // Modern Capacitor (v3+) replaced the `.isNative` boolean with
    // `.isNativePlatform()`. Support both so this works regardless of version.
    _detectNative() {
        if (!window.Capacitor) return false;
        if (typeof window.Capacitor.isNativePlatform === 'function') {
            return window.Capacitor.isNativePlatform();
        }
        return !!window.Capacitor.isNative;
    }

    async initialize() {
        // Prevent duplicate init — this gets called from main.js,
        // user-dashboard.js, AND the auto-init at the bottom of this file.
        if (this.initialized || this.initializing) {
            console.log('Push service already initialized/initializing — skipping');
            return;
        }
        this.initializing = true;

        if (this._detectNative()) {
            this.isNative = true;
            await this.initializeNative();
        } else {
            await this.initializeWeb();
        }

        this.initializing = false;
    }

    // Native (Capacitor) initialization
    async initializeNative() {
        try {
            const { PushNotifications } = Capacitor.Plugins;

            const permission = await PushNotifications.requestPermissions();
            if (permission.receive !== 'granted') {
                console.warn('Push permission not granted:', permission);
                return;
            }

            await PushNotifications.register();

            PushNotifications.addListener('registration', token => {
                console.log('FCM TOKEN:', token.value);
                this.lastToken = token.value;
                this._saveToken(token.value);
            });

            PushNotifications.addListener('registrationError', err => {
                console.error('Push registration error:', err);
            });

            PushNotifications.addListener('pushNotificationReceived', notification => {
                console.log("Push received", notification);
            });

            PushNotifications.addListener('pushNotificationActionPerformed', action => {
                const data = action.notification.data;
                if (data.type === "announcement") {
                    window.location.href = "/public/user/announcements.php";
                }
                if (data.type === "request_update") {
                    window.location.href = "/public/user/request_status.php";
                }
            });

            this.initialized = true;
            console.log('Native push notifications initialized');
        } catch (error) {
            console.error('Error initializing native push:', error);
        }
    }

    // Web browser initialization (fallback)
    async initializeWeb() {
        try {
            if (!('Notification' in window)) {
                console.log('Browser notifications not supported');
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.log('Notification permission denied');
                return;
            }

            this.initialized = true;
            console.log('Web notifications initialized');
        } catch (error) {
            console.error('Error initializing web notifications:', error);
        }
    }

    // Sends/re-sends the current token to the backend with the current user_id.
    // Called on its own (e.g. registration listener) or externally after login,
    // in case the token was generated before window.currentUserId was available.
    async _saveToken(token) {
        const userId = window.currentUserId || 0;

        if (!userId) {
            console.warn('No currentUserId available yet — skipping token save.');
            return;
        }

        try {
            const res = await fetch('/public/api/save-fcm-token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: userId,
                    fcm_token: token,
                    device_type: 'android'
                })
            });
            const data = await res.json();
            if (!data.success) {
                console.error('Failed to save FCM token:', data);
            } else {
                console.log('FCM token saved successfully');
            }
        } catch (err) {
            console.error('Error saving FCM token:', err);
        }
    }

    // Public method called from user-dashboard.js after login to make sure
    // the token on file is associated with the now-known user_id.
    // Previously missing entirely, which caused an uncaught TypeError.
    async syncTokenAfterLogin() {
        if (!window.currentUserId) {
            console.warn('syncTokenAfterLogin called but currentUserId not set yet');
            return;
        }

        // Not initialized yet at all — just run the normal flow.
        if (!this.initialized && !this.initializing) {
            await this.initialize();
            return;
        }

        // Already have a token from this session — resend it now that we
        // have the correct user_id (covers the case where registration
        // fired before login/user_id was available).
        if (this.isNative && this.lastToken) {
            await this._saveToken(this.lastToken);
        }
    }

    // Method to send web notification
    async sendAnnouncementNotification(data) {
        try {
            if (!this.initialized) {
                console.log('Notifications not initialized');
                return;
            }

            if (!this.isNative && 'Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification('📢 New Announcement', {
                    body: data.title || 'New announcement available',
                    icon: '/assets/1772429077726-removebg-preview.png',
                    tag: 'announcement-' + Date.now(),
                    requireInteraction: true
                });

                notification.onclick = function () {
                    window.focus();
                    this.close();
                    window.location.href = '/public/user/announcements.php';
                };

                setTimeout(() => {
                    notification.close();
                }, 10000);
            }
        } catch (error) {
            console.error('Error sending notification:', error);
        }
    }
}

// Create instance and make it globally available
const pushNotificationService = new PushNotificationService();
window.PushNotificationService = pushNotificationService;

// Auto-initialize when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        pushNotificationService.initialize();
    });
} else {
    pushNotificationService.initialize();
}

// Export for ES modules
export default pushNotificationService;
