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
        console.log('Respuesta completa del servidor:', data);
        
        if (data.success) {
            // Mostrar URL encriptada
            if (data.encryptedUrl && data.encryptedUrl !== 'undefined') {
                document.getElementById('encryptedUrl').value = data.encryptedUrl;
                console.log('URL encriptada recibida:', data.encryptedUrl);
            } else {
                console.error('URL encriptada no válida:', data.encryptedUrl);
                document.getElementById('encryptedUrl').value = 'Error: URL no válida';
            }
            
            // Mostrar código iframe
            if (data.iframeCode) {
                document.getElementById('iframeCode').value = data.iframeCode;
            }
            
            // Usar downloadUrl del servidor directamente
            if (data.downloadUrl && data.downloadUrl !== 'undefined') {
                document.getElementById('downloadLink').value = data.downloadUrl;
                console.log('URL de descarga del servidor:', data.downloadUrl);
            } else {
                // Método de respaldo: generar enlace manualmente
                console.log('Generando enlace de descarga como respaldo...');
                generateDownloadLink(data.encryptedUrl);
            }
            
            // Mostrar preview
            if (data.iframeCode) {
                document.getElementById('preview-container').innerHTML = data.iframeCode;
            }
            
            // Mostrar contenedor de resultados
            document.getElementById('result-container').style.display = 'block';
            
            // Debug info
            const debugInfo = document.getElementById('debugInfo');
            if (debugInfo) {
                debugInfo.textContent = JSON.stringify(data, null, 2);
            }
            
        } else {
            console.error('Error del servidor:', data);
            alert('Error: ' + (data.error || 'Error desconocido'));
            
            // Mostrar info de debug si está disponible
            const debugInfo = document.getElementById('debugInfo');
            if (debugInfo && data.debug) {
                debugInfo.textContent = JSON.stringify(data, null, 2);
                document.getElementById('result-container').style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('Error al generar la URL: ' + error.message);
    }
}

// Función de respaldo para generar enlace de descarga
function generateDownloadLink(encryptedUrl) {
    console.log('Generando enlace de descarga con URL:', encryptedUrl);
    
    if (!encryptedUrl || encryptedUrl === 'undefined' || encryptedUrl.trim() === '') {
        console.error('URL encriptada no válida:', encryptedUrl);
        document.getElementById('downloadLink').value = 'Error: URL no válida';
        return;
    }
    
    try {
        // Método 1: Usar URL object
        try {
            const url = new URL(encryptedUrl);
            const encryptedId = url.searchParams.get('v');
            
            if (encryptedId) {
                const currentUrl = window.location;
                const baseUrl = currentUrl.protocol + '//' + currentUrl.host;
                const pathArray = currentUrl.pathname.split('/');
                pathArray.pop(); // Remover 'dashboard.php'
                const basePath = pathArray.join('/');
                
                const downloadUrl = baseUrl + basePath + '/download.php?v=' + encodeURIComponent(encryptedId);
                
                document.getElementById('downloadLink').value = downloadUrl;
                console.log('URL de descarga generada (método URL):', downloadUrl);
                return;
            }
        } catch (urlError) {
            console.log('Método URL falló, intentando regex...', urlError.message);
        }
        
        // Método 2: Regex como respaldo
        const urlMatch = encryptedUrl.match(/[?&]v=([^&]+)/);
        if (urlMatch && urlMatch[1]) {
            const encryptedId = decodeURIComponent(urlMatch[1]);
            
            const currentUrl = window.location;
            const baseUrl = currentUrl.protocol + '//' + currentUrl.host;
            const pathArray = currentUrl.pathname.split('/');
            pathArray.pop();
            const basePath = pathArray.join('/');
            
            const downloadUrl = baseUrl + basePath + '/download.php?v=' + encodeURIComponent(encryptedId);
            
            document.getElementById('downloadLink').value = downloadUrl;
            console.log('URL de descarga generada (método regex):', downloadUrl);
            return;
        }
        
        // Si ningún método funciona
        console.error('No se pudo extraer el ID encriptado de la URL:', encryptedUrl);
        document.getElementById('downloadLink').value = 'Error: No se pudo extraer ID de la URL';
        
    } catch (e) {
        console.error('Error al generar enlace de descarga:', e);
        document.getElementById('downloadLink').value = 'Error: ' + e.message;
    }
}

// Función para copiar URL
function copyUrl() {
    const urlInput = document.getElementById('encryptedUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    showCopyFeedback(event.target);
}

// Función para copiar iframe
function copyIframe() {
    const iframeTextarea = document.getElementById('iframeCode');
    iframeTextarea.select();
    iframeTextarea.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    showCopyFeedback(event.target);
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
    
    showCopyFeedback(event.target);
}

// Función para mostrar feedback de copiado
function showCopyFeedback(btn) {
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
            field.placeholder = field.id === 'encryptedUrl' ? 'Esperando URL...' : 
                               field.id === 'iframeCode' ? 'Esperando código...' : 'Esperando enlace...';
        }
    });
    
    const debugInfo = document.getElementById('debugInfo');
    if (debugInfo) {
        debugInfo.textContent = '';
    }
});

// Función de debug mejorada
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

// Función para testing manual
function testGenerate() {
    console.log('=== TEST GENERATE ===');
    document.getElementById('videoId').value = 'nIqwsZvyQM1gMQ';
    generateVideo();
}
