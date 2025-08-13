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
            // Mostrar URL encriptada
            if (data.encryptedUrl) {
                document.getElementById('encryptedUrl').value = data.encryptedUrl;
                console.log('URL encriptada recibida:', data.encryptedUrl);
            } else {
                console.error('URL encriptada no recibida');
                document.getElementById('encryptedUrl').value = 'Error: URL no recibida';
            }
            
            // Mostrar código iframe
            document.getElementById('iframeCode').value = data.iframeCode;
            
            // Generar enlace de descarga CORREGIDO
            generateDownloadLink(data.encryptedUrl);
            
            // Mostrar preview
            document.getElementById('preview-container').innerHTML = data.iframeCode;
            
            // Mostrar contenedor de resultados
            document.getElementById('result-container').style.display = 'block';
            
            // Debug info
            const debugInfo = document.getElementById('debugInfo');
            if (debugInfo) {
                debugInfo.textContent = JSON.stringify(data, null, 2);
            }
            
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al generar la URL: ' + error.message);
    }
}

// Función CORREGIDA para generar enlace de descarga
function generateDownloadLink(encryptedUrl) {
    console.log('Generando enlace de descarga con URL:', encryptedUrl);
    
    if (encryptedUrl && encryptedUrl !== 'undefined' && encryptedUrl.trim() !== '') {
        try {
            // Extraer el parámetro v de la URL
            const urlMatch = encryptedUrl.match(/[?&]v=([^&]+)/);
            if (urlMatch && urlMatch[1]) {
                const encryptedId = urlMatch[1];
                
                // Construir la URL de descarga correctamente
                const currentUrl = window.location;
                const baseUrl = currentUrl.protocol + '//' + currentUrl.host;
                const pathArray = currentUrl.pathname.split('/');
                pathArray.pop(); // Remover 'dashboard.php'
                const basePath = pathArray.join('/');
                
                const downloadUrl = baseUrl + basePath + '/download.php?v=' + encryptedId;
                
                document.getElementById('downloadLink').value = downloadUrl;
                console.log('URL de descarga generada:', downloadUrl);
                
            } else {
                console.error('No se pudo extraer el ID encriptado de la URL:', encryptedUrl);
                document.getElementById('downloadLink').value = 'Error: No se pudo extraer ID';
            }
        } catch (e) {
            console.error('Error al generar enlace de descarga:', e);
            document.getElementById('downloadLink').value = 'Error: ' + e.message;
        }
    } else {
        console.error('URL encriptada no válida:', encryptedUrl);
        document.getElementById('downloadLink').value = 'Error: URL no válida';
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
    if (!downloadInput.value || downloadInput.value.startsWith('Error:') || downloadInput.value === 'Esperando enlace...') {
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
    
    // Limpiar campos al cargar la página
    const fields = ['encryptedUrl', 'iframeCode', 'downloadLink'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
        }
    });
    
    const debugInfo = document.getElementById('debugInfo');
    if (debugInfo) {
        debugInfo.textContent = '';
    }
});

// Función de debug para verificar el estado
function debugState() {
    console.log('=== DEBUG STATE ===');
    console.log('URL Encriptada:', document.getElementById('encryptedUrl').value);
    console.log('Enlace descarga:', document.getElementById('downloadLink').value);
    console.log('Código iframe:', document.getElementById('iframeCode').value);
    
    // Intentar regenerar el enlace de descarga
    const encryptedUrl = document.getElementById('encryptedUrl').value;
    if (encryptedUrl && encryptedUrl !== 'undefined') {
        console.log('Intentando regenerar enlace...');
        generateDownloadLink(encryptedUrl);
    }
}