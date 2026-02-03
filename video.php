<?php
// Función para desencriptar
function decryptVideoId($encryptedId) {
    $SECRET_KEY = 'YEn7To3@x$7D32EjaHGSm=b9r';
    $method = 'AES-256-CBC';
    $key = hash('sha256', $SECRET_KEY);
    $iv = substr(hash('sha256', 'iv_' . $SECRET_KEY), 0, 16);
    
    $decrypted = openssl_decrypt(base64_decode($encryptedId), $method, $key, 0, $iv);
    return $decrypted;
}

// Obtener y desencriptar el ID
$encryptedId = $_GET['v'] ?? '';
$videoId = '';

if (!empty($encryptedId)) {
    try {
        $videoId = decryptVideoId($encryptedId);
    } catch (Exception $e) {
        $videoId = $encryptedId;
    }
}

// Generar un token único para esta sesión
$sessionToken = bin2hex(random_bytes(16));
session_start();
$_SESSION['video_token_' . $sessionToken] = $videoId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="//yadi.sk/favicon.ico" type="image/x-icon">
    <meta name="robots" content="noindex">
    <meta name="googlebot" content="noindex">
    <meta name="referrer" content="never">
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>YaDisk</title>
    <script src="//code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://ssl.p.jwpcdn.com/player/v/8.23.1/jwplayer.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            background-color: black;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
        }
        
        #player {
            width: 100vw !important;
            height: 100vh !important;
        }
        
        @media (max-width: 768px) {
            body {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                -webkit-overflow-scrolling: touch;
            }
            
            #player {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                z-index: 999;
            }
        }
        
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
    <script>
        // Deshabilitar clic derecho
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Deshabilitar F12 y otras teclas de desarrollo
        document.onkeydown = function(e) {
            if(e.keyCode == 123) return false; // F12
            if(e.ctrlKey && e.shiftKey && e.keyCode == 73) return false; // Ctrl+Shift+I
            if(e.ctrlKey && e.shiftKey && e.keyCode == 74) return false; // Ctrl+Shift+J
            if(e.ctrlKey && e.keyCode == 85) return false; // Ctrl+U
        };
    </script>
</head>
<body>
    <div id="player"></div>
    
    <script>
        // Ofuscar el código
        (function() {
            var _0x4e2a = ['<?php echo $sessionToken; ?>', 'getVideoUrl.php', 'POST', 'json', 'href', 'width', 'height', 
                         'video/mp4', 'uniform', 'ready', 'error', 'Player ready', 'Error loading video'];
            
            var token = _0x4e2a[0];
            
            // Cargar el video de forma asíncrona
            function loadVideo() {
                $.ajax({
                    url: _0x4e2a[1],
                    type: _0x4e2a[2],
                    data: { token: token },
                    dataType: _0x4e2a[3],
                    success: function(response) {
                        if (response.success && response.url) {
                            initPlayer(response.url);
                        } else {
                            showError('Invalid video data');
                        }
                    },
                    error: function() {
                        showError('Failed to load video');
                    }
                });
            }
            
            function initPlayer(videoUrl) {
                jwplayer.key = "XSuP4qMl+9tK17QNb+4+th2Pm9AWgMO/cYH8CI0HGGr7bdjo";
                
                var player = jwplayer("player").setup({
                    file: videoUrl,
                    type: _0x4e2a[7],
                    width: $(window)[_0x4e2a[5]](),
                    height: $(window)[_0x4e2a[6]](),
                    autostart: true,
                    controls: true,
                    stretching: _0x4e2a[8]
                });
                
                player.on(_0x4e2a[9], function() {
                    console.log(_0x4e2a[11]);
                });
                
                player.on(_0x4e2a[10], function(e) {
                    showError(_0x4e2a[12] + ': ' + (e.message || 'Unknown error'));
                });
            }
            
            function showError(message) {
                document.getElementById('player').innerHTML = 
                    '<div style="color: white; text-align: center; padding-top: 50vh; font-family: Arial;">' + 
                    message + '</div>';
            }
            
            // Iniciar carga
            loadVideo();
        })();
    </script>
</body>
</html>
