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
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit();
}

$result = supabaseSignIn($email, $password);

if (isset($result['error'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

if (isset($result['access_token'])) {
    $userId = $result['user']['id'] ?? '';
    $username = $result['user']['user_metadata']['username'] ?? '';

    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['username'] = $username;
    $_SESSION['access_token'] = $result['access_token'];
    $_SESSION['refresh_token'] = $result['refresh_token'] ?? '';

    $profile = supabaseQuery('user_profiles', 'GET', null, ['id' => 'eq.' . $userId, 'select' => '*'], true);
    if (isset($profile['data'][0]['username'])) {
        $_SESSION['username'] = $profile['data'][0]['username'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => ['id' => $userId, 'email' => $email, 'username' => $_SESSION['username']]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Login failed']);
}
?>
