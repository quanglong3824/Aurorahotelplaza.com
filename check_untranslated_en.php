<?php
$vi = require 'lang/vi.php';
$en = require 'lang/en.php';

function flatten_array($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flatten_array($value, $prefix . $key . '.'));
        } else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}

$vi_flat = flatten_array($vi);
$en_flat = flatten_array($en);

$same_value = [];
foreach ($vi_flat as $key => $val) {
    if (isset($en_flat[$key]) && $val === $en_flat[$key] && !empty($val) && !is_numeric($val)) {
        // Ignore strings that are often the same in both languages like brand names or short codes
        if (strlen($val) > 3 && !in_array($val, ['VND', 'USD', 'Email', 'Hotline', 'Map', 'Blog', 'Spa', 'Gym'])) {
            $same_value[$key] = $val;
        }
    }
}

echo "Keys with the same value in VI and EN (potential untranslated en):\n";
foreach ($same_value as $key => $val) {
    echo "- $key: $val\n";
}
