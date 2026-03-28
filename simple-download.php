<?php
/**
 * Descarga directa desde Yandex sin usar API
 * Simplemente obtiene la URL de Yandex y la redirige
 */

$folderId = $_GET['folder'] ?? '';
$fileName = $_GET['file'] ?? '';

if (empty($folderId) || empty($fileName)) {
    die('Parámetros inválidos');
}

// Decodificar parámetros
$folderId = urldecode($folderId);
$fileName = urldecode($fileName);

error_log("Simple Download - Folder: $folderId, File: $fileName");

// Intentar obtener enlace de descarga desde Yandex directamente
$folderUrls = [
    "https://disk.yandex.com/d/{$folderId}",
    "https://disk.yandex.ru/d/{$folderId}",
    "https://yadi.sk/d/{$folderId}"
];

$downloadUrl = null;

foreach ($folderUrls as $folderUrl) {
    // Intentar con el endpoint de descarga directo
    $apiUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
        'public_key' => $folderUrl,
        'path' => '/' . $fileName
    ]);
    
    error_log("Intentando: $apiUrl");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // NO seguir redireccionamiento aún
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headers = curl_getinfo($ch);
    curl_close($ch);
    
    error_log("HTTP Code: $httpCode");
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        
        if (isset($data['href'])) {
            $downloadUrl = $data['href'];
            error_log("URL de descarga obtenida: $downloadUrl");
            break;
        }
    }
    
    // Si es 301/302, el href está en Location header
    if ($httpCode == 301 || $httpCode == 302) {
        if (isset($headers['redirect_url'])) {
            $downloadUrl = $headers['redirect_url'];
            error_log("URL desde redireccionamiento: $downloadUrl");
            break;
        }
    }
}

if (empty($downloadUrl)) {
    error_log("No se pudo obtener URL de descarga");
    die('<html><body><h1>Error: No se pudo obtener el archivo</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

error_log("Iniciando descarga de: $downloadUrl como: $fileName");

// Ahora descargar el archivo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $downloadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

// Detectar tipo MIME
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$mimeTypes = [
    'rar' => 'application/x-rar-compressed',
    'zip' => 'application/zip',
    '7z' => 'application/x-7z-compressed',
    'mp4' => 'video/mp4',
    'mkv' => 'video/x-matroska',
    'avi' => 'video/avi',
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'txt' => 'text/plain'
];

$mimeType = $mimeTypes[$fileExt] ?? 'application/octet-stream';

// Configurar headers de respuesta
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Limpiar buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Stream del archivo
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo $data;
    flush();
    return strlen($data);
});

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

error_log("Descarga completada - HTTP Code: $httpCode");

if ($result === false) {
    error_log("Error en descarga: $curlError");
    header('Content-Type: text/html; charset=UTF-8');
    echo '<html><body><h1>Error al descargar</h1><p>' . htmlspecialchars($curlError) . '</p></body></html>';
}

exit;
?>