# BarangayLink - Barangay Management System

## 📋 Overview
BarangayLink is a comprehensive web-based barangay management system designed to streamline communication and service delivery between barangay officials and residents. The system provides a digital platform for managing announcements, service requests, resident information, and generating reports.

## ✨ Features

### For Residents
- 🔐 **Secure Authentication** - Register and login with remember me functionality
- 📢 **Announcements** - View barangay announcements with real-time updates
- 📝 **Service Requests** - Submit various service requests (clearance, certificates, complaints)
- 📊 **Request Tracking** - Monitor status of submitted requests
- 👤 **Profile Management** - Update personal information and change password

### For Administrators
- 📊 **Dashboard** - Overview of system statistics and recent activities
- 📢 **Announcement Management** - Create, edit, and delete announcements
- 📋 **Service Request Management** - View and update request status
- 👥 **Resident Management** - View and manage resident accounts
- 📈 **Reports & Analytics** - Generate reports and export data
- 📥 **Data Export** - Export residents list and service requests to CSV

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache/Nginx
- **Additional**: AJAX for real-time updates

## 📁 Project Structure

```
barangaylink/
├── assets/               # Images, uploads, and icons
├── database/             # SQL database dump
│   └── barangaylink_db.sql
├── includes/             # Core PHP files
│   ├── config.php        # Configuration settings
│   ├── database.php      # Database connection class
│   ├── auth.php          # Authentication class
│   ├── functions.php     # Helper functions
│   └── ajax_auth.php     # AJAX authentication
├── public/               # Public-facing files
│   ├── index.php         # Login page
│   ├── register.php      # Registration page
│   ├── logout.php        # Logout handler
│   ├── admin/            # Admin panel
│   │   ├── dashboard.php
│   │   ├── announcements.php
│   │   ├── service_requests.php
│   │   ├── residents.php
│   │   ├── reports.php
│   │   ├── print_report.php
│   │   ├── export_residents.php
│   │   └── export_requests.php
│   └── user/             # User panel
│       ├── dashboard.php
│       ├── announcements.php
│       ├── service_request.php
│       ├── request_status.php
│       ├── profile.php
│       ├── get_announcements.php
│       └── check_announcements_updates.php
└── src/                  # Source files
    ├── css/
    │   └── main.css      # Single unified stylesheet
    └── js/
        ├── main.js       # Global JavaScript functionality
        └── mobile-menu.js # Mobile menu handler
```

## 🚀 Installation Guide

### Prerequisites
- XAMPP/WAMP/LAMP or any PHP development environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Edge, etc.)

### Step-by-Step Installation

1. **Clone or Download the Project**
   ```bash
   # If using git
   git clone https://github.com/yourusername/barangaylink.git
   
   # Or download and extract the ZIP file to your web server directory
   # For XAMPP: C:\xampp\htdocs\barangaylink\
   # For WAMP: C:\wamp64\www\barangaylink\
   # For Linux: /var/www/html/barangaylink/
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `barangaylink_db`
   - Import `database/barangaylink_db.sql` file

3. **Configure Database Connection**
   - Open `includes/config.php`
   - Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'barangaylink_db');
   ```

4. **Set Directory Permissions**
   ```bash
   # Make sure uploads directory is writable
   chmod 755 assets/uploads/
   ```

5. **Create Admin Account**
   - After importing the database, you need to set an admin password
   - Use this PHP code to generate a password hash:
   ```php
   <?php echo password_hash('yourpassword', PASSWORD_DEFAULT); ?>
   ```
   - Update the admin password in the users table

6. **Access the System**
   - Open browser and navigate to: `http://localhost/barangaylink/public/`
   - Default admin credentials (after setup):
     - Username: `admin`
     - Password: `[the password you set]`

## 👥 User Roles

### Administrator
- **Username**: admin
- **Email**: admin@barangaylink.com
- **Access**: Full system access, all management features

### Resident
- **Registration**: Self-registration through the register page
- **Access**: View announcements, submit requests, track status, manage profile

