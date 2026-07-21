// public/src/js/pull-to-refresh.js

/**
 * Pull to Refresh functionality
 * Handles touch-based pull to refresh for mobile devices
 */

window.BarangayLink = window.BarangayLink || {};

class PullToRefresh {
    constructor(options = {}) {
        this.container = options.container || document.querySelector('.main-content .container');
        this.ptrContainer = document.getElementById('ptrContainer');
        this.ptrText = document.getElementById('ptrText');
        this.onRefresh = options.onRefresh || (() => {});
        this.touchStartY = 0;
        this.touchMoveY = 0;
        this.pulling = false;
        
        this.init();
    }
    
    init() {
        if (!this.container || !this.ptrContainer) return;
        
        this.container.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                this.touchStartY = e.touches[0].clientY;
                this.pulling = true;
            }
        }, { passive: true });
        
        this.container.addEventListener('touchmove', (e) => {
            if (!this.pulling || window.scrollY > 0) return;
            
            this.touchMoveY = e.touches[0].clientY;
            const pullDistance = this.touchMoveY - this.touchStartY;
            
            if (pullDistance > 0 && pullDistance < 150) {
                this.ptrContainer.style.height = Math.min(pullDistance, 50) + 'px';
                
                if (pullDistance > 70) {
                    this.ptrText.textContent = 'Release to refresh';
                } else {
                    this.ptrText.textContent = 'Pull to refresh';
                }
            }
        }, { passive: true });
        
        this.container.addEventListener('touchend', (e) => {
            if (!this.pulling) return;
            
            const pullDistance = this.touchMoveY - this.touchStartY;
            
            if (pullDistance > 70 && window.scrollY === 0) {
                // Trigger refresh
                this.ptrContainer.style.height = '50px';
                this.ptrText.textContent = 'Refreshing...';
                this.onRefresh();
                
                setTimeout(() => {
                    this.ptrContainer.style.height = '0';
                }, 1000);
            } else {
                this.ptrContainer.style.height = '0';
            }
            
            this.pulling = false;
            this.touchStartY = 0;
            this.touchMoveY = 0;
        }, { passive: true });
    }
    
    // Reset the pull to refresh state
    reset() {
        if (this.ptrContainer) {
            this.ptrContainer.style.height = '0';
        }
        this.pulling = false;
        this.touchStartY = 0;
        this.touchMoveY = 0;
    }
}

// ============================================
// FIXED: Notification system with safe vibration
// ============================================
window.BarangayLink.showNotification = function(message, type = 'info') {
    console.log(message, type);
    
    // Safe vibration - only with user gesture
    try {
        if (navigator.vibrate) {
            const hasUserGesture = document.hasFocus() && 
                                   (document.activeElement !== document.body || 
                                    document.querySelector('*:focus'));
            
            if (hasUserGesture) {
                navigator.vibrate(50);
            } else {
                console.debug('Vibration skipped - no user gesture');
            }
        }
    } catch (e) {
        console.debug('Vibration not available:', e.message);
    }
    
    // Create a toast notification
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: ${type === 'error' ? 'var(--danger-color)' : type === 'success' ? 'var(--success-color)' : 'var(--info-color)'};
        color: white;
        padding: 12px 20px;
        border-radius: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 9999;
        font-size: 0.9rem;
        max-width: 90%;
        text-align: center;
        animation: slideUp 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
};

// Auto-refresh manager
class AutoRefreshManager {
    constructor(options = {}) {
        this.checkInterval = options.interval || 15000;
        this.onUpdate = options.onUpdate || (() => {});
        this.lastUpdate = options.lastUpdate || Math.floor(Date.now() / 1000);
        this.checkUrl = options.checkUrl || 'check_announcements_updates.php';
        this.intervalId = null;
        this.indicator = document.getElementById('refreshIndicator');
        this.newCount = 0;
        this.newCountBadge = document.getElementById('newCount');
        this.clickHint = document.getElementById('clickHint');
    }
    
    start() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        this.intervalId = setInterval(() => this.check(), this.checkInterval);
    }
    
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    check() {
        if (this.indicator) {
            this.indicator.classList.add('checking');
        }
        
        fetch(`${this.checkUrl}?since=${this.lastUpdate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // ONLY show if there are ACTUAL new updates
                if (data.hasUpdates && data.count > 0) {
                    this.newCount = data.count;
                    
                    if (this.newCountBadge) {
                        this.newCountBadge.textContent = '✨ ' + this.newCount + ' new';
                        this.newCountBadge.style.display = 'inline-block';
                        this.newCountBadge.classList.add('pulse');
                    }
                    
                    if (this.clickHint) {
                        // Only show hint if it's not already visible
                        if (this.clickHint.style.display !== 'block') {
                            this.clickHint.style.display = 'block';
                            // Auto-hide after 5 seconds
                            clearTimeout(window.hintTimeout);
                            window.hintTimeout = setTimeout(() => {
                                this.clickHint.style.display = 'none';
                            }, 5000);
                        }
                    }
                    
                    // Safe vibration
                    try {
                        if (navigator.vibrate && document.hasFocus()) {
                            navigator.vibrate(50);
                        }
                    } catch (e) {
                        console.debug('Vibration skipped:', e.message);
                    }
                    
                    this.onUpdate(data);
                } else {
                    // No updates - hide badge and hint
                    if (this.newCountBadge) {
                        this.newCountBadge.style.display = 'none';
                        this.newCountBadge.classList.remove('pulse');
                    }
                    if (this.clickHint) {
                        this.clickHint.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error checking updates:', error))
            .finally(() => {
                if (this.indicator) {
                    this.indicator.classList.remove('checking');
                }
            });
    }
    
    reset(newLastUpdate) {
        this.lastUpdate = newLastUpdate;
        this.newCount = 0;
        
        if (this.newCountBadge) {
            this.newCountBadge.style.display = 'none';
            this.newCountBadge.classList.remove('pulse');
        }
        
        if (this.clickHint) {
            this.clickHint.style.display = 'none';
            clearTimeout(window.hintTimeout);
        }
    }
    
    updateLastUpdate(timestamp) {
        this.lastUpdate = timestamp;
    }
}

// Export for use in other files
window.BarangayLink.PullToRefresh = PullToRefresh;
window.BarangayLink.AutoRefreshManager = AutoRefreshManager;

// Add CSS animation styles if not already in your CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        to {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
    }
`;
document.head.appendChild(style);

console.log('Pull to refresh and notification system loaded');