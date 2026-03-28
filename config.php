<?php
session_start();

// Configuración de la base de datos
define('USERS', [
    'admin' => password_hash('mp4secure2025', PASSWORD_DEFAULT),
    'usuario1' => password_hash('mp4secure2025', PASSWORD_DEFAULT)
]);

// Clave secreta para encriptación
define('SECRET_KEY', 'YEn7To3@x$7D32EjaHGSm=b9r');

// URL base apuntando al archivo video.php
define('BASE_URL', 'https://xcuca.net/yandescarg/video.php?v=');

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Función para encriptar el ID del video
function encryptVideoId($videoId) {
    $method = 'AES-256-CBC';
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . SECRET_KEY), 0, 16);
    
    $encrypted = openssl_encrypt($videoId, $method, $key, 0, $iv);
    return base64_encode($encrypted);
}

// Función para desencriptar el ID del video
function decryptVideoId($encryptedId) {
    $method = 'AES-256-CBC';
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . SECRET_KEY), 0, 16);
    
    $decrypted = openssl_decrypt(base64_decode($encryptedId), $method, $key, 0, $iv);
    return $decrypted;
}

// Función para generar la URL completa con encriptación
function generateVideoUrl($videoId) {
    $encryptedId = encryptVideoId($videoId);
    return BASE_URL . urlencode($encryptedId);
}

// Función para obtener la URL de descarga de Yandex (soporta /i/ y /d/)
function getYandexDownloadUrl($videoId) {
    // Intentar con múltiples formatos
    $publicKeys = [
        'https://disk.yandex.com/d/' . $videoId,
        'https://disk.yandex.ru/d/' . $videoId,
        'https://disk.yandex.com/i/' . $videoId,
        'https://yadi.sk/d/' . $videoId,
        'https://yadi.sk/i/' . $videoId
    ];
    
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
        
        if ($httpCode == 200 && !empty($response)) {
            $data = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['href'])) {
                return [
                    'success' => true,
                    'url' => $data['href'],
                    'publicKey' => $publicKey
                ];
            }
        }
    }
    
    return [
        'success' => false,
        'error' => 'No se pudo obtener el archivo de Yandex'
    ];
}
?>
