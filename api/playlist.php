<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$playlistId = isset($_GET['id']) ? trim($_GET['id']) : 'trending';

$playlists = [
    'trending' => ['name' => 'Trending', 'q' => 'trending hits 2025'],
    'chill' => ['name' => 'Chill', 'q' => 'chill lofi beats relaxing'],
    'energy' => ['name' => 'Energy', 'q' => 'workout energy motivational'],
    'romance' => ['name' => 'Romance', 'q' => 'love songs romantic hits'],
    'focus' => ['name' => 'Focus', 'q' => 'study focus instrumental deep']
];

$playlist = $playlists[$playlistId] ?? $playlists['trending'];

$cacheKey = 'playlist_' . $playlistId;
if (isset($_SESSION[$cacheKey]) && (time() - ($_SESSION[$cacheKey]['time'] ?? 0)) < 600) {
    echo json_encode($_SESSION[$cacheKey]['data']);
    exit();
}

$url = "https://api.deezer.com/search?" . http_build_query([
    'q' => $playlist['q'],
    'limit' => 20
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false
]);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo json_encode(['success' => true, 'data' => [], 'count' => 0, 'playlist' => $playlist['name']]);
    exit();
}

$data = json_decode($response, true);

if (!$data || !isset($data['data']) || count($data['data']) === 0) {
    echo json_encode(['success' => true, 'data' => [], 'count' => 0, 'playlist' => $playlist['name']]);
    exit();
}

$songs = [];

foreach ($data['data'] as $item) {
    $preview = $item['preview'] ?? '';
    if (!$preview) continue;

    $songs[] = [
        'id' => 'deezer_' . $item['id'],
        'title' => $item['title'] ?? 'Unknown',
        'artist' => $item['artist']['name'] ?? 'Unknown',
        'album' => $item['album']['title'] ?? '',
        'duration_text' => gmdate('i:s', $item['duration'] ?? 0),
        'cover_image' => str_replace('250x250', '500x500', $item['album']['cover_medium'] ?? ''),
        'track_url' => $item['link'] ?? '',
        'file_path' => $preview,
        'source' => 'deezer',
        'deezer_id' => $item['id']
    ];
}

$songs = array_slice($songs, 0, 12);

$result = [
    'success' => true,
    'data' => $songs,
    'count' => count($songs),
    'playlist' => $playlist['name']
];

$_SESSION[$cacheKey] = ['data' => $result, 'time' => time()];

echo json_encode($result);
?>