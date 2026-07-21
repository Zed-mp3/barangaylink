<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$auth = new Auth();

// This will automatically redirect if user tries to access admin page
$auth->requireAdmin();

$db = new Database();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=service_requests_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['ID', 'Resident Name', 'Request Type', 'Description', 'Status', 'Created Date', 'Last Updated']);

// Get requests data with resident names
$requests = $db->query("SELECT sr.*, u.full_name FROM service_requests sr JOIN users u ON sr.user_id = u.id ORDER BY sr.id");

// Add data rows
while ($row = $requests->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'],
        $row['request_type'],
        $row['description'],
        $row['status'],
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);
exit();
?>