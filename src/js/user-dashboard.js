// user-dashboard.js


// ----------------------------
// PUSH NOTIFICATION INIT
// ----------------------------
import('../../src/js/push-notification-service.js').then(module => {

    const pushService = module.default;

    if (window.currentUserId) {

        pushService.initialize();
        pushService.syncTokenAfterLogin();

    }

});



// ----------------------------
// ANDROID APP DOWNLOAD DETECTION
// ----------------------------
document.addEventListener('DOMContentLoaded', function() {

    setTimeout(function() {

        const downloadBtn = document.getElementById('android-download');

        if (!downloadBtn) return;

        const ua = navigator.userAgent.toLowerCase();

        const isCapacitor = !!(window.Capacitor &&
            (window.Capacitor.isNative ||
             window.Capacitor.getPlatform));

        const isCordova = !!(window.cordova || window.phonegap);

        const isWebView = /wv|webview|(android.*version\/\d+(\.\d+)*.*mobile)/i.test(ua);

        const isFileProtocol = window.location.protocol === 'file:';

        const isAndroidWebView =
            /; wv\)/.test(ua) ||
            /android.*applewebkit.*(?!chrome).*version\//i.test(ua);

        const isStandalone =
            window.navigator.standalone ||
            window.matchMedia('(display-mode: standalone)').matches;

        const isBarangayLinkApp =
            !!window.BarangayLinkApp ||
            document.documentElement.hasAttribute('data-app') ||
            localStorage.getItem('isBarangayLinkApp') === 'true';

        const hasCapacitorPlugins =
            !!(window.Capacitor && window.Capacitor.Plugins);

        let isAndroidPlatform = false;

        if (window.Capacitor && window.Capacitor.getPlatform) {

            isAndroidPlatform =
                window.Capacitor.getPlatform() === 'android';

        }

        const hasAppCookie =
            document.cookie.indexOf('barangaylink_app=true') !== -1;

        const isInApp =
            isCapacitor ||
            isCordova ||
            isWebView ||
            isFileProtocol ||
            isAndroidWebView ||
            isStandalone ||
            isBarangayLinkApp ||
            hasCapacitorPlugins ||
            isAndroidPlatform ||
            hasAppCookie;

        const isAndroid = /android/i.test(ua);
        const isMobile = /mobile|mobi|phone|tablet/i.test(ua);

        if (isAndroid && isMobile && !isInApp) {

            downloadBtn.style.display = 'block';

            console.log('Android browser detected - showing download button');

            sessionStorage.setItem('downloadButtonShown', 'true');

        } else {

            downloadBtn.style.display = 'none';

            console.log('In app or not Android - hiding button');

            if (isInApp) {

                localStorage.setItem('isBarangayLinkApp', 'true');

            }

        }

    }, 500);

});