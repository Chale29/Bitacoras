@extends('Template-profesor')


@section('title', 'Escáner QR - Profesor')


@section('content')
<div class="scanner-wrapper">
    <div class="scanner-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Realizar solicitud</h2>
            <a href="{{ route('profesor-llave.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>


        <!-- Estado del escáner -->
        <div class="scanner-status mb-3">
            <div id="scanner-info" class="alert alert-info">
                <i class="bi bi-camera"></i> Inicializando escáner...
            </div>
            <!-- Botón para solicitar permisos manualmente -->
            <button id="request-permission" class="btn btn-outline-primary w-100 mt-2" style="display: none;">
                <i class="bi bi-shield-check"></i> Permitir Acceso a Cámara
            </button>
        </div>


        <!-- Vista de la cámara -->
        <div class="camera-container">
            <div id="reader" class="qr-reader"></div>
            <div class="scanner-overlay" id="scanner-overlay" style="display: none;">
                <div class="scanner-frame"></div>
                <p class="scanner-text">Enfoca el código QR dentro del marco</p>
            </div>
        </div>


        <!-- Controles -->
        <div class="scanner-controls mt-4">
            <div class="row g-2">
                <div class="col-6">
                    <button id="start-scan" class="btn btn-success w-100" disabled>
                        <i class="bi bi-camera"></i> Iniciar
                    </button>
                </div>
                <div class="col-6">
                    <button id="stop-scan" class="btn btn-danger w-100" disabled>
                        <i class="bi bi-camera-video-off"></i> Detener
                    </button>
                </div>
                <div class="col-6 mt-2">
                    <button id="switch-camera" class="btn btn-outline-primary w-100" disabled>
                        <i class="bi bi-arrow-repeat"></i> Cambiar Cámara
                    </button>
                </div>
                <div class="col-6 mt-2">
                    <button id="test-camera" class="btn btn-outline-info w-100">
                        <i class="bi bi-gear"></i> Probar Cámara
                    </button>
                </div>
            </div>
        </div>


        <!-- Resultado del escaneo -->
        <div id="scan-result" class="mt-4" style="display: none;">
            <div class="alert alert-success">
                <h5><i class="bi bi-check-circle"></i> QR Escaneado</h5>
                <p class="mb-0">Código: <strong id="scanned-code"></strong></p>
            </div>
        </div>


        <!-- Input manual como alternativa -->
        <div class="manual-input mt-4">
            <h5>Entrada Manual</h5>
            <form id="manual-form">
                <div class="input-group mb-3">
                    <input type="text" id="manual-code" class="form-control" placeholder="Ingresa el código QR manualmente">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal de resultado -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modal-header-success" style="display: none;">
                <h5 class="modal-title text-success">
                    <i class="bi bi-check-circle"></i> ¡Éxito!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-header" id="modal-header-error" style="display: none;">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modal-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<!-- Librera Html5-QRCode para escaneo QR -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
