// Función para generar el archivo o carpeta
async function generateVideo() {
    const videoId = document.getElementById('videoId').value.trim();
    const fileType = document.getElementById('fileType').value;
    
    if (!videoId) {
        alert('Por favor, ingrese un ID de archivo o carpeta');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('videoId', videoId);
        formData.append('fileType', fileType);
        
        const response = await fetch('api/generate-url.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Respuesta completa del servidor:', data);
        
        if (data.success) {
            // Ocultar/mostrar secciones según el tipo
            const iframeSection = document.getElementById('iframe-section');
            const downloadSection = document.getElementById('download-section');
            const folderSection = document.getElementById('folder-section');
            
            // Mostrar URL encriptada
            if (data.encryptedUrl && data.encryptedUrl !== 'undefined') {
                document.getElementById('encryptedUrl').value = data.encryptedUrl;
                console.log('URL encriptada recibida:', data.encryptedUrl);
            } else {
                console.error('URL encriptada no válida:', data.encryptedUrl);
                document.getElementById('encryptedUrl').value = 'Error: URL no válida';
            }
            
            // Verificar si es una carpeta
            if (data.isFolder || data.resourceType === 'dir') {
                // Es una carpeta - mostrar solo la opción de ver carpeta
                iframeSection.style.display = 'none';
                downloadSection.style.display = 'none';
                folderSection.style.display = 'block';
                
                // Configurar enlace para ver carpeta
                const viewFolderLink = document.getElementById('viewFolderLink');
                const folderUrl = 'list-folder.php?v=' + encodeURIComponent(data.encryptedUrl.split('v=')[1]);
                viewFolderLink.href = folderUrl;
                viewFolderLink.onclick = function(e) {
                    e.preventDefault();
                    window.open(folderUrl, '_blank');
                };
                
                // Preview para carpeta
                const previewContainer = document.getElementById('preview-container');
                previewContainer.innerHTML = `
                    <div style="text-align: center; padding: 50px; color: #666;">
                        <div style="font-size: 80px; margin-bottom: 20px;">📁</div>
                        <h3 style="margin-bottom: 10px;">Carpeta de Yandex Disk</h3>
                        <p>Esta es una carpeta con múltiples archivos</p>
                        <p style="margin-top: 15px;">
                            <a href="${viewFolderLink.href}" target="_blank" 
                               style="display: inline-block; padding: 12px 30px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px;">
                                📂 Abrir carpeta
                            </a>
                        </p>
                    </div>
                `;
            } else {
                // Es un archivo individual
                iframeSection.style.display = 'block';
                downloadSection.style.display = 'block';
                folderSection.style.display = 'none';
                
                // Mostrar código iframe solo para videos
                const iframeTextarea = document.getElementById('iframeCode');
                if ((fileType === 'video' || fileType === 'auto') && data.iframeCode) {
                    iframeTextarea.value = data.iframeCode;
                    iframeTextarea.placeholder = 'Código iframe para incrustar';
                } else {
                    iframeTextarea.value = '';
                    iframeTextarea.placeholder = 'El iframe solo está disponible para archivos de video';
                }
                
                // Mostrar URL de descarga
                if (data.downloadUrl && data.downloadUrl !== 'undefined') {
                    document.getElementById('downloadLink').value = data.downloadUrl;
                    console.log('URL de descarga del servidor:', data.downloadUrl);
                } else {
                    console.log('Generando enlace de descarga como respaldo...');
                    generateDownloadLink(data.encryptedUrl);
                }
                
                // Mostrar preview solo para videos
                const previewContainer = document.getElementById('preview-container');
                if ((fileType === 'video' || fileType === 'auto') && data.iframeCode) {
                    previewContainer.innerHTML = data.iframeCode;
                } else {
                    const fileTypeNames = {
                        'rar': 'Archivo RAR',
                        'zip': 'Archivo ZIP',
                        '7z': 'Archivo 7Z',
                        'pdf': 'Documento PDF',
                        'other': 'Archivo'
                    };
                    
                    const typeName = fileTypeNames[fileType] || 'Archivo';
                    
                    previewContainer.innerHTML = `
                        <div style="text-align: center; padding: 50px; color: #666;">
                            <div style="font-size: 80px; margin-bottom: 20px;">📦</div>
                            <h3 style="margin-bottom: 10px;">${typeName} listo para descargar</h3>
                            <p>Use el enlace de descarga generado arriba</p>
                        </div>
                    `;
                }
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
                pathArray.pop();
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
    
    if (!iframeTextarea.value || iframeTextarea.value.includes('solo está disponible')) {
        alert('El código iframe solo está disponible para archivos de video');
        return;
    }
    
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
            field.placeholder = fieldId === 'encryptedUrl' ? 'Esperando URL...' : 
                               fieldId === 'iframeCode' ? 'Solo disponible para videos' : 'Esperando enlace...';
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
    console.log('Tipo archivo:', document.getElementById('fileType').value);
    
    const encryptedUrl = document.getElementById('encryptedUrl').value;
    if (encryptedUrl && encryptedUrl !== 'undefined') {
        console.log('Intentando regenerar enlace...');
        generateDownloadLink(encryptedUrl);
    }
}
