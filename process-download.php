<?php
session_start();
header('Content-Type: application/json');

// Función para desencriptar
function decryptVideoId($encryptedId) {
    $SECRET_KEY = 'YEn7To3@x$7D32EjaHGSm=b9r';
    $method = 'AES-256-CBC';
    $key = hash('sha256', $SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . $SECRET_KEY), 0, 16);
    
    $decrypted = openssl_decrypt(base64_decode($encryptedId), $method, $key, 0, $iv);
    return $decrypted;
}

// Verificar token
$token = $_POST['token'] ?? '';

if (empty($token) || !isset($_SESSION['download_token_' . $token])) {
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

// Obtener el ID encriptado y desencriptar
$encryptedId = $_SESSION['download_token_' . $token];
$videoId = decryptVideoId($encryptedId);

// Eliminar token usado
unset($_SESSION['download_token_' . $token]);

// Validar que el videoId no esté vacío
if (empty($videoId)) {
    echo json_encode(['success' => false, 'error' => 'ID de video inválido']);
    exit;
}

// Obtener URL del video de Yandex
$yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
    'public_key' => 'https://yadi.sk/i/' . $videoId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $yandexUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Debug info
error_log("Video ID: " . $videoId);
error_log("Yandex URL: " . $yandexUrl);
error_log("HTTP Code: " . $httpCode);
error_log("Response: " . $response);

if ($httpCode == 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['href'])) {
        // Devolver la URL de descarga como JSON
        echo json_encode([
            'success' => true,
            'downloadUrl' => $data['href']
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Respuesta inválida de Yandex',
            'debug' => [
                'json_error' => json_last_error_msg(),
                'response' => substr($response, 0, 500)
            ]
        ]);
        exit;
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al conectar con Yandex: HTTP ' . $httpCode,
        'debug' => [
            'curl_error' => $curlError,
            'response' => substr($response, 0, 500)
        ]
    ]);
    exit;
}
?>
