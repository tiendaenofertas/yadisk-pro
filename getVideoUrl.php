<?php
session_start();
header('Content-Type: application/json');

// Verificar token
$token = $_POST['token'] ?? '';

if (empty($token) || !isset($_SESSION['video_token_' . $token])) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

// Obtener el ID del video
$videoId = $_SESSION['video_token_' . $token];

// Eliminar el token después de usarlo (opcional, para mayor seguridad)
unset($_SESSION['video_token_' . $token]);

// Hacer la petición a Yandex
$yandexUrl = "https://cloud-api.yandex.net/v1/disk/public/resources/download?" . http_build_query([
    'public_key' => 'https://yadi.sk/i/' . $videoId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $yandexUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['href'])) {
        echo json_encode([
            'success' => true,
            'url' => $data['href']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No video URL found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to get video from Yandex']);
}
?>