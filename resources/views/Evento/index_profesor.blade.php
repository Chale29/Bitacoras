@extends('Template-profesor')

@section('title', 'Sistema de Eventos')

@section('content')

    <head>
        <link rel="stylesheet" href="{{ asset('Css/reporte.css') }}">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <div class="wrapper">
        <div class="main-content">
            <!-- Loading spinner -->
            <div id="loadingSpinner" class="text-center d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>

            <!-- Botones para alternar entre activos e inactivos -->
            <div class="d-flex flex-column flex-sm-row justify-content-end mb-3 gap-2">
                <a href="{{ route('evento.index_profesor', array_merge(request()->except('ver_inactivos'), ['ver_inactivos' => null])) }}" class="btn btn-outline-primary @if(!request('ver_inactivos')) active @endif">
                    Ver Activos
                </a>
                <a href="{{ route('evento.index_profesor', array_merge(request()->except('ver_inactivos'), ['ver_inactivos' => 1])) }}" class="btn btn-outline-secondary @if(request('ver_inactivos')) active @endif">
                    Ver Inactivos
                </a>
            </div>

            <!-- Tabla de eventos responsiva -->
            <div id="tabla-reportes" class="tabla-contenedor shadow-sm rounded">
                <!-- Encabezados - Solo visible en desktop -->
                <div class="header-row text-white d-none d-lg-grid" style="background-color: #134496;">
                    <div class="col-header">Docente</div>
                    <div class="col-header">Recinto</div>
                    <div class="col-header">Lección</div>
                    <div class="col-header">Fecha</div>
                    <div class="col-header">Hora</div>
                    <div class="col-header">Institución</div>
                    <div class="col-header">Condición</div>
                    <div class="col-header">Prioridad</div>
                    <div class="col-header">Estado</div>
                    <div class="col-header">Acciones</div>
                </div>

                <!-- Contenedor para datos asíncronos -->
                <div id="eventos-container" class="eventos-grid">
                    @php
                        $verInactivos = request('ver_inactivos');
                    @endphp
                    @foreach ($eventos as $evento)
                    @can('view_eventos')
                        @if(($verInactivos && $evento->condicion == 0) || (!$verInactivos && $evento->condicion == 1))
                        <!-- Desktop row / Mobile card -->
                        <div class="evento-item">
                            <div class="evento-card">
                                <div class="evento-field" data-label="Docente">
                                    <span class="field-label d-lg-none">Docente:</span>
                                    <span class="field-value">{{ $evento->usuario->name ?? 'N/A' }}</span>
                                </div>
                                <div class="evento-field" data-label="Recinto">
                                    <span class="field-label d-lg-none">Recinto:</span>
                                    <span class="field-value">{{ $evento->horario->recinto->nombre ?? '' }}</span>
                                </div>
                                <div class="evento-field" data-label="Lección">
                                    <span class="field-label d-lg-none">Lección:</span>
                                    <span class="field-value">{{ $evento->horarioLeccion->leccion->leccion ?? '' }}</span>
                                </div>
                                <div class="evento-field" data-label="Fecha">
                                    <span class="field-label d-lg-none">Fecha:</span>
                                    <span class="field-value">{{ \Carbon\Carbon::parse($evento->fecha)->format('d/m/Y') }}</span>
                                </div>
                                <div class="evento-field" data-label="Hora">
                                    <span class="field-label d-lg-none">Hora:</span>
                                    <span class="field-value">{{ \Carbon\Carbon::parse($evento->hora_envio)->format('H:i') }}</span>
                                </div>
                                <div class="evento-field" data-label="Institución">
                                    <span class="field-label d-lg-none">Institución:</span>
                                    <span class="field-value">{{ $evento->horario->recinto->institucion->nombre ?? '' }}</span>
                                </div>
                                <div class="evento-field" data-label="Condición">
                                    <span class="field-label d-lg-none">Condición:</span>
                                    <span class="field-value">{{ $evento->condicion == 1 ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                                <div class="evento-field" data-label="Prioridad">
                                    <span class="field-label d-lg-none">Prioridad:</span>
                                    <span class="badge bg-secondary">{{ ucfirst($evento->prioridad) }}</span>
                                </div>
                                <div class="evento-field" data-label="Estado">
                                    <span class="field-label d-lg-none">Estado:</span>
                                    <span class="badge bg-secondary">
                                        @if($evento->estado == 'en_espera')
                                            En espera
                                        @elseif($evento->estado == 'en_proceso')
                                            En proceso
                                        @elseif($evento->estado == 'completado')
                                            Completado
                                        @else
                                            {{ ucfirst($evento->estado) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="evento-field evento-actions" data-label="Acciones">
                                    <span class="field-label d-lg-none">Acciones:</span>
                                    <div class="d-flex gap-2 justify-content-start justify-content-lg-center flex-wrap">
                                    @if($evento->condicion == 1)
                                        @can('edit_eventos')
                                            <button class="btn btn-sm btn-primary rounded-pill px-3" style="background-color: #134496;"
                                                onclick='abrirModal(@json($evento))'>
                                                <i class="bi bi-pencil"></i>
                                                <span class="d-lg-none ms-1">Editar</span>
                                            </button>
                                        @endcan
                                        @can('delete_eventos')
                                        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" data-bs-toggle="modal"
                                            data-bs-target="#modalConfirmacionEliminar-{{ $evento->id }}" aria-label="Eliminar Evento">
                                            <i class="bi bi-trash"></i>
                                            <span class="d-lg-none ms-1">Eliminar</span>
                                        </button>
                                        @endcan
                                        @else
                                        @can('delete_eventos')
                                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-toggle="modal"
                                            data-bs-target="#modalConfirmacionEliminar-{{ $evento->id }}" aria-label="Restaurar Evento">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span class="d-lg-none ms-1">Restaurar</span>
                                        </button>
                                        @endcan
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                        <!-- Modal eliminar -->
                        <div class="modal fade" id="modalConfirmacionEliminar-{{ $evento->id }}" tabindex="-1"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border-radius: 15px; border: none;">
                                    <div class="modal-body p-4 text-center">
                                        <div class="mb-4">
                                            <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                                        </div>
                                        <h4 class="mb-3" style="color: #2c3e50;">¿Desea desactivar este evento?</h4>
                                        <p class="text-muted mb-4">Esta acción no se puede deshacer</p>
                                        <div class="d-flex justify-content-center gap-3">
                                            <form action="{{ route('evento.destroy', ['evento' => $evento->id]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-primary px-4"
                                                    style="background-color: #134496; border: none;">
                                                    <i class="bi bi-check-lg me-2"></i>Sí, desactivar
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                                                <i class="bi bi-x-lg me-2"></i>Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal Éxito Eliminar -->
                        <div class="modal fade" id="modalExitoEliminar" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content text-center">
                                <div class="modal-body d-flex flex-column align-items-center gap-3 p-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 256 256">
                                    <g fill="#efc737" fill-rule="nonzero">
                                        <g transform="scale(5.12,5.12)">
                                        <path d="M25,2c-12.683,0 -23,10.317 -23,23c0,12.683 10.317,23 23,23c12.683,0 23,-10.317 23,-23c0,-4.56 -1.33972,-8.81067 -3.63672,-12.38867l-1.36914,1.61719c1.895,3.154 3.00586,6.83148 3.00586,10.77148c0,11.579 -9.421,21 -21,21c-11.579,0 -21,-9.421 -21,-21c0,-11.579 9.421,-21 21,-21c5.443,0 10.39391,2.09977 14.12891,5.50977l1.30859,-1.54492c-4.085,-3.705 -9.5025,-5.96484 -15.4375,-5.96484zM43.23633,7.75391l-19.32227,22.80078l-8.13281,-7.58594l-1.36328,1.46289l9.66602,9.01563l20.67969,-24.40039z"/>
                                        </g>
                                    </g>
                                    </svg>
                                    <p class="mb-0">Reporte eliminado con éxito</p>
                                </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
    </div>

@endsection

@push('styles')
    <style>
        :root {
            --primary-blue: #134496;
        }

        /* Container principal */
        .tabla-contenedor {
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: white;
            overflow: hidden;
        }

        /* Grid para desktop */
        .header-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1rem;
            font-weight: 600;
            align-items: center;
        }

        .col-header {
            text-align: center;
            font-size: 0.9rem;
        }

        /* Contenedor de eventos */
        .eventos-grid {
            display: flex;
            flex-direction: column;
        }

        /* Item individual de evento */
        .evento-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .evento-item:hover {
            background-color: rgba(19, 68, 150, 0.02);
        }

        .evento-item:last-child {
            border-bottom: none;
        }

        /* Card del evento */
        .evento-card {
            padding: 1rem;
        }

        /* Desktop layout */
        @media (min-width: 992px) {
            .evento-card {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
                gap: 1rem;
                align-items: center;
                padding: 0.75rem 1rem;
            }

            .evento-field {
                text-align: center;
                font-size: 0.9rem;
            }

            .field-value {
                display: block;
            }
        }

        /* Mobile layout */
        @media (max-width: 991px) {
            .evento-card {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                background: white;
                border-radius: 8px;
                margin-bottom: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .evento-item {
                margin: 0.5rem;
                border: none;
                border-radius: 8px;
                overflow: hidden;
            }

            .evento-field {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .evento-field:last-child {
                border-bottom: none;
            }

            .field-label {
                font-weight: 600;
                color: var(--primary-blue);
                font-size: 0.9rem;
                min-width: 100px;
            }

            .field-value {
                text-align: right;
                font-size: 0.9rem;
                flex: 1;
            }

            .evento-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .evento-actions .d-flex {
                justify-content: center !important;
            }
        }

        /* Tablet layout */
        @media (min-width: 768px) and (max-width: 991px) {
            .evento-card {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                align-items: start;
            }

            .evento-field {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                padding: 0.5rem;
                border: 1px solid rgba(0, 0, 0, 0.1);
                border-radius: 6px;
                background: rgba(19, 68, 150, 0.02);
            }

            .field-label {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
            }

            .evento-actions {
                grid-column: span 2;
                justify-self: center;
            }
        }

        /* Badges responsive */
        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
            font-size: 0.85rem;
        }

        @media (max-width: 991px) {
            .badge {
                font-size: 0.8rem;
                padding: 0.3em 0.6em;
            }
        }

        /* Buttons responsive */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        @media (max-width: 991px) {
            .btn-sm {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
                width: auto;
                min-width: 120px;
            }
        }

        /* Toggle buttons responsive */
        @media (max-width: 576px) {
            .d-flex.flex-column {
                flex-direction: column !important;
            }

            .btn {
                text-align: center;
            }
        }

        /* Loading spinner */
        .spinner-border.text-primary {
            color: var(--primary-blue) !important;
        }

        /* Transitions */
        #eventos-container {
            transition: opacity 0.15s ease-in-out;
        }

        .evento-item {
            transition: all 0.3s ease;
        }

        /* Focus states */
        .btn:focus, .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(19, 68, 150, 0.25);
        }

        /* Primary color overrides */
        .bg-primary {
            background-color: var(--primary-blue) !important;
        }

        .btn-primary {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
        }

        .btn-outline-primary {
            color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .btn-outline-primary:hover, .btn-outline-primary.active {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        /* Modal responsiveness */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 1rem;
            }

            .modal-content {
                border-radius: 12px;
            }
        }

        /* SweetAlert custom styles */
        .swal2-custom-popup {
            background-color: #ffffff !important;
            border-radius: 12px !important;
            padding: 20px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
        }

        .swal2-custom-popup .swal2-title {
            font-size: 1.5rem !important;
            font-weight: 600;
            color: #134496;
        }

        .swal2-custom-popup .form-control,
        .swal2-custom-popup .form-select {
            border-radius: 10px;
            margin-bottom: 10px;
        }

        @media (max-width: 576px) {
            .swal2-custom-popup {
                margin: 1rem !important;
                padding: 15px !important;
            }

            .swal2-custom-popup .form-control,
            .swal2-custom-popup .form-select {
                font-size: 16px; /* Previene zoom en iOS */
            }
        }

        /* Utility classes for better mobile experience */
        @media (max-width: 991px) {
            .table-responsive-stack .evento-field {
                display: block;
                width: 100%;
                text-align: left;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const loadingSpinner = document.getElementById('loadingSpinner');
        const eventosContainer = document.getElementById('eventos-container');
        let currentTimestamp = '{{ $eventos->max('updated_at') }}';

        async function cargarEventos() {
            try {
                const response = await fetch(`{{ route('eventos.profesor.load') }}?timestamp=${currentTimestamp}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error en la red');

                const data = await response.json();

                if (data.success && data.hasNewData) {
                    loadingSpinner.classList.remove('d-none');
                    eventosContainer.style.opacity = '0.6';

                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;

                    const newEventosContainer = tempDiv.querySelector('#eventos-container');
                    if (newEventosContainer) {
                        eventosContainer.innerHTML = newEventosContainer.innerHTML;
                        currentTimestamp = data.timestamp;
                    }

                    eventosContainer.style.opacity = '1';
                    loadingSpinner.classList.add('d-none');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Comprobar cambios cada 3 segundos
        const intervalId = setInterval(cargarEventos, 3000);

        // Función para abrir modal responsivo
        function abrirModal(evento) {
            const isMobile = window.innerWidth < 768;
            
            Swal.fire({
                title: 'Detalles del Evento',
                html: `
                <div class="container-fluid">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Docente:</label>
                            <input type="text" class="form-control" value="${evento.usuario.name ?? 'N/A'}" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Institución:</label>
                            <input type="text" class="form-control" value="${evento.horario.recinto.institucion?.nombre ?? ''}" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">SubÁrea:</label>
                            <input type="text" class="form-control" value="${evento.subarea?.nombre ?? ''}" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Sección:</label>
                            <input type="text" class="form-control" value="${evento.seccion?.nombre ?? ''}" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Especialidad:</label>
                            <input type="text" class="form-control" value="${evento.subarea?.especialidad?.nombre ?? ''}" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha:</label>
                            <input type="text" class="form-control" value="${evento.fecha_formateada}" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Hora:</label>
                            <input type="text" class="form-control" value="${evento.hora_formateada}" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Recinto:</label>
                            <input type="text" class="form-control" value="${evento.horario.recinto.nombre ?? ''}" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Prioridad:</label>
                            <select class="form-select" id="prioridadInput">
                                <option value="alta" ${evento.prioridad == 'alta' ? 'selected' : ''}>Alta</option>
                                <option value="media" ${evento.prioridad == 'media' ? 'selected' : ''}>Media</option>
                                <option value="regular" ${evento.prioridad == 'regular' ? 'selected' : ''}>Regular</option>
                                <option value="baja" ${evento.prioridad == 'baja' ? 'selected' : ''}>Baja</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones:</label>
                            <textarea id="observacionInput" class="form-control" rows="3">${evento.observacion}</textarea>
                        </div>
                    </div>
                </div>
            `,
                showCancelButton: true,
                confirmButtonText: 'Guardar Cambios',
                cancelButtonText: 'Cerrar',
                width: isMobile ? '95%' : '600px',
                customClass: {
                    popup: 'swal2-custom-popup',
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                preConfirm: () => {
                    return {
                        prioridad: document.getElementById('prioridadInput').value,
                        observacion: document.getElementById('observacionInput').value
                    };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await guardarCambios(evento.id, result.value);
                }
            });
        }

        function cerrarModal(id) {
            Swal.close();
        }

        // Función para confirmar eliminación
        function confirmarEliminacion(id) {
            Swal.fire({
                title: '¿Desea desactivar este evento?',
                html: '<div class="text-muted">Esta acción no se puede deshacer</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#134496',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-check-lg me-1"></i>Sí, desactivar',
                cancelButtonText: '<i class="bi bi-x-lg me-1"></i>Cancelar',
                customClass: {
                    container: 'delete-modal-container',
                    popup: 'delete-modal-popup',
                    title: 'delete-modal-title',
                    htmlContainer: 'delete-modal-content',
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-secondary px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarEvento(id);
                }
            });
        }

        async function guardarCambios(id, data) {
            try {
                const formData = new FormData();
                formData.append('prioridad', data.prioridad);
                formData.append('observacion', data.observacion);
                if (data.estado) {
                    formData.append('estado', data.estado);
                }
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch(`/evento/${id}/update`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cambios guardados',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500
                    });
                    location.reload();
                } else {
                    throw new Error(result.message || 'Error al guardar cambios');
                }

            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            }
        }

        // Limpiar intervalo cuando se abandona la página
        window.addEventListener('beforeunload', () => {
            clearInterval(intervalId);
        });

        // Cargar datos iniciales
        document.addEventListener('DOMContentLoaded', cargarEventos);

        // Handle responsive changes
        window.addEventListener('resize', () => {
            // Opcional: recargar modal si está abierto para ajustar tamaño
        });
    </script>
@endpush