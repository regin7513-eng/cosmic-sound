<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/cache.php';

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cacheKey = 'favorites_' . $user['user_id'];
    $cached = cacheGet($cacheKey, 120);
    if ($cached !== null) {
        echo json_encode($cached);
        exit();
    }
    $result = supabaseQuery('favorites', 'GET', null, [
        'user_id' => 'eq.' . $user['user_id'],
        'select' => '*',
        'order' => 'created_at.desc'
    ], true);
    $response = ['success' => true, 'data' => $result['data'] ?? []];
    cacheSet($cacheKey, $response);
    echo json_encode($response);
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result = supabaseQuery('favorites', 'POST', [
        'user_id' => $user['user_id'],
        'track_id' => $input['track_id'] ?? '',
        'title' => $input['title'] ?? '',
        'artist' => $input['artist'] ?? '',
        'album' => $input['album'] ?? '',
        'cover_image' => $input['cover_image'] ?? '',
        'track_url' => $input['track_url'] ?? '',
        'file_path' => $input['file_path'] ?? '',
        'duration_text' => $input['duration_text'] ?? ''
    ], [], true);
    cacheDelete('favorites_' . $user['user_id']);
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'message' => $result['error']]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Added to favorites']);
    }
    exit();
}

if ($method === 'DELETE') {
    $trackId = $_GET['track_id'] ?? '';
    if (empty($trackId)) {
        echo json_encode(['success' => false, 'message' => 'track_id required']);
        exit();
    }
    $result = supabaseQuery('favorites', 'DELETE', null, [
        'user_id' => 'eq.' . $user['user_id'],
        'track_id' => 'eq.' . $trackId
    ], true);
    cacheDelete('favorites_' . $user['user_id']);
    echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
    exit();
}
?>
