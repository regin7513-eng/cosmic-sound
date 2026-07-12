<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/cache.php';

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $playlistId = $_GET['id'] ?? '';

    if ($playlistId) {
        $cacheKey = 'playlist_' . $user['user_id'] . '_' . $playlistId;
        $cached = cacheGet($cacheKey, 120);
        if ($cached !== null) { echo json_encode($cached); exit(); }

        $result = supabaseQuery('playlists', 'GET', null, [
            'id' => 'eq.' . $playlistId,
            'user_id' => 'eq.' . $user['user_id'],
            'select' => '*'
        ], true);

        $playlist = $result['data'][0] ?? null;
        if (!$playlist) {
            echo json_encode(['success' => false, 'message' => 'Playlist not found']);
            exit();
        }

        $tracks = supabaseQuery('playlist_tracks', 'GET', null, [
            'playlist_id' => 'eq.' . $playlistId,
            'select' => '*',
            'order' => 'position.asc'
        ], true);

        $trackData = array_map(function($t) {
            if (empty($t['track_url']) && !empty($t['track_id'])) {
                $t['track_url'] = $t['track_id'];
            }
            return $t;
        }, $tracks['data'] ?? []);

        $response = ['success' => true, 'data' => $playlist, 'tracks' => $trackData];
        cacheSet($cacheKey, $response);
        echo json_encode($response);
    } else {
        $cacheKey = 'playlists_list_' . $user['user_id'];
        $cached = cacheGet($cacheKey, 120);
        if ($cached !== null) { echo json_encode($cached); exit(); }

        $result = supabaseQuery('playlists', 'GET', null, [
            'user_id' => 'eq.' . $user['user_id'],
            'select' => 'id,name,description,created_at',
            'order' => 'created_at.desc'
        ], true);

        $playlists = $result['data'] ?? [];
        foreach ($playlists as &$p) {
            $allTracks = supabaseQuery('playlist_tracks', 'GET', null, [
                'playlist_id' => 'eq.' . $p['id'],
                'select' => 'cover_image',
                'order' => 'position.asc'
            ], true);
            $allCovers = array_map(fn($t) => $t['cover_image'] ?? '', $allTracks['data'] ?? []);
            $p['track_covers'] = array_slice($allCovers, 0, 4);
            $p['track_count'] = count($allCovers);
        }
        unset($p);

        $response = ['success' => true, 'data' => $playlists];
        cacheSet($cacheKey, $response);
        echo json_encode($response);
    }
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result = supabaseQuery('playlists', 'POST', [
        'user_id' => $user['user_id'],
        'name' => $input['name'] ?? 'My Playlist',
        'description' => $input['description'] ?? '',
        'cover_image' => $input['cover_image'] ?? ''
    ], [], true);
    cacheDelete('playlists_list_' . $user['user_id']);
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'message' => $result['error']]);
    } else {
        echo json_encode(['success' => true, 'data' => $result['data'][0] ?? null]);
    }
    exit();
}

if ($method === 'DELETE') {
    $playlistId = $_GET['id'] ?? '';
    if (empty($playlistId)) {
        echo json_encode(['success' => false, 'message' => 'id required']);
        exit();
    }
    supabaseQuery('playlists', 'DELETE', null, [
        'id' => 'eq.' . $playlistId,
        'user_id' => 'eq.' . $user['user_id']
    ], true);
    cacheDelete('playlists_list_' . $user['user_id']);
    cacheDelete('playlist_' . $user['user_id'] . '_' . $playlistId);
    echo json_encode(['success' => true, 'message' => 'Playlist deleted']);
    exit();
}
?>
