@extends('Template-administrador')


@section('title', 'QR Temporales Activos')


@section('content')
<div class="wrapper">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-qr-code-scan"></i> QR Temporales Activos
            </h2>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2" id="total-qrs">{{ $qrsTemporales->count() }}</span>
                <small class="text-muted">
                    <i class="bi bi-arrow-clockwise loading-pulse"></i>
                    Tiempo real
                </small>
            </div>
        </div>


        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Vista Administrativa:</strong> Aquí puedes ver todas las solicitudes temporales generados por los profesores que aún están activos.
        </div>


        @if($qrsTemporales->count() > 0)
            <div class="row" id="qrs-container">
                @foreach($qrsTemporales as $qr)
                    <div class="col-md-6 col-lg-4 mb-4" data-qr-id="{{ $qr->id }}">
                        <div class="card shadow-sm {{ $qr->usado ? 'border-secondary' : 'border-success' }}">
                            <div class="card-header {{ $qr->usado ? 'bg-secondary' : 'bg-success' }} text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-qr-code"></i> {{ $qr->codigo_qr }}
                                    </h6>
                                    <span class="badge {{ $qr->usado ? 'bg-light text-dark' : 'bg-warning text-dark' }}" data-qr-estado="{{ $qr->usado ? 'usado' : 'activo' }}">
                                        {{ $qr->usado ? 'Usado' : 'Activo' }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong><i class="bi bi-person"></i> Profesor:</strong>
                                    <span class="text-primary">{{ $qr->profesor_nombre }}</span>
                                </div>
                               
                                <div class="mb-2">
                                    <strong><i class="bi bi-building"></i> Recinto:</strong>
                                    {{ $qr->recinto_nombre }}
                                </div>
                               
                                <div class="mb-2">
                                    <strong><i class="bi bi-key"></i> Llave:</strong>
                                    {{ $qr->llave_nombre }}
                                    <span class="badge {{ $qr->llave_estado == 0 ? 'bg-success' : 'bg-warning' }} ms-1 llave-estado-badge" data-llave-estado="{{ $qr->llave_estado }}">
                                        {{ $qr->llave_estado == 0 ? 'Entregada' : 'No Entregada' }}
                                    </span>
                                </div>
                               
                                <div class="mb-2">
                                    <strong><i class="bi bi-clock"></i> Generado:</strong>
                                    {{ \Carbon\Carbon::parse($qr->created_at)->format('d/m/Y H:i:s') }}
                                </div>
                               
                                <div class="mb-3">
                                    <strong><i class="bi bi-alarm"></i> Expira:</strong>
                                    <span class="text-{{ \Carbon\Carbon::parse($qr->expira_en) < now() ? 'danger' : 'warning' }} expira-tiempo">
                                        {{ \Carbon\Carbon::parse($qr->expira_en)->format('d/m/Y H:i:s') }}
                                    </span>
                                </div>


                                <div class="text-center">
                                    <button class="btn btn-outline-primary btn-sm btn-ver-qr"
                                            data-qr-code="{{ $qr->codigo_qr }}"
                                            data-profesor-nombre="{{ $qr->profesor_nombre }}"
                                            data-recinto-nombre="{{ $qr->recinto_nombre }}"
                                            data-llave-nombre="{{ $qr->llave_nombre }}">
                                        <i class="bi bi-eye"></i> Ver Info
                                    </button>
                                   
                                    @if(!$qr->usado && \Carbon\Carbon::parse($qr->expira_en) > now())
                                        <button class="btn btn-outline-danger btn-sm ms-2 btn-escanear"
                                                data-qr-code="{{ $qr->codigo_qr }}">
                                            <i class="bi bi-upc-scan"></i> Solicitar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5" id="qrs-container">
                <i class="bi bi-qr-code" style="font-size: 4rem; color: #6c757d;"></i>
                <h4 class="mt-3 text-muted">No hay solicitudes activas</h4>
                <p class="text-muted">Los profesores aún no han generado solicitudes temporales.</p>
            </div>
        @endif
    </div>
</div>


<!-- Modal Ver QR -->
<div class="modal fade" id="modalVerQR" tabindex="-1" aria-labelledby="modalVerQRLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVerQRLabel">
                    Solicitud- Detalles
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-info" class="mb-3">
                    <p><strong>Profesor:</strong> <span id="modal-profesor"></span></p>
                    <p><strong>Recinto:</strong> <span id="modal-recinto"></span></p>
                    <p><strong>Llave:</strong> <span id="modal-llave"></span></p>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal para Simulación de Escaneo -->
<div class="modal fade modal-scan" id="scanSimulationModal" tabindex="-1" aria-labelledby="scanSimulationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scanSimulationModalLabel">
                    <i class="bi bi-upc-scan me-2"></i>Solicitar 
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                
                <h5 class="mb-3">¿Estás seguro de realizar la solicitud?</h5>
                <p class="text-muted mb-4">
                    Esta acción cambiará el estado de la llave y bitácora, será registrada en el sistema.
                    <br>
                    
                </p>
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>
                        Al terminar la solicxitud se cambiara el estado de la llave y la bitácora asociadas 
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-scan" id="confirmScanBtn">
                    <i class="bi bi-check-circle me-1"></i>Confirmar Escaneo
                </button>
            </div>
        </div>
    </div>
</div>


@endsection


@push('scripts')
<script>
$(document).ready(function() {
    console.log('🎯 Admin QR - Sistema Iniciado');
   
    let pollingInterval;
    let lastUpdateTime = '';


    // Event listener para ver QR (usando delegación de eventos)
    $(document).on('click', '.btn-ver-qr', function(e) {
        e.preventDefault();
        console.log('Click en ver QR detectado');
        const codigo = $(this).data('qr-code');
        const qrUrl = $(this).data('qr-url') || `https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=${encodeURIComponent(codigo)}`;
        const profesor = $(this).data('profesor-nombre');
        const recinto = $(this).data('recinto-nombre');
        const llave = $(this).data('llave-nombre');
       
        console.log('Datos QR:', {codigo, profesor, recinto, llave});
       
        $('#modal-profesor').text(profesor);
        $('#modal-recinto').text(recinto);
        $('#modal-llave').text(llave);
        $('#modal-codigo').text(codigo);
        $('#qr-image').attr('src', qrUrl);
        $('#modalVerQR').modal('show');
    });


    // Event listener para simular escaneo (usando delegación de eventos)
    $(document).on('click', '.btn-escanear', function(e) {
        e.preventDefault();
        console.log('Click en escanear QR detectado');
        const button = $(this);
        const qrCode = button.data('qr-code');
       
        console.log('QR Code para escanear:', qrCode);
       
        if (!qrCode) {
            showToast('Cdigo QR no encontrado', 'error');
            return;
        }
       
        // Verificar que el modal existe
        const modal = $('#scanSimulationModal');
        console.log('Modal encontrado:', modal.length > 0);
        
        if (modal.length === 0) {
            console.error('Modal no encontrado, usando confirm como fallback');
            if (!confirm('¿Simular escaneo? Esto cambiará el estado de la llave.')) {
                return;
            }
            performScan(button, qrCode);
            return;
        }
       
        // Mostrar modal
        $('#qr-code-display').text(qrCode);
        modal.modal('show');
        
        // Guardar datos en el modal para usarlos después
        modal.data('scanButton', button);
        modal.data('qrCode', qrCode);
        
        console.log('Modal mostrado con datos:', {qrCode});
    });

    // Manejar confirmación del modal
    $(document).on('click', '#confirmScanBtn', function(e) {
        e.preventDefault();
        console.log('Click en confirmar escaneo');
        
        const modal = $('#scanSimulationModal');
        const button = modal.data('scanButton');
        const qrCode = modal.data('qrCode');
        
        console.log('Datos del modal:', {button: button ? 'found' : 'not found', qrCode});
        
        if (!button || !qrCode) {
            console.error('Datos del modal no encontrados');
            showToast('Error interno: datos no encontrados', 'error');
            return;
        }
        
        // Cerrar modal
        modal.modal('hide');
        
        // Continuar con el escaneo
        performScan(button, qrCode);
    });
    
    function performScan(button, qrCode) {
        button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Escaneando...');
       
        $.ajax({
            url: '{{ route("qr.escanear") }}',
            method: 'POST',
            data: {
                qr_code: qrCode,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Respuesta exitosa:', response);
                if (response.success) {
                    showToast(response.mensaje, 'success');
                    // El sistema de tiempo real actualizará automáticamente
                }
            },
            error: function(xhr) {
                console.log('Error en AJAX:', xhr);
                const error = xhr.responseJSON?.error || 'Error al escanear QR';
                showToast(error, 'error');
                button.prop('disabled', false).html('<i class="bi bi-upc-scan"></i> Simular Escaneo');
            }
        });
    }


    // ===== SISTEMA DE TIEMPO REAL =====
    function initRealTimeSystem() {
        console.log('🚀 Iniciando sistema de tiempo real - QRs cada 3 segundos');
        updateQRsRealTime();
        pollingInterval = setInterval(updateQRsRealTime, 3000);
    }
   
    function updateQRsRealTime() {
        console.log('🔄 Actualizando QRs temporales...');
       
        $.ajax({
            url: '{{ route("admin.qr.realtime") }}',
            method: 'GET',
            timeout: 5000,
            cache: false,
            success: function(response) {
                if (response.status === 'success') {
                    console.log('✅ QRs actualizados:', response.total);
                   
                    // Actualizar contador
                    $('#total-qrs').text(response.total);
                   
                    // Actualizar QRs
                    updateExistingQRs(response.qrs);
                   
                    // Mostrar indicador (comentado para ocultar mensaje)
                    // showUpdateIndicator(response.timestamp);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error actualizando QRs:', error);
               
                // Reducir frecuencia si hay error
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    setTimeout(() => {
                        pollingInterval = setInterval(updateQRsRealTime, 6000);
                    }, 5000);
                }
            }
        });
    }
   
    function updateExistingQRs(qrs) {
        const container = $('#qrs-container');
       
        if (qrs.length === 0) {
            container.html(`
                <div class="text-center py-5">
                    <i class="bi bi-qr-code" style="font-size: 4rem; color: #6c757d;"></i>
                    <h4 class="mt-3 text-muted">No hay códigos QR activos</h4>
                    <p class="text-muted">Los profesores aún no han generado códigos QR temporales.</p>
                </div>
            `);
            return;
        }
       
        // Asegurar estructura de tarjetas
        if (!container.hasClass('row')) {
            container.removeClass().addClass('row');
        }
       
        // Crear un array de IDs de QRs recibidos
        const qrIds = qrs.map(qr => qr.id.toString());
       
        // Actualizar QRs existentes y agregar nuevos
        qrs.forEach(function(qr) {
            updateOrCreateQRCard(qr);
        });
       
        // Remover QRs expirados (que no están en la respuesta)
        container.find('[data-qr-id]').each(function() {
            const cardId = $(this).data('qr-id').toString();
            if (!qrIds.includes(cardId)) {
                console.log('Removiendo QR expirado:', cardId);
                $(this).fadeOut(500, function() {
                    $(this).remove();
                });
            }
        });
    }
   
    function updateOrCreateQRCard(qr) {
        let card = $(`[data-qr-id="${qr.id}"]`);
       
        if (card.length === 0) {
            // Crear nueva tarjeta solo si no existe
            console.log('Creando nueva tarjeta QR:', qr.id);
            const newCardHtml = createQRCardHTML(qr);
            $('#qrs-container').prepend(newCardHtml);
            card = $(`[data-qr-id="${qr.id}"]`);
            card.hide().fadeIn(500);
        } else {
            // Solo actualizar contenido existente, no reemplazar toda la tarjeta
            updateQRCardContent(card, qr);
        }
    }
   
    function createQRCardHTML(qr) {
        return `
            <div class="col-md-6 col-lg-4 mb-4" data-qr-id="${qr.id}">
                <div class="card shadow-sm ${qr.usado ? 'border-secondary' : 'border-success'}">
                    <div class="card-header ${qr.estado_qr_badge} text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-qr-code"></i> ${qr.codigo_qr}
                            </h6>
                            <span class="badge ${qr.usado ? 'bg-light text-dark' : 'bg-warning text-dark'}" data-qr-estado="${qr.estado_qr.toLowerCase()}">
                                ${qr.estado_qr}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong><i class="bi bi-person"></i> Profesor:</strong>
                            <span class="text-primary">${qr.profesor_nombre}</span>
                        </div>
                       
                        <div class="mb-2">
                            <strong><i class="bi bi-building"></i> Recinto:</strong>
                            ${qr.recinto_nombre}
                        </div>
                       
                        <div class="mb-2">
                            <strong><i class="bi bi-key"></i> Llave:</strong>
                            ${qr.llave_nombre}
                            <span class="badge ${qr.llave_estado_badge} ms-1 llave-estado-badge" data-llave-estado="${qr.llave_estado}">
                                ${qr.llave_estado_texto}
                            </span>
                        </div>
                       
                        <div class="mb-2">
                            <strong><i class="bi bi-clock"></i> Generado:</strong>
                            ${qr.created_at}
                        </div>
                       
                        <div class="mb-3">
                            <strong><i class="bi bi-alarm"></i> Expira:</strong>
                            <span class="${qr.expira_class} expira-tiempo">
                                ${qr.expira_en}
                            </span>
                        </div>


                        <div class="text-center">
                            <button class="btn btn-outline-primary btn-sm btn-ver-qr"
                                    data-qr-code="${qr.codigo_qr}"
                                    data-qr-url="${qr.qr_url}"
                                    data-profesor-nombre="${qr.profesor_nombre}"
                                    data-recinto-nombre="${qr.recinto_nombre}"
                                    data-llave-nombre="${qr.llave_nombre}">
                                <i class="bi bi-eye"></i> Ver QR
                            </button>
                            ${!qr.usado ? `
                                <button class="btn btn-outline-danger btn-sm ms-2 btn-escanear"
                                        data-qr-code="${qr.codigo_qr}">
                                    <i class="bi bi-upc-scan"></i> Simular Escaneo
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
   
    function updateQRCardContent(card, qr) {
        // Actualizar estado del QR en el badge
        const estadoBadge = card.find('[data-qr-estado]');
        const currentEstado = estadoBadge.data('qr-estado');
        if (currentEstado !== qr.estado_qr.toLowerCase()) {
            estadoBadge.removeClass('bg-light text-dark bg-warning')
                      .addClass(qr.usado ? 'bg-light text-dark' : 'bg-warning text-dark')
                      .text(qr.estado_qr)
                      .data('qr-estado', qr.estado_qr.toLowerCase());
           
            // Animación de cambio
            estadoBadge.addClass('estado-actualizado');
            setTimeout(() => estadoBadge.removeClass('estado-actualizado'), 1500);
        }
       
        // Actualizar estado de la llave
        const llaveBadge = card.find('.llave-estado-badge');
        const currentLlaveEstado = llaveBadge.data('llave-estado');
        if (currentLlaveEstado != qr.llave_estado) {
            llaveBadge.removeClass('bg-success bg-warning')
                      .addClass(qr.llave_estado_badge.replace('bg-warning text-dark', 'bg-warning'))
                      .text(qr.llave_estado_texto)
                      .data('llave-estado', qr.llave_estado);
                     
            // Notificación de cambio
            showToast(` Llave ${qr.llave_nombre}: ${qr.llave_estado_texto}`, 'info', 3000);
        }
       
        // Actualizar tiempo de expiración
        const expiraSpan = card.find('.expira-tiempo');
        expiraSpan.removeClass('text-warning text-danger text-success')
                  .addClass(qr.expira_class)
                  .text(qr.expira_en);
       
        // Actualizar el borde de la tarjeta según el estado
        const cardElement = card.find('.card');
        cardElement.removeClass('border-success border-secondary')
                  .addClass(qr.usado ? 'border-secondary' : 'border-success');
       
        // Actualizar header de la tarjeta
        const cardHeader = card.find('.card-header');
        cardHeader.removeClass('bg-success bg-secondary')
                 .addClass(qr.estado_qr_badge);
       
        // Actualizar botones si el estado cambió de activo a usado
        if (qr.usado) {
            card.find('.btn-escanear').remove(); // Remover botón de escaneo si ya fue usado
        }
    }
   
    function showUpdateIndicator(timestamp) {
        if (lastUpdateTime !== timestamp) {
            lastUpdateTime = timestamp;
           
            let indicator = $('#update-indicator');
            if (indicator.length === 0) {
                $('body').append(`
                    <div id="update-indicator" class="position-fixed top-0 end-0 m-3 p-2 bg-success text-white rounded-pill" style="z-index: 9999; opacity: 0;">
                        <i class="bi bi-check-circle"></i> Actualizado
                    </div>
                `);
                indicator = $('#update-indicator');
            }
           
            indicator.stop().animate({opacity: 1}, 200).delay(1500).animate({opacity: 0}, 500);
        }
    }
   
    // Limpiar interval al salir
    $(window).on('beforeunload', function() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });
   
    // Inicializar sistema de tiempo real
    initRealTimeSystem();
   
    console.log('✅ Sistema QR Admin en tiempo real configurado');
});


function showToast(message, type = 'info', duration = 3000) {
    const toastTypes = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };
   
    const toastClass = toastTypes[type] || 'bg-info';
    const toastId = 'toast-' + Date.now();
   
    const toast = $(`
        <div id="${toastId}" class="toast align-items-center text-white ${toastClass} border-0 mb-2" role="alert" style="opacity: 0;">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="$('#${toastId}').remove()"></button>
            </div>
        </div>
    `);
   
    // Agregar al contenedor
    let container = $('.toast-container');
    if (container.length === 0) {
        $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
        container = $('.toast-container');
    }
   
    container.append(toast);
    const toastElement = $(`#${toastId}`);
   
    // Mostrar con animación
    toastElement.animate({opacity: 1}, 300);
   
    // Auto-remover
    setTimeout(() => {
        toastElement.animate({opacity: 0}, 300, function() {
            $(this).remove();
        });
    }, duration);
}
</script>
@endpush


