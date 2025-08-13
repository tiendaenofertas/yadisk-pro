<?php
session_start();

// Obtener el ID encriptado del video
$videoId = $_GET['v'] ?? '';

if (empty($videoId)) {
    header('Location: index.php');
    exit;
}

// Generar token para la descarga
$downloadToken = bin2hex(random_bytes(16));
$_SESSION['download_token_' . $downloadToken] = $videoId;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descarga de Video</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* Fondo animado */
        .background-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .shape1 {
            width: 400px;
            height: 400px;
            top: -200px;
            left: -200px;
        }

        .shape2 {
            width: 600px;
            height: 600px;
            bottom: -300px;
            right: -300px;
        }

        /* Contenedor principal */
        .download-container {
            text-align: center;
            z-index: 10;
            position: relative;
        }

        /* Animación del astronauta */
        .space-animation {
            width: 300px;
            height: 300px;
            margin: 0 auto 30px;
            position: relative;
        }

        .moon {
            width: 150px;
            height: 150px;
            background: #e8e8e8;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 
                inset -20px -20px 40px rgba(0,0,0,0.2),
                0 0 40px rgba(255,255,255,0.5);
        }

        .moon::before {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            background: #d0d0d0;
            border-radius: 50%;
            top: 30px;
            left: 40px;
            box-shadow: inset -5px -5px 10px rgba(0,0,0,0.2);
        }

        .moon::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: #d0d0d0;
            border-radius: 50%;
            bottom: 40px;
            right: 30px;
            box-shadow: inset -3px -3px 8px rgba(0,0,0,0.2);
        }

        .orbit {
            position: absolute;
            width: 200px;
            height: 200px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .astronaut {
            position: absolute;
            width: 40px;
            height: 50px;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
        }

        .astronaut::before {
            content: '👨‍🚀';
            font-size: 35px;
            position: absolute;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(10deg); }
        }

        .rocket {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 40px;
            animation: fly 15s ease-in-out infinite;
        }

        @keyframes fly {
            0% { transform: translate(0, 0) rotate(45deg); }
            50% { transform: translate(-150px, -150px) rotate(45deg); }
            100% { transform: translate(0, 0) rotate(45deg); }
        }

        /* Contador de porcentaje */
        .percentage {
            font-size: 48px;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        /* Texto */
        .download-text {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        /* Botones */
        .download-button, .fallback-button {
            background: #4a7fff;
            color: white;
            border: none;
            padding: 15px 50px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: not-allowed;
            opacity: 0.5;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(74, 127, 255, 0.3);
            margin: 10px;
            text-decoration: none;
        }

        .fallback-button {
            background: #28a745;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3);
        }

        .download-button.active, .fallback-button.active {
            cursor: pointer;
            opacity: 1;
            transform: translateY(-2px);
        }

        .download-button.active {
            box-shadow: 0 8px 30px rgba(74, 127, 255, 0.5);
        }

        .fallback-button.active {
            box-shadow: 0 8px 30px rgba(40, 167, 69, 0.5);
        }

        .download-button:hover.active {
            background: #3a6fff;
            transform: translateY(-4px);
            box-shadow: 0 10px 35px rgba(74, 127, 255, 0.6);
        }

        .fallback-button:hover.active {
            background: #218838;
            transform: translateY(-4px);
            box-shadow: 0 10px 35px rgba(40, 167, 69, 0.6);
        }

        .download-icon {
            font-size: 20px;
        }

        /* Estrellas animadas */
        .stars {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Buttons container */
        .buttons-container {
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .space-animation {
                width: 250px;
                height: 250px;
            }

            .moon {
                width: 120px;
                height: 120px;
            }

            .percentage {
                font-size: 36px;
            }

            .download-button, .fallback-button {
                padding: 12px 30px;
                font-size: 14px;
                margin: 5px;
                display: block;
                width: 80%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
    </div>

    <div class="stars"></div>

    <div class="download-container">
        <div class="space-animation">
            <div class="moon"></div>
            <div class="orbit">
                <div class="astronaut"></div>
            </div>
            <div class="rocket">🚀</div>
        </div>

        <div class="percentage" id="percentage">0%</div>
        <div class="download-text">Your Download Link</div>
        
        <div class="buttons-container">
            <button class="download-button" id="downloadBtn" disabled>
                <span class="spinner" id="spinner"></span>
                <span id="buttonText">PREPARING</span>
            </button>
            
            <a href="#" class="fallback-button" id="fallbackBtn" style="display: none;">
                <span class="download-icon">🔗</span>
                <span>OPEN LINK</span>
            </a>
        </div>
        
        <div id="instructions" style="display: none; color: rgba(255,255,255,0.8); margin-top: 20px; font-size: 14px;">
            <p>Si la descarga automática no funciona, usa el botón "OPEN LINK" para abrir el archivo en una nueva pestaña.</p>
        </div>
    </div>

    <script>
        // Generar estrellas aleatorias
        function createStars() {
            const starsContainer = document.querySelector('.stars');
            for (let i = 0; i < 50; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 3 + 's';
                starsContainer.appendChild(star);
            }
        }

        createStars();

        // Configuración del contador
        const duration = 6; // Reducido a 6 segundos para pruebas
        let currentTime = 0;
        const percentage = document.getElementById('percentage');
        const downloadBtn = document.getElementById('downloadBtn');
        const fallbackBtn = document.getElementById('fallbackBtn');
        const buttonText = document.getElementById('buttonText');
        const spinner = document.getElementById('spinner');
        const instructions = document.getElementById('instructions');
        const token = '<?php echo $downloadToken; ?>';

        // Actualizar contador
        const interval = setInterval(() => {
            currentTime++;
            const progress = Math.round((currentTime / duration) * 100);
            percentage.textContent = progress + '%';

            if (currentTime >= duration) {
                clearInterval(interval);
                enableDownload();
            }
        }, 1000);

        // Habilitar descarga
        function enableDownload() {
            percentage.textContent = '100%';
            downloadBtn.classList.add('active');
            downloadBtn.disabled = false;
            spinner.style.display = 'none';
            buttonText.innerHTML = '<span class="download-icon">⬇</span> DOWNLOAD';
            
            // Mostrar botón alternativo y instrucciones
            fallbackBtn.style.display = 'inline-flex';
            fallbackBtn.classList.add('active');
            instructions.style.display = 'block';
            
            // Configurar enlace directo
            fallbackBtn.href = 'direct-download.php?token=' + encodeURIComponent(token);
            fallbackBtn.target = '_blank';
            
            downloadBtn.onclick = function() {
                startDownload();
            };
        }

        // Función de descarga con timeout
        function startDownload() {
            // Cambiar texto del botón
            buttonText.textContent = 'DOWNLOADING...';
            downloadBtn.disabled = true;
            
            // Crear iframe oculto para la descarga
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = 'direct-download.php?token=' + encodeURIComponent(token);
            document.body.appendChild(iframe);
            
            // Restaurar botón después de 5 segundos
            setTimeout(() => {
                buttonText.innerHTML = '<span class="download-icon">⬇</span> DOWNLOAD';
                downloadBtn.disabled = false;
                document.body.removeChild(iframe);
                
                // Resaltar el botón alternativo si la descarga no funcionó
                fallbackBtn.style.animation = 'pulse 1s infinite';
            }, 5000);
        }

        // Prevenir recarga de página
        window.addEventListener('beforeunload', function (e) {
            if (currentTime < duration) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // CSS para la animación de pulso
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1) translateY(-2px); }
                50% { transform: scale(1.05) translateY(-4px); }
                100% { transform: scale(1) translateY(-2px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
