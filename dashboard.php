<?php
require_once 'config.php';

// Verificar si está logueado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Archivos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Sistema de Archivos Yandex</h1>
            <div class="user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </header>
        
        <main>
            <div class="video-generator">
                <h2>🚀 Generador de Enlaces - Archivos y Carpetas</h2>
                
                <div class="form-container">
                    <div class="form-group">
                        <label for="videoId">ID del Archivo o Carpeta de Yandex:</label>
                        <input type="text" id="videoId" placeholder="Ej: Ieso5Vkk16EkLw" value="">
                        
                        <div style="margin-top: 15px; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px;">
                            <strong>📌 Cómo obtener el ID:</strong><br><br>
                            
                            <strong>Para ARCHIVOS individuales:</strong><br>
                            • URL: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">https://disk.yandex.com/i/ABC123xyz</code><br>
                            • ID: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">ABC123xyz</code><br><br>
                            
                            <strong>Para CARPETAS con múltiples archivos:</strong><br>
                            • URL: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">https://disk.yandex.com/d/Ieso5Vkk16EkLw</code><br>
                            • ID: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">Ieso5Vkk16EkLw</code><br><br>
                            
                            <strong style="color: #4CAF50;">✅ El sistema detecta automáticamente si es archivo o carpeta</strong>
                        </div>                        
                        <div style="margin-top: 10px;">
                            <label for="fileType">Tipo de contenido:</label>
                            <select id="fileType" style="padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="auto">🤖 Detección Automática</option>
                                <option value="video">🎬 Video (MP4, MKV, AVI, WEBM)</option>
                                <option value="folder">📁 Carpeta con múltiples archivos</option>
                                <option value="rar">📦 Archivo RAR</option>
                                <option value="zip">📦 Archivo ZIP</option>
                                <option value="7z">📦 Archivo 7Z</option>
                                <option value="pdf">📄 Documento PDF</option>
                                <option value="other">📄 Otro tipo de archivo</option>
                            </select>
                        </div>
                        
                        <button onclick="generateVideo()" class="btn-generate" style="margin-top: 15px;">🚀 Generar Enlaces</button>
                    </div>
                </div>
                
                <div id="result-container" style="display: none;">
                    <h3>URL Encriptada:</h3>
                    <div class="url-display">
                        <input type="text" id="encryptedUrl" readonly placeholder="Esperando URL...">
                        <button onclick="copyUrl()" class="btn-copy">Copiar</button>
                    </div>
                    
                    <div id="iframe-section">
                        <h3>Código iframe (solo para videos):</h3>
                        <div class="iframe-display">
                            <textarea id="iframeCode" readonly rows="3" placeholder="Solo disponible para videos"></textarea>
                            <button onclick="copyIframe()" class="btn-copy">Copiar</button>
                        </div>
                    </div>
                    
                    <div id="download-section">
                        <h3>Enlace de descarga:</h3>
                        <div class="download-link-display">
                            <input type="text" id="downloadLink" readonly placeholder="Esperando enlace...">
                            <button onclick="copyDownloadLink()" class="btn-copy">Copiar</button>
                        </div>
                    </div>
                    
                    <div id="folder-section" style="display: none;">
                        <h3>📁 Carpeta detectada:</h3>
                        <div style="padding: 20px; background: #e3f2fd; border-radius: 5px; text-align: center;">
                            <p style="margin-bottom: 15px; font-size: 16px;">
                                ✅ Carpeta de Yandex Disk detectada correctamente
                            </p>
                            <a id="viewFolderLink" href="#" 
                               style="display: inline-block; padding: 12px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                                📂 Ver archivos en la carpeta
                            </a>
                            <p style="margin-top: 15px; font-size: 13px; opacity: 0.8;">
                                Se abrirá en una nueva pestaña
                            </p>
                        </div>
                    </div> en la carpeta
                            </a>
                        </div>
                    </div>
                    
                    <!-- Debug info - quitar en producción -->
                    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                        <h4>Debug Info:</h4>
                        <pre id="debugInfo" style="font-size: 12px;"></pre>
                    </div>
                    
                    <h3>Vista previa:</h3>
                    <div id="preview-container">
                        <p style="text-align: center; color: #666; padding: 20px;">
                            La vista previa solo está disponible para archivos de video
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
