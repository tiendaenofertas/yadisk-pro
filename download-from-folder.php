<?php
session_start();

// Verificar token
$token = $_GET['token'] ?? '';

if (empty($token) || !isset($_SESSION['file_download_' . $token])) {
    die('<html><body><h1>Token inválido o expirado</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

// Obtener información del archivo
$downloadData = $_SESSION['file_download_' . $token];
$file = $downloadData['file'];
$folderId = $downloadData['folderId'];

// Eliminar token usado
unset($_SESSION['file_download_' . $token]);

// Obtener la URL de descarga directa del archivo
$downloadUrl = $file['file'] ?? null;

if (empty($downloadUrl)) {
    die('<html><body><h1>URL de descarga no disponible</h1><p><a href="javascript:history.back()">Volver</a></p></body></html>');
}

$fileName = $file['name'];
$fileSize = $file['size'];
$mimeType = $file['mime_type'] ?? 'application/octet-stream';

// Configurar headers para la descarga
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $fileSize);

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

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false || $httpCode !== 200) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<html><body><h1>Error al descargar el archivo</h1><p>HTTP Code: ' . $httpCode . '</p><p><a href="javascript:history.back()">Volver</a></p></body></html>';
}

exit;
?>