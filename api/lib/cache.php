<?php
if (!defined('CACHE_DATA_DIR')) define('CACHE_DATA_DIR', __DIR__ . '/../../cache/data');

function cached(string $key, int $ttl, callable $fn) {
    if (!is_dir(CACHE_DATA_DIR)) @mkdir(CACHE_DATA_DIR, 0775, true);
    $file = CACHE_DATA_DIR . '/' . preg_replace('/[^a-z0-9_-]/i', '', $key) . '.json';

    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $hit = json_decode(file_get_contents($file), true);
        if ($hit !== null) return $hit;
    }

    $fresh = $fn();
    if (is_array($fresh) && !isset($fresh['error'])) {
        @file_put_contents($file, json_encode($fresh), LOCK_EX);
        return $fresh;
    }
    if (is_file($file)) {
        $stale = json_decode(file_get_contents($file), true);
        if ($stale !== null) return $stale;
    }
    return $fresh;
}
