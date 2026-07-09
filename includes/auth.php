<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['access_token'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'access_token' => $_SESSION['access_token']
    ];
}

function supabaseAuthHeaders($token) {
    return [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
}
?>
