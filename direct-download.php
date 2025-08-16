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
$token = $_GET['token'] ?? '';

if (empty($token) || !isset($_SESSION['download_token_' . $token])) {
    die('<html><body><h1>Error: Token inválido</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

// Obtener el ID encriptado y desencriptar
$encryptedId = $_SESSION['download_token_' . $token];
$videoId = decryptVideoId($encryptedId);

// Eliminar token usado
unset($_SESSION['download_token_' . $token]);

// Validar que el videoId no esté vacío
if (empty($videoId)) {
    die('<html><body><h1>Error: ID de video inválido</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
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

if ($httpCode == 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['href'])) {
        $downloadUrl = $data['href'];
        
        // DESCARGAR EL ARCHIVO A TRAVÉS DEL SERVIDOR (PROXY)
        
        // Iniciar la descarga del archivo
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_NOBODY, true); // Solo headers para obtener info del archivo
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // Obtener información del archivo primero
        $headers = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Extraer nombre del archivo y tipo de contenido
        $filename = 'video_' . $videoId . '.mp4'; // Nombre por defecto
        $contentType = 'application/octet-stream';
        
        // Intentar extraer nombre del archivo de los headers
        if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $headers, $matches)) {
            $filename = trim($matches[1], '"\'');
        }
        
        // Intentar extraer content-type
        if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $matches)) {
            $contentType = trim($matches[1]);
        }
        
        // Configurar headers para la descarga
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        if (isset($info['download_content_length']) && $info['download_content_length'] > 0) {
            header('Content-Length: ' . $info['download_content_length']);
        }
        
        // Desactivar buffering para archivos grandes
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Ahora descargar y enviar el archivo real
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            echo $data;
            flush();
            return strlen($data);
        });
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($result === false || $httpCode !== 200) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<html><body><h1>Error al descargar el archivo</h1><p>HTTP Code: ' . $httpCode . '</p><p><a href="javascript:history.back()">Volver</a></p></body></html>';
        }
        
        exit;
    } else {
        $errorMsg = 'Respuesta inválida de Yandex';
    }
} else {
    $errorMsg = 'Error al conectar con Yandex: HTTP ' . $httpCode;
    if (!empty($curlError)) {
        $errorMsg .= ' - ' . $curlError;
    }
}

// Si llegamos aquí, hubo un error
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error de descarga</title>
    <meta charset="UTF-8">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            text-align: center;
            padding: 50px;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .error-container {
            background: rgba(255,255,255,0.1);
            padding: 40px;
            border-radius: 10px;
            max-width: 600px;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 2em;
        }
        p {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        a {
            color: #4a7fff;
            text-decoration: none;
            background: rgba(74, 127, 255, 0.2);
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
            transition: background 0.3s;
        }
        a:hover {
            background: rgba(74, 127, 255, 0.3);
        }
        .debug-info {
            margin-top: 30px;
            font-size: 0.9em;
            opacity: 0.7;
            text-align: left;
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>🚫 Error al descargar el video</h1>
        <p><?php echo htmlspecialchars($errorMsg ?? 'Error desconocido'); ?></p>
        <p>Por favor, inténtalo de nuevo en unos minutos.</p>
        
        <a href="javascript:history.back()">← Volver</a>
        
        <div class="debug-info">
            <strong>Información de debug:</strong><br>
            Video ID: <?php echo htmlspecialchars($videoId); ?><br>
            HTTP Code: <?php echo $httpCode; ?><br>
            <?php if (!empty($curlError)): ?>
            cURL Error: <?php echo htmlspecialchars($curlError); ?><br>
            <?php endif; ?>
            Response Preview: <?php echo htmlspecialchars(substr($response ?? '', 0, 200)); ?>...
        </div>
    </div>
</body>
</html>
