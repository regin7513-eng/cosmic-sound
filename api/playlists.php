<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $playlistId = $_GET['id'] ?? '';

    if ($playlistId) {
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

        echo json_encode(['success' => true, 'data' => $playlist, 'tracks' => $tracks['data'] ?? []]);
    } else {
        $result = supabaseQuery('playlists', 'GET', null, [
            'user_id' => 'eq.' . $user['user_id'],
            'select' => '*',
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

        echo json_encode(['success' => true, 'data' => $playlists]);
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

    echo json_encode(['success' => true, 'message' => 'Playlist deleted']);
    exit();
}
?>
