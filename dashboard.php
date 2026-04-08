<?php
require_once 'config.php';

// Verificar timeout de sesión
if (!checkSessionTimeout()) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Verificar si está logueado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Generar token CSRF
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Videos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Sistema de Videos</h1>
            <div class="user-info">
                <span>Bienvenido, <?php echo sanitizeOutput($_SESSION['user']); ?></span>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </header>
        
        <main>
            <div class="video-generator">
                <h2>Generador de Videos</h2>
                
                <div class="form-container">
                    <div class="form-group">
                        <label for="videoId">ID del Video (ej: nIqwsZvyQM1gMQ):</label>
                        <input 
                            type="text" 
                            id="videoId" 
                            placeholder="Ingrese el ID del video" 
                            value=""
                            maxlength="100"
                            pattern="[a-zA-Z0-9_-]+"
                        >
                        <!-- Token CSRF oculto -->
                        <input type="hidden" id="csrfToken" value="<?php echo sanitizeOutput($csrfToken); ?>">
                        <button onclick="generateVideo()" class="btn-generate">Generar Video</button>
                    </div>
                </div>
                
                <div id="result-container" style="display: none;">
                    <h3>URL Encriptada:</h3>
                    <div class="url-display">
                        <input type="text" id="encryptedUrl" readonly placeholder="Esperando URL...">
                        <button onclick="copyUrl()" class="btn-copy">Copiar</button>
                    </div>
                    
                    <h3>Código iframe:</h3>
                    <div class="iframe-display">
                        <textarea id="iframeCode" readonly rows="3" placeholder="Esperando código..."></textarea>
                        <button onclick="copyIframe()" class="btn-copy">Copiar</button>
                    </div>
                    
                    <h3>Enlace de descarga:</h3>
                    <div class="download-link-display">
                        <input type="text" id="downloadLink" readonly placeholder="Esperando enlace...">
                        <button onclick="copyDownloadLink()" class="btn-copy">Copiar</button>
                    </div>
                    
                    <h3>Vista previa:</h3>
                    <div id="preview-container"></div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
