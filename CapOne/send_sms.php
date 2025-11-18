<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$apikey = $_POST['apikey'] ?? '';
$number = $_POST['number'] ?? '';
$message = $_POST['message'] ?? '';

if (!$apikey || !$number || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$postFields = http_build_query([
    'apikey' => $apikey,
    'number' => $number,
    'message' => $message,
]);

$ch = curl_init('https://api.semaphore.co/api/v4/messages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . $curlError]);
    exit;
}

http_response_code($status ?: 200);
echo $response ?: json_encode(['error' => 'Empty response from provider']);
