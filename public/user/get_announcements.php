<?php
// public/user/get_announcements.php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_auth.php';

// Authentication
$auth = requireAjaxAuth('user');

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get published announcements
$announcements = $db->query("
SELECT a.*, u.full_name
FROM announcements a
JOIN users u ON a.created_by = u.id
WHERE a.status = 'published'
ORDER BY a.is_pinned DESC, a.created_at DESC
");

// Timestamp for refresh system
$last_updated = time();

if ($announcements->num_rows > 0):

while ($row = $announcements->fetch_assoc()):

$announcement_id = (int)$row['id'];

/*
|--------------------------------------------------------------------------
| TRACK VIEW
|--------------------------------------------------------------------------
*/

// Track view only once per session
if (!isset($_SESSION['viewed_announcements'])) {
    $_SESSION['viewed_announcements'] = [];
}

if (!in_array($announcement_id, $_SESSION['viewed_announcements'])) {

    $db->query("
    UPDATE announcements
    SET views = views + 1
    WHERE id = $announcement_id
    ");

    $_SESSION['viewed_announcements'][] = $announcement_id;
}

/*
|--------------------------------------------------------------------------
| TRACK READ
|--------------------------------------------------------------------------
*/

$db->query("
INSERT IGNORE INTO announcement_reads
(announcement_id, user_id)
VALUES ($announcement_id, $user_id)
");

?>

<div class="announcement-item"
     data-id="<?php echo $row['id']; ?>"
     data-updated="<?php echo strtotime($row['created_at']); ?>">

    <h3 class="announcement-title">

        <?php if (!empty($row['is_pinned'])): ?>
            <span class="badge bg-warning">📌 Pinned</span>
        <?php endif; ?>

        <?php echo htmlspecialchars($row['title']); ?>

    </h3>

    <div class="announcement-meta">

        <span>
            📝 Posted by
            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
        </span>

        <span>
            📅 <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?>
        </span>

        <span>
            ⏱️ <?php echo timeAgo($row['created_at']); ?>
        </span>

        <span>
            👁 <?php echo (int)$row['views']; ?> views
        </span>

    </div>

    <div class="announcement-content">
        <?php echo nl2br(htmlspecialchars($row['content'])); ?>
    </div>

</div>

<?php
endwhile;

else:
?>

<div class="text-center p-20">
    <p class="text-muted">No announcements available at the moment.</p>
</div>

<?php
endif;
?>

<!-- Hidden timestamp used by auto refresh -->
<input type="hidden" id="last-updated-timestamp" value="<?php echo $last_updated; ?>">