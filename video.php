<?php
require_once 'config.php';

header("Content-Security-Policy: default-src 'self' https://ssl.p.jwpcdn.com https://code.jquery.com https://cloud-api.yandex.net https://downloader.disk.yandex.ru; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://ssl.p.jwpcdn.com https://code.jquery.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; media-src 'self' https: blob:;");

function decryptVideoIdLocal($encryptedId) {
    if (empty($encryptedId)) {
        return false;
    }
    
    $encryptedId = filter_var($encryptedId, FILTER_SANITIZE_STRING);
    return decryptVideoId($encryptedId);
}

$encryptedId = $_GET['v'] ?? '';
$videoId = '';

if (!empty($encryptedId)) {
    $videoId = decryptVideoIdLocal($encryptedId);
    
    if ($videoId === false) {
        logSecurityEvent('invalid_video_id_attempt', ['encrypted_id' => substr($encryptedId, 0, 20)]);
        die('Invalid video ID');
    }
}

if (empty($videoId)) {
    die('No video ID provided');
}

$sessionToken = bin2hex(random_bytes(32));
$_SESSION['video_token_' . $sessionToken] = $videoId;
$_SESSION['video_token_expiry_' . $sessionToken] = time() + 900;
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
        
        #loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-family: Arial, sans-serif;
            font-size: 18px;
            z-index: 1000;
        }
        
        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            width: 40px;
            height: 40px;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        document.onkeydown = function(e) {
            if(e.keyCode == 123) return false;
            if(e.ctrlKey && e.shiftKey && e.keyCode == 73) return false;
            if(e.ctrlKey && e.shiftKey && e.keyCode == 74) return false;
            if(e.ctrlKey && e.keyCode == 85) return false;
        };
    </script>
</head>
<body>
    <div id="loading">
        <div class="spinner"></div>
        <div>Loading video...</div>
    </div>
    <div id="player"></div>
    
    <script>
        // ✅ OFUSCACIÓN: Código difícil de leer y copiar
        (function(_0x4a2c){var _0x5d73={'token':'<?php echo sanitizeOutput($sessionToken); ?>','apiUrl':'getVideoUrl.php','jwKey':'XSuP4qMl+9tK17QNb+4+th2Pm9AWgMO/cYH8CI0HGGr7bdjo'};var _0x1f8e=function(_0x2d4a){var _0x3c9b=document['getElementById'](_0x2d4a);if(_0x3c9b){_0x3c9b['style']['display']='none';}};var _0x8a4f=function(_0x1b2e){_0x1f8e('loading');document['getElementById']('player')['innerHTML']='<div style="color: white; text-align: center; padding-top: 45vh; font-family: Arial; font-size: 18px;"><div style="margin-bottom: 20px; font-size: 48px;">⚠️</div><div>'+_0x6c3d(_0x1b2e)+'</div></div>';};var _0x6c3d=function(_0x9f1a){var _0x7e2b={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};return _0x9f1a['replace'](/[&<>"']/g,function(_0x4d8c){return _0x7e2b[_0x4d8c];});};var _0x3b9e=function(){$['ajax']({'url':_0x5d73['apiUrl'],'type':'POST','data':{'token':_0x5d73['token']},'dataType':'json','timeout':15000,'success':function(_0x2f5c){if(_0x2f5c&&_0x2f5c['success']&&_0x2f5c['p1']&&_0x2f5c['p2']){_0x9d4a(_0x2f5c);}else{_0x8a4f('Unable to load video.');}},'error':function(_0x8b3e,_0x1c7f,_0x4e9a){var _0x6f2d='Failed to load video.';if(_0x1c7f==='timeout'){_0x6f2d='Request timeout. Please try again.';}_0x8a4f(_0x6f2d);}});};var _0x9d4a=function(_0x7a3c){try{var _0x2e1b=atob(_0x7a3c['p1'])+atob(_0x7a3c['p2'])+atob(_0x7a3c['p3'])+'?'+atob(_0x7a3c['p4']);_0x4c8e(_0x2e1b);}catch(_0x5f9d){_0x8a4f('Error processing video: '+_0x5f9d['message']);}};var _0x4c8e=function(_0x1d4e){try{jwplayer['key']=_0x5d73['jwKey'];var _0x8c2f=jwplayer('player')['setup']({'file':_0x1d4e,'type':'video/mp4','width':$(window)['width'](),'height':$(window)['height'](),'autostart':false,'controls':true,'stretching':'uniform','playbackRateControls':true,'displaytitle':false,'cast':{},'sharing':{'sites':[]}});_0x8c2f['on']('ready',function(){_0x1f8e('loading');console['log']('Player ready. Press play to start.');});_0x8c2f['on']('error',function(_0x3a7b){_0x8a4f('Playback error: '+(_0x3a7b['message']||'Unknown error'));});_0x8c2f['on']('complete',function(){console['log']('Video playback completed');});$(window)['on']('resize',function(){_0x8c2f['resize']($(window)['width'](),$(window)['height']());});}catch(_0x9e4d){_0x8a4f('Error initializing player: '+_0x9e4d['message']);}};$(document)['ready'](function(){_0x3b9e();});})();
    </script>
</body>
</html>
