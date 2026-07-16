<?php
/**
 * YouTube Music InnerTube API
 * Search + Stream URL via ANDROID_VR (search) + IOS (player)
 * 
 * Endpoints:
 *   GET  ?action=search&q={query}        → search results
 *   GET  ?action=stream&videoId={id}      → audio stream URL
 *   GET  ?action=streamUrl&videoId={id}   → redirect to audio stream
 *   GET  ?action=info&videoId={id}        → video metadata
 */

require_once __DIR__ . '/../config/cache.php';

// ============================================================
// CONFIG
// ============================================================

$YT_SEARCH_CLIENT = [
    'clientName'    => 'ANDROID_VR',
    'clientVersion' => '1.61.48',
    'clientId'      => 28,
    'userAgent'     => 'com.google.android.apps.youtube.vr.oculus/1.61.48 (Linux; U; Android 12; eureka-user Build/SQ3A.220605.009.A1) gzip',
];

$YT_PLAYER_CLIENT = [
    'clientName'    => 'IOS',
    'clientVersion' => '21.03.1',
    'clientId'      => 5,
    'userAgent'     => 'com.google.ios.youtube/21.03.1 (iPhone16,2; U; CPU iOS 18_3_2 like Mac OS X;)',
];

// ============================================================
// INNER TUBE API
// ============================================================

