<?php
$trackUrl = $_GET['url'] ?? '';
if (!$trackUrl) {
    http_response_code(400);
    exit('Missing url');
}

$API_KEY = 'planaai';
$BASE = 'https://www.sankavollerei.web.id';

$apiUrl = "$BASE/download/spotify?apikey=$API_KEY&url=" . urlencode($trackUrl);
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0',
    ],
]);
$resp = curl_exec($ch);
curl_close($ch);

$data = json_decode($resp, true);
$downloadUrl = $data['data']['download'] ?? '';
if (!$downloadUrl) {
    http_response_code(404);
    exit('Stream not found');
}

header('Location: ' . $downloadUrl, true, 302);
exit;