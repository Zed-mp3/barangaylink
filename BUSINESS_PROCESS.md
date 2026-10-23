# BarangayLink Business Process Documentation

## A. System Business Process Overview

BarangayLink is a web-based community management system that enables residents to submit service requests, receive announcements, and interact with barangay officials. The system supports three user roles: residents (regular citizens), officials (barangay officials), and administrators (system administrators). Residents can submit various types of service requests (like barangay clearances, certificates of residency) and track their status. Officials and administrators can manage these requests, post announcements, and generate reports. The system uses PHP with MySQL backend and implements role-based access control.

## B. Actors and Responsibilities

| Actor | Responsibilities |
| ----- | ---------------- |
| Resident/User | Register account, login/logout, submit service requests, view request status, view announcements, update profile |
| Barangay Official | Login/logout, view dashboard, manage service requests (view/update status), post announcements, view residents list |
| Administrator | Login/logout, access all official functions, manage registration codes, export data, generate reports, full system administration |

## C. Main Business Processes

| Process | Actor | Input | System Process | Output |
| ------- | ----- | ----- | -------------- | ------ |
| User Registration | Visitor | Registration form data (username, email, password, full name, address, contact, user type, secret code for admin/official) | Validate input → Check username/email uniqueness → Hash password → Insert into users table → Log registration activity | Success message → Redirect to login page OR Error message (validation failure) |
| User Login | Visitor | Username/email and password | Validate credentials → Create session → Set remember token if requested → Log login activity → Redirect based on user type (admin/dashboard or user/dashboard) | Access to appropriate dashboard OR Error message (invalid credentials) |
| Submit Service Request | Resident | Request type and description | Validate input → Insert into service_requests table with status 'pending' → Log activity | Request submitted successfully → Notification shown |
| View Service Request Status | Resident | None (automatic from session) | Retrieve user's service requests from database ordered by creation date | Display list of user's requests with status |
| Manage Service Requests | Official/Admin | Request ID and new status (optional) | Retrieve request details → Update status if provided → Log activity | Display request details and option to update status |
| Post Announcement | Official/Admin | Announcement title and content | Insert into announcements table with creator ID → Log activity | Announcement created successfully → Added to announcements list |
| View Announcements | Resident/Official/Admin | None | Retrieve all announcements ordered by creation date | Display list of announcements |
| Generate Reports | Administrator | Report type selection | Query relevant data based on report type → Format for display/export | Display report data → Option to export |
| Logout | Any authenticated user | None | Clear remember token → Log logout activity → Destroy session → Redirect to login page | User logged out successfully |

## D. Detailed Business Process Flow

START
↓
User visits homepage
↓
[Already logged in?]
├── NO → Show login/register options
└── YES
   ↓
   [Is admin/official?]
   ├── NO → Redirect to user/dashboard.php
   └── YES → Redirect to admin/dashboard.php
↓
[At login page]
├── User attempts login
│   ↓
│   [Credentials valid?]
│   ├── NO → Show error → Try again
│   └── YES
│      ↓
│      [Remember me checked?]
│      ├── YES → Set remember token (30 days)
│      └── NO → Skip remember token
│      ↓
│      Log login activity
│      ↓
│      [User type admin/official?]
│      ├── YES → Redirect to admin dashboard
│      └── NO → Redirect to user dashboard
│      ↓
├── User attempts registration
│   ↓
│   [Passwords match?]
│   ├── NO → Show error → Try again
│   └── YES
│      ↓
│      [User type resident?]
│      ├── NO → [Secret code valid?]
│      │      ├── NO → Show error → Try again
│      │      └── YES → Continue
│      └── YES → Skip secret code validation
│      ↓
│      [Username/email unique?]
│      ├── NO → Show error → Try again
│      └── YES
│         ↓
│         Hash password
│         ↓
│         Insert user record
│         ↓
│         Log registration activity
│         ↓
│         Show success message
│         ↓
│         Auto-redirect to login after 2 seconds
│         ↓
└── User navigating authenticated areas
    ↓
    [Session valid? (active & <8 hours)?]
    ├── NO → Force logout → Redirect to login
    └── YES
       ↓
       [Accessing admin-only page?]
       ├── NO → Allow access
       └── YES
          ↓
          [Is admin/official?]
          ├── NO → Force logout → Redirect to login
          └── YES → Allow access
       ↓
       [Performing action (submit request, etc.)]
       ↓
       Process action → Update relevant database table(s) → Log activity
       ↓
       Show result/feedback
       ↓
       Continue navigation
       ↓
[User initiates logout]
   ↓
   Clear remember token
   ↓
   Log logout activity
   ↓
   Destroy session
   ↓
   Redirect to login page
   ↓
END

## E. Database Tables Involved

| Process | Database Table | Purpose |
| ------- | -------------- | ------- |
| User Registration | users | Stores user account information including credentials, profile data, role, and status |
| User Login | users, user_logs | users for credential verification; user_logs to track login activities |
| Remember Me Functionality | users (remember_token, token_expiry fields) | Stores persistent login tokens for automatic login |
| Submit Service Request | service_requests | Stores resident service requests with status tracking |
| View Service Request Status | service_requests | Retrieves resident's requests for display |
| Manage Service Requests | service_requests | Updates request status and retrieves request details |
| Post Announcement | announcements | Stores community announcements with creator reference |
| View Announcements | announcements | Retrieves all announcements for display |
| Generate Reports | users, service_requests, announcements, user_logs | Various tables queried based on report type |
| Logout | users, user_logs | users to clear remember token; user_logs to track logout activity |

## F. Presentation/Defense Explanation

BarangayLink is a community management system designed to bridge the gap between barangay residents and their local government. The system operates on a simple three-role architecture: residents who need services, barangay officials who provide those services, and administrators who manage the system itself.

Residents begin by registering for an account, providing basic information and selecting their role. For security, official and administrator roles require special secret codes that are distributed by barangay authorities. Once registered, users can log in using their username or email and password, with an optional "remember me" feature that maintains their login for 30 days.

After logging in, residents are directed to their personal dashboard where they can submit various types of service requests—such as barangay clearances for employment or certificates of residency for school enrollment. Each request is tracked through its lifecycle from submission to completion, with residents able to check the status at any time.

Barangay officials and administrators access an administrative dashboard where they can view all incoming service requests, update their status as they work on them, and post important community announcements. Officials can also view the list of registered residents in their barangay. Administrators have additional capabilities including exporting data, generating reports on service request trends, and managing registration codes for new official accounts.

The system maintains security through session management that automatically logs users out after 8 hours of inactivity, and all user activities are logged for audit purposes. When users log out explicitly, the system clears all temporary authentication data to ensure their privacy.

Overall, BarangayLink streamlines the traditional barangay request process by making it accessible online, reducing the need for physical visits to the barangay hall while maintaining proper oversight and accountability through role-based access controls and comprehensive tracking.