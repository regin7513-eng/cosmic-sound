<?php
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://ualjdbpzalgnctpachbw.supabase.co');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVhbGpkYnB6YWxnbmN0cGFjaGJ3Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODM1ODgwMDQsImV4cCI6MjA5OTE2NDAwNH0.T8M548MyiFZyuzL0DTrN_3uQGEn9MnoaOhJ8Ic8ckEs');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVhbGpkYnB6YWxnbmN0cGFjaGJ3Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MzU4ODAwNCwiZXhwIjoyMDk5MTY0MDA0fQ.DfgKVsTkfwYsjnhGtgw1drfu2owxB5ij_TkBqXSqv9w');

function httpRequest($url, $method = 'GET', $data = null, $headers = []) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            if ($data !== null) $opts[CURLOPT_POSTFIELDS] = is_string($data) ? $data : json_encode($data);
        } elseif ($method === 'PATCH') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            if ($data !== null) $opts[CURLOPT_POSTFIELDS] = is_string($data) ? $data : json_encode($data);
        } elseif ($method === 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return ['body' => $response, 'code' => $httpCode, 'error' => $error];
    }

    $opts = ['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'ignore_errors' => true,
        'timeout' => 15
    ]];
    if ($data !== null) {
        $opts['http']['content'] = is_string($data) ? $data : json_encode($data);
    }
    $ctx = stream_context_create($opts);
    $response = @file_get_contents($url, false, $ctx);
    $httpCode = 0;
    if (isset($http_response_header[0])) {
        preg_match('/\s(\d{3})\s/', $http_response_header[0], $m);
        $httpCode = isset($m[1]) ? (int)$m[1] : 0;
    }
    return ['body' => $response, 'code' => $httpCode, 'error' => ''];
}

function supabaseQuery($table, $method = 'GET', $data = null, $filters = [], $useServiceKey = false, $userToken = null) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    $params = [];
    foreach ($filters as $key => $val) {
        $params[] = $key . '=' . urlencode($val);
    }
    if ($params) $url .= '?' . implode('&', $params);

    $key = $useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    $authKey = $userToken ? $userToken : $key;

    $headers = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $authKey,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    $result = httpRequest($url, $method, $data, $headers);

    if ($result['error']) {
        return ['error' => $result['error'], 'code' => 0];
    }

    $decoded = json_decode($result['body'], true);

    if ($result['code'] >= 400) {
        return ['error' => $decoded['message'] ?? 'Unknown error', 'code' => $result['code'], 'details' => $decoded];
    }

    return ['data' => $decoded, 'code' => $result['code']];
}

function supabaseSignUp($email, $password, $username) {
    $url = SUPABASE_URL . '/auth/v1/signup';
    $result = httpRequest($url, 'POST', [
        'email' => $email,
        'password' => $password,
        'data' => ['username' => $username]
    ], [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ]);
    return json_decode($result['body'], true);
}

function supabaseAdminCreateUser($email, $password, $username) {
    $url = SUPABASE_URL . '/auth/v1/admin/users';
    $result = httpRequest($url, 'POST', [
        'email' => $email,
        'password' => $password,
        'email_confirm' => true,
        'user_metadata' => ['username' => $username]
    ], [
        'apikey: ' . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'Content-Type: application/json'
    ]);
    $decoded = json_decode($result['body'], true);
    if ($result['code'] >= 400) {
        return ['error' => $decoded['msg'] ?? $decoded['message'] ?? 'Unknown error', 'code' => $result['code']];
    }
    return $decoded;
}

function supabaseSignIn($email, $password) {
    $url = SUPABASE_URL . '/auth/v1/token?grant_type=password';
    $result = httpRequest($url, 'POST', [
        'email' => $email,
        'password' => $password
    ], [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ]);
    return json_decode($result['body'], true);
}

function supabaseRefreshToken($refreshToken) {
    $url = SUPABASE_URL . '/auth/v1/token?grant_type=refresh_token';
    $result = httpRequest($url, 'POST', [
        'refresh_token' => $refreshToken
    ], [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ]);
    return json_decode($result['body'], true);
}
