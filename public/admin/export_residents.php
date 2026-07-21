<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$auth = new Auth();

// This will automatically redirect if user tries to access admin page
$auth->requireAdmin();

$db = new Database();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=residents_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['ID', 'Username', 'Full Name', 'Email', 'Contact Number', 'Address', 'Status', 'Registered Date']);

// Get residents data
$residents = $db->query("SELECT id, username, full_name, email, contact_number, address, status, created_at FROM users WHERE user_type='resident' ORDER BY id");

// Add data rows
while ($row = $residents->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>