<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
$query  = $_GET['q'] ?? '';
$id     = $_GET['id'] ?? '';
$limit  = min((int)($_GET['limit'] ?? 18), 20);

$API = 'https://saavn.sumit.co/api';

if ($action === 'search' && $query) {
    $url = "$API/search/songs?query=" . urlencode($query) . "&limit=$limit&page=1";
    $data = fetchJson($url);

    if (!$data || empty($data['data']['results'])) {
        echo json_encode(['success' => false, 'data' => []]);
        exit;
    }

    $songs = $data['data']['results'];
    $ids = array_map(fn($s) => $s['id'], $songs);
    $ids = array_slice($ids, 0, 12);

    $idStr = implode(',', $ids);
    $details = fetchJson("$API/songs?ids=$idStr");

    if (empty($details['data'])) {
        echo json_encode(['success' => false, 'data' => []]);
        exit;
    }

    $detailMap = [];
    foreach ($details['data'] as $d) {
        $detailMap[$d['id']] = $d;
    }

    $result = [];
    foreach ($ids as $songId) {
        if (empty($detailMap[$songId])) continue;
        $d = $detailMap[$songId];

        $dlUrl = '';
        if (!empty($d['downloadUrl'])) {
            foreach ($d['downloadUrl'] as $dl) {
                if (($dl['quality'] ?? '') === '320kbps') {
                    $dlUrl = $dl['url'];
                    break;
                }
            }
            if (!$dlUrl) $dlUrl = end($d['downloadUrl'])['url'] ?? '';
        }

        $img = '';
        if (!empty($d['image'])) {
            foreach ($d['image'] as $im) {
                if (($im['quality'] ?? '') === '500x500') {
                    $img = $im['url'];
                    break;
                }
            }
            if (!$img) $img = end($d['image'])['url'] ?? '';
        }

        $artists = '';
        if (!empty($d['artists']['primary'])) {
            $artists = implode(', ', array_map(fn($a) => $a['name'], $d['artists']['primary']));
        }

        if ($dlUrl) {
            $result[] = [
                'id'            => $d['id'],
                'title'         => $d['name'] ?? 'Unknown',
                'artist'        => $artists ?: 'Unknown',
                'album'         => $d['album']['name'] ?? '',
                'duration_text' => formatDur($d['duration'] ?? 0),
                'duration'      => $d['duration'] ?? 0,
                'cover_image'   => $img,
                'track_url'     => $d['url'] ?? '',
                'file_path'     => $dlUrl,
                'source'        => 'jiosaavn'
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $result]);

} elseif ($action === 'song' && $id) {
    $detail = fetchJson("$API/songs/$id");
    if (empty($detail['data'][0])) {
        echo json_encode(['success' => false, 'data' => null]);
        exit;
    }

    $d = $detail['data'][0];
    $dlUrl = '';
    if (!empty($d['downloadUrl'])) {
        foreach ($d['downloadUrl'] as $dl) {
            if (($dl['quality'] ?? '') === '320kbps') {
                $dlUrl = $dl['url'];
                break;
            }
        }
        if (!$dlUrl) $dlUrl = end($d['downloadUrl'])['url'] ?? '';
    }

    $img = '';
    if (!empty($d['image'])) {
        foreach ($d['image'] as $im) {
            if (($im['quality'] ?? '') === '500x500') {
                $img = $im['url'];
                break;
            }
        }
        if (!$img) $img = end($d['image'])['url'] ?? '';
    }

    $artists = '';
    if (!empty($d['artists']['primary'])) {
        $artists = implode(', ', array_map(fn($a) => $a['name'], $d['artists']['primary']));
    }

    echo json_encode(['success' => true, 'data' => [
        'id'            => $d['id'],
        'title'         => $d['name'] ?? 'Unknown',
        'artist'        => $artists ?: 'Unknown',
        'album'         => $d['album']['name'] ?? '',
        'duration_text' => formatDur($d['duration'] ?? 0),
        'duration'      => $d['duration'] ?? 0,
        'cover_image'   => $img,
        'track_url'     => $d['url'] ?? '',
        'file_path'     => $dlUrl,
        'source'        => 'jiosaavn'
    ]]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}

function fetchJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

function formatDur($sec) {
    if (!$sec) return '0:00';
    $m = floor($sec / 60);
    $s = $sec % 60;
    return "$m:" . ($s < 10 ? '0' : '') . $s;
}
