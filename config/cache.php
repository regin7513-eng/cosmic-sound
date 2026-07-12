<?php
function cacheGet($key, $ttl = 60) {
    $dir = sys_get_temp_dir() . '/ginz_cache';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $file = $dir . '/' . md5($key) . '.cache';
    if (file_exists($file)) {
        $age = time() - filemtime($file);
        if ($age < $ttl) {
            return json_decode(file_get_contents($file), true);
        }
    }
    return null;
}

function cacheSet($key, $data) {
    $dir = sys_get_temp_dir() . '/ginz_cache';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $file = $dir . '/' . md5($key) . '.cache';
    @file_put_contents($file, json_encode($data));
}

function cacheDelete($key) {
    $dir = sys_get_temp_dir() . '/ginz_cache';
    $file = $dir . '/' . md5($key) . '.cache';
    if (file_exists($file)) @unlink($file);
}

function cacheClearUser($userId) {
    $dir = sys_get_temp_dir() . '/ginz_cache';
    if (!is_dir($dir)) return;
    $files = glob($dir . '/*.cache');
    foreach ($files as $f) {
        $content = @file_get_contents($f);
        if (strpos($content, $userId) !== false) @unlink($f);
    }
}
?>
