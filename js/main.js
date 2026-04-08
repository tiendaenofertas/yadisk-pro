// Función para generar el video
async function generateVideo() {
    const videoIdInput = document.getElementById('videoId');
    const videoId = videoIdInput.value.trim();
    
    // Validación de entrada en el frontend
    if (!videoId) {
        alert('Por favor, ingrese un ID de video');
        return;
    }
    
    // Validar formato (solo alfanuméricos, guiones y guiones bajos)
    const videoIdPattern = /^[a-zA-Z0-9_-]+$/;
    if (!videoIdPattern.test(videoId)) {
        alert('El ID del video contiene caracteres no válidos. Solo se permiten letras, números, guiones y guiones bajos.');
        return;
    }
    
    // Validar longitud
    if (videoId.length < 5 || videoId.length > 100) {
        alert('El ID del video debe tener entre 5 y 100 caracteres');
        return;
    }
    
    // Obtener token CSRF
    const csrfToken = document.getElementById('csrfToken').value;
    
    if (!csrfToken) {
        alert('Error de seguridad: Token CSRF no encontrado. Por favor, recargue la página.');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('videoId', videoId);
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('api/generate-url.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Incluir cookies de sesión
        });
        
        // Verificar código de estado HTTP
        if (!response.ok) {
            if (response.status === 401) {
                alert('Sesión expirada. Por favor, inicie sesión nuevamente.');
                window.location.href = 'index.php';
                return;
            } else if (response.status === 403) {
                alert('Error de seguridad. Por favor, recargue la página.');
                window.location.reload();
                return;
            }
            throw new Error('Error del servidor: ' + response.status);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Validar que los datos recibidos sean válidos
            if (!data.encryptedUrl || data.encryptedUrl === 'undefined') {
                throw new Error('URL encriptada inválida');
            }
            
            // Mostrar URL encriptada
            document.getElementById('encryptedUrl').value = data.encryptedUrl;
            
            // Mostrar código iframe
            if (data.iframeCode) {
                document.getElementById('iframeCode').value = data.iframeCode;
            }
            
            // Mostrar enlace de descarga
            if (data.downloadUrl && data.downloadUrl !== 'undefined') {
                document.getElementById('downloadLink').value = data.downloadUrl;
            } else {
                // Método de respaldo
                generateDownloadLink(data.encryptedUrl);
            }
            
            // Mostrar preview
            if (data.iframeCode) {
                document.getElementById('preview-container').innerHTML = data.iframeCode;
            }
            
            // Mostrar contenedor de resultados
            document.getElementById('result-container').style.display = 'block';
            
        } else {
            const errorMsg = data.error || 'Error desconocido';
            alert('Error: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al generar la URL: ' + error.message);
    }
}

// Función de respaldo para generar enlace de descarga
function generateDownloadLink(encryptedUrl) {
    if (!encryptedUrl || encryptedUrl === 'undefined' || encryptedUrl.trim() === '') {
        document.getElementById('downloadLink').value = 'Error: URL no válida';
        return;
    }
    
    try {
        // Intentar extraer el parámetro 'v' de la URL
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
            return;
        }
        
        // Método alternativo con regex
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
            return;
        }
        
        document.getElementById('downloadLink').value = 'Error: No se pudo extraer ID';
        
    } catch (e) {
        console.error('Error al generar enlace de descarga:', e);
        document.getElementById('downloadLink').value = 'Error: ' + e.message;
    }
}

// Función para copiar URL
function copyUrl() {
    const urlInput = document.getElementById('encryptedUrl');
    
    if (!urlInput.value || urlInput.value === 'Esperando URL...') {
        alert('No hay URL disponible para copiar');
        return;
    }
    
    urlInput.select();
    urlInput.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showCopyFeedback(event.target);
    } catch (err) {
        alert('Error al copiar. Por favor, copie manualmente.');
    }
}

// Función para copiar iframe
function copyIframe() {
    const iframeTextarea = document.getElementById('iframeCode');
    
    if (!iframeTextarea.value || iframeTextarea.value === 'Esperando código...') {
        alert('No hay código iframe disponible para copiar');
        return;
    }
    
    iframeTextarea.select();
    iframeTextarea.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showCopyFeedback(event.target);
    } catch (err) {
        alert('Error al copiar. Por favor, copie manualmente.');
    }
}

// Función para copiar enlace de descarga
function copyDownloadLink() {
    const downloadInput = document.getElementById('downloadLink');
    
    if (!downloadInput.value || 
        downloadInput.value.startsWith('Error:') || 
        downloadInput.value === 'Esperando enlace...') {
        alert('No hay enlace de descarga disponible');
        return;
    }
    
    downloadInput.select();
    downloadInput.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showCopyFeedback(event.target);
    } catch (err) {
        alert('Error al copiar. Por favor, copie manualmente.');
    }
}

// Función para mostrar feedback de copiado
function showCopyFeedback(btn) {
    const originalText = btn.textContent;
    const originalBg = btn.style.background;
    
    btn.textContent = '¡Copiado!';
    btn.style.background = '#28a745';
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = originalBg;
    }, 2000);
}

// Sanitizar entrada HTML para prevenir XSS
function sanitizeHTML(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

// Event listeners al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Permitir generar con Enter
    const videoIdInput = document.getElementById('videoId');
    if (videoIdInput) {
        videoIdInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                generateVideo();
            }
        });
        
        // Validación en tiempo real
        videoIdInput.addEventListener('input', function(e) {
            const value = e.target.value;
            const isValid = /^[a-zA-Z0-9_-]*$/.test(value);
            
            if (!isValid && value !== '') {
                e.target.style.borderColor = '#dc3545';
                e.target.title = 'Solo se permiten letras, números, guiones y guiones bajos';
            } else {
                e.target.style.borderColor = '#ddd';
                e.target.title = '';
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
    
    // Ocultar contenedor de resultados
    const resultContainer = document.getElementById('result-container');
    if (resultContainer) {
        resultContainer.style.display = 'none';
    }
});

// Prevenir envío múltiple
let isGenerating = false;

const originalGenerateVideo = generateVideo;
generateVideo = async function() {
    if (isGenerating) {
        console.log('Ya hay una solicitud en proceso...');
        return;
    }
    
    isGenerating = true;
    
    try {
        await originalGenerateVideo();
    } finally {
        isGenerating = false;
    }
};
