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

// Función para obtener URL de descarga de Yandex
function getYandexDownloadUrl($videoId) {
    // Intentar primero con /d/ (carpetas/nuevo formato)
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        error_log("Intentando con: $publicKey - HTTP Code: $httpCode");
        
        // Si encontramos un código exitoso, procesar la respuesta
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
    
    // Si ninguno funcionó, devolver error
    return [
        'success' => false,
        'error' => 'No se pudo obtener el archivo de Yandex',
        'httpCode' => $httpCode ?? 0,
        'curlError' => $curlError ?? ''
    ];
}

// Verificar token
$token = $_GET['token'] ?? '';

if (empty($token) || !isset($_SESSION['download_token_' . $token])) {
    die('<html><body><h1>Recarga: La Pagina De Nuevo </h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

// Obtener el ID encriptado y desencriptar
$encryptedId = $_SESSION['download_token_' . $token];
$videoId = decryptVideoId($encryptedId);

// Eliminar token usado
unset($_SESSION['download_token_' . $token]);

// Validar que el videoId no esté vacío
if (empty($videoId)) {
    die('<html><body><h1>Error: ID de archivo inválido</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

// Obtener URL de descarga
$result = getYandexDownloadUrl($videoId);

if ($result['success']) {
    $downloadUrl = $result['url'];
    
    // Obtener información del archivo
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $downloadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $headers = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    // Extraer nombre del archivo y tipo de contenido
    $filename = 'archivo_' . $videoId;
    $contentType = 'application/octet-stream';
    
    // Intentar extraer nombre del archivo de los headers
    if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $headers, $matches)) {
        $filename = trim($matches[1], '"\'');
    } else {
        // Detectar tipo por content-type
        if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $matches)) {
            $detectedType = trim($matches[1]);
            $contentType = $detectedType;
            
            // Mapeo de tipos MIME a extensiones
            $extensionMap = [
                'video/mp4' => '.mp4',
                'video/x-matroska' => '.mkv',
                'video/webm' => '.webm',
                'video/avi' => '.avi',
                'application/x-rar-compressed' => '.rar',
                'application/x-rar' => '.rar',
                'application/vnd.rar' => '.rar',
                'application/zip' => '.zip',
                'application/x-zip-compressed' => '.zip',
                'application/x-7z-compressed' => '.7z',
                'application/pdf' => '.pdf',
                'image/jpeg' => '.jpg',
                'image/png' => '.png',
            ];
            
            foreach ($extensionMap as $mime => $ext) {
                if (stripos($detectedType, $mime) !== false) {
                    $filename = 'archivo_' . substr($videoId, 0, 8) . $ext;
                    break;
                }
            }
        }
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
    
    // Desactivar buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Descargar y enviar el archivo
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
    
    $curlResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curlResult === false || $httpCode !== 200) {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<html><body><h1>Error al descargar el archivo</h1><p>HTTP Code: ' . $httpCode . '</p><p><a href="javascript:history.back()">Volver</a></p></body></html>';
    }
    
    exit;
}

// Si llegamos aquí, hubo un error
$errorMsg = $result['error'] ?? 'Error desconocido';
$httpCode = $result['httpCode'] ?? 0;
$curlError = $result['curlError'] ?? '';
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
        .help-box {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        code {
            background: rgba(0,0,0,0.3);
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
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
        <h1>🚫 Error al descargar el archivo</h1>
        <p style="white-space: pre-line;"><?php echo htmlspecialchars($errorMsg); ?></p>
        
        <div class="help-box">
            <strong>💡 Cómo obtener un ID válido de Yandex Disk:</strong><br><br>
            <strong>1. Para archivos individuales:</strong><br>
            • URL: <code>https://disk.yandex.com/i/XXXXX</code><br>
            • El ID es: <code>XXXXX</code><br><br>
            
            <strong>2. Para carpetas (nuevo formato):</strong><br>
            • URL: <code>https://disk.yandex.com/d/XXXXX</code><br>
            • El ID es: <code>XXXXX</code><br><br>
            
            <strong>Pasos:</strong><br>
            1. Ve a <a href="https://disk.yandex.com" target="_blank" style="padding: 2px 5px; margin: 0;">Yandex Disk</a><br>
            2. Haz clic en tu archivo → "Compartir" o "Share"<br>
            3. Asegúrate de que sea público<br>
            4. Copia el enlace completo<br>
            5. Extrae solo el ID (lo que viene después de <code>/i/</code> o <code>/d/</code>)
        </div>
        
        <p>En tu caso, el ID es: <code><?php echo htmlspecialchars($videoId); ?></code></p>
        
        <a href="javascript:history.back()">← Volver e intentar de nuevo</a>
        
        <div class="debug-info">
            <strong>Información de debug:</strong><br>
            ID del archivo: <?php echo htmlspecialchars($videoId); ?><br>
            HTTP Code: <?php echo $httpCode; ?><br>
            <?php if (!empty($curlError)): ?>
            cURL Error: <?php echo htmlspecialchars($curlError); ?><br>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
