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
define('BASE_URL', 'https://xcuca.net/yadixpro/video.php?v=');

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

// Función para generar la URL completa con encriptación
function generateVideoUrl($videoId) {
    $encryptedId = encryptVideoId($videoId);
    return BASE_URL . urlencode($encryptedId);
}
?>
