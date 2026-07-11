<?php
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400');

$imgUrl = $_GET['url'] ?? '';
if (!$imgUrl || !preg_match('#^https?://#i', $imgUrl)) {
    http_response_code(400);
    exit;
}

$ch = curl_init($imgUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_MAXREDIRS      => 5,
]);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err = curl_error($ch);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 400 && $data && strlen($data) > 100) {
    if ($contentType && strpos($contentType, 'image') !== false) {
        header('Content-Type: ' . $contentType);
    } else {
        header('Content-Type: image/jpeg');
    }
    header('Content-Length: ' . strlen($data));
    echo $data;
} else {
    http_response_code(404);
}
