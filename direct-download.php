<?php
session_start();

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Función para desencriptar
function decryptVideoId($encryptedId) {
    $SECRET_KEY = 'YEn7To3@x$7D32EjaHGSm=b9r';
    $method = 'AES-256-CBC';
    $key = hash('sha256', $SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . $SECRET_KEY), 0, 16);
    
    $decoded = base64_decode($encryptedId, true);
    if ($decoded === false) {
        return false;
    }
    
    $decrypted = openssl_decrypt($decoded, $method, $key, 0, $iv);
    return $decrypted;
}

// Verificar token
$token = $_GET['token'] ?? '';

// Sanitizar token
$token = preg_replace('/[^a-zA-Z0-9]/', '', $token);

if (empty($token) || !isset($_SESSION['download_token_' . $token])) {
    showError('Token inválido o expirado', 'Por favor, recarga la página de descarga.');
    exit;
}

// Verificar expiración del token
if (isset($_SESSION['download_token_expiry_' . $token])) {
    if (time() > $_SESSION['download_token_expiry_' . $token]) {
        unset($_SESSION['download_token_' . $token]);
        unset($_SESSION['download_token_expiry_' . $token]);
        showError('Token expirado', 'El enlace de descarga ha expirado. Por favor, genera uno nuevo.');
        exit;
    }
}

// Obtener el ID encriptado y desencriptar
$encryptedId = $_SESSION['download_token_' . $token];
$videoId = decryptVideoId($encryptedId);

// Eliminar token usado
unset($_SESSION['download_token_' . $token]);
if (isset($_SESSION['download_token_expiry_' . $token])) {
    unset($_SESSION['download_token_expiry_' . $token]);
}

// Validar que el videoId no esté vacío y tenga formato válido
if (empty($videoId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $videoId)) {
    showError('ID de video inválido', 'El ID del video no es válido.');
    exit;
}

// Obtener URL del video de Yandex
$yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
    'public_key' => 'https://yadi.sk/i/' . $videoId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $yandexUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode == 200 && !empty($response)) {
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['href'])) {
        $downloadUrl = $data['href'];
        
        // Validar que sea una URL válida
        if (!filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
            showError('URL inválida', 'La URL de descarga no es válida.');
            exit;
        }
        
        // DESCARGAR EL ARCHIVO A TRAVÉS DEL SERVIDOR (PROXY)
        
        // Obtener información del archivo primero
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $headers = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Extraer nombre del archivo y tipo de contenido
        $filename = 'video_' . htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8') . '.mp4';
        $contentType = 'application/octet-stream';
        
        // Intentar extraer nombre del archivo de los headers
        if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $headers, $matches)) {
            $extractedFilename = trim($matches[1], '"\'');
            // Sanitizar el nombre del archivo
            $extractedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $extractedFilename);
            if (!empty($extractedFilename)) {
                $filename = $extractedFilename;
            }
        }
        
        // Intentar extraer content-type
        if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $matches)) {
            $extractedType = trim($matches[1]);
            // Validar que sea un tipo MIME válido
            if (preg_match('/^[a-zA-Z0-9\-]+\/[a-zA-Z0-9\-+.]+$/', $extractedType)) {
                $contentType = $extractedType;
            }
        }
        
        // Configurar headers para la descarga
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
        
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            echo $data;
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            return strlen($data);
        });
        
        $result = curl_exec($ch);
        $finalHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalCurlError = curl_error($ch);
        curl_close($ch);
        
        if ($result === false || $finalHttpCode !== 200) {
            // Limpiar cualquier salida anterior
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            showError('Error al descargar el archivo', 'HTTP Code: ' . $finalHttpCode . 
                     ($finalCurlError ? ' - ' . htmlspecialchars($finalCurlError) : ''));
        }
        
        exit;
    } else {
        $errorMsg = 'Respuesta inválida de Yandex';
        showError('Error al obtener el video', $errorMsg);
        exit;
    }
} else {
    $errorMsg = 'Error al conectar con Yandex: HTTP ' . $httpCode;
    if (!empty($curlError)) {
        $errorMsg .= ' - ' . htmlspecialchars($curlError);
    }
    
    // Log del error (sin exponer al usuario)
    error_log("Yandex Download Error - Video ID: $videoId, HTTP: $httpCode, Error: $curlError");
    
    showError('Error de conexión', 'No se pudo conectar con el servidor de archivos.');
    exit;
}

// Función para mostrar errores de forma segura
function showError($title, $message) {
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <title>Error de descarga</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: white;
                text-align: center;
                padding: 50px 20px;
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
                width: 100%;
            }
            h1 {
                margin-bottom: 20px;
                font-size: 2em;
            }
            p {
                margin-bottom: 15px;
                line-height: 1.6;
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
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>🚫 <?php echo $title; ?></h1>
            <p><?php echo $message; ?></p>
            <p>Por favor, inténtalo de nuevo.</p>
            
            <a href="javascript:history.back()">← Volver</a>
        </div>
    </body>
    </html>
    <?php
}
?>