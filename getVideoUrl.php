<?php
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

error_reporting(0);
ini_set('display_errors', 0);

function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$token = $_POST['token'] ?? '';

if (empty($token) || !is_string($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$token = preg_replace('/[^a-zA-Z0-9]/', '', $token);

if (!isset($_SESSION['video_token_' . $token])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token not found or expired']);
    exit;
}

if (isset($_SESSION['video_token_expiry_' . $token])) {
    if (time() > $_SESSION['video_token_expiry_' . $token]) {
        unset($_SESSION['video_token_' . $token]);
        unset($_SESSION['video_token_expiry_' . $token]);
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token expired']);
        exit;
    }
}

$videoId = $_SESSION['video_token_' . $token];

unset($_SESSION['video_token_' . $token]);
if (isset($_SESSION['video_token_expiry_' . $token])) {
    unset($_SESSION['video_token_expiry_' . $token]);
}

if (empty($videoId) || !is_string($videoId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid video ID']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $videoId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid video ID format']);
    exit;
}

$yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
    'public_key' => 'https://yadi.sk/i/' . $videoId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $yandexUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Accept-Language: en-US,en;q=0.9'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode == 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['href'])) {
        if (filter_var($data['href'], FILTER_VALIDATE_URL)) {
            // ✅ Dividir la URL en partes para ofuscar
            $url = $data['href'];
            $parts = parse_url($url);
            
            // Dividir en componentes
            echo json_encode([
                'success' => true,
                'p1' => base64_encode($parts['scheme'] . '://'),
                'p2' => base64_encode($parts['host']),
                'p3' => base64_encode($parts['path'] ?? ''),
                'p4' => base64_encode($parts['query'] ?? ''),
                'ts' => time()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Invalid video URL']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Invalid response from video service']);
    }
} else {
    http_response_code(502);
    error_log("Yandex API Error - HTTP Code: $httpCode, cURL Error: $curlError");
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve video']);
}
?>
