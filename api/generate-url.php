<?php
require_once '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Verificar si está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener el ID del video/archivo/carpeta
$videoId = $_POST['videoId'] ?? '';
$fileType = $_POST['fileType'] ?? 'auto';

if (empty($videoId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID del archivo requerido']);
    exit;
}

try {
    // Función para detectar el tipo de recurso
    function detectResourceType($videoId) {
        $publicKeys = [
            'https://disk.yandex.com/d/' . $videoId,
            'https://disk.yandex.com/i/' . $videoId,
            'https://yadi.sk/d/' . $videoId,
            'https://yadi.sk/i/' . $videoId
        ];
        
        foreach ($publicKeys as $publicKey) {
            $yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources?" . http_build_query([
                'public_key' => $publicKey
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $yandexUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 && !empty($response)) {
                $data = json_decode($response, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['type'])) {
                    return [
                        'success' => true,
                        'type' => $data['type'], // 'dir' o 'file'
                        'publicKey' => $publicKey,
                        'data' => $data
                    ];
                }
            }
        }
        
        return ['success' => false, 'error' => 'No se pudo detectar el tipo de recurso'];
    }
    
    // Detectar tipo de recurso si es auto
    $resourceType = null;
    $isFolder = false;
    
    if ($fileType === 'auto' || $fileType === 'folder') {
        $detection = detectResourceType($videoId);
        
        if ($detection['success']) {
            $resourceType = $detection['type'];
            $isFolder = ($resourceType === 'dir');
        }
    }
    
    // Generar URL encriptada
    $encryptedId = encryptVideoId($videoId);
    $encryptedUrl = generateVideoUrl($videoId);
    
    if (empty($encryptedUrl)) {
        throw new Exception('No se pudo generar la URL encriptada');
    }
    
    // Si es una carpeta, solo devolver la URL para list-folder.php
    if ($isFolder) {
        echo json_encode([
            'success' => true,
            'isFolder' => true,
            'resourceType' => 'dir',
            'encryptedUrl' => $encryptedUrl,
            'folderUrl' => str_replace('video.php?v=', 'list-folder.php?v=', $encryptedUrl),
            'message' => 'Carpeta detectada - usa el enlace para ver los archivos'
        ]);
        exit;
    }
    
    // Para archivos individuales, generar todo normalmente
    $iframeCode = '<iframe src="' . htmlspecialchars($encryptedUrl) . '" frameborder="0" width="510" height="400" scrolling="no" allowfullscreen></iframe>';
    $downloadUrl = str_replace('video.php?v=', 'download.php?v=', $encryptedUrl);
    
    $debugInfo = [
        'videoId' => $videoId,
        'encryptedId' => $encryptedId,
        'encryptedUrl' => $encryptedUrl,
        'downloadUrl' => $downloadUrl,
        'baseUrl' => BASE_URL,
        'fileType' => $fileType,
        'resourceType' => $resourceType,
        'isFolder' => $isFolder
    ];
    
    echo json_encode([
        'success' => true,
        'isFolder' => false,
        'resourceType' => $resourceType ?? 'file',
        'encryptedUrl' => $encryptedUrl,
        'downloadUrl' => $downloadUrl,
        'iframeCode' => $iframeCode,
        'debug' => $debugInfo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al generar URL: ' . $e->getMessage(),
        'debug' => [
            'videoId' => $videoId ?? null,
            'fileType' => $fileType ?? null,
            'exception' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]
    ]);
}
?>