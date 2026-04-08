<?php
require_once '../config.php';

// Configuración de errores solo en desarrollo
// Para producción, comentar estas líneas:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// En producción, usar:
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

header('Content-Type: application/json');

// Verificar timeout de sesión
if (!checkSessionTimeout()) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired']);
    exit;
}

// Verificar si está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    logSecurityEvent('unauthorized_api_access', ['endpoint' => 'generate-url']);
    exit;
}

// Validar token CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    logSecurityEvent('csrf_token_validation_failed', ['endpoint' => 'generate-url']);
    exit;
}

// Obtener el ID del video
$videoId = $_POST['videoId'] ?? '';

// Sanitizar entrada
$videoId = trim($videoId);
$videoId = filter_var($videoId, FILTER_SANITIZE_STRING);

if (empty($videoId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Video ID required']);
    exit;
}

// Validar formato del videoId
if (!validateVideoId($videoId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid video ID format']);
    logSecurityEvent('invalid_video_id_format', ['video_id' => substr($videoId, 0, 20)]);
    exit;
}

try {
    // Generar URL encriptada
    $encryptedId = encryptVideoId($videoId);
    $encryptedUrl = generateVideoUrl($videoId);
    
    // Verificar que la URL se generó correctamente
    if (empty($encryptedUrl)) {
        throw new Exception('Failed to generate encrypted URL');
    }
    
    // Generar el código iframe con sanitización
    $iframeCode = '<iframe src="' . htmlspecialchars($encryptedUrl, ENT_QUOTES, 'UTF-8') . 
                  '" frameborder="0" width="510" height="400" scrolling="no" allowfullscreen></iframe>';
    
    // Generar enlace de descarga
    $downloadUrl = str_replace('video.php?v=', 'download.php?v=', $encryptedUrl);
    
    // Log de generación exitosa
    logSecurityEvent('video_url_generated', [
        'video_id_hash' => hash('sha256', $videoId),
        'user' => $_SESSION['user']
    ]);
    
    // Enviar respuesta sin información de debug en producción
    $response = [
        'success' => true,
        'encryptedUrl' => $encryptedUrl,
        'downloadUrl' => $downloadUrl,
        'iframeCode' => $iframeCode
    ];
    
    // Solo incluir debug en desarrollo (comentar en producción)
    // $response['debug'] = [
    //     'videoId' => $videoId,
    //     'encryptedId' => $encryptedId,
    //     'baseUrl' => BASE_URL
    // ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    
    // Log del error
    logSecurityEvent('video_generation_error', [
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
    
    // Respuesta sin información sensible en producción
    echo json_encode([
        'success' => false,
        'error' => 'Error generating URL. Please try again.'
    ]);
    
    // Solo en desarrollo, incluir detalles:
    // echo json_encode([
    //     'success' => false,
    //     'error' => 'Error generating URL: ' . $e->getMessage(),
    //     'debug' => [
    //         'exception' => $e->getMessage(),
    //         'line' => $e->getLine(),
    //         'file' => $e->getFile()
    //     ]
    // ]);
}
?>
