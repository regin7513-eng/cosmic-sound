<?php
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://ualjdbpzalgnctpachbw.supabase.co');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVhbGpkYnB6YWxnbmN0cGFjaGJ3Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODM1ODgwMDQsImV4cCI6MjA5OTE2NDAwNH0.T8M548MyiFZyuzL0DTrN_3uQGEn9MnoaOhJ8Ic8ckEs');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVhbGpkYnB6YWxnbmN0cGFjaGJ3Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MzU4ODAwNCwiZXhwIjoyMDk5MTY0MDA0fQ.DfgKVsTkfwYsjnhGtgw1drfu2owxB5ij_TkBqXSqv9w');

function supabaseQuery($table, $method = 'GET', $data = null, $filters = [], $useServiceKey = false) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    $params = [];
    foreach ($filters as $key => $val) {
        $params[] = $key . '=' . urlencode($val);
    }
    if ($params) $url .= '?' . implode('&', $params);

    $key = $useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;

    $ch = curl_init();
    $headers = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $headers
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => $error, 'code' => 0];
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
        return ['error' => $decoded['message'] ?? 'Unknown error', 'code' => $httpCode, 'details' => $decoded];
    }

    return ['data' => $decoded, 'code' => $httpCode];
}

function supabaseSignUp($email, $password, $username) {
    $url = SUPABASE_URL . '/auth/v1/signup';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password,
            'data' => ['username' => $username]
        ]),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function supabaseAdminCreateUser($email, $password, $username) {
    $url = SUPABASE_URL . '/auth/v1/admin/users';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => ['username' => $username]
        ]),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        return ['error' => $decoded['msg'] ?? $decoded['message'] ?? 'Unknown error', 'code' => $httpCode];
    }
    return $decoded;
}

function supabaseSignIn($email, $password) {
    $url = SUPABASE_URL . '/auth/v1/token?grant_type=password';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password
        ]),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
