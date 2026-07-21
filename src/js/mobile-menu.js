// Mobile Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing mobile menu');
    createMobileMenu();
    setupMobileMenu();
    setupLogoutHandlers(); // Add logout handler setup
});

function getBaseUrl() {
    // Get the current path to determine correct base URL
    const path = window.location.pathname;
    
    // Check if we're in admin or user folder
    if (path.includes('/admin/')) {
        return path.substring(0, path.indexOf('/admin/'));
    } else if (path.includes('/user/')) {
        return path.substring(0, path.indexOf('/user/'));
    }
    
    // If we're at root or other pages
    const lastSlash = path.lastIndexOf('/');
    if (lastSlash > 0) {
        return path.substring(0, lastSlash);
    }
    
    return '/public'; // Default fallback
}

function getCurrentPage() {
    const path = window.location.pathname;
    const filename = path.split('/').pop() || 'index.php';
    return filename;
}

function setupLogoutHandlers() {
    // This function will be called after menu is created
    // It sets up logout confirmation for any logout links
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    
    logoutLinks.forEach(link => {
        // Remove any existing click handlers
        link.removeEventListener('click', handleLogoutClick);
        // Add new click handler
        link.addEventListener('click', handleLogoutClick);
    });
}

function handleLogoutClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const logoutUrl = this.href;
    
    // Create custom confirmation dialog
    const confirmDialog = document.createElement('div');
    confirmDialog.className = 'logout-confirm-dialog';
    confirmDialog.innerHTML = `
        <div class="logout-confirm-content">
            <div class="logout-confirm-header">
                <h3>Confirm Logout</h3>
                <button class="logout-confirm-close">&times;</button>
            </div>
            <div class="logout-confirm-body">
                <p>Are you sure you want to logout?</p>
                <p class="logout-confirm-subtitle">You will need to login again to access your account.</p>
            </div>
            <div class="logout-confirm-footer">
                <button class="btn btn-secondary logout-cancel-btn">Cancel</button>
                <button class="btn btn-danger logout-confirm-btn">Yes, Logout</button>
            </div>
        </div>
    `;
    
    // Add styles if they don't exist
    if (!document.getElementById('logout-confirm-styles')) {
        const style = document.createElement('style');
        style.id = 'logout-confirm-styles';
        style.textContent = `
            .logout-confirm-dialog {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease;
            }
            
            .logout-confirm-content {
                background: white;
                border-radius: 12px;
                width: 90%;
                max-width: 400px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                animation: slideUp 0.3s ease;
            }
            
            .logout-confirm-header {
                padding: 20px;
                border-bottom: 1px solid #dddddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .logout-confirm-header h3 {
                margin: 0;
                color: #2c3e50;
                font-size: 1.2rem;
            }
            
            .logout-confirm-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #999;
                transition: color 0.3s;
                padding: 0 5px;
                line-height: 1;
            }
            
            .logout-confirm-close:hover {
                color: #e74c3c;
            }
            
            .logout-confirm-body {
                padding: 20px;
            }
            
            .logout-confirm-body p {
                margin: 0 0 10px;
                font-size: 1rem;
            }
            
            .logout-confirm-subtitle {
                color: #666;
                font-size: 0.9rem !important;
                margin-bottom: 0 !important;
            }
            
            .logout-confirm-footer {
                padding: 20px;
                border-top: 1px solid #dddddd;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }
            
            .logout-confirm-footer .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
                min-width: 100px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .logout-confirm-footer .btn-secondary {
                background: #95a5a6;
                color: white;
            }
            
            .logout-confirm-footer .btn-secondary:hover {
                background: #7f8c8d;
            }
            
            .logout-confirm-footer .btn-danger {
                background: #e74c3c;
                color: white;
            }
            
            .logout-confirm-footer .btn-danger:hover {
                background: #c0392b;
            }
            
            .logout-confirm-footer .btn-danger:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            /* Mobile styles */
            @media (max-width: 768px) {
                .logout-confirm-content {
                    width: 95%;
                    margin: 20px;
                }
                
                .logout-confirm-footer {
                    flex-direction: column;
                }
                
                .logout-confirm-footer .btn {
                    width: 100%;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(confirmDialog);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Close mobile menu if open
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    const menuBtn = document.getElementById('mobile-menu-btn');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (mobileSidebar && mobileSidebar.classList.contains('active')) {
        mobileSidebar.classList.remove('active');
        if (menuBtn) menuBtn.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = ''; // Reset temporarily, will be set again by dialog
    }
    
    // Handle close button
    const closeBtn = confirmDialog.querySelector('.logout-confirm-close');
    closeBtn.addEventListener('click', function() {
        closeLogoutDialog(confirmDialog);
    });
    
    // Handle cancel button
    const cancelBtn = confirmDialog.querySelector('.logout-cancel-btn');
    cancelBtn.addEventListener('click', function() {
        closeLogoutDialog(confirmDialog);
    });
    
    // Handle confirm button
    const confirmBtn = confirmDialog.querySelector('.logout-confirm-btn');
    confirmBtn.addEventListener('click', function() {
        // Show loading state
        confirmBtn.innerHTML = 'Logging out...';
        confirmBtn.disabled = true;
        
        // Close the mobile menu if it's open
        if (mobileSidebar) {
            mobileSidebar.classList.remove('active');
            if (menuBtn) menuBtn.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
        
        // Perform logout via AJAX
        fetch(logoutUrl + (logoutUrl.includes('?') ? '&' : '?') + 'confirm=true&ajax=true&t=' + Date.now())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    confirmDialog.innerHTML = `
                        <div class="logout-confirm-content" style="text-align: center;">
                            <div class="logout-confirm-body" style="padding: 40px 20px;">
                                <div style="font-size: 3rem; margin-bottom: 20px;">👋</div>
                                <h3 style="color: #27ae60; margin-bottom: 10px;">Logout Successful!</h3>
                                <p style="color: #666;">Redirecting you to home page...</p>
                                <div class="spinner-small" style="margin: 20px auto;"></div>
                            </div>
                        </div>
                    `;
                    
                    // Add spinner style if not exists
                    if (!document.getElementById('spinner-small-style')) {
                        const spinnerStyle = document.createElement('style');
                        spinnerStyle.id = 'spinner-small-style';
                        spinnerStyle.textContent = `
                            .spinner-small {
                                width: 30px;
                                height: 30px;
                                border: 3px solid #f3f3f3;
                                border-top: 3px solid #3498db;
                                border-radius: 50%;
                                animation: spin 1s linear infinite;
                            }
                            @keyframes spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                        `;
                        document.head.appendChild(spinnerStyle);
                    }
                    
                    // Redirect after delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    // Fallback to regular logout
                    window.location.href = logoutUrl;
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                // Fallback to regular logout
                window.location.href = logoutUrl;
            });
    });
    
    // Close on overlay click
    confirmDialog.addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutDialog(confirmDialog);
        }
    });
    
    // Close on escape key
    const escapeHandler = function(e) {
        if (e.key === 'Escape') {
            document.removeEventListener('keydown', escapeHandler);
            closeLogoutDialog(confirmDialog);
        }
    };
    document.addEventListener('keydown', escapeHandler);
}

function closeLogoutDialog(dialog) {
    if (dialog && document.body.contains(dialog)) {
        document.body.removeChild(dialog);
        document.body.style.overflow = '';
    }
}

function createMobileMenu() {
    console.log('Creating mobile menu...');
    
    // Check if mobile menu already exists
    if (document.querySelector('.mobile-sidebar')) {
        console.log('Mobile menu already exists');
        return;
    }

    // Get current user info from navbar
    const userInfo = document.querySelector('.user-info');
    let userName = 'Guest';
    if (userInfo) {
        const userText = userInfo.textContent.replace('Welcome,', '').replace('(Admin)', '').replace('(Resident)', '').trim();
        userName = userText || 'Guest';
    }
    
    const path = window.location.pathname;
    const isAdmin = path.includes('/admin/');
    const isUser = path.includes('/user/');
    const userType = isAdmin ? 'Administrator' : (isUser ? 'Resident' : 'Guest');
    
    // Get current page for active state
    const currentPage = getCurrentPage();
    const baseUrl = getBaseUrl();
    
    console.log('User info:', { userName, isAdmin, isUser, userType, baseUrl, currentPage, path });

    // Get navigation links based on user type
    let menuItems = '';
    
    if (isAdmin) {
    const isSuperAdmin = document.body.dataset.userRole === "admin";

    menuItems = `
        <li><a href="${baseUrl}/admin/dashboard.php"><span>📊</span> Dashboard</a></li>
        <li><a href="${baseUrl}/admin/announcements.php"><span>📢</span> Announcements</a></li>
        <li><a href="${baseUrl}/admin/service_requests.php"><span>📋</span> Service Requests</a></li>
        <li><a href="${baseUrl}/admin/residents.php"><span>👥</span> Residents</a></li>
        <li><a href="${baseUrl}/admin/reports.php"><span>📈</span> Reports</a></li>
        ${isSuperAdmin ? `<li><a href="${baseUrl}/admin/registration_codes.php"><span>🔑</span> Registration Codes</a></li>` : ``}
        <li><a href="${baseUrl}/admin/profile.php"><span>👤</span> Profile</a></li>
        <li class="mobile-sidebar-divider"></li>
        <li><a href="${baseUrl}/logout.php"><span>🚪</span> Logout</a></li>
    `;
} else if (isUser) {
        menuItems = `
            <li><a href="${baseUrl}/user/dashboard.php" class="${currentPage === 'dashboard.php' ? 'active' : ''}"><span>📊</span> Dashboard</a></li>
            <li><a href="${baseUrl}/user/announcements.php" class="${currentPage === 'announcements.php' ? 'active' : ''}"><span>📢</span> Announcements</a></li>
            <li><a href="${baseUrl}/user/service_request.php" class="${currentPage === 'service_request.php' ? 'active' : ''}"><span>📝</span> Request Service</a></li>
            <li><a href="${baseUrl}/user/request_status.php" class="${currentPage === 'request_status.php' ? 'active' : ''}"><span>📋</span> My Requests</a></li>
            <li><a href="${baseUrl}/user/profile.php" class="${currentPage === 'profile.php' ? 'active' : ''}"><span>👤</span> Profile</a></li>
            <li class="mobile-sidebar-divider"></li>
            <li><a href="${baseUrl}/logout.php" class="logout-link"><span>🚪</span> Logout</a></li>
        `;
    } else {
        // For public pages
        menuItems = `
            <li><a href="${baseUrl}/index.php" class="${currentPage === 'index.php' || currentPage === '' ? 'active' : ''}"><span>🏠</span> Home</a></li>
            <li><a href="${baseUrl}/login.php" class="${currentPage === 'login.php' ? 'active' : ''}"><span>🔑</span> Login</a></li>
            <li><a href="${baseUrl}/register.php" class="${currentPage === 'register.php' ? 'active' : ''}"><span>📝</span> Register</a></li>
        `;
    }

    // Create mobile sidebar HTML - Removed initial after name
    const mobileSidebar = document.createElement('div');
    mobileSidebar.className = 'mobile-sidebar';
    mobileSidebar.innerHTML = `
        <div class="mobile-sidebar-header">
            <div class="sidenav-logo">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
            </div>
            <h3>BarangayLink</h3>
            <p>${userType}</p>
        </div>
        <div class="mobile-user-info">
            <div class="mobile-user-name">${userName}</div>
            <div class="mobile-user-type">${userType}</div>
        </div>
        <ul class="mobile-sidebar-menu">
            ${menuItems}
        </ul>
    `;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';

    // Add to body
    document.body.appendChild(mobileSidebar);
    document.body.appendChild(overlay);
    console.log('Mobile menu and overlay added to DOM');

    // Add mobile menu button to navbar
    const navbar = document.querySelector('.navbar');
    const brand = document.querySelector('.navbar-brand');
    
    if (navbar && !document.querySelector('.mobile-menu-btn')) {
        const menuBtn = document.createElement('button');
        menuBtn.className = 'mobile-menu-btn';
        menuBtn.innerHTML = '☰';
        menuBtn.setAttribute('aria-label', 'Toggle menu');
        menuBtn.setAttribute('id', 'mobile-menu-btn');
        
        // Insert after brand
        if (brand) {
            brand.insertAdjacentElement('afterend', menuBtn);
            console.log('Menu button added after brand');
        } else {
            navbar.insertBefore(menuBtn, navbar.firstChild);
            console.log('Menu button added to navbar');
        }
    } else {
        console.log('Navbar not found or menu button already exists');
    }
    
    // Setup logout handlers for the new menu items
    setTimeout(setupLogoutHandlers, 100);
}

function setupMobileMenu() {
    console.log('Setting up mobile menu events...');
    
    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (!menuBtn) {
        console.log('Menu button not found, retrying in 500ms');
        setTimeout(setupMobileMenu, 500);
        return;
    }

    if (!mobileSidebar) {
        console.log('Mobile sidebar not found');
        return;
    }

    if (!overlay) {
        console.log('Overlay not found');
        return;
    }

    console.log('All mobile menu elements found');

    // Toggle menu
    menuBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Menu button clicked');
        
        this.classList.toggle('active');
        mobileSidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        if (mobileSidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            console.log('Menu opened');
        } else {
            document.body.style.overflow = '';
            console.log('Menu closed');
        }
    };

    // Close menu when clicking overlay
    overlay.onclick = function() {
        console.log('Overlay clicked');
        menuBtn.classList.remove('active');
        mobileSidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Close menu when clicking a link (except logout which is handled separately)
    const mobileLinks = mobileSidebar.querySelectorAll('a:not(.logout-link)');
    mobileLinks.forEach(link => {
        link.onclick = function() {
            console.log('Menu link clicked:', this.href);
            menuBtn.classList.remove('active');
            mobileSidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        };
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            if (mobileSidebar.classList.contains('active')) {
                menuBtn.classList.remove('active');
                mobileSidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });

    // Touch events for swipe
    let touchStartX = 0;
    let touchEndX = 0;

    mobileSidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, false);

    mobileSidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        if (touchEndX < touchStartX && (touchStartX - touchEndX) > 50) {
            console.log('Swipe detected - closing menu');
            menuBtn.classList.remove('active');
            mobileSidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }, false);
}

// Initialize on load
window.addEventListener('load', function() {
    console.log('Window loaded');
    if (document.querySelector('.navbar')) {
        createMobileMenu();
        setupMobileMenu();
    }
});

// Re-attach logout handlers when DOM changes (for dynamic content)
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
            setupLogoutHandlers();
        }
    });
});

// Start observing when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    observer.observe(document.body, { childList: true, subtree: true });
});