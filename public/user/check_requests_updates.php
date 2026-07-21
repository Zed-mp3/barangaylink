<?php
// public/user/check_requests_updates.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = $auth->getCurrentUser();
$since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$db = new Database();

// Build the WHERE clause
$where_clause = "user_id = {$user['id']} AND UNIX_TIMESTAMP(updated_at) > $since";
if ($status_filter) {
    $status_filter_escaped = $db->escape($status_filter);
    $where_clause .= " AND status = '$status_filter_escaped'";
}

// Check if there are any updates since the given timestamp
$sql = "SELECT COUNT(*) as count, MAX(UNIX_TIMESTAMP(updated_at)) as latest 
        FROM service_requests 
        WHERE $where_clause";

$result = $db->query($sql);
$row = $result->fetch_assoc();

$hasUpdates = $row['count'] > 0;

echo json_encode([
    'hasUpdates' => $hasUpdates,
    'count' => (int)$row['count'],
    'latest' => $row['latest'] ? (int)$row['latest'] : $since
]);