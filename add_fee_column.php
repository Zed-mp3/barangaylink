<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';

$db = new Database();

// Add fee column to service_requests table
$sql = "ALTER TABLE service_requests ADD COLUMN fee DECIMAL(10,2) DEFAULT 0.00 AFTER description;";

if ($db->query($sql)) {
    echo "Fee column added successfully to service_requests table.\n";
} else {
    echo "Error adding fee column: " . $db->error . "\n";
}
?>