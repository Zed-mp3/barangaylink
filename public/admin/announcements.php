<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$user = $auth->getCurrentUser();

$edit_mode = false;
$edit_id = 0;
$edit_title = '';
$edit_content = '';

/*
|--------------------------------------------------------------------------
| HANDLE ADD / EDIT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    /*
    |--------------------------------------------------------------------------
    | ADD ANNOUNCEMENT
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        $title   = $db->escape($_POST['title']);
        $content = $db->escape($_POST['content']);
        $status = isset($_POST['status']) ? $db->escape($_POST['status']) : 'draft';
        $publish_at = !empty($_POST['publish_at']) ? "'".$db->escape($_POST['publish_at'])."'" : "NULL";

        $sql = "INSERT INTO announcements
(title, content, created_by, status, publish_at, is_pinned)
VALUES ('$title','$content',{$user['id']},'$status',$publish_at,$is_pinned)";


        if ($db->query($sql)) {

            /*
            |--------------------------------------------------------------------------
            | SEND PUSH NOTIFICATION ONLY IF PUBLISHED
            |--------------------------------------------------------------------------
            */

            if ($status === 'published') {

                $tokens = [];

                $device_query = $db->query("
                    SELECT fcm_token
                    FROM user_devices
                    WHERE fcm_token IS NOT NULL
                ");

                while ($device = $device_query->fetch_assoc()) {
                    $tokens[] = $device['fcm_token'];
                }

                if (!empty($tokens)) {

                    $notification = [
                        'title' => '📢 New Announcement',
                        'body'  => $title,
                        'icon'  => 'ic_stat_icon'
                    ];

                    $data = [
                        'type' => 'announcement'
                    ];

                    sendFCMNotification($tokens, $notification, $data);
                }
            }

            echo "<script>
                sessionStorage.setItem('notification',
                JSON.stringify({message:'Announcement saved!', type:'success'}));
            </script>";

        } else {

            echo "<script>
                sessionStorage.setItem('notification',
                JSON.stringify({message:'Failed to save announcement', type:'error'}));
            </script>";
        }

        echo "<script>window.location.href='announcements.php';</script>";
        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT ANNOUNCEMENT
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $id      = (int)$_POST['id'];
        $title   = $db->escape($_POST['title']);
        $content = $db->escape($_POST['content']);
        $status  = isset($_POST['status']) ? $db->escape($_POST['status']) : 'draft';

        $sql = "UPDATE announcements
                SET title='$title', content='$content', status='$status'
                WHERE id=$id";

        if ($db->query($sql)) {

            echo "<script>
                sessionStorage.setItem('notification',
                JSON.stringify({message:'Announcement updated successfully!', type:'success'}));
            </script>";

        } else {

            echo "<script>
                sessionStorage.setItem('notification',
                JSON.stringify({message:'Failed to update announcement', type:'error'}));
            </script>";
        }

        echo "<script>window.location.href='announcements.php';</script>";
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| PUBLISH DRAFT
|--------------------------------------------------------------------------
*/

