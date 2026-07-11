<?php
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400');

$imgUrl = $_GET['url'] ?? '';
if (!$imgUrl) {
    http_response_code(400);
    exit;
}

$ch = curl_init($imgUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode === 200 && $data) {
    header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
    echo $data;
} else {
    http_response_code(404);
}