function ytInnerTube($endpoint, $body, $client) {
    $url = "https://music.youtube.com/youtubei/v1/{$endpoint}?prettyPrint=false";
    $payload = json_encode($body);

    $headers = [
        "Content-Type: application/json",
        "Accept: application/json",
        "User-Agent: {$client['userAgent']}",
        "X-Goog-Api-Format-Version: 1",
        "X-YouTube-Client-Name: {$client['clientId']}",
        "X-YouTube-Client-Version: {$client['clientVersion']}",
        "Origin: https://music.youtube.com",
        "Referer: https://music.youtube.com/",
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;
    return json_decode($response, true);
}

function ytContext($client) {
    return [
        'client' => [
            'clientName'    => $client['clientName'],
            'clientVersion' => $client['clientVersion'],
            'hl' => 'en',
            'gl' => 'US',
        ],
    ];
}

// ============================================================
// SEARCH
// ============================================================

function ytSearch($query, $limit = 5) {
    global $YT_SEARCH_CLIENT;

    $cacheKey = 'yt_search_' . md5(strtolower(trim($query)));
    $cached = cacheGet($cacheKey, 7200); // 2 hours
    if ($cached !== null) return $cached;

    $body = [
        'context' => ytContext($YT_SEARCH_CLIENT),
        'query'   => $query,
    ];

    $data = ytInnerTube('search', $body, $YT_SEARCH_CLIENT);
    if (!$data) return [];

    $results = [];
    $sections = $data['contents']['sectionListRenderer']['contents'] ?? [];

    foreach ($sections as $section) {
        $items = $section['itemSectionRenderer']['contents'] ?? [];
        foreach ($items as $item) {
            $cvr = $item['compactVideoRenderer'] ?? null;
            if (!$cvr) continue;

            $videoId = $cvr['videoId'] ?? null;
            $titleRuns = $cvr['title']['runs'] ?? [];
            $artistRuns = $cvr['longBylineText']['runs'] ?? [];
            $durRuns = $cvr['lengthText']['runs'] ?? [];
            $thumbList = $cvr['thumbnail']['thumbnails'] ?? [];

            if (!$videoId || empty($titleRuns)) continue;

            $results[] = [
                'title'     => $titleRuns[0]['text'] ?? '',
                'artist'    => $artistRuns[0]['text'] ?? '',
                'videoId'   => $videoId,
                'duration'  => $durRuns[0]['text'] ?? '',
                'thumbnail' => end($thumbList)['url'] ?? '',
                'source'    => 'youtube',
            ];

            if (count($results) >= $limit) break;
        }
        if (count($results) >= $limit) break;
    }

    cacheSet($cacheKey, $results);
    return $results;
}

// ============================================================
// STREAM URL
// ============================================================

function ytGetStreamUrl($videoId) {
    global $YT_PLAYER_CLIENT;

    $cacheKey = 'yt_stream_' . $videoId;
    $cached = cacheGet($cacheKey, 18000); // 5 hours (streams expire in 6h)
    if ($cached !== null) return $cached;

    $body = [
        'context'        => ytContext($YT_PLAYER_CLIENT),
        'videoId'        => $videoId,
        'contentCheckOk' => true,
        'racyCheckOk'    => true,
    ];

    $data = ytInnerTube('player', $body, $YT_PLAYER_CLIENT);
    if (!$data) return null;
    if (($data['playabilityStatus']['status'] ?? '') !== 'OK') return null;

    $streaming = $data['streamingData'] ?? null;
    if (!$streaming) return null;

    // Find best audio format
    $best = null;
    foreach ($streaming['adaptiveFormats'] ?? [] as $fmt) {
        if (isset($fmt['width'])) continue;
        if (!isset($fmt['url'])) continue;
        if (strpos($fmt['mimeType'] ?? '', 'audio') === false) continue;

        $bitrate = $fmt['bitrate'] ?? 0;
        if (!$best || $bitrate > $best['bitrate']) {
            $best = [
                'url'           => $fmt['url'],
                'mimeType'      => $fmt['mimeType'] ?? '',
                'bitrate'       => $bitrate,
                'contentLength' => $fmt['contentLength'] ?? 0,
                'audioQuality'  => $fmt['audioQuality'] ?? '',
            ];
        }
    }

    if ($best) {
        cacheSet($cacheKey, $best);
    }
    return $best;
}

function ytGetInfo($videoId) {
    global $YT_PLAYER_CLIENT;

    $body = [
        'context' => ytContext($YT_PLAYER_CLIENT),
        'videoId' => $videoId,
    ];
    $data = ytInnerTube('player', $body, $YT_PLAYER_CLIENT);
    if (!$data) return null;

    $vd = $data['videoDetails'] ?? [];
    $thumbs = $vd['thumbnail']['thumbnails'] ?? [];
    return [
        'title'         => $vd['title'] ?? '',
        'author'        => $vd['author'] ?? '',
        'lengthSeconds' => (int)($vd['lengthSeconds'] ?? 0),
        'thumbnail'     => end($thumbs)['url'] ?? '',
    ];
}

// ============================================================
// HTTP HANDLER
// ============================================================

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'search';

switch ($action) {

    case 'search':
        $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
        if (!$query) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing query (q)']);
            exit;
        }
        $limit = min((int)($_GET['limit'] ?? 5), 20);
        $results = ytSearch($query, $limit);
        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'results' => $results,
        ]);
        break;

    case 'stream':
        $videoId = trim($_GET['videoId'] ?? $_POST['videoId'] ?? '');
        if (!$videoId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing videoId']);
            exit;
        }
        $stream = ytGetStreamUrl($videoId);
        if (!$stream) {
            http_response_code(404);
            echo json_encode(['error' => 'Stream not available']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'stream'  => $stream,
        ]);
        break;

    case 'streamUrl':
        $videoId = trim($_GET['videoId'] ?? $_POST['videoId'] ?? '');
        if (!$videoId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing videoId']);
            exit;
        }
        $stream = ytGetStreamUrl($videoId);
        if (!$stream) {
            http_response_code(404);
            echo json_encode(['error' => 'Stream not available']);
            exit;
        }
        header('Location: ' . $stream['url'], true, 302);
        exit;

    case 'info':
        $videoId = trim($_GET['videoId'] ?? $_POST['videoId'] ?? '');
        if (!$videoId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing videoId']);
            exit;
        }
        $info = ytGetInfo($videoId);
        if (!$info) {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'info'    => $info,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        break;
}
