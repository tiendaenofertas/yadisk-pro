<?php
// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Requiere HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Headers de seguridad
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Configuración de la base de datos de usuarios
// ⚠️ IMPORTANTE: En producción, mover estas credenciales a variables de entorno o archivo .env
define('USERS', [
    'admin' => password_hash('mp4secure2025', PASSWORD_DEFAULT),
    'usuario1' => password_hash('mp4secure2025', PASSWORD_DEFAULT)
]);

// Clave secreta para encriptación
// ⚠️ CRÍTICO: Mover a archivo .env y NUNCA commitear en Git
define('SECRET_KEY', 'YEn7To3@x$7D32EjaHGSm=b9r');

// URL base apuntando al archivo video.php
define('BASE_URL', 'https://xzorra.net/yadixpro/video.php?v=');

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['login_time']);
}

// Función para regenerar ID de sesión (prevenir session fixation)
function regenerateSession() {
    session_regenerate_id(true);
    $_SESSION['login_time'] = time();
}

// Función para validar videoId
function validateVideoId($videoId) {
    // Solo permitir caracteres alfanuméricos, guiones y guiones bajos
    // Longitud razonable para IDs de Yandex (típicamente entre 10-50 caracteres)
    if (empty($videoId) || !is_string($videoId)) {
        return false;
    }
    
    if (strlen($videoId) < 5 || strlen($videoId) > 100) {
        return false;
    }
    
    // Solo caracteres alfanuméricos, guiones, guiones bajos
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $videoId)) {
        return false;
    }
    
    return true;
}

// Función para encriptar el ID del video
function encryptVideoId($videoId) {
    // Validar entrada
    if (!validateVideoId($videoId)) {
        throw new Exception('Invalid video ID format');
    }
    
    $method = 'AES-256-CBC';
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . SECRET_KEY), 0, 16);
    
    $encrypted = openssl_encrypt($videoId, $method, $key, 0, $iv);
    
    if ($encrypted === false) {
        throw new Exception('Encryption failed');
    }
    
    return base64_encode($encrypted);
}

// Función para desencriptar el ID del video
function decryptVideoId($encryptedId) {
    if (empty($encryptedId) || !is_string($encryptedId)) {
        return false;
    }
    
    $method = 'AES-256-CBC';
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . SECRET_KEY), 0, 16);
    
    $decoded = base64_decode($encryptedId, true);
    if ($decoded === false) {
        return false;
    }
    
    $decrypted = openssl_decrypt($decoded, $method, $key, 0, $iv);
    
    // Validar el resultado desencriptado
    if ($decrypted === false || !validateVideoId($decrypted)) {
        return false;
    }
    
    return $decrypted;
}

// Función para generar la URL completa con encriptación
function generateVideoUrl($videoId) {
    $encryptedId = encryptVideoId($videoId);
    return BASE_URL . urlencode($encryptedId);
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Función para validar token CSRF
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Token expirado (1 hora)
    if ((time() - $_SESSION['csrf_token_time']) > 3600) {
        return false;
    }
    
    // Comparación segura contra timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Función para sanitizar salida HTML
function sanitizeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Función para logging de seguridad (básico)
function logSecurityEvent($event, $details = []) {
    $logFile = __DIR__ . '/logs/security.log';
    $logDir = dirname($logFile);
    
    // Crear directorio de logs si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0750, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user' => $_SESSION['user'] ?? 'anonymous',
        'details' => $details
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Verificar timeout de sesión (30 minutos de inactividad)
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutos
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}
?>
