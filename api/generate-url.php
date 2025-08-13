<?php
require_once '../config.php';

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

// Generar URL encriptada
$encryptedUrl = generateVideoUrl($videoId);

// Generar el código iframe
$iframeCode = '<iframe src="' . htmlspecialchars($encryptedUrl) . '" frameborder="0" width="510" height="400" scrolling="no" allowfullscreen></iframe>';

// IMPORTANTE: Asegurarse de que se envíe la URL correcta
echo json_encode([
    'success' => true,
    'encryptedUrl' => $encryptedUrl,  // Esta línea es crucial
    'iframeCode' => $iframeCode
]);
?>
