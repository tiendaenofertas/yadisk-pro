<?php
session_start();

// Obtener parámetros
$fileName = $_GET['file'] ?? '';
$folderId = $_GET['folder'] ?? '';

// Debug
error_log("Download - File: $fileName, Folder: $folderId");

if (empty($fileName) || empty($folderId)) {
    die('Parámetros inválidos: file=' . $_GET['file'] . ', folder=' . $_GET['folder']);
}

// Construir URLs de Yandex Disk para descargar directamente
$folderUrls = [
    "https://disk.yandex.com/d/{$folderId}",
    "https://disk.yandex.ru/d/{$folderId}",
    "https://yadi.sk/d/{$folderId}"
];

$downloadUrl = null;

// Intentar obtener la URL de descarga
foreach ($folderUrls as $folderUrl) {
    // Usar la API para obtener el archivo
    $apiUrl = "https://cloud-api.yandex.net/v1/disk/public/resources?" . http_build_query([
        'public_key' => $folderUrl,
        'limit' => 100
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && !empty($response)) {
        $data = json_decode($response, true);
        
        if (isset($data['_embedded']['items'])) {
            foreach ($data['_embedded']['items'] as $item) {
                if ($item['name'] === $fileName && $item['type'] === 'file') {
                    if (isset($item['file'])) {
                        $downloadUrl = $item['file'];
                        break 2;
                    }
                }
            }
        }
    }
}

// Si no encontramos por API, intentar método alternativo
if (!$downloadUrl) {
    // Método alternativo: ir directamente a Yandex
    $downloadUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
        'public_key' => "https://disk.yandex.com/d/{$folderId}",
        'path' => "/" . rawurlencode($fileName)
    ]);
}

if (empty($downloadUrl)) {
    die('<html><body><h1>No se pudo obtener el archivo</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

// Obtener información del archivo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $downloadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

// Detectar tipo MIME por extensión
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$mimeTypes = [
    'rar' => 'application/x-rar-compressed',
    'zip' => 'application/zip',
    '7z' => 'application/x-7z-compressed',
    'mp4' => 'video/mp4',
    'mkv' => 'video/x-matroska',
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$mimeType = $mimeTypes[$fileExt] ?? 'application/octet-stream';

// Configurar headers - USAR EL NOMBRE REAL DEL ARCHIVO
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Desactivar buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Stream del archivo
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo $data;
    flush();
    return strlen($data);
});

curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

exit;
?>
