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
    
    // Generar el código iframe
    $iframeCode = '<iframe src="' . htmlspecialchars($encryptedUrl) . '" frameborder="0" width="510" height="400" scrolling="no" allowfullscreen></iframe>';
    
    // Para debug - ver qué se está generando
    $debugInfo = [
        'videoId' => $videoId,
        'encryptedId' => $encryptedId,
        'encryptedUrl' => $encryptedUrl,
        'baseUrl' => BASE_URL
    ];
    
    // Enviar respuesta
    echo json_encode([
        'success' => true,
        'encryptedUrl' => $encryptedUrl,
        'iframeCode' => $iframeCode,
        'debug' => $debugInfo // Solo para debug, quitar en producción
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al generar URL: ' . $e->getMessage()
    ]);
}
?>
