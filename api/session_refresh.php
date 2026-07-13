<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false]); exit(); }

if (empty($_SESSION['user_id']) && empty($_COOKIE['gs_refresh_token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'expired' => true]);
    exit();
}

$refreshToken = $_SESSION['refresh_token'] ?? $_COOKIE['gs_refresh_token'] ?? '';

if (empty($refreshToken)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'expired' => true]);
    exit();
}

$token = $_SESSION['access_token'] ?? '';
$needsRefresh = true;

if (!empty($token)) {
    $parts = explode('.', $token);
    $payload = @json_decode(base64_decode(str_pad(str_replace(['-','_'], ['+','/'], $parts[1] ?? ''), strlen($parts[1] ?? '') % 4, '=')), true);
    if ($payload && isset($payload['exp']) && $payload['exp'] > time() + 300) {
        $needsRefresh = false;
    }
}

if ($needsRefresh) {
    $result = supabaseRefreshToken($refreshToken);

    if (isset($result['access_token'])) {
        $_SESSION['access_token'] = $result['access_token'];
        $_SESSION['refresh_token'] = $result['refresh_token'] ?? $refreshToken;

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        setcookie('gs_refresh_token', $_SESSION['refresh_token'], [
            'expires' => time() + 604800,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure
        ]);

        echo json_encode(['success' => true, 'refreshed' => true]);
    } else {
        setcookie('gs_refresh_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        http_response_code(401);
        echo json_encode(['success' => false, 'expired' => true]);
    }
} else {
    echo json_encode(['success' => true, 'refreshed' => false]);
}
?>
