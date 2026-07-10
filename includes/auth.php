<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['access_token'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $token = $_SESSION['access_token'];
    $parts = explode('.', $token);
    $payload = json_decode(base64_decode(str_pad(str_replace(['-','_'], ['+','/'], $parts[1] ?? ''), strlen($parts[1] ?? '') % 4, '=')), true);
    if ($payload && isset($payload['exp']) && $payload['exp'] < time() && !empty($_SESSION['refresh_token'])) {
        $refreshResult = supabaseRefreshToken($_SESSION['refresh_token']);
        if (isset($refreshResult['access_token'])) {
            $_SESSION['access_token'] = $refreshResult['access_token'];
            $_SESSION['refresh_token'] = $refreshResult['refresh_token'] ?? $_SESSION['refresh_token'];
            $token = $refreshResult['access_token'];
        }
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'access_token' => $token
    ];
}

function supabaseAuthHeaders($token) {
    return [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Prefer: return=representation'
    ];
}
?>
