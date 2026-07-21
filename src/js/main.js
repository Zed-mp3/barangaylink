// main.js - Global JavaScript for BarangayLink System
document.addEventListener("DOMContentLoaded", () => {

    if(window.PushNotificationService){
        PushNotificationService.initialize();
    }

});

document.addEventListener('DOMContentLoaded', function() {
    console.log('BarangayLink JS initialized');
    
    // Initialize Android download button visibility
    initAndroidDownloadButton();
    
    // Initialize all components
    initAlerts();
    initForms();
    initTables();
    initTooltips();
    initModalWindows();
    initAutoRefresh();
    initFormValidation();
    initDataTables();
    
    // Handle splash screen if in Capacitor (but don't rely on import)
    handleSplashScreen();
});

/**
 * Handle splash screen for Capacitor (without imports)
 */
function handleSplashScreen() {
    // Check if we're in a Capacitor app by looking for the global object
    if (window.Capacitor) {
        console.log('Capacitor detected');
        
        // Try to hide splash screen after load
        window.addEventListener('load', function() {
            // Give it a moment, then try to hide splash
            setTimeout(function() {
                try {
                    // Try to access splash screen plugin if available
                    if (window.Capacitor.Plugins && 
                        window.Capacitor.Plugins.SplashScreen) {
                        window.Capacitor.Plugins.SplashScreen.hide();
                    } else if (window.SplashScreen) {
                        // Alternative method
                        window.SplashScreen.hide();
                    } else {
                        // If we can't hide it, at least we know we're in the app
                        console.log('In Capacitor app, splash screen handled natively');
                    }
                } catch (e) {
                    console.log('Splash screen handling not available');
                }
            }, 1000);
        });
    }
}

/**
 * Initialize Android download button - shows only when NOT in Capacitor app
 * and detects if user is on Android device for better UX
 */
/**
 * Initialize Android download button - shows only when NOT in the installed app
 */
function initAndroidDownloadButton() {
    const downloadBtn = document.getElementById('android-download');
    
    if (!downloadBtn) return;
    
    // MULTIPLE DETECTION METHODS FOR INSTALLED APP
    
    // Method 1: Check for Capacitor specific properties
    const isCapacitor = !!(window.Capacitor && 
                          (window.Capacitor.isNative || 
                           window.Capacitor.getPlatform === 'android' ||
                           window.Capacitor.getPlatform() === 'android'));
    
    // Method 2: Check for Cordova/PhoneGap
    const isCordova = !!(window.cordova || window.phonegap);
    
    // Method 3: Check for WebView specific user agents
    const ua = navigator.userAgent.toLowerCase();
    const isWebView = /wv|webview|(android.*version\/d+(\.\d+)*.*mobile)/i.test(ua);
    
    // Method 4: Check if running as file:// protocol (APK installed apps often use file://)
    const isFileProtocol = window.location.protocol === 'file:';
    
    // Method 5: Check for specific Android WebView indicators
    const isAndroidWebView = /; wv\)/.test(ua) || /android.*applewebkit.*(?!chrome).*version\//i.test(ua);
    
    // Method 6: Check if the app is in standalone mode (added to home screen)
    const isStandalone = window.navigator.standalone || 
                         window.matchMedia('(display-mode: standalone)').matches;
    
    // Method 7: Check for our custom app identifier (you can add this in your Capacitor app)
    const isBarangayLinkApp = !!window.BarangayLinkApp || 
                              document.documentElement.hasAttribute('data-app') ||
                              localStorage.getItem('isBarangayLinkApp') === 'true';
    
    // Method 8: Check screen dimensions (apps often have different dimensions)
    const isAppSize = window.screen.width < 500 && window.screen.height < 1000;
    
    // If ANY of these indicators are true, we're likely in the installed app
    if (isCapacitor || isCordova || isWebView || isFileProtocol || 
        isAndroidWebView || isStandalone || isBarangayLinkApp) {
        
        downloadBtn.style.display = 'none';
        console.log('Running inside installed app - hiding download button');
        
        // Optional: Set a flag to prevent future checks
        localStorage.setItem('isBarangayLinkApp', 'true');
        
        return;
    }
    
    // Running in web browser - check if Android device
    const isAndroid = /android/i.test(ua);
    const isMobile = /mobile|mobi|phone|tablet/i.test(ua);
    
    if (isAndroid && isMobile) {
        // Android device in browser - show download button
        downloadBtn.style.display = 'block';
        console.log('Android device detected in browser - showing download button');
        trackDownloadClick();
    } else {
        // Not Android or desktop - hide button
        downloadBtn.style.display = 'none';
    }
}


