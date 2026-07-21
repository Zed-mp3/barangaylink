import { BackgroundFetch } from '@ionic-native/background-fetch';
import { LocalNotifications } from '@capacitor/local-notifications';

class BackgroundService {
    async initialize() {
        if (!window.cordova) return;

        try {
            // Configure background fetch
            BackgroundFetch.configure({
                minimumFetchInterval: 15, // 15 minutes
                stopOnTerminate: false,
                startOnBoot: true,
                enableHeadless: true,
            }, async () => {
                // Background task runs here
                await this.checkForUpdates();
                BackgroundFetch.finish();
            }, (error) => {
                console.error('BackgroundFetch error:', error);
            });

            console.log('Background service initialized');
        } catch (error) {
            console.error('Failed to initialize background service:', error);
        }
    }

    async checkForUpdates() {
        try {
            const response = await fetch('https://barangaylink.sbs/public/api/background-check.php');
            const data = await response.json();

            if (data.newAnnouncements?.length > 0) {
                for (const ann of data.newAnnouncements) {
                    await LocalNotifications.schedule({
                        notifications: [{
                            title: '📢 New Announcement',
                            body: ann.title,
                            id: Math.floor(Math.random() * 10000),
                            schedule: { at: new Date() },
                            smallIcon: 'ic_stat_icon',
                            iconColor: '#2c3e50'
                        }]
                    });
                }
            }

            if (data.newNotifications?.length > 0) {
                for (const notif of data.newNotifications) {
                    await LocalNotifications.schedule({
                        notifications: [{
                            title: notif.title,
                            body: notif.message,
                            id: notif.id,
                            schedule: { at: new Date() },
                            smallIcon: 'ic_stat_icon',
                            iconColor: '#2c3e50'
                        }]
                    });
                }
            }
        } catch (error) {
            console.error('Background check failed:', error);
        }
    }
}

export default new BackgroundService();