<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/cache.php';

$action = $_GET['action'] ?? '';
$query  = $_GET['q'] ?? '';
$url    = $_GET['url'] ?? '';
$limit  = min((int)($_GET['limit'] ?? 18), 50);

$API_KEY = 'planaai';
$BASE = 'https://www.sankavollerei.web.id';

if ($action === 'search' && $query) {
    $cacheKey = 'search_' . $query . '_' . $limit;
    $cached = cacheGet($cacheKey, 1800);
    if ($cached !== null) { echo json_encode($cached); exit(); }

    $apiUrl = "$BASE/search/spotify?apikey=$API_KEY&q=" . urlencode($query);
    $data = fetchJson($apiUrl);

    if (!$data || empty($data['result'])) {
        $response = ['success' => false, 'data' => []];
        echo json_encode($response);
        exit();
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

    $response = ['success' => true, 'data' => $result];
    cacheSet($cacheKey, $response);
    echo json_encode($response);

} elseif ($action === 'download' && $url) {
    $cacheKey = 'download_' . md5($url);
    $cached = cacheGet($cacheKey, 3600);
    if ($cached !== null) { echo json_encode($cached); exit(); }

    $apiUrl = "$BASE/download/spotify?apikey=$API_KEY&url=" . urlencode($url);
    $data = fetchJson($apiUrl, 1);

    if (!$data || empty($data['data']['download'])) {
        $response = ['success' => false, 'download_url' => ''];
        echo json_encode($response);
        exit();
    }

    $response = [
        'success'       => true,
        'download_url'  => $data['data']['download'],
        'title'         => $data['data']['title'] ?? '',
        'artist'        => $data['data']['artis'] ?? '',
        'duration'      => ($data['data']['durasi'] ?? 0) / 1000,
        'cover_image'   => $data['data']['image'] ?? '',
        'album'         => $data['data']['album'] ?? '',
    ];
    cacheSet($cacheKey, $response);
    echo json_encode($response);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}

function fetchJson($url, $retries = 2) {
    for ($attempt = 0; $attempt <= $retries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Referer: https://www.sankavollerei.web.id/',
                'Origin: https://www.sankavollerei.web.id',
                'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: cross-site',
            ],
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            error_log('[sankavollerei] curl error (attempt ' . $attempt . '): ' . $err . ' url=' . $url);
            if ($attempt < $retries) { usleep(300000); continue; }
            return null;
        }
        if (empty($resp)) {
            if ($attempt < $retries) { usleep(300000); continue; }
            return null;
        }
        $decoded = json_decode($resp, true);
        if (!$decoded || ($code !== 200 && $code !== 304)) {
            error_log('[sankavollerei] bad response (attempt ' . $attempt . ') code=' . $code . ' url=' . $url . ' body=' . substr($resp, 0, 200));
            if ($attempt < $retries) { usleep(300000); continue; }
            return null;
        }
        return $decoded;
    }
    return null;
}

function parseDuration($dur) {
    if (is_numeric($dur)) return (int)$dur;
    $parts = explode(':', (string)$dur);
    if (count($parts) === 2) return (int)$parts[0] * 60 + (int)$parts[1];
    if (count($parts) === 3) return (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
    return 0;
}
