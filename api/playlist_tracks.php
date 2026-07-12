<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $playlistId = $input['playlist_id'] ?? '';
    $trackId = $input['track_id'] ?? '';

    if (empty($playlistId) || empty($trackId)) {
        echo json_encode(['success' => false, 'message' => 'playlist_id and track_id required']);
        exit();
    }

    $check = supabaseQuery('playlists', 'GET', null, [
        'id' => 'eq.' . $playlistId,
        'user_id' => 'eq.' . $user['user_id'],
        'select' => 'id'
    ], true);

    if (empty($check['data'])) {
        echo json_encode(['success' => false, 'message' => 'Playlist not found']);
        exit();
    }

    $maxPos = supabaseQuery('playlist_tracks', 'GET', null, [
        'playlist_id' => 'eq.' . $playlistId,
        'select' => 'position',
        'order' => 'position.desc',
        'limit' => '1'
    ], true);

    $nextPos = 0;
    if (!empty($maxPos['data'][0]['position'])) {
        $nextPos = intval($maxPos['data'][0]['position']) + 1;
    }

    $trackUrl = $input['track_url'] ?? '';
    if (empty($trackUrl)) {
        $trackUrl = $trackId;
    }

    $result = supabaseQuery('playlist_tracks', 'POST', [
        'playlist_id' => $playlistId,
        'track_id' => $trackId,
        'title' => $input['title'] ?? '',
        'artist' => $input['artist'] ?? '',
        'album' => $input['album'] ?? '',
        'cover_image' => $input['cover_image'] ?? '',
        'track_url' => $trackUrl,
        'file_path' => $input['file_path'] ?? '',
        'duration_text' => $input['duration_text'] ?? '',
        'position' => $nextPos
    ], [], false, $user['access_token']);

    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'message' => $result['error']]);
    } else {
        require_once __DIR__ . '/../config/cache.php';
        cacheDelete('playlist_' . $user['user_id'] . '_' . $playlistId);
        echo json_encode(['success' => true, 'message' => 'Added to playlist']);
    }
    exit();
}

if ($method === 'DELETE') {
    $trackId = $_GET['track_id'] ?? '';
    $playlistId = $_GET['playlist_id'] ?? '';

    if (empty($trackId) || empty($playlistId)) {
        echo json_encode(['success' => false, 'message' => 'track_id and playlist_id required']);
        exit();
    }

    supabaseQuery('playlist_tracks', 'DELETE', null, [
        'playlist_id' => 'eq.' . $playlistId,
        'track_id' => 'eq.' . $trackId
    ], true);

    require_once __DIR__ . '/../config/cache.php';
    cacheDelete('playlist_' . $user['user_id'] . '_' . $playlistId);
    echo json_encode(['success' => true, 'message' => 'Removed from playlist']);
    exit();
}
?>