## 🎨 Design Features

- **Responsive Layout** - Works on desktop, tablet, and mobile devices
- **Mobile Menu** - Hamburger menu with slide-out sidebar on phones
- **Consistent UI** - Unified design language across all pages
- **Toast Notifications** - Non-intrusive feedback messages
- **Tooltips** - Helpful hints on interactive elements
- **Loading States** - Visual feedback during async operations
- **Print Styles** - Optimized printing for reports
- **Dark Mode Ready** - CSS variables support dark theme

## 🔧 Customization

### Changing Colors
Edit CSS variables in `src/css/main.css`:
```css
:root {
    --primary-color: #2c3e50;    /* Dark blue - headers, footer */
    --secondary-color: #3498db;   /* Bright blue - buttons, links */
    --success-color: #27ae60;     /* Green - success messages */
    --danger-color: #e74c3c;      /* Red - errors, delete */
    --warning-color: #f39c12;     /* Orange - warnings */
}
```

### Adding New Pages
1. Create new PHP file in appropriate directory (admin/ or user/)
2. Include necessary authentication:
   ```php
   require_once '../../includes/config.php';
   require_once '../../includes/functions.php';
   require_once '../../includes/auth.php';
   $auth = new Auth();
   $auth->requireAdmin(); // or requireUser()
   ```
3. Use existing HTML structure with container class
4. Add navigation links to navbar-menu

## 📱 Mobile Responsiveness

The system automatically adapts to different screen sizes:
- **Desktop (>768px)**: Full navigation menu, multi-column layouts
- **Tablet (576-768px)**: Collapsed sidebar, adjusted spacing
- **Mobile (<576px)**: Hamburger menu, stacked columns, optimized touch targets

## 🔒 Security Features

- **Password Hashing** - Using PHP's `password_hash()`
- **Session Management** - Secure session handling with regeneration
- **Remember Me Tokens** - Random 32-byte tokens stored in database
- **SQL Injection Prevention** - Input escaping via database class
- **XSS Protection** - `htmlspecialchars()` on all output
- **CSRF Ready** - Structure supports CSRF tokens
- **Access Control** - Strict role-based page access
- **HTTP-only Cookies** - For remember me functionality

## 🧪 Testing

Test the system with:
- Different user roles (admin vs resident)
- Multiple browser tabs (session handling)
- Mobile view (responsive design)
- Form validation (empty fields, invalid data)
- File uploads (if implemented)
- Report generation and printing

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check credentials in `includes/config.php`
- Verify MySQL service is running
- Ensure database exists

**Login Issues**
- Clear browser cookies
- Check if account is active in database
- Verify password hash is correct

**Mobile Menu Not Working**
- Ensure JavaScript files are loaded
- Check browser console for errors
- Verify viewport meta tag is present

**404 Errors on Links**
- Check file paths in navigation
- Verify .htaccess configuration
- Ensure correct base URL

## 📊 Performance Optimization

- **Single CSS file** - Reduces HTTP requests
- **Minified CSS** - Smaller file size
- **Optimized images** - Compressed assets
- **AJAX updates** - Partial page updates
- **Cache headers** - For static assets
- **Lazy loading** - For content where applicable

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a pull request

## 📄 License

This project is for educational and research purposes only.

## 👨‍💻 Developer Notes

- Built with vanilla PHP/JS/CSS - no frameworks
- Follows MVC-like pattern for organization
- Single entry point for authentication
- Consistent error handling
- Extensible architecture

## 📞 Support

For issues or questions:
- Check the troubleshooting section
- Review browser console for errors
- Verify database connectivity
- Check file permissions

## 🎯 Future Enhancements

- Email notifications
- SMS alerts
- Document uploads
- Payment integration
- Mobile app version
- API endpoints
- Multi-barangay support
- Advanced analytics
- Calendar integration
- Push notifications

---

**BarangayLink v1.0** - Making Barangay Services Accessible to Everyone! 🏘️