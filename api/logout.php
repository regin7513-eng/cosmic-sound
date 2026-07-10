<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

session_unset();
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out']);
?>