if (isset($_GET['publish'])) {

    $id = (int)$_GET['publish'];

    // Update status to published
    $sql = "UPDATE announcements SET status='published' WHERE id=$id";

    if ($db->query($sql)) {

        // Get announcement title
        $ann = $db->query("SELECT title FROM announcements WHERE id=$id")->fetch_assoc();
        $title = $ann['title'];

        // Get device tokens
        $tokens = [];

        $device_query = $db->query("
            SELECT fcm_token
            FROM user_devices
            WHERE fcm_token IS NOT NULL
        ");

        while ($device = $device_query->fetch_assoc()) {
            $tokens[] = $device['fcm_token'];
        }

        if (!empty($tokens)) {

            $notification = [
                'title' => '📢 New Announcement',
                'body'  => $title,
                'icon'  => 'ic_stat_icon'
            ];

            $data = [
                'type' => 'announcement'
            ];

            sendFCMNotification($tokens, $notification, $data);
        }

        echo "<script>
        sessionStorage.setItem('notification',
        JSON.stringify({message:'Announcement published!', type:'success'}));
        </script>";

    } else {

        echo "<script>
        sessionStorage.setItem('notification',
        JSON.stringify({message:'Failed to publish announcement', type:'error'}));
        </script>";
    }

    echo "<script>window.location.href='announcements.php';</script>";
    exit();
}

/*
|--------------------------------------------------------------------------
| DELETE ANNOUNCEMENT
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    $sql = "DELETE FROM announcements WHERE id=$id";

    if ($db->query($sql)) {

        echo "<script>
            sessionStorage.setItem('notification',
            JSON.stringify({message:'Announcement deleted successfully!', type:'success'}));
        </script>";

    } else {

        echo "<script>
            sessionStorage.setItem('notification',
            JSON.stringify({message:'Failed to delete announcement', type:'error'}));
        </script>";
    }

    echo "<script>window.location.href='announcements.php';</script>";
    exit();
}


/*
|--------------------------------------------------------------------------
| CHECK EDIT MODE
|--------------------------------------------------------------------------
*/

if (isset($_GET['edit'])) {

    $edit_id = (int)$_GET['edit'];

    $edit_query = $db->query("SELECT * FROM announcements WHERE id = $edit_id");

    if ($edit_query->num_rows > 0) {

        $edit_data = $edit_query->fetch_assoc();

        $edit_mode = true;
        $edit_title = $edit_data['title'];
        $edit_content = $edit_data['content'];
    }
}


/*
|--------------------------------------------------------------------------
| GET ANNOUNCEMENTS (ADMIN CAN SEE DRAFT + PUBLISHED)
|--------------------------------------------------------------------------
*/
$db->query("
UPDATE announcements
SET status='published'
WHERE publish_at IS NOT NULL
AND publish_at <= NOW()
AND status='draft'
");

$announcements = $db->query("
    SELECT a.*, u.full_name
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    ORDER BY a.is_pinned DESC, a.created_at DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - BarangayLink Admin</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
</head>
<body data-user-role="<?php echo $_SESSION['user_type']; ?>">
    <!-- Side Navigation (Admin Version) -->
    <div class="sidenav admin-sidenav">
        <div class="sidenav-header">
            <div class="sidenav-logo">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
            </div>
            <h2>BarangayLink</h2>
            <p>Admin Portal</p>
        </div>
        
        <div class="sidenav-user">
            <div class="sidenav-avatar admin-avatar">
                <?php 
                $initial = strtoupper(substr($user['full_name'], 0, 1));
                echo $initial;
                ?>
            </div>
            <div class="sidenav-user-info">
                <div class="sidenav-user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="sidenav-user-type admin-badge">Administrator</div>
            </div>
        </div>
        
        <ul class="sidenav-menu">
            <li class="sidenav-item">
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">📊</span>
                    <span class="sidenav-text">Dashboard</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="announcements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">📢</span>
                    <span class="sidenav-text">Announcements</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="service_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'service_requests.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">📋</span>
                    <span class="sidenav-text">Service Requests</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="residents.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">👥</span>
                    <span class="sidenav-text">Residents</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">📈</span>
                    <span class="sidenav-text">Reports</span>
                </a>
            </li>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
<li class="sidenav-item">
   <a href="registration_codes.php">
      <span class="sidenav-icon">🔑</span>
      <span class="sidenav-text">Registration Codes</span>
   </a>
</li>
<?php endif; ?>
            <li class="sidenav-item">
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">👤</span>
                    <span class="sidenav-text">Profile</span>
                </a>
            </li>
            <li class="sidenav-divider"></li>
            <li class="sidenav-item">
                <a href="../logout.php" class="logout-link">
                    <span class="sidenav-icon">🚪</span>
                    <span class="sidenav-text">Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="sidenav-footer">
            <p>&copy; 2026 BarangayLink</p>
            <p class="sidenav-version">Admin v1.0.0</p>
        </div>
    </div>

    <!-- Top Navbar (Mobile Only) -->
    <nav class="navbar mobile-only">
        <div class="navbar-brand">
            <a href="dashboard.php">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink" style="height: 30px; vertical-align: middle;">
                BarangayLink Admin
            </a>
        </div>
        <div class="user-info">
            <span class="user-avatar"><?php echo $initial; ?></span>
            <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>
    
    <div class="main-content">
        <div class="container">
            <h1>Manage Announcements</h1>
            
            <div class="card">
                <div class="card-header">
                    <?php echo $edit_mode ? 'Edit Announcement' : 'Add New Announcement'; ?>
                    <button onclick="toggleForm()" class="header-action-btn">
                        <?php echo $edit_mode ? 'Cancel' : 'Add New'; ?>
                    </button>
                </div>
                <div class="card-body" id="addForm" style="<?php echo $edit_mode ? 'display: block;' : 'display: none;'; ?>">
                    <form method="POST" action="" id="announcementForm">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>
                        <div class="form-group">
<label>
<input type="checkbox" name="is_pinned" value="1">
📌 Pin this announcement
</label>
</div>
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_title); ?>" data-tooltip="Enter announcement title">
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <div class="form-group">
<label>Schedule Publish (optional)</label>
<input type="datetime-local" name="publish_at" class="form-control">
</div>
                            <textarea name="content" class="form-control" rows="5" required data-tooltip="Enter announcement content"><?php echo htmlspecialchars($edit_content); ?></textarea>
                        </div>
                       <button type="submit" name="status" value="published" class="btn btn-success">
    <?php echo $edit_mode ? 'Update Announcement' : 'Publish Announcement'; ?>
</button>

<?php if (!$edit_mode): ?>
<button type="submit" name="status" value="draft" class="btn btn-secondary">
    Save as Draft
</button>
<?php endif; ?>
                        
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
    All Announcements

    <div class="announcement-tabs">
        <button class="tab-btn active" onclick="filterAnnouncements(event,'all')">All</button>
<button class="tab-btn" onclick="filterAnnouncements(event,'published')">Published</button>
<button class="tab-btn" onclick="filterAnnouncements(event,'draft')">Draft</button>
    </div>

    <span class="badge bg-info"><?php echo $announcements->num_rows; ?> total</span>
</div>
                <div class="card-body">
                    <?php if ($announcements->num_rows > 0): ?>
                        <?php while($row = $announcements->fetch_assoc()): ?>
<div class="announcement-item"
     data-id="<?php echo $row['id']; ?>"
     data-status="<?php echo $row['status']; ?>">
                                <h3 class="announcement-title">
                                <?php if($row['is_pinned']): ?>
<span class="badge bg-warning">📌 Pinned</span>
<?php endif; ?>
                                <?php echo htmlspecialchars($row['title']); ?></h3>
                                <div class="announcement-meta">
                                    <span>
<?php echo $row['status'] == 'draft' ? '📝 Draft' : '📢 Published'; ?>
</span>
                                    <span>👁 Views: <?php echo $row['views']; ?></span>
                                    <span>📝 Posted by <strong><?php echo htmlspecialchars($row['full_name']); ?></strong></span>
                                    <span>📅 <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?></span>
                                    <span>⏱️ <?php echo timeAgo($row['created_at']); ?></span>
                                </div>
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                                </div>
                                <div class="announcement-footer">
                                    <div class="announcement-actions">

<?php if ($row['status'] === 'draft'): ?>
<button onclick="publishAnnouncement(<?php echo $row['id']; ?>)"
        class="btn btn-success btn-sm">
🚀 Publish
</button>
<?php endif; ?>

<a href="?edit=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
✏️ Edit
</a>

<button onclick="confirmDelete(<?php echo $row['id']; ?>)"
        class="btn btn-danger btn-sm">
🗑️ Delete
</button>

</div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center p-20">
                            <p>No announcements yet.</p>
                            <button onclick="toggleForm()" class="btn btn-primary">Create First Announcement</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
   <script>
// Check for stored notification on page load
document.addEventListener('DOMContentLoaded', function() {
    const notification = sessionStorage.getItem('notification');
    if (notification) {
        const { message, type } = JSON.parse(notification);
        // Wait a tiny bit for everything to load
        setTimeout(() => {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification(message, type);
            } else {
                // Fallback if BarangayLink is not ready
                alert(message);
            }
        }, 100);
        // Clear the notification
        sessionStorage.removeItem('notification');
    }
});

function toggleForm() {
    const form = document.getElementById('addForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        // If we're toggling to show form in add mode, clear any edit parameters
        if (!<?php echo $edit_mode ? 'true' : 'false'; ?>) {
            document.querySelector('input[name="title"]').value = '';
            document.querySelector('textarea[name="content"]').value = '';
            document.querySelector('input[name="action"]').value = 'add';
        }
    } else {
        form.style.display = 'none';
        // If we're in edit mode and hiding form, redirect to clear edit mode
        if (<?php echo $edit_mode ? 'true' : 'false'; ?>) {
            window.location.href = 'announcements.php';
        }
    }
}

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) {
        window.location.href = '?delete=' + id;
    }
}