$(document).ready(function() {
    let html5QrcodeScanner = null;
    let isScanning = false;
    let cameras = [];
    let currentCameraIndex = 0;
    let currentCameraId = null;


    // Información de debugging
    console.log('Inicializando QR Scanner...');
    console.log('Navegador:', navigator.userAgent);
    console.log('HTTPS:', location.protocol === 'https:');
    console.log('getUserMedia disponible:', !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia));


    // Función para solicitar permisos de cámara
    async function requestCameraPermission() {
        try {
            $('#scanner-info').html('<i class="bi bi-camera"></i> Solicitando permisos de cámara...')
                .removeClass().addClass('alert alert-info');


            // Solicitar permisos de cmara
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
           
            // Detener el stream inmediatamente, solo necesitábamos los permisos
            stream.getTracks().forEach(track => track.stop());
           
            $('#scanner-info').html('<i class="bi bi-check-circle"></i> ¡Permisos concedidos! Detectando cámaras...')
                .removeClass().addClass('alert alert-success');
           
            return true;
        } catch (error) {
            console.error('Error solicitando permisos:', error);
           
            let errorMessage = 'No se pudieron obtener permisos de cámara';
            let solutions = [];
           
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Permisos de cámara denegados';
                solutions = [
                    'Haz clic en "Permitir" cuando aparezca la notificación',
                    'Si ya la rechazaste, busca el ícono de cámara 🎥 en la barra de direcciones',
                    'Haz clic en él y selecciona "Permitir siempre"',
                    'Luego recarga la página'
                ];
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No se encontró cámara en tu dispositivo';
                solutions = [
                    'Conecta una cámara web si usas computadora',
                    'Intenta desde un dispositivo móvil',
                    'Verifica que la cámara esté funcionando'
                ];
            } else if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                errorMessage = 'Se requiere HTTPS para usar la cámara';
                solutions = [
                    'Accede al sitio usando https://',
                    'O usa localhost para desarrollo'
                ];
            }
           
            let solutionsHtml = solutions.length > 0
                ? '<br><br><small><strong>¿Cómo solucionarlo?</strong><br>• ' + solutions.join('<br>• ') + '</small>'
                : '';
               
            $('#scanner-info').html(`<i class="bi bi-exclamation-triangle"></i> ${errorMessage}${solutionsHtml}`)
                .removeClass().addClass('alert alert-danger');
           
            // Mostrar botón para reintentar permisos
            $('#request-permission').show();
           
            return false;
        }
    }


    // Configuración del escáner
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        disableFlip: false,
        videoConstraints: {
            facingMode: "environment" // Preferir cámara trasera
        }
    };


    // Inicializar escáner
    async function initScanner() {
        try {
            // Verificar si el navegador soporta getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Tu navegador no soporta acceso a la cámara');
            }


            // Primero solicitar permisos de cámara
            const permissionsGranted = await requestCameraPermission();
            if (!permissionsGranted) {
                return; // No continuar si no se concedieron permisos
            }


            // Obtener cámaras disponibles
            const devices = await Html5Qrcode.getCameras();
           
            if (devices && devices.length > 0) {
                cameras = devices;
               
                console.log('Cámaras disponibles:', cameras.map(cam => cam.label || 'Cámara sin nombre'));
               
                // Intentar encontrar cámara trasera
                const backCamera = cameras.find(camera => {
                    const label = camera.label.toLowerCase();
                    return label.includes('back') ||
                           label.includes('rear') ||
                           label.includes('trasera') ||
                           label.includes('environment') ||
                           label.includes('facing back');
                });
               
                if (backCamera) {
                    currentCameraId = backCamera.id;
                    currentCameraIndex = cameras.findIndex(cam => cam.id === backCamera.id);
                    console.log('Usando cámara trasera:', backCamera.label);
                } else {
                    currentCameraId = cameras[0].id;
                    currentCameraIndex = 0;
                    console.log('Usando primera cámara disponible:', cameras[0].label);
                }


                $('#scanner-info').html('<i class="bi bi-check-circle"></i> ¡Todo listo! Presiona "Iniciar" para escanear códigos QR')
                    .removeClass().addClass('alert alert-success');
                $('#start-scan').prop('disabled', false);
               
                if (cameras.length > 1) {
                    $('#switch-camera').prop('disabled', false);
                    $('#scanner-info').append(`<br><small>Se encontraron ${cameras.length} cámaras. Puedes cambiar entre ellas.</small>`);
                }
            } else {
                throw new Error('No se encontraron cámaras disponibles en tu dispositivo');
            }


        } catch (error) {
            console.error('Error inicializando cámara:', error);
            let errorMessage = 'Error desconocido';
           
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Permisos de cámara denegados. Por favor, permite el acceso a la cámara.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No se encontró ninguna cámara en tu dispositivo.';
            } else if (error.name === 'NotSupportedError') {
                errorMessage = 'Tu navegador no soporta acceso a la cámara.';
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'La cámara está siendo usada por otra aplicación.';
            } else if (error.message) {
                errorMessage = error.message;
            }
           
            $('#scanner-info').html(`<i class="bi bi-exclamation-triangle"></i> ${errorMessage}`)
                .removeClass().addClass('alert alert-danger');
               
            // Mostrar consejos para resolver el problema
            $('#scanner-info').append(`
                <br><small class="mt-2">
                    <strong>Consejos:</strong><br>
                    • Asegúrate de permitir el acceso a la cámara<br>
                    • Prueba en HTTPS (requerido en muchos navegadores)<br>
                    • Cierra otras aplicaciones que usen la cámara<br>
                    • Intenta con otro navegador (Chrome, Firefox, Safari)
                </small>
            `);
        }
    }


    // Iniciar escaneo
    async function startScan() {
        if (isScanning) return;


        try {
            // Verificar que tenemos una cámara seleccionada
            if (!currentCameraId) {
                throw new Error('No hay cámara disponible para usar');
            }


            html5QrcodeScanner = new Html5Qrcode("reader");
           
            // Callback cuando se detecta un QR
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                console.log('QR Code detected:', decodedText);
               
                // Mostrar código detectado
                $('#scanned-code').text(decodedText);
                $('#scan-result').show();
               
                // Procesar QR automáticamente despus de 500ms
                setTimeout(() => {
                    processQRCode(decodedText);
                }, 500);
            };


            // Callback de error (opcional, para debugging)
            const qrCodeErrorCallback = (error) => {
                // No mostrar errores de no detección, son muy frecuentes
                // Solo logear en consola para debugging
                // console.log('QR scan attempt:', error);
            };


            // Configuración mejorada
            const scanConfig = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                disableFlip: false,
                videoConstraints: {
                    facingMode: "environment",
                    width: { ideal: 1280, max: 1920 },
                    height: { ideal: 720, max: 1080 }
                }
            };


            console.log('Iniciando scanner con cámara:', currentCameraId);
           
            // Iniciar el escáner
            await html5QrcodeScanner.start(
                currentCameraId,
                scanConfig,
                qrCodeSuccessCallback,
                qrCodeErrorCallback
            );


            isScanning = true;
            $('#start-scan').prop('disabled', true);
            $('#stop-scan').prop('disabled', false);
            $('#scanner-overlay').show();
            $('#scanner-info').html('<i class="bi bi-search"></i> Escaneando... Enfoca el código QR')
                .removeClass().addClass('alert alert-primary');


        } catch (error) {
            console.error('Error iniciando escáner:', error);
           
            let errorMessage = 'Error desconocido al iniciar el escáner';
           
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Permisos de cámara denegados. Permite el acceso y recarga la página.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No se pudo acceder a la cámara seleccionada.';
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'La cámara está ocupada por otra aplicación. Ciérrala e intenta de nuevo.';
            } else if (error.name === 'OverconstrainedError') {
                errorMessage = 'La configuracin de la cámara no es compatible. Intenta con otra cámara.';
            } else if (error.message) {
                errorMessage = error.message;
            }
           
            $('#scanner-info').html(`<i class="bi bi-exclamation-triangle"></i> ${errorMessage}`)
                .removeClass().addClass('alert alert-danger');
               
            isScanning = false;
            $('#start-scan').prop('disabled', false);
            $('#stop-scan').prop('disabled', true);
        }
    }


    // Detener escaneo
    async function stopScan() {
        if (html5QrcodeScanner && isScanning) {
            try {
                await html5QrcodeScanner.stop();
                await html5QrcodeScanner.clear();
            } catch (error) {
                console.error('Error deteniendo escáner:', error);
            }
        }
       
        html5QrcodeScanner = null;
        isScanning = false;
        $('#start-scan').prop('disabled', false);
        $('#stop-scan').prop('disabled', true);
        $('#scanner-overlay').hide();
        $('#scanner-info').html('<i class="bi bi-camera"></i> Escáner detenido')
            .removeClass().addClass('alert alert-secondary');
        $('#scan-result').hide();
    }


    // Cambiar cámara
    async function switchCamera() {
        if (cameras.length <= 1) return;
       
        await stopScan();
       
        // Cambiar al siguiente índice
        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        currentCameraId = cameras[currentCameraIndex].id;
       
        $('#scanner-info').html('<i class="bi bi-arrow-repeat"></i> Cambiando cámara...')
            .removeClass().addClass('alert alert-info');
       
        // Reiniciar escáner con nueva cámara después de un breve delay
        setTimeout(async () => {
            await startScan();
        }, 1000);
    }


    // Procesar código QR
    async function processQRCode(code) {
        await stopScan(); // Detener escaneo mientras procesamos
       
        // Mostrar estado de procesamiento
        $('#scanner-info').html('<i class="bi bi-hourglass-split"></i> Procesando código QR...')
            .removeClass().addClass('alert alert-warning');
       
        try {
            const response = await $.ajax({
                url: '{{ route("profesor-llave.escanear-qr") }}',
                method: 'POST',
                data: {
                    qr_code: code,
                    _token: '{{ csrf_token() }}'
                },
                timeout: 10000 // 10 segundos de timeout
            });


            if (response.success) {
                showModal(true, response.mensaje);
               
                // Redirigir después de 3 segundos
                setTimeout(() => {
                    window.location.href = '{{ route("profesor-llave.index") }}';
                }, 3000);
            } else {
                showModal(false, response.error || 'Error procesando el código QR');
               
                // Permitir escanear de nuevo después del error
                setTimeout(() => {
                    $('#scanner-info').html('<i class="bi bi-camera"></i> Listo para escanear')
                        .removeClass().addClass('alert alert-success');
                    $('#start-scan').prop('disabled', false);
                }, 3000);
            }


        } catch (xhr) {
            let errorMsg = 'Error de conexión';
           
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            } else if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    errorMsg = errorResponse.error || errorMsg;
                } catch (e) {
                    errorMsg = 'Error del servidor';
                }
            }
           
            showModal(false, errorMsg);
           
            // Permitir escanear de nuevo después del error
            setTimeout(() => {
                $('#scanner-info').html('<i class="bi bi-camera"></i> Listo para escanear')
                    .removeClass().addClass('alert alert-success');
                $('#start-scan').prop('disabled', false);
            }, 3000);
        }
    }


    // Mostrar modal de resultado
    function showModal(success, message) {
        if (success) {
            $('#modal-header-success').show();
            $('#modal-header-error').hide();
        } else {
            $('#modal-header-success').hide();
            $('#modal-header-error').show();
        }
       
        $('#modal-message').text(message);
        $('#resultModal').modal('show');
    }


    // Event listeners
    $('#start-scan').click(startScan);
    $('#stop-scan').click(stopScan);
    $('#switch-camera').click(switchCamera);
   
    // Botón para solicitar permisos manualmente
    $('#request-permission').click(async function() {
        $('#request-permission').hide();
        await initScanner();
    });
   
    // Botón para probar cámara
    $('#test-camera').click(async function() {
        try {
            $('#scanner-info').html('<i class="bi bi-hourglass-split"></i> Probando acceso a la cámara...')
                .removeClass().addClass('alert alert-info');
               
            // Probar acceso básico a la cámara
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
           
            // Si llegamos aquí, los permisos están bien
            stream.getTracks().forEach(track => track.stop());
           
            $('#scanner-info').html('<i class="bi bi-check-circle"></i> ¡Cámara funciona correctamente! Ahora puedes iniciar el escáner.')
                .removeClass().addClass('alert alert-success');
               
            // Re-inicializar el escáner después de confirmar que la cámara funciona
            setTimeout(initScanner, 1000);
           
        } catch (error) {
            console.error('Error probando cámara:', error);
           
            let errorMessage = 'Error al probar la cámara';
            let solutions = [];
           
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Permisos de cámara denegados';
                solutions = [
                    'Haz clic en el ícono de cámara en la barra de direcciones',
                    'Selecciona "Permitir" para el acceso a la cámara',
                    'Recarga la página después de dar permisos'
                ];
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No se encontró cámara en tu dispositivo';
                solutions = [
                    'Conecta una cámara web si usas una computadora',
                    'Verifica que tu cámara no est dañada',
                    'Intenta desde un dispositivo mvil'
                ];
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'Cámara ocupada por otra aplicación';
                solutions = [
                    'Cierra otras aplicaciones que usen la cámara',
                    'Reinicia el navegador',
                    'Reinicia el dispositivo si es necesario'
                ];
            }
           
            let solutionsHtml = solutions.length > 0
                ? '<br><small><strong>Soluciones:</strong><br>• ' + solutions.join('<br>• ') + '</small>'
                : '';
               
            $('#scanner-info').html(`<i class="bi bi-exclamation-triangle"></i> ${errorMessage}${solutionsHtml}`)
                .removeClass().addClass('alert alert-danger');
        }
    });
   
    // Formulario manual
    $('#manual-form').submit(function(e) {
        e.preventDefault();
        const code = $('#manual-code').val().trim();
        if (code) {
            processQRCode(code);
        }
    });


    // Inicializar al cargar
    initScanner();


    // Limpiar recursos al salir
    $(window).on('beforeunload', async function() {
        await stopScan();
    });


    // Manejar cambios de visibilidad de la página (móviles)
    document.addEventListener('visibilitychange', async function() {
        if (document.hidden && isScanning) {
            await stopScan();
        }
    });


    // Manejar orientación en móviles
    window.addEventListener('orientationchange', function() {
        if (isScanning) {
            setTimeout(async () => {
                await stopScan();
                setTimeout(startScan, 1000);
            }, 500);
        }
    });
});
</script>
@endpush


