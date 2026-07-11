<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
$query  = $_GET['q'] ?? '';
$url    = $_GET['url'] ?? '';
$limit  = min((int)($_GET['limit'] ?? 18), 50);

$API_KEY = 'planaai';
$BASE = 'https://www.sankavollerei.web.id';

if ($action === 'search' && $query) {
    $apiUrl = "$BASE/search/spotify?apikey=$API_KEY&q=" . urlencode($query);
    $data = fetchJson($apiUrl);

    if (!$data || empty($data['result'])) {
        echo json_encode(['success' => false, 'data' => [], 'debug_url' => $apiUrl, 'debug_data' => $data]);
        exit;
    }

    $tracks = array_slice($data['result'], 0, $limit);
    $result = [];

    foreach ($tracks as $track) {
        $durationSec = parseDuration($track['duration'] ?? '0:00');

        $result[] = [
            'id'            => $track['track_url'] ?? uniqid(),
            'title'         => $track['title'] ?? 'Unknown',
            'artist'        => $track['artist'] ?? 'Unknown',
            'album'         => $track['album'] ?? '',
            'duration_text' => $track['duration'] ?? '0:00',
            'duration'      => $durationSec,
            'cover_image'   => $track['thumbnail'] ?? '',
            'track_url'     => $track['track_url'] ?? '',
            'file_path'     => '',
            'source'        => 'spotify',
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);

} elseif ($action === 'download' && $url) {
    $apiUrl = "$BASE/download/spotify?apikey=$API_KEY&url=" . urlencode($url);
    $data = fetchJson($apiUrl);

    if (!$data || empty($data['data']['download'])) {
        echo json_encode(['success' => false, 'download_url' => '']);
        exit;
    }

    echo json_encode([
        'success'       => true,
        'download_url'  => $data['data']['download'],
        'title'         => $data['data']['title'] ?? '',
        'artist'        => $data['data']['artis'] ?? '',
        'duration'      => ($data['data']['durasi'] ?? 0) / 1000,
        'cover_image'   => $data['data']['image'] ?? '',
        'album'         => $data['data']['album'] ?? '',
    ]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}

function fetchJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer: https://www.sankavollerei.web.id/',
            'Origin: https://www.sankavollerei.web.id',
        ],
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return null;
    if (empty($resp)) return null;
    $decoded = json_decode($resp, true);
    if (!$decoded) return null;
    return $decoded;
}

function parseDuration($dur) {
    if (is_numeric($dur)) return (int)$dur;
    $parts = explode(':', (string)$dur);
    if (count($parts) === 2) return (int)$parts[0] * 60 + (int)$parts[1];
    if (count($parts) === 3) return (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
    return 0;
}