// Form validation
document.getElementById('announcementForm')?.addEventListener('submit', function(e) {
    const title = this.querySelector('input[name="title"]').value.trim();
    const content = this.querySelector('textarea[name="content"]').value.trim();
    
    if (!title || !content) {
        e.preventDefault();
        if (window.BarangayLink && window.BarangayLink.showNotification) {
            window.BarangayLink.showNotification('Please fill in all fields', 'error');
        } else {
            alert('Please fill in all fields');
        }
    }
    
});
function filterAnnouncements(event, status) {

    const items = document.querySelectorAll('.announcement-item');
    const tabs = document.querySelectorAll('.tab-btn');

    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    items.forEach(item => {

        const itemStatus = item.dataset.status;

        if (status === 'all') {
            item.style.display = 'block';
        }
        else if (status === itemStatus) {
            item.style.display = 'block';
        }
        else {
            item.style.display = 'none';
        }

    });

}
function publishAnnouncement(id) {

    if (confirm('Publish this announcement now? Residents will receive a notification.')) {

        window.location.href = '?publish=' + id;

    }

}
// ===== UNSAVED ANNOUNCEMENT WARNING (FINAL FIX) =====

window.isDirty = false;

// Detect typing
document.addEventListener('input', function(e){

    const form = document.getElementById('announcementForm');
    if (!form || !form.contains(e.target)) return;

    const title = form.querySelector('input[name="title"]').value.trim();
    const content = form.querySelector('textarea[name="content"]').value.trim();

    window.isDirty = (title !== '' || content !== '');

});