/**
 * Track download button clicks (optional - for analytics)
 */
function trackDownloadClick() {
    const downloadLink = document.querySelector('#android-download a');
    
    if (downloadLink) {
        downloadLink.addEventListener('click', function(e) {
            console.log('Redirecting to thank you page');
            
            // You can add Google Analytics or other tracking here
            if (typeof gtag !== 'undefined') {
                gtag('event', 'download_page_view', {
                    'event_category': 'app',
                    'event_label': 'thank_you_page'
                });
            }
        });
    }
}

// ===== Alert Messages =====
function initAlerts() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Add close buttons to alerts
    alerts.forEach(alert => {
        if (!alert.querySelector('.alert-close')) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'alert-close';
            closeBtn.innerHTML = '×';
            closeBtn.onclick = function() {
                alert.remove();
            };
            alert.appendChild(closeBtn);
        }
    });
}

// ===== Form Enhancements =====
function initForms() {
    // Add validation styling
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                    
                    // Add error message if not exists
                    let errorMsg = field.parentNode.querySelector('.form-error');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'form-error';
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.parentNode.querySelector('.form-error');
                    if (errorMsg) errorMsg.remove();
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Remove error styling on input
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorMsg = this.parentNode.querySelector('.form-error');
                if (errorMsg) errorMsg.remove();
            });
        });
    });
    
    // Password strength indicator - IMPROVED VERSION
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        // Check if we already set up this field
        if (field.dataset.passwordInit) return;
        field.dataset.passwordInit = 'true';
        
        // Create strength indicator container if it doesn't exist
        let indicator = field.closest('.form-group')?.querySelector('.password-strength');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'password-strength';
            indicator.style.display = 'none'; // Hidden by default
            // Insert after the password wrapper
            const formGroup = field.closest('.form-group');
            if (formGroup) {
                formGroup.appendChild(indicator);
            }
        }
        
        // Add input event with debounce for better performance
        let timeout;
        field.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                updatePasswordStrength(this);
            }, 100);
        });
        
        // Also check on blur
        field.addEventListener('blur', function() {
            updatePasswordStrength(this);
        });
        
        // Initial check if field has value
        if (field.value.trim() !== '') {
            updatePasswordStrength(field);
        }
    });
}

// Separate function to update password strength
function updatePasswordStrength(field) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    let indicator = formGroup.querySelector('.password-strength');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'password-strength';
        formGroup.appendChild(indicator);
    }
    
    const password = field.value;
    
    // Hide indicator if password is empty
    if (!password || password.length === 0) {
        indicator.style.display = 'none';
        indicator.textContent = '';
        indicator.className = 'password-strength';
        return;
    }
    
    // Show indicator
    indicator.style.display = 'block';
    
    // Calculate strength
    const strength = checkPasswordStrength(password);
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][strength] || 'Very Weak';
    const strengthClass = ['very-weak', 'weak', 'fair', 'good', 'strong'][strength] || 'very-weak';
    
    // Add visual feedback with requirements
    let requirements = [];
    if (password.length < 8) requirements.push('8+ characters');
    if (!password.match(/[a-z]+/)) requirements.push('lowercase');
    if (!password.match(/[A-Z]+/)) requirements.push('uppercase');
    if (!password.match(/[0-9]+/)) requirements.push('number');
    if (!password.match(/[$@#&!]+/)) requirements.push('special char');
    
    let message = `Strength: ${strengthText}`;
    if (requirements.length > 0 && strength < 3) {
        message += ` (Missing: ${requirements.join(', ')})`;
    }
    
    indicator.className = `password-strength ${strengthClass}`;
    indicator.textContent = message;
}

// Enhanced password strength checker
function checkPasswordStrength(password) {
    if (!password) return 0;
    
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Character variety checks
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    // Bonus for mixing character types
    const uniqueChars = new Set(password).size;
    if (uniqueChars > 5) strength = Math.min(strength + 0.5, 5);
    
    // Normalize to 0-5 scale
    return Math.min(Math.floor(strength), 5);
}

// ===== Table Enhancements =====
function initTables() {
    // Add sorting to table headers
    const tables = document.querySelectorAll('.table.sortable');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            if (!header.classList.contains('no-sort')) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
            }
        });
    });
    
    // Add row click handlers for expandable rows
    const expandableRows = document.querySelectorAll('.expandable-row');
    expandableRows.forEach(row => {
        row.addEventListener('click', function() {
            const nextRow = this.nextElementSibling;
            if (nextRow && nextRow.classList.contains('expandable-content')) {
                nextRow.classList.toggle('hidden');
            }
        });
    });
}

