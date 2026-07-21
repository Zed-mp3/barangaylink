<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = new Database();

$user_id = $_POST['user_id'];

$db->query("
    UPDATE user_devices
    SET last_active = NOW()
    WHERE user_id = '$user_id'
");

echo json_encode(["success"=>true]);