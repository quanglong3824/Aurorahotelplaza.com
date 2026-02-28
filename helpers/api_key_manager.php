<?php
// helpers/api_key_manager.php

function get_active_gemini_key()
{
    global $GEMINI_API_KEYS;

    // Load config nếu chưa có
    $key_file = __DIR__ . '/../config/api_keys.php';
    if (file_exists($key_file)) {
        require_once $key_file;
    }

    $valid_keys = get_all_valid_keys();

    if (empty($valid_keys)) {
        return '';
    }

    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    if ($current_idx >= count($valid_keys)) {
        $current_idx = 0;
        file_put_contents($index_file, 0);
    }

    return $valid_keys[$current_idx];
}

function rotate_gemini_key()
{
    $valid_keys = get_all_valid_keys();

    if (count($valid_keys) <= 1)
        return false; // Không có key để xoay vòng

    $index_file = __DIR__ . '/../config/current_key_idx.txt';
    $current_idx = 0;
    if (file_exists($index_file)) {
        $current_idx = (int) file_get_contents($index_file);
    }

    $current_idx++;
    if ($current_idx >= count($valid_keys)) {
        $current_idx = 0;
    }

    // Cập nhật index xuống file
    file_put_contents($index_file, $current_idx);

    return $valid_keys[$current_idx];
}

function get_all_valid_keys()
{
    global $GEMINI_API_KEYS;
    $key_file = __DIR__ . '/../config/api_keys.php';
    if (file_exists($key_file)) {
        require_once $key_file;
    }

    $valid_keys = [];
    if (!empty($GEMINI_API_KEYS) && is_array($GEMINI_API_KEYS)) {
        $valid_keys = array_filter($GEMINI_API_KEYS, function ($k) {
            return !empty(trim($k)) && strpos($k, 'ĐIỀN_API_KEY') === false;
        });
        $valid_keys = array_values($valid_keys);
    }

    // Tương thích ngược với define cũ
    if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && strpos(GEMINI_API_KEY, 'ĐIỀN_API_KEY') === false) {
        if (!in_array(GEMINI_API_KEY, $valid_keys)) {
            $valid_keys[] = GEMINI_API_KEY;
        }
    }

    if (empty($valid_keys)) {
        $env_key = getenv('GEMINI_API_KEY');
        if ($env_key)
            $valid_keys[] = $env_key;
    }

    return $valid_keys;
}
