<?php
require_once 'config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Generar token CSRF para el formulario
$csrfToken = generateCSRFToken();

// Procesar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($submittedToken)) {
        $error = 'Error de seguridad. Por favor, recargue la página.';
        logSecurityEvent('csrf_validation_failed', ['context' => 'login']);
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Sanitizar entrada
        $username = trim($username);
        $username = filter_var($username, FILTER_SANITIZE_STRING);
        
        // Validar que no estén vacíos
        if (empty($username) || empty($password)) {
            $error = 'Usuario y contraseña son requeridos';
        } else {
            // Verificar credenciales
            if (isset(USERS[$username]) && password_verify($password, USERS[$username])) {
                // Login exitoso
                
                // Regenerar ID de sesión para prevenir session fixation
                regenerateSession();
                
                // Establecer variables de sesión
                $_SESSION['user'] = $username;
                $_SESSION['last_activity'] = time();
                
                // Log de login exitoso
                logSecurityEvent('successful_login', ['username' => $username]);
                
                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                // Login fallido
                $error = 'Usuario o contraseña incorrectos';
                
                // Log de intento fallido
                logSecurityEvent('failed_login_attempt', ['username' => $username]);
                
                // Pequeño delay para prevenir ataques de fuerza bruta
                sleep(1);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Videos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Sistema de Videos</h1>
            <h2>Iniciar Sesión</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Token CSRF oculto -->
                <input type="hidden" name="csrf_token" value="<?php echo sanitizeOutput($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        maxlength="50"
                        autocomplete="username"
                        pattern="[a-zA-Z0-9_-]+"
                        title="Solo letras, números, guiones y guiones bajos"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn-login">Ingresar</button>
            </form>
        </div>
    </div>
</body>
</html>
