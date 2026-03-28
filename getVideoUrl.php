<?php
session_start();
header('Content-Type: application/json');

// Verificar token
$token = $_POST['token'] ?? '';

if (empty($token) || !isset($_SESSION['video_token_' . $token])) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

// Obtener el ID del video
$videoId = $_SESSION['video_token_' . $token];

// Eliminar el token después de usarlo
unset($_SESSION['video_token_' . $token]);

// Intentar con múltiples formatos de URL de Yandex
$publicKeys = [
    'https://disk.yandex.com/d/' . $videoId,
    'https://disk.yandex.ru/d/' . $videoId,
    'https://disk.yandex.com/i/' . $videoId,
    'https://yadi.sk/d/' . $videoId,
    'https://yadi.sk/i/' . $videoId
];

$lastError = '';
$lastHttpCode = 0;

foreach ($publicKeys as $publicKey) {
    $yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
        'public_key' => $publicKey
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $yandexUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $lastHttpCode = $httpCode;
    
    if ($httpCode == 200 && !empty($response)) {
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($data['href'])) {
            echo json_encode([
                'success' => true,
                'url' => $data['href'],
                'publicKey' => $publicKey
            ]);
            exit;
        }
    }
    
    $lastError = "Intentado con $publicKey - HTTP $httpCode";
}

// Si llegamos aquí, ningún formato funcionó
echo json_encode([
    'success' => false, 
    'error' => 'Failed to get video from Yandex with any URL format',
    'debug' => [
        'videoId' => $videoId,
        'lastHttpCode' => $lastHttpCode,
        'lastError' => $lastError
    ]
]);
?>
