document.addEventListener("DOMContentLoaded", () => {

  if (!window.Capacitor || !window.Capacitor.isNative) return;

  const { PushNotifications } = Capacitor.Plugins;

  PushNotifications.requestPermissions().then(permission => {
    if (permission.receive === 'granted') {
      PushNotifications.register();
    } else {
      console.warn("Push permission not granted:", permission);
    }
  }).catch(err => {
    console.error("Push permission request failed:", err);
  });

  PushNotifications.addListener('registration', token => {
    console.log("FCM TOKEN:", token.value);

    // window.currentUserId is set inline in dashboard.php before this script loads
    const userId = window.currentUserId || 0;

    if (!userId) {
      console.warn("No currentUserId available yet — skipping token save.");
      return;
    }

    fetch("https://barangaylink-2.onrender.com/public/api/save-fcm-token.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        user_id: userId,
        fcm_token: token.value,
        device_type: "android"
      })
    })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Failed to save FCM token:", data);
      } else {
        console.log("FCM token saved successfully");
      }
    })
    .catch(err => {
      console.error("Error saving FCM token:", err);
    });
  });

  PushNotifications.addListener('registrationError', err => {
    console.error("Push registration error:", err);
  });

});
