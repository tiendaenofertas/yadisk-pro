<?php
session_start();

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
    die('Token inválido');
}

// Obtener el ID encriptado y desencriptar
$encryptedId = $_SESSION['download_token_' . $token];
$videoId = decryptVideoId($encryptedId);

// Eliminar token usado
unset($_SESSION['download_token_' . $token]);

// Obtener URL del video de Yandex
$yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
    'public_key' => 'https://yadi.sk/i/' . $videoId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $yandexUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['href'])) {
        // Redirigir a la URL de descarga
        header('Location: ' . $data['href']);
        exit;
    }
}

// Si hay error, mostrar mensaje
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error de descarga</title>
    <style>
        body {
            background: #1e3c72;
            color: white;
            text-align: center;
            padding: 50px;
            font-family: Arial, sans-serif;
        }
        a {
            color: #4a7fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>Error al descargar el video</h1>
    <p>No se pudo obtener el enlace de descarga.</p>
    <p><a href="javascript:history.back()">Volver</a></p>
</body>
</html>