@push('styles')
<style>
.scanner-wrapper {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
    min-height: 100vh;
}


.scanner-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}


.camera-container {
    position: relative;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 1/1;
    max-height: 400px;
    margin: 0 auto;
}


.qr-reader {
    width: 100%;
    height: 100%;
    border-radius: 10px;
}


.qr-reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 10px;
}


.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    pointer-events: none;
    z-index: 10;
}


.scanner-frame {
    width: 200px;
    height: 200px;
    border: 3px solid #28a745;
    border-radius: 10px;
    position: relative;
    animation: pulse 2s infinite;
}


.scanner-frame::before,
.scanner-frame::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 3px solid #28a745;
}


.scanner-frame::before {
    top: -3px;
    left: -3px;
    border-right: none;
    border-bottom: none;
}


.scanner-frame::after {
    bottom: -3px;
    right: -3px;
    border-left: none;
    border-top: none;
}


.scanner-text {
    color: white;
    margin-top: 20px;
    text-align: center;
    background: rgba(0,0,0,0.8);
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
}


@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}


.manual-input {
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
}


.btn {
    border-radius: 10px;
    font-weight: 500;
}


.alert {
    border-radius: 10px;
    border: none;
}


/* Ocultar elementos internos del html5-qrcode */
#reader__scan_region {
    background: transparent !important;
}


#reader__dashboard_section {
    display: none !important;
}


#reader__camera_permission_button {
    display: none !important;
}


/* Responsive */
@media (max-width: 768px) {
    .scanner-wrapper {
        padding: 10px;
    }
   
    .scanner-container {
        padding: 15px;
    }
   
    .scanner-frame {
        width: 160px;
        height: 160px;
    }
   
    .camera-container {
        max-height: 320px;
    }
   
    .scanner-text {
        font-size: 12px;
        padding: 8px 16px;
    }
}


/* Orientación horizontal en móviles */
@media (max-height: 600px) and (orientation: landscape) {
    .camera-container {
        max-height: 250px;
    }
   
    .scanner-frame {
        width: 140px;
        height: 140px;
    }
   
    .scanner-text {
        font-size: 11px;
        margin-top: 10px;
    }
}


/* iOS específico */
@supports (-webkit-touch-callout: none) {
    .qr-reader video {
        -webkit-transform: scaleX(-1);
        transform: scaleX(-1);
    }
}


/* Android Chrome */
@media screen and (-webkit-min-device-pixel-ratio: 0) {
    .qr-reader {
        border-radius: 10px;
        overflow: hidden;
    }
}
</style>
@endpush