// Prevent refresh
window.addEventListener('beforeunload', function (e) {
    if (window.isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// ===== GLOBAL NAVIGATION GUARD (FIXED FOR EDIT MODE) =====
document.addEventListener('click', function(e){

    if (!window.isDirty) return;

    const target = e.target.closest('a, button');

    if (!target) return;

    // ❌ Ignore modal buttons
    if (target.closest('.modal')) return;

    // ❌ Ignore form submit (allow saving)
    if (target.type === 'submit') return;

    // ❌ Ignore continue editing
    if (target.id === 'continueEdit') return;

    let href = target.getAttribute('href');

    // ✅ ONLY block real navigation
    let isNavigation =
        (target.tagName === 'A' && href && !href.startsWith('#')) ||
        target.classList.contains('logout-link') ||
        target.closest('.sidenav') ||
        target.closest('.navbar');

    if (!isNavigation) return;

    e.preventDefault();
    e.stopPropagation();

    showDraftModal(() => {
        window.isDirty = false;

        if (target.tagName === 'A') {
            window.location.href = href;
        }
    });

}, true);

// Reset after submit
document.getElementById('announcementForm')?.addEventListener('submit', function(){
    window.isDirty = false;
});

// Modal
function showDraftModal(callback){

    if (document.querySelector('.modal.show')) return;

    const modal = document.createElement('div');
    modal.className = 'modal show';

    modal.innerHTML = `
        <div class="modal-content" style="max-width:400px; margin:auto; padding:20px;">
            <h3>📝 Unsaved Announcement</h3>
            <p>Do you want to finish your announcement later?</p>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:15px;">
                <button class="btn btn-secondary" id="continueEdit">Continue Editing</button>
                <button class="btn btn-danger" id="discard">Discard</button>
                <button class="btn btn-success" id="saveDraft">Save as Draft</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector('#continueEdit').onclick = () => {
        modal.remove();
    };

    modal.querySelector('#discard').onclick = () => {
        window.isDirty = false;
        modal.remove();
        callback();
    };

    modal.querySelector('#saveDraft').onclick = () => {

        const form = document.getElementById('announcementForm');

        let statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = 'draft';

        form.appendChild(statusInput);

        window.isDirty = false;
        form.submit();
    };

}
</script>

    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
</body>
</html>