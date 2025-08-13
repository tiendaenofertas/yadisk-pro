<?php
require_once '../config.php';

// Para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Verificar si está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener el ID del video
$videoId = $_POST['videoId'] ?? '';

if (empty($videoId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID del video requerido']);
    exit;
}

try {
    // Generar URL encriptada
    $encryptedId = encryptVideoId($videoId);
    $encryptedUrl = generateVideoUrl($videoId);
    
    // Verificar que la URL se generó correctamente
    if (empty($encryptedUrl)) {
        throw new Exception('No se pudo generar la URL encriptada');
    }
    
    // Generar el código iframe
    $iframeCode = '<iframe src="' . htmlspecialchars($encryptedUrl) . '" frameborder="0" width="510" height="400" scrolling="no" allowfullscreen></iframe>';
    
    // Generar enlace de descarga directamente aquí
    $downloadUrl = str_replace('video.php?v=', 'download.php?v=', $encryptedUrl);
    
    // Para debug - ver qué se está generando
    $debugInfo = [
        'videoId' => $videoId,
        'encryptedId' => $encryptedId,
        'encryptedUrl' => $encryptedUrl,
        'downloadUrl' => $downloadUrl,
        'baseUrl' => BASE_URL
    ];
    
    // Enviar respuesta
    echo json_encode([
        'success' => true,
        'encryptedUrl' => $encryptedUrl,
        'downloadUrl' => $downloadUrl,
        'iframeCode' => $iframeCode,
        'debug' => $debugInfo // Solo para debug, quitar en producción
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al generar URL: ' . $e->getMessage(),
        'debug' => [
            'videoId' => $videoId,
            'exception' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]
    ]);
}
?>
