<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit();
}

$cacheKey = 'search_' . md5($query) . '_' . $limit;
if (isset($_SESSION[$cacheKey]) && (time() - ($_SESSION[$cacheKey]['time'] ?? 0)) < 300) {
    echo json_encode($_SESSION[$cacheKey]['data']);
    exit();
}

$limit = min($limit, 25);

$url = "https://api.deezer.com/search?" . http_build_query([
    'q' => $query,
    'limit' => $limit
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
    echo json_encode(['success' => false, 'message' => 'Failed to fetch']);
    exit();
}

$data = json_decode($response, true);

if (!$data || !isset($data['data']) || count($data['data']) === 0) {
    echo json_encode(['success' => false, 'message' => 'No results']);
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

$songs = array_slice($songs, 0, $limit);

$result = [
    'success' => true,
    'data' => $songs,
    'count' => count($songs),
    'query' => $query
];

$_SESSION[$cacheKey] = ['data' => $result, 'time' => time()];

echo json_encode($result);
?>