class PushNotificationService {
    constructor() {
        this.initialized = false;
        this.isNative = false;
    }

    async initialize() {
        // Check if we're in a native environment (Capacitor)
        if (window.Capacitor && window.Capacitor.isNative) {
            this.isNative = true;
            await this.initializeNative();
        } else {
            // Web browser fallback - use browser notifications
            await this.initializeWeb();
        }
    }

    // Native (Capacitor) initialization
    async initializeNative() {
        try {
            const { PushNotifications } = Capacitor.Plugins;
            
            const permission = await PushNotifications.requestPermissions();
            if (permission.receive !== 'granted') return;

            await PushNotifications.register();

            PushNotifications.addListener('registration', token => {
                fetch('/public/api/save-fcm-token.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        user_id: window.currentUserId || 0,
                        fcm_token: token.value,
                        device_type: 'android'
                    })
                });
            });

            PushNotifications.addListener('pushNotificationReceived', notification => {
                console.log("Push received", notification);
            });

            PushNotifications.addListener('pushNotificationActionPerformed', action => {
                const data = action.notification.data;
                if(data.type === "announcement") {
                    window.location.href = "/public/user/announcements.php";
                }
                if(data.type === "request_update") {
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

            // Request permission
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

    // Method to send web notification
    async sendAnnouncementNotification(data) {
        try {
            if (!this.initialized) {
                console.log('Notifications not initialized');
                return;
            }

            // Only works in web browser
            if (!this.isNative && 'Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification('📢 New Announcement', {
                    body: data.title || 'New announcement available',
                    icon: '/assets/1772429077726-removebg-preview.png',
                    tag: 'announcement-' + Date.now(),
                    requireInteraction: true
                });

                notification.onclick = function() {
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