function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isNumeric = rows.every(row => !isNaN(row.children[column]?.textContent.trim()));
    
    rows.sort((a, b) => {
        const aVal = a.children[column]?.textContent.trim();
        const bVal = b.children[column]?.textContent.trim();
        
        if (isNumeric) {
            return parseFloat(aVal) - parseFloat(bVal);
        }
        return aVal.localeCompare(bVal);
    });
    
    // Toggle sort direction
    if (table.sortColumn === column) {
        rows.reverse();
        table.sortDirection = table.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        table.sortColumn = column;
        table.sortDirection = 'asc';
    }
    
    // Update header indicators
    const headers = table.querySelectorAll('th');
    headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
    headers[column].classList.add(`sort-${table.sortDirection}`);
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// ===== Tooltips =====
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.dataset.tooltip;
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.top = `${rect.top - 30}px`;
    tooltip.style.left = `${rect.left + (rect.width / 2)}px`;
    
    document.body.appendChild(tooltip);
    
    setTimeout(() => {
        tooltip.classList.add('show');
    }, 10);
    
    e.target._tooltip = tooltip;
}

function hideTooltip(e) {
    if (e.target._tooltip) {
        e.target._tooltip.remove();
        delete e.target._tooltip;
    }
}

// ===== Modal Windows =====
function initModalWindows() {
    // Open modal triggers - using data-modal attribute
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                if (modal.classList.contains('active')) {
                    return;
                } else {
                    openModal(modalId);
                }
            }
        });
    });
    
    // Close modal buttons
    const closeButtons = document.querySelectorAll('.modal-close, [data-modal-close]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                if (modal.classList.contains('active')) {
                    if (typeof window.closeModal === 'function') {
                        window.closeModal();
                    } else {
                        modal.classList.remove('active');
                    }
                } else {
                    modal.classList.remove('show');
                }
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close on overlay click
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.classList.contains('active')) {
                    if (typeof window.closeModal === 'function') {
                        window.closeModal();
                    } else {
                        this.classList.remove('active');
                    }
                } else {
                    this.classList.remove('show');
                }
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            const showModal = document.querySelector('.modal.show');
            
            if (activeModal) {
                if (typeof window.closeModal === 'function') {
                    window.closeModal();
                } else {
                    activeModal.classList.remove('active');
                }
                document.body.style.overflow = '';
            } else if (showModal) {
                showModal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    if (modalId === 'residentModal' && typeof window.openAddModal === 'function') {
        return;
    }
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    if (!modalId) {
        const activeModal = document.querySelector('.modal.active');
        const showModal = document.querySelector('.modal.show');
        
        if (activeModal) {
            activeModal.classList.remove('active');
        } else if (showModal) {
            showModal.classList.remove('show');
        }
    } else {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active', 'show');
        }
    }
    document.body.style.overflow = '';
}

// ===== Auto-refresh for Data Tables =====
function initAutoRefresh() {
    const refreshIntervals = document.querySelectorAll('[data-refresh]');
    refreshIntervals.forEach(element => {
        const interval = parseInt(element.dataset.refresh) * 1000;
        if (interval > 0) {
            setInterval(() => {
                refreshData(element);
            }, interval);
        }
    });
}

function refreshData(element) {
    const url = element.dataset.refreshUrl;
    if (!url) return;
    
    fetch(url)
        .then(response => response.text())
        .then(data => {
            element.innerHTML = data;
            showNotification('Data refreshed successfully', 'success');
        })
        .catch(error => {
            console.error('Refresh failed:', error);
            showNotification('Failed to refresh data', 'error');
        });
}

