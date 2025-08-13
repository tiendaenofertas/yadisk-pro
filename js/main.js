// Función para generar el video
async function generateVideo() {
    const videoId = document.getElementById('videoId').value.trim();
    
    if (!videoId) {
        alert('Por favor, ingrese un ID de video');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('videoId', videoId);
        
        const response = await fetch('api/generate-url.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar resultados - verificar que encryptedUrl existe
            if (data.encryptedUrl) {
                document.getElementById('encryptedUrl').value = data.encryptedUrl;
            } else {
                console.error('URL encriptada no recibida');
            }
            
            document.getElementById('iframeCode').value = data.iframeCode;
            
            // Generar enlace de descarga DESPUÉS de establecer la URL encriptada
            setTimeout(() => {
                generateDownloadLink();
            }, 100);
            
            // Mostrar preview
            document.getElementById('preview-container').innerHTML = data.iframeCode;
            
            // Mostrar contenedor de resultados
            document.getElementById('result-container').style.display = 'block';
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al generar la URL: ' + error.message);
    }
}

// Función para generar enlace de descarga (MEJORADA)
function generateDownloadLink() {
    const encryptedUrl = document.getElementById('encryptedUrl').value;
    console.log('URL encriptada:', encryptedUrl); // Para debug
    
    if (encryptedUrl && encryptedUrl !== 'undefined') {
        try {
            // Extraer el parámetro v de la URL
            const urlMatch = encryptedUrl.match(/[?&]v=([^&]+)/);
            if (urlMatch && urlMatch[1]) {
                const encryptedId = urlMatch[1];
                // Construir la URL de descarga correctamente
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const downloadUrl = baseUrl + '/yadisk-pro2/download.php?v=' + encryptedId;
                document.getElementById('downloadLink').value = downloadUrl;
                console.log('URL de descarga generada:', downloadUrl); // Para debug
            } else {
                console.error('No se pudo extraer el ID encriptado de la URL');
            }
        } catch (e) {
            console.error('Error al generar enlace de descarga:', e);
        }
    } else {
        console.error('URL encriptada no válida:', encryptedUrl);
    }
}

// Función para copiar URL
function copyUrl() {
    const urlInput = document.getElementById('encryptedUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // Para móviles
    document.execCommand('copy');
    
    // Mostrar feedback
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = '¡Copiado!';
    const originalBg = btn.style.background;
    btn.style.background = '#28a745';
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = originalBg;
    }, 2000);
}

// Función para copiar iframe
function copyIframe() {
    const iframeTextarea = document.getElementById('iframeCode');
    iframeTextarea.select();
    iframeTextarea.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Mostrar feedback
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = '¡Copiado!';
    const originalBg = btn.style.background;
    btn.style.background = '#28a745';
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = originalBg;
    }, 2000);
}

// Función para copiar enlace de descarga
function copyDownloadLink() {
    const downloadInput = document.getElementById('downloadLink');
    if (!downloadInput.value) {
        alert('No hay enlace de descarga disponible');
        return;
    }
    
    downloadInput.select();
    downloadInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Mostrar feedback
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = '¡Copiado!';
    const originalBg = btn.style.background;
    btn.style.background = '#28a745';
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = originalBg;
    }, 2000);
}

// Permitir generar con Enter
document.addEventListener('DOMContentLoaded', function() {
    const videoIdInput = document.getElementById('videoId');
    if (videoIdInput) {
        videoIdInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                generateVideo();
            }
        });
    }
});

// Función de debug para verificar el estado
function debugState() {
    console.log('URL Encriptada:', document.getElementById('encryptedUrl').value);
    console.log('Enlace descarga:', document.getElementById('downloadLink').value);
}
