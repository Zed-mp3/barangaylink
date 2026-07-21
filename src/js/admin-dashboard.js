// Update last login timestamp handled in PHP

let isRefreshing = false;
let lastRefreshTime = 0;
let pullToRefresh = null;
let lastUpdate = Math.floor(Date.now() / 1000);

document.addEventListener('DOMContentLoaded', function() {

    initPullToRefresh();

    refreshDashboard();

    setInterval(checkForUpdates, 30000);

});

function initPullToRefresh() {

    const ptrContainer = document.getElementById('ptrContainer');
    const ptrText = document.getElementById('ptrText');

    let startY = 0;
    let currentY = 0;
    let isDragging = false;
    let refreshThreshold = 80;

    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isInApp = !!window.Capacitor || !!window.cordova;

    if (!isMobile && !isInApp) return;

    function handleTouchStart(e) {

        if (document.querySelector('.mobile-sidebar.active')) return;

        if (window.scrollY > 20) return;

        startY = e.touches[0].clientY;
        isDragging = true;

    }

    function handleTouchMove(e) {

        if (e.target.closest('a, button, .mobile-sidebar, .navbar')) return;

        if (!isDragging || isRefreshing) return;

        currentY = e.touches[0].clientY;
        const diff = currentY - startY;

        if (diff > 0) {

            e.preventDefault();

            ptrContainer.classList.add('visible');

            if (diff > refreshThreshold) {
                ptrText.textContent = 'Release to refresh';
            } else {
                ptrText.textContent = 'Pull to refresh';
            }

        }

    }

    function handleTouchEnd() {

        if (!isDragging || isRefreshing) {

            ptrContainer.classList.remove('visible');
            return;

        }

        const diff = currentY - startY;

        if (diff > refreshThreshold) {

            refreshDashboard();

        } else {

            ptrContainer.classList.remove('visible');

        }

        isDragging = false;
        ptrText.textContent = 'Pull to refresh';

    }

    const container = document.querySelector('.main-content');

    container.addEventListener('touchstart', handleTouchStart, { passive:false });
    container.addEventListener('touchmove', handleTouchMove, { passive:false });
    container.addEventListener('touchend', handleTouchEnd);

}

function refreshDashboard() {

    if (isRefreshing) return;

    isRefreshing = true;

    const loading = document.getElementById('loading');
    const indicator = document.getElementById('refreshIndicator');
    const ptrContainer = document.getElementById('ptrContainer');
    const newCountBadge = document.getElementById('newCount');

    loading.classList.add('active');
    indicator.classList.add('checking');

    newCountBadge.style.display = 'none';
    newCountBadge.classList.remove('pulse');

    document.getElementById('clickHint').style.display = 'none';

    const timestamp = new Date().getTime();

    fetch('get_admin_dashboard_data.php?_=' + timestamp)
        .then(response => response.json())
        .then(data => {

            document.getElementById('totalResidents').textContent = data.total_residents;
            document.getElementById('totalRequests').textContent = data.total_requests;
            document.getElementById('pendingRequests').textContent = data.pending_requests;
            document.getElementById('totalAnnouncements').textContent = data.total_announcements;

            document.getElementById('requestsContainer').innerHTML = data.requests_html;

            document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();

            lastUpdate = Math.floor(Date.now() / 1000);

            if (window.BarangayLink && window.BarangayLink.showNotification) {

                window.BarangayLink.showNotification('Dashboard updated','success');

            }

        })
        .catch(error => {

            console.error(error);

        })
        .finally(() => {

            loading.classList.remove('active');
            indicator.classList.remove('checking');
            ptrContainer.classList.remove('visible');

            isRefreshing = false;

        });

}

function checkForUpdates() {

    if (isRefreshing) return;

    fetch('check_admin_updates.php?lastUpdate=' + lastUpdate)
        .then(response => response.json())
        .then(data => {

            if (data.has_updates && data.count > 0) {

                showNewCountBadge(data.count);

            }

        })
        .catch(error => console.error(error));

}

function showNewCountBadge(count) {

    const badge = document.getElementById('newCount');
    const hint = document.getElementById('clickHint');

    if (badge) {

        badge.textContent = `✨ ${count} new`;
        badge.style.display = 'inline-block';
        badge.classList.add('pulse');

    }

    if (hint) {

        hint.style.display = 'block';

        setTimeout(()=>{

            hint.style.display='none';

        },5000);

    }

}

document.querySelectorAll('.quick-action-btn').forEach(btn => {

    btn.addEventListener('click',function(){

        if(this.href && !this.classList.contains('no-loading')){

            this.innerHTML='<span class="icon">⏳</span><span>Loading...</span>';

        }

    });

});

window.refreshDashboard = refreshDashboard;