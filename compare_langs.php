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

$missing_in_en = array_diff_key($vi_flat, $en_flat);
$missing_in_vi = array_diff_key($en_flat, $vi_flat);

echo "Missing in EN (present in VI):\n";
foreach ($missing_in_en as $key => $val) {
    echo "- $key: $val\n";
}

echo "\nMissing in VI (present in EN):\n";
foreach ($missing_in_vi as $key => $val) {
    echo "- $key: $val\n";
}
