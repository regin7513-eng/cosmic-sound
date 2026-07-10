<?php
require_once __DIR__ . '/../config/supabase.php';
header('Content-Type: application/json');

$result = supabaseSignIn('test@test.com', 'test123');

echo json_encode([
    'php_version' => phpversion(),
    'curl_available' => function_exists('curl_init'),
    'result' => $result,
    'result_type' => gettype($result)
]);
