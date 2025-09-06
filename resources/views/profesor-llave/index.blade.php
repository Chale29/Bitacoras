@extends('Template-profesor')


@section('title', 'Gestión de Llaves - Profesor')


@section('content')
<style>
.spin {
    animation: spin 1s linear infinite;
}


@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive improvements */
@media (max-width: 768px) {
    .wrapper {
        padding: 10px;
    }
    
    .main-content {
        padding: 15px;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.align-items-center.gap-3 {
        flex-direction: column;
        width: 100%;
        gap: 0.5rem !important;
    }
    
    .badge.bg-info {
        width: 100%;
        text-align: center;
        padding: 0.5rem;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .card-body .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    h2 {
        font-size: 1.5rem;
        text-align: center;
    }
    
    .card-header h6 {
        font-size: 0.9rem;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
    
    .modal-dialog {
        margin: 1rem;
    }
}
</style>


<div class="wrapper">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Gestión de Llaves</h2>
            <div class="d-flex align-items-center gap-3">
                @if(isset($profesor) && $profesor)
                    <div class="badge bg-info fs-6">
                        <i class="bi bi-person"></i> {{ $profesor->usuario->name }}
                    </div>
                @endif
                <!-- Botón manual para debug -->
                <button id="btn-actualizar-qrs" class="btn btn-outline-secondary btn-sm" title="Actualizar QRs manualmente">
                    <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-md-inline">Debug QRs</span>
                </button>
                <!-- Botón para escanear QR -->
                <a href="{{ route('profesor-llave.scanner') }}" class="btn btn-primary">
                    <i class="bi bi-camera"></i> <span class="d-none d-sm-inline">Escanear QR</span>
                </a>
            </div>
        </div>


        @if(isset($error))
            <div class="alert alert-warning">
                <h4><i class="bi bi-exclamation-triangle"></i> Atención</h4>
                <p>{{ $error }}</p>
            </div>
        @endif


        @if(!isset($error) && $recintos->count() > 0)
            <div class="row g-3">
                @foreach($recintos as $item)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="card shadow-sm border-primary h-100">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-0 flex-grow-1">
                                        <i class="bi bi-building"></i> {{ $item->recinto_nombre }}
                                    </h6>
                                    <span class="badge {{ $item->llave_estado == 0 ? 'bg-success' : 'bg-warning text-dark' }} ms-2 flex-shrink-0">
                                        <span class="d-none d-sm-inline">{{ $item->llave_estado == 0 ? 'Entregada' : 'No Entregada' }}</span>
                                        <span class="d-sm-none">{{ $item->llave_estado == 0 ? '✓' : '✗' }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3 flex-grow-1">
                                    <strong><i class="bi bi-key"></i> Llave:</strong>
                                    <span class="text-primary d-block">{{ $item->llave_nombre }}</span>
                                </div>
                               
                                <div class="text-center mt-auto">
                                    <button class="btn btn-success btn-generar-qr w-100"
                                            data-recinto-id="{{ $item->recinto_id }}"
                                            data-llave-id="{{ $item->llave_id }}"
                                            data-recinto-nombre="{{ $item->recinto_nombre }}"
                                            data-llave-nombre="{{ $item->llave_nombre }}">
                                        <i class="bi bi-qr-code"></i> <span class="d-none d-sm-inline">Generar </span>QR
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif


        @if(!isset($error) && $recintos->count() == 0 && isset($profesor))
            <div class="text-center py-5">
                <i class="bi bi-building" style="font-size: 4rem; color: #6c757d;"></i>
                <h4 class="mt-3 text-muted">No tienes recintos asignados</h4>
                <p class="text-muted">Contacta al administrador para que te asigne recintos en tus horarios.</p>
            </div>
        @endif


        <!-- QRs Temporales Activos -->
        @if(!isset($error) && $qrsTemporales->count() > 0)
            <div class="mt-5">
                <h4><i class="bi bi-clock-history"></i> QRs Temporales Activos</h4>
                <div class="row g-3" id="qrs-container">
                    @foreach($qrsTemporales as $qr)
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <div class="card border-success h-100">
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title text-truncate">{{ $qr->codigo_qr }}</h6>
                                    <div class="card-text flex-grow-1">
                                        <strong>Recinto:</strong> <span class="d-block">{{ $qr->recinto_nombre }}</span>
                                        <strong>Llave:</strong> <span class="d-block">{{ $qr->llave_nombre }}</span>
                                        <strong>Expira:</strong> <span class="d-block small">{{ \Carbon\Carbon::parse($qr->expira_en)->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm btn-ver-qr w-100 mt-2"
                                            data-qr-code="{{ $qr->codigo_qr }}"
                                            data-recinto-nombre="{{ $qr->recinto_nombre }}"
                                            data-llave-nombre="{{ $qr->llave_nombre }}">
                                        <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">Ver </span>QR
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>


<!-- Modal para mostrar QR -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="qrModalLabel">
                    <i class="bi bi-qr-code"></i> Código QR Generado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-info" class="mb-3 row">
                    <div class="col-12 col-md-4 mb-2">
                        <strong>Recinto:</strong> <span id="modal-recinto" class="d-block d-md-inline"></span>
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <strong>Llave:</strong> <span id="modal-llave" class="d-block d-md-inline"></span>
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <strong>Código:</strong> <span id="modal-codigo" class="d-block d-md-inline small"></span>
                    </div>
                </div>
               
                <div id="qr-image-container" class="mb-3">
                    <img id="qr-image" src="" alt="Código QR" class="img-fluid" style="max-width: 250px; height: auto;">
                </div>
               
                <div class="mt-3">
                    <small class="text-muted">El código QR expira en 30 minutos</small>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection


@push('scripts')
<script>
$(document).ready(function() {
    // Generar QR
    $('.btn-generar-qr').click(function() {
        const button = $(this);
        const recintoId = button.data('recinto-id');
        const llaveId = button.data('llave-id');
        const recintoNombre = button.data('recinto-nombre');
        const llaveNombre = button.data('llave-nombre');
       
        button.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Generando...');
       
        $.ajax({
            url: '{{ route("profesor-llave.generar-qr") }}',
            method: 'POST',
            data: {
                recinto_id: recintoId,
                llave_id: llaveId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar modal
                    $('#modal-recinto').text(recintoNombre);
                    $('#modal-llave').text(llaveNombre);
                    $('#modal-codigo').text(response.codigo_qr);
                    $('#qr-image').attr('src', response.qr_url);
                   
                    // Mostrar mensaje informativo si existe
                    if (response.mensaje) {
                        // Crear o actualizar mensaje informativo en el modal
                        let messageHtml = '';
                        if (response.mensaje.includes('Ya existe')) {
                            messageHtml = `<div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                                <i class="bi bi-info-circle"></i> ${response.mensaje}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>`;
                        } else {
                            messageHtml = `<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                <i class="bi bi-check-circle"></i> ${response.mensaje}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>`;
                        }
                       
                        // Remover mensaje anterior si existe
                        $('#qrModal .modal-body .alert').remove();
                        // Agregar nuevo mensaje
                        $('#qrModal .modal-body').append(messageHtml);
                    }
                   
                    // Mostrar modal
                    $('#qrModal').modal('show');
                   
                    // Recargar página después de cerrar modal
                    $('#qrModal').on('hidden.bs.modal', function () {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Error al generar QR';
                alert('Error: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="bi bi-qr-code"></i> Generar QR');
            }
        });
    });
   
    // Ver QR existente
    $('.btn-ver-qr').click(function() {
        const qrCode = $(this).data('qr-code');
        const recintoNombre = $(this).data('recinto-nombre');
        const llaveNombre = $(this).data('llave-nombre');
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrCode}`;
       
        // Llenar información del modal
        $('#modal-recinto').text(recintoNombre);
        $('#modal-llave').text(llaveNombre);
        $('#modal-codigo').text(qrCode);
        $('#qr-image').attr('src', qrUrl);
       
        // Mostrar modal
        $('#qrModal').modal('show');
    });
   
    // ===== SISTEMA DE TIEMPO REAL =====
    let pollingInterval;
   
    function initRealTimeSystem() {
        console.log('🚀 Iniciando sistema de tiempo real - QRs cada 5 segundos');
        pollingInterval = setInterval(function() {
            updateQRsRealTime();
        }, 5000);
    }
   
    function updateQRsRealTime() {
        console.log('🔄 Actualizando QRs del profesor...');
       
        $.ajax({
            url: '{{ route("profesor-llave.qrs-realtime") }}' + '?t=' + Date.now(),
            method: 'GET',
            timeout: 5000,
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                if (response.status === 'success') {
                    console.log('✅ QRs actualizados:', response.total);
                    console.log('📋 Debug info:', response.debug);
                    console.log('📊 QRs data:', response.qrs);
                   
                    // Si no hay QRs activos, ocultar la sección
                    if (response.total === 0) {
                        $('#qrs-container').parent().hide();
                        console.log('ℹ️ No hay QRs activos, sección oculta');
                       
                        // Detener polling si no hay QRs
                        if (pollingInterval) {
                            clearInterval(pollingInterval);
                            console.log('⏹️ Polling detenido - no hay QRs activos');
                        }
                    } else {
                        // Mostrar la sección si estaba oculta
                        $('#qrs-container').parent().show();
                        // Actualizar la sección de QRs
                        updateQRsDisplay(response.qrs);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.warn('⚠️ Error actualizando QRs:', error);
                // En caso de error, continuar pero con intervalo más largo
                clearInterval(pollingInterval);
                pollingInterval = setInterval(updateQRsRealTime, 10000); // 10 segundos
            }
        });
    }
   
    function updateQRsDisplay(qrs) {
        let html = '';
       
        qrs.forEach(function(qr) {
            html += `
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="card border-success h-100">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title text-truncate">${qr.codigo_qr}</h6>
                            <div class="card-text flex-grow-1">
                                <strong>Recinto:</strong> <span class="d-block">${qr.recinto_nombre}</span>
                                <strong>Llave:</strong> <span class="d-block">${qr.llave_nombre}</span>
                                <small class="text-muted d-block">Expira: ${qr.expira_en_humano}</small>
                            </div>
                            <button class="btn btn-primary btn-sm btn-ver-qr w-100 mt-2"
                                    data-qr-code="${qr.codigo_qr}"
                                    data-recinto-nombre="${qr.recinto_nombre}"
                                    data-llave-nombre="${qr.llave_nombre}">
                                <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">Ver </span>QR
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
       
        $('#qrs-container').html(html);
       
        // Reactivar eventos para los botones ver QR
        bindVerQREvents();
    }
   
    function bindVerQREvents() {
        $('.btn-ver-qr').off('click').on('click', function() {
            const qrCode = $(this).data('qr-code');
            const recintoNombre = $(this).data('recinto-nombre');
            const llaveNombre = $(this).data('llave-nombre');
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrCode}`;
           
            $('#modal-recinto').text(recintoNombre);
            $('#modal-llave').text(llaveNombre);
            $('#modal-codigo').text(qrCode);
            $('#qr-image').attr('src', qrUrl);
            $('#qrModal').modal('show');
        });
    }
   
    // Inicializar sistema de tiempo real solo si hay QRs activos
    const hasActiveQRs = $('#qrs-container').length > 0;
    if (hasActiveQRs) {
        initRealTimeSystem();
    }
   
    // Botón manual para debug
    $('#btn-actualizar-qrs').on('click', function() {
        console.log('🔧 Actualización manual de QRs...');
        $(this).find('i').addClass('spin');
        updateQRsRealTime();
       
        setTimeout(() => {
            $(this).find('i').removeClass('spin');
        }, 1000);
    });
});
</script>
@endpush


@push('styles')
<style>
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

.wrapper {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.main-content {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
    border-radius: 10px;
}

/* Enhanced responsive grid */
.row.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

@media (max-width: 576px) {
    .row.g-3 {
        --bs-gutter-x: 0.5rem;
        --bs-gutter-y: 0.5rem;
    }
}

/* Card improvements */
.card {
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.h-100 {
    height: 100% !important;
}

/* Text truncation for long names */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Responsive modal */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 90vw;
    }
}
</style>
@endpush