// ===== Form Validation =====
function initFormValidation() {
    // Email validation
    const emailFields = document.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateEmail(this);
        });
    });
    
    // Phone number validation
    const phoneFields = document.querySelectorAll('input[type="tel"], input[name*="contact"]');
    phoneFields.forEach(field => {
        field.addEventListener('blur', function() {
            validatePhone(this);
        });
    });
}

function validateEmail(field) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = emailRegex.test(field.value);
    
    if (field.value && !isValid) {
        field.classList.add('error');
        showFieldError(field, 'Please enter a valid email address');
    } else {
        field.classList.remove('error');
        removeFieldError(field);
    }
}

function validatePhone(field) {
    const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
    const isValid = phoneRegex.test(field.value);
    
    if (field.value && !isValid) {
        field.classList.add('error');
        showFieldError(field, 'Please enter a valid phone number');
    } else {
        field.classList.remove('error');
        removeFieldError(field);
    }
}

function showFieldError(field, message) {
    let errorMsg = field.parentNode.querySelector('.field-error');
    if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'field-error';
        field.parentNode.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
}

function removeFieldError(field) {
    const errorMsg = field.parentNode.querySelector('.field-error');
    if (errorMsg) errorMsg.remove();
}

// ===== Data Tables (Simple version) =====
function initDataTables() {
    const dataTables = document.querySelectorAll('.data-table');
    dataTables.forEach(table => {
        addSearchToTable(table);
        addPaginationToTable(table);
    });
}

function addSearchToTable(table) {
    const searchBox = document.createElement('input');
    searchBox.type = 'text';
    searchBox.placeholder = 'Search...';
    searchBox.className = 'form-control table-search';
    searchBox.style.marginBottom = '10px';
    searchBox.style.maxWidth = '300px';
    
    table.parentNode.insertBefore(searchBox, table);
    
    searchBox.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

function addPaginationToTable(table) {
    const rowsPerPage = 10;
    const rows = table.querySelectorAll('tbody tr');
    const pageCount = Math.ceil(rows.length / rowsPerPage);
    
    if (pageCount <= 1) return;
    
    const pagination = document.createElement('div');
    pagination.className = 'pagination';
    pagination.style.marginTop = '10px';
    pagination.style.textAlign = 'center';
    
    for (let i = 1; i <= pageCount; i++) {
        const btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-secondary';
        btn.textContent = i;
        btn.style.margin = '0 2px';
        
        btn.addEventListener('click', function() {
            const start = (i - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            
            rows.forEach((row, index) => {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });
            
            pagination.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
        
        pagination.appendChild(btn);
    }
    
    table.parentNode.appendChild(pagination);
    pagination.querySelector('button:first-child')?.click();
}

// ===== Notification System =====
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close">×</button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
    
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

// ===== Print Functionality =====
function printPage() {
    window.print();
}

// ===== Export to CSV =====
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => rowData.push('"' + col.textContent.trim() + '"'));
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// ===== Back to Top Button =====
function initBackToTop() {
    const btn = document.createElement('button');
    btn.id = 'back-to-top';
    btn.innerHTML = '↑';
    btn.title = 'Back to Top';
    
    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    });
    
    document.body.appendChild(btn);
}

// Initialize back to top button
initBackToTop();

// ===== Confirm Actions =====
function confirmAction(message, callback) {
    if (confirm(message || 'Are you sure?')) {
        callback();
    }
}

// Make functions globally available
window.BarangayLink = {
    showNotification,
    confirmAction,
    printPage,
    exportToCSV,
    openModal,
    closeModal,
    refreshData
};

// Don't override page-specific modal functions
if (typeof window.openAddModal !== 'function') {
    window.openAddModal = function() {
        // Default implementation if needed
    };
}

if (typeof window.closeModal !== 'function') {
    window.closeModal = function() {
        const modals = document.querySelectorAll('.modal.active, .modal.show');
        modals.forEach(modal => {
            modal.classList.remove('active', 'show');
        });
        document.body.style.overflow = '';
    };
}