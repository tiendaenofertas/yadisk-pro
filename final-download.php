<?php
/**
 * SOLUCIÓN FINAL: Descarga directa desde Yandex con nombre correcto
 * 
 * Flujo:
 * 1. Recibe el ID de la carpeta y nombre del archivo
 * 2. Obtiene la URL de descarga de Yandex API
 * 3. Descarga el archivo con nombre correcto en los headers
 */

$folderId = $_GET['folder'] ?? '';
$fileName = $_GET['file'] ?? '';

if (empty($folderId) || empty($fileName)) {
    http_response_code(400);
    die('Parámetros inválidos');
}

$folderId = urldecode($folderId);
$fileName = urldecode($fileName);

// Función para obtener URL de descarga
function getDownloadUrlFromYandex($folderId, $fileName) {
    $folderUrl = "https://disk.yandex.com/d/{$folderId}";
    
    $apiUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
        'public_key' => $folderUrl,
        'path' => '/' . $fileName
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['href'])) {
            return $data['href'];
        }
    }
    
    return null;
}

// Obtener URL de descarga
$downloadUrl = getDownloadUrlFromYandex($folderId, $fileName);

if (empty($downloadUrl)) {
    http_response_code(404);
    die('<html><body><h1>Error: No se pudo obtener el archivo</h1></body></html>');
}

// Detectar tipo MIME por extensión
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$mimeTypes = [
    'rar' => 'application/x-rar-compressed',
    'zip' => 'application/zip',
    '7z' => 'application/x-7z-compressed',
    'mp4' => 'video/mp4',
    'mkv' => 'video/x-matroska',
    'avi' => 'video/avi',
    'mov' => 'video/quicktime',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'txt' => 'text/plain'
];

$mimeType = $mimeTypes[$fileExt] ?? 'application/octet-stream';

// Headers de respuesta con el nombre correcto
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($fileName));
header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Descargar sin buffer
@ob_end_clean();

// Stream del archivo desde Yandex
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $downloadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

// Stream binario
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo $data;
    flush();
    return strlen($data);
});

curl_exec($ch);
curl_close($ch);

exit;
?>