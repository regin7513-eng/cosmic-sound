<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/cache.php';

$artist = $_GET['artist'] ?? '';
$track = $_GET['track'] ?? '';

if (empty($artist) || empty($track)) {
    echo json_encode(['success' => false, 'message' => 'artist and track required']);
    exit();
}

$cacheKey = 'lyrics_' . md5($artist . '|' . $track);
$cached = cacheGet($cacheKey, 3600);
if ($cached !== null) { echo json_encode($cached); exit(); }

$url = "https://lrclib.net/api/search?" . http_build_query([
    'artist_name' => $artist,
    'track_name' => $track
]);

$response = null;
for ($attempt = 0; $attempt < 3; $attempt++) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: GinzSong/1.0',
            'Accept: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && ($code === 200 || $code === 304)) break;
    if ($attempt < 2) usleep(500000);
}

if (!$response || !$response) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: GinzSong/1.0\r\nAccept: application/json\r\n"]]);
    $response = @file_get_contents($url, false, $ctx);
}

if (!$response) {
    $result = ['success' => false, 'message' => 'Failed to fetch lyrics'];
    echo json_encode($result);
    exit();
}

$data = json_decode($response, true);

if (!is_array($data) || count($data) === 0) {
    $result = ['success' => false, 'message' => 'Lyrics not found'];
    cacheSet($cacheKey, $result);
    echo json_encode($result);
    exit();
}

$best = null;
foreach ($data as $item) {
    if (!empty($item['syncedLyrics'])) {
        $best = $item;
        break;
    }
}

if (!$best) {
    $best = $data[0];
}

$lyrics = [];
if (!empty($best['syncedLyrics'])) {
    $lines = explode("\n", $best['syncedLyrics']);
    foreach ($lines as $line) {
        if (preg_match('/^\[(\d{2}):(\d{2})\.(\d{2,3})\]\s*(.*)$/', $line, $m)) {
            $time = intval($m[1]) * 60 + intval($m[2]) + intval($m[3]) / (strlen($m[3]) === 2 ? 100 : 1000);
            $text = trim($m[4]);
            if ($text !== '') {
                $lyrics[] = ['time' => $time, 'text' => $text];
            }
        }
    }
}

$result = [
    'success' => true,
    'synced' => count($lyrics) > 0,
    'lyrics' => count($lyrics) > 0 ? $lyrics : $best['plainLyrics'] ?? '',
    'track' => $best['trackName'] ?? $track,
    'artist' => $best['artistName'] ?? $artist,
    'album' => $best['albumName'] ?? '',
    'duration' => $best['duration'] ?? 0
];

cacheSet($cacheKey, $result);
echo json_encode($result);
?>