@push('styles')
<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}


.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}


.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1055;
}


.border-success {
    border-color: #198754 !important;
}


.border-secondary {
    border-color: #6c757d !important;
}


.wrapper {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}


.main-content {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
    border-radius: 10px;
}


/* Animaciones para tiempo real */
.estado-actualizado {
    animation: pulso-estado 1.5s ease-in-out;
}


@keyframes pulso-estado {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 0 10px rgba(0,123,255,0.5); }
    100% { transform: scale(1); }
}


.llave-estado-badge {
    transition: all 0.3s ease;
}


.llave-estado-badge.actualizado {
    animation: brillo-llave 2s ease-in-out;
}


@keyframes brillo-llave {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; transform: scale(1.05); }
}


.expira-tiempo {
    transition: color 0.3s ease;
}


/* Indicador de tiempo real */
#update-indicator {
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}


/* Estados de conectividad */
.realtime-status {
    position: relative;
}


.realtime-status::after {
    content: '';
    width: 8px;
    height: 8px;
    background-color: #28a745;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    right: -15px;
    transform: translateY(-50%);
    animation: parpadeo-conexion 2s infinite;
}


@keyframes parpadeo-conexion {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}


.realtime-status.error::after {
    background-color: #dc3545;
    animation: parpadeo-error 1s infinite;
}


@keyframes parpadeo-error {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}


/* Efectos de aparición de nuevas tarjetas */
@keyframes aparicion-card {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}


.nueva-card {
    animation: aparicion-card 0.5s ease-out;
}


/* Efectos de desaparicin de tarjetas expiradas */
@keyframes desaparicion-card {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0.95) translateY(20px);
    }
}


.card-expirada {
    animation: desaparicion-card 0.5s ease-in;
}


/* Mejorar apariencia del contador */
.badge-contador {
    font-size: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}


/* Responsive design para tablets y móviles */
@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
   
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
   
    #update-indicator {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
}

/* Estilos para el modal de simulación */
.modal-scan .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.modal-scan .btn-scan {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.modal-scan .btn-scan:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.scan-icon {
    font-size: 3rem;
    color: #667eea;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
}
</style>
@endpush




