<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit(); }

require_once __DIR__ . '/../config/supabase.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

$result = supabaseAdminCreateUser($email, $password, $username);

if (isset($result['error']) || isset($result['msg'])) {
    $msg = $result['error'] ?? $result['msg'] ?? 'Unknown error';
    if (strpos($msg, 'already') !== false) {
        $msg = 'Email already registered';
    }
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

echo json_encode(['success' => true, 'message' => 'Account created! You can now login.']);
?>
