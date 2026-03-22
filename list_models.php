<?php
require_once 'helpers/api_key_manager.php';
$api_key = get_active_gemini_key();

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>