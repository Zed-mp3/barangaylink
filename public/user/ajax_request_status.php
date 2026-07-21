<?php
// public/user/ajax_request_status.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    exit;
}

$db = new Database();
$user = $auth->getCurrentUser();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get user's requests
$where_clause = "WHERE user_id = {$user['id']}";
if ($status_filter) {
    $status_filter_escaped = $db->escape($status_filter);
    $where_clause .= " AND status = '$status_filter_escaped'";
}

$requests = $db->query("SELECT * FROM service_requests $where_clause ORDER BY created_at DESC");
?>

<div id="requests-container">
    <?php if ($requests->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table sortable" id="requestsTable">
                <thead>
                    <tr>
                        <th>Request Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $requests->fetch_assoc()): ?>
                    <tr data-request-id="<?php echo $row['id']; ?>" data-updated="<?php echo strtotime($row['updated_at']); ?>">
                        <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                        <td><?php echo getStatusBadge($row['status']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['updated_at'] != $row['created_at'] ? timeAgo($row['updated_at']) : 'Not updated'; ?></td>
                        <td>
                            <button onclick="toggleDetails(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm" data-tooltip="View details">👁️ View</button>
                        </td>
                    </tr>
                    <tr id="details-<?php echo $row['id']; ?>" class="hidden details-row">
                        <td colspan="5">
                            <div class="card" style="margin: 10px 0;">
                                <div class="card-body">
                                    <h4>Request Details</h4>
                                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                    <p><strong>Submitted:</strong> <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?></p>
                                    <?php if ($row['updated_at'] != $row['created_at']): ?>
                                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y h:i A', strtotime($row['updated_at'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <!-- Show admin notes/rejection reason if available -->
                                    <?php if (!empty($row['admin_notes'])): ?>
                                        <div class="admin-notes <?php echo strpos($row['admin_notes'], 'Rejection reason:') === 0 ? 'rejection' : 'info'; ?>">
                                            <strong>
                                                <?php if (strpos($row['admin_notes'], 'Rejection reason:') === 0): ?>
                                                    ❌ Rejection Reason:
                                                <?php else: ?>
                                                    📝 Admin Notes:
                                                <?php endif; ?>
                                            </strong>
                                            <p>
                                                <?php 
                                                // Remove the "Rejection reason: " prefix if present
                                                $notes = $row['admin_notes'];
                                                if (strpos($notes, 'Rejection reason:') === 0) {
                                                    $notes = substr($notes, 16); // Remove "Rejection reason: "
                                                }
                                                echo nl2br(htmlspecialchars($notes));
                                                ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">No service requests found.</p>
        <div class="text-center">
            <a href="service_request.php" class="btn btn-success">📝 Submit New Request</a>
        </div>
    <?php endif; ?>
</div>