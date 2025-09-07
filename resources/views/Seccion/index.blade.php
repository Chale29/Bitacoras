@extends('Template-administrador')


@section('title', 'Registro de Sección')


@section('content')
<style>
    /* Fix for dropdown arrow positioning */
    .form-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 16px 12px !important;
        padding-right: 2.25rem !important;
    }
   
    /* Ensure no conflicting pseudo-elements */
    .form-select::after,
    .form-select::before {
        display: none !important;
    }
   
    /* Fix for input-group select styling */
    .input-group .form-select {
        position: relative;
        z-index: 1;
    }
</style>
<div class="wrapper">
    <div class="main-content">


        <div class="search-bar-wrapper mb-4">
            <div class="search-bar">
                <form id="busquedaForm" method="GET" action="{{ route('seccion.index') }}" class="w-100 position-relative">
                    <span class="search-icon">
                        <i class="bi bi-search"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Buscar sección..."
                        name="busquedaSeccion"
                        value="{{ request('busquedaSeccion') }}"
                        id="inputBusqueda"
                        autocomplete="off"
                    >
                    @if(request('busquedaSeccion'))
                    <button
                        type="button"
                        class="btn btn-outline-secondary border-0 position-absolute end-0 top-50 translate-middle-y me-2"
                        id="limpiarBusqueda"
                        title="Limpiar búsqueda"
                        style="background: transparent;"
                    >
                        <i class="bi bi-x-circle"></i>
                    </button>
                    @endif
                </form>
            </div>
            @if(Auth::user() && !Auth::user()->hasRole('director'))
                <button class="btn btn-primary rounded-pill px-4 d-flex align-items-center ms-3 btn-agregar"
                    data-bs-toggle="modal" data-bs-target="#modalAgregarSeccion"
                    title="Agregar Sección" style="background-color: #134496; font-size: 1.2rem;">
                    Agregar <i class="bi bi-plus-circle ms-2"></i>
                </button>
            @endif
        </div>


        {{-- Indicador de resultados de búsqueda --}}
        @if(request('busquedaSeccion'))
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <span>
                    Mostrando {{ $secciones->count() }} resultado(s) para "<strong>{{ request('busquedaSeccion') }}</strong>"
                    <a href="{{ route('seccion.index') }}" class="btn btn-sm btn-outline-primary ms-2">Ver todas</a>
                </span>
            </div>
        @endif


       


        {{-- Tabla --}}
        <div class="table-responsive">
            {{-- Botones para mostrar/ocultar secciones inactivas --}}
            <a href="{{ route('seccion.index', ['inactivos' => 1]) }}" class="btn btn-warning mb-3">
                Mostrar inactivas
            </a>
            <a href="{{ route('seccion.index') }}" class="btn btn-primary mb-3">
                Mostrar activas
            </a>
            <table class="table table-striped">
                <thead>
                    <tr class="header-row">
                        <th class="text-center">Sección</th>
                        <th class="text-center">Especialidad</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $mostrarInactivas = request('inactivos') == 1;
                    @endphp
                    @forelse($secciones as $seccion)
                        @if(($mostrarInactivas && $seccion->condicion == 0) || (!$mostrarInactivas && $seccion->condicion == 1))
                        <tr class="record-row">
                            <td class="text-center">{{ $seccion->nombre }}</td>
                            <td class="text-center">
                                @if($seccion->especialidades->count() > 0)
                                    @foreach($seccion->especialidades as $especialidad)
                                        {{ $especialidad->nombre }}@if(!$loop->last), @endif
                                    @endforeach
                                @else
                                    <span class="text-muted">Sin especialidades</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ isset($seccion->condicion) && $seccion->condicion ? 'bg-success' : 'bg-danger' }}">
                                    {{ isset($seccion->condicion) && $seccion->condicion ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($seccion->condicion == 1)
                                    @can('edit_seccion')
                                        <button class="btn btn-link text-info p-0 me-2" data-bs-toggle="modal" data-bs-target="#modalEditarSeccion-{{ $seccion->id }}">
                                            <i class="bi bi-pencil" style="font-size: 1.5rem;"></i>
                                        </button>
                                    @endcan
                                    @can('delete_seccion')
                                        <button class="btn btn-link text-danger p-0" data-bs-toggle="modal" data-bs-target="#modalEliminarSeccion-{{ $seccion->id }}">
                                            <i class="bi bi-trash" style="font-size: 1.5rem;"></i>
                                        </button>
                                    @endcan
                                @else
                                    @can('delete_seccion')
                                        <button class="btn p-0 me-2" data-bs-toggle="modal" data-bs-target="#modalReactivarSeccion-{{ $seccion->id }}" title="Reactivar sección">
                                            <i class="bi bi-arrow-counterclockwise icon-eliminar" style="font-size: 1.5rem; color: #28a745;"></i>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        {{-- Modal Editar --}}

                        {{-- Modal Editar --}}
                        
                        <div class="modal fade" id="modalEditarSeccion-{{ $seccion->id }}" tabindex="-1" aria-labelledby="modalEditarSeccionLabel-{{ $seccion->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-0" id="modalEditarSeccionContent">
                            <div class="modal-header rounded-0 custom-header">
                                <button type="button" class="btn p-0 me-3" data-bs-dismiss="modal" aria-label="Volver" style="color: #FFD600; font-size: 1.5rem; background: none; border: none;">
                                <span class="icono-atras">
                                    <i><img width="40" height="40" src="https://img.icons8.com/external-solid-adri-ansyah/64/FAB005/external-ui-basic-ui-solid-adri-ansyah-26.png" alt="external-ui-basic-ui-solid-adri-ansyah-26"/></i>
                                </span>
                                </button>
                                <h3 class="flex-grow-1">Editar sección</h3>
                            </div>
                            <div class="modal-body pb-0" style="border-bottom: 8px solid #003366;">
                                <form id="formEditarSeccion-{{ $seccion->id }}" action="{{ route('seccion.update', $seccion->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                
                                <div class="mb-3">
                                    <label for="nombreSeccion-{{ $seccion->id }}" class="form-label mb-1">Nombre de la sección</label>
                                    <input type="text" name="nombre" class="form-control @if(session('modal_editar_id') == $seccion->id && $errors->has('nombre')) is-invalid @endif"
                                        id="nombreSeccion-{{ $seccion->id }}" value="{{ old('nombre', $seccion->nombre) }}" required>
                                    @if(session('modal_editar_id') == $seccion->id && $errors->has('nombre'))
                                        <div class="invalid-feedback">{{ $errors->first('nombre') }}</div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="selectInstitucion-{{ $seccion->id }}" class="form-label mb-1">Institución</label>
                                    <select id="selectInstitucion-{{ $seccion->id }}" name="id_institucion" class="form-select @if(session('modal_editar_id') == $seccion->id && $errors->has('id_institucion')) is-invalid @endif">
                                        @foreach($instituciones as $institucion)
                                            <option value="{{ $institucion->id }}" {{ $seccion->id_institucion == $institucion->id ? 'selected' : '' }}>{{ $institucion->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @if(session('modal_editar_id') == $seccion->id && $errors->has('id_institucion'))
                                        <div class="invalid-feedback">{{ $errors->first('id_institucion') }}</div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="especialidadSeccion-{{ $seccion->id }}" class="form-label mb-1">Especialidades</label>
                                    @if(session('modal_editar_id') == $seccion->id && $errors->has('especialidades'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('especialidades') }}</div>
                                    @endif
                                    
                                    <div class="row">
                                        @foreach ($especialidades as $especialidad)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="especialidades[]" value="{{ $especialidad->id }}" 
                                                        id="especialidad{{ $especialidad->id }}_seccion{{ $seccion->id }}"
                                                        {{ $seccion->especialidades->contains($especialidad->id) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="especialidad{{ $especialidad->id }}_seccion{{ $seccion->id }}">
                                                        {{ $especialidad->nombre }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-4 mb-2">
                                    <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-guardar rounded-pill px-4">Guardar</button>
                                </div>
                                </form>
                            </div>
                            </div>
                        </div>
                        </div>


                        {{-- Modal Eliminar --}}
                        <div class="modal fade" id="modalEliminarSeccion-{{ $seccion->id }}" tabindex="-1" aria-labelledby="modalSeccionEliminarLabel-{{ $seccion->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content custom-modal">
                                    <div class="modal-body text-center">
                                        <div class="icon-container">
                                            <div class="circle-icon">
                                                <i class="bi bi-exclamation-circle"></i>
                                            </div>
                                        </div>
                                        <p class="modal-text">¿Desea eliminar la sección?</p>
                                        <div class="btn-group-custom">
                                            <form action="{{ route('seccion.destroy', ['seccion' => $seccion->id]) }}" method="post">
                                                @method('DELETE')
                                                @csrf
                                                <button type="submit" class="btn btn-custom {{ $seccion->condicion == 1 }}">Sí</button>
                                                <button type="button" class="btn btn-custom" data-bs-dismiss="modal">No</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Modal Reactivar --}}
                        <div class="modal fade" id="modalReactivarSeccion-{{ $seccion->id }}" tabindex="-1" aria-labelledby="modalReactivarSeccionLabel-{{ $seccion->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content custom-modal">
                                    <div class="modal-body text-center">
                                        <div class="icon-container">
                                            <div class="circle-icon" style="background-color: #28a745; color: #fff;">
                                                <i class="bi bi-arrow-counterclockwise" style="color: #fff;"></i>
                                            </div>
                                        </div>
                                        <p class="modal-text">¿Desea reactivar la sección?</p>
                                        <div class="btn-group-custom">
                                            <form action="{{ route('seccion.destroy', ['seccion' => $seccion->id]) }}" method="post">
                                                @method('DELETE')
                                                @csrf
                                                <button type="submit" class="btn btn-custom" style="background-color: #28a745; color: #fff;">Sí</button>
                                                <button type="button" class="btn btn-custom" data-bs-dismiss="modal">No</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @empty
                    <tr class="record-row">
                        <td class="text-center" colspan="4">No hay secciones registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>


    </div>
</div>


{{-- Modal Crear Sección --}}
<div class="modal fade" id="modalAgregarSeccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <button class="btn-back" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <h5 class="modal-title">Registro de sección</h5>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="formCrearSeccion" action="{{ route('seccion.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sección</label>
                        <input type="text" name="nombre" class="form-control @if(session('modal_crear') && $errors->has('nombre')) is-invalid @endif"
                               value="{{ old('nombre') }}">
                        @if(session('modal_crear') && $errors->has('nombre'))
                            <div class="invalid-feedback">{{ $errors->first('nombre') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Institución</label>
                        <select id="selectInstitucion" name="id_institucion" class="form-select @if(session('modal_crear') && $errors->has('id_institucion')) is-invalid @endif">
                            <option value="">Seleccione una institución</option>
                            @foreach(($instituciones ?? collect()) as $inst)
                                <option value="{{ $inst->id }}" {{ old('id_institucion') == $inst->id ? 'selected' : '' }}>{{ $inst->nombre }}</option>
                            @endforeach
                        </select>
                        @if(session('modal_crear') && $errors->has('id_institucion'))
                            <div class="invalid-feedback">{{ $errors->first('id_institucion') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Especialidad</label>
                        @if(session('modal_crear') && $errors->has('especialidades'))
                            <div class="text-danger small mb-2">
                                {{ $errors->first('especialidades') }}
                                <br><small><i class="bi bi-info-circle"></i> Una sección debe tener al menos una especialidad asignada.</small>
                            </div>
                        @endif
                        <div id="especialidades">
                            <div class="input-group dynamic-group">
                                <select id="selectEspecialidad" class="form-select" {{ empty($instituciones) ? 'disabled' : '' }}>
                                    <option value="">Seleccione primero una institución</option>
                                </select>
                                <button type="button" class="btn btn-success d-flex align-items-center justify-content-center" onclick="agregarEspecialidad()" style="height: 9%; min-width: 38px; padding: 0;">
                                    <i class="bi bi-plus" style="height: 49px;"></i>
                                </button>
                            </div>
                            <!-- Contenedor para especialidades seleccionadas -->
                            <div id="especialidadesSeleccionadas" class="mt-2"></div>
                           
                            <!-- Contenedor para mensajes de validación -->
                            <div id="mensajeValidacionCrear" class="alert alert-danger d-none mt-2" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <span id="textoMensajeCrear"></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    // Variables globales para crear sección
    let especialidadesAgregadas = [];
    // Catálogo de especialidades por institución inyectado desde el servidor
    const ESPECIALIDADES_POR_INST = @json(($especialidadesPorInstitucion ?? collect())->toArray());

    // Recargar la página al cerrar cualquier modal de crear o editar sección
    document.addEventListener('DOMContentLoaded', function() {
        var modalCrear = document.getElementById('modalAgregarSeccion');
        if (modalCrear) {
            modalCrear.addEventListener('hidden.bs.modal', function () {
                window.location.reload();
            });
        }
        document.querySelectorAll('[id^="modalEditarSeccion-"]').forEach(function(modalEditar) {
            modalEditar.addEventListener('hidden.bs.modal', function () {
                window.location.reload();
            });
        });
    });


    // ========== FUNCIONES PARA VALIDACIÓN ==========
   
    function mostrarMensajeValidacion(seccionId, mensaje) {
        const contenedorMensaje = document.getElementById(`mensajeValidacion-${seccionId}`);
        const textoMensaje = document.getElementById(`textoMensaje-${seccionId}`);
       
        if (contenedorMensaje && textoMensaje) {
            textoMensaje.textContent = mensaje;
            contenedorMensaje.classList.remove('d-none');
           
            // Auto-ocultar después de 4 segundos
            setTimeout(() => {
                ocultarMensajeValidacion(seccionId);
            }, 4000);
        }
    }
   
    function ocultarMensajeValidacion(seccionId) {
        const contenedorMensaje = document.getElementById(`mensajeValidacion-${seccionId}`);
        if (contenedorMensaje) {
            contenedorMensaje.classList.add('d-none');
        }
    }
   
    // Funciones para validación del modal de crear
    function mostrarMensajeValidacionCrear(mensaje) {
        const contenedorMensaje = document.getElementById('mensajeValidacionCrear');
        const textoMensaje = document.getElementById('textoMensajeCrear');
       
        if (contenedorMensaje && textoMensaje) {
            textoMensaje.textContent = mensaje;
            contenedorMensaje.classList.remove('d-none');
           
            // Auto-ocultar después de 4 segundos
            setTimeout(() => {
                ocultarMensajeValidacionCrear();
            }, 4000);
        }
    }
   
    function ocultarMensajeValidacionCrear() {
        const contenedorMensaje = document.getElementById('mensajeValidacionCrear');
        if (contenedorMensaje) {
            contenedorMensaje.classList.add('d-none');
        }
    }


    // ========== FUNCIONES PARA CREAR SECCIÓN ==========


    function agregarEspecialidad() {
        // Ocultar mensaje de validación anterior
        ocultarMensajeValidacionCrear();
       
        const instSelect = document.getElementById('selectInstitucion');
        const institucionId = instSelect ? instSelect.value : '';
        if (!institucionId) {
            mostrarMensajeValidacionCrear('Primero seleccione una institución');
            return;
        }

        const select = document.getElementById('selectEspecialidad');
        const selectedOption = select.options[select.selectedIndex];
       
        if (!selectedOption || !selectedOption.value) {
            mostrarMensajeValidacionCrear('Por favor seleccione una especialidad');
            return;
        }
       
        const id = selectedOption.value;
        const nombre = selectedOption.getAttribute('data-nombre');
       
        // Verificar si ya está agregada
        if (especialidadesAgregadas.includes(id)) {
            mostrarMensajeValidacionCrear('Esta especialidad ya está agregada');
            return;
        }
       
        // Agregar al array de control
        especialidadesAgregadas.push(id);
       
        // Crear elemento visual
        const contenedor = document.getElementById('especialidadesSeleccionadas');
        const especialidadDiv = document.createElement('div');
        especialidadDiv.className = 'input-group mt-2';
        especialidadDiv.setAttribute('data-id', id);
       
        // Crear los elementos por separado para evitar problemas con comillas
        const inputTexto = document.createElement('input');
        inputTexto.type = 'text';
        inputTexto.className = 'form-control';
        inputTexto.value = nombre;
        inputTexto.readOnly = true;
       
        const inputHidden = document.createElement('input');
        inputHidden.type = 'hidden';
        inputHidden.name = 'especialidades[]';
        inputHidden.value = id;
       
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.className = 'btn btn-danger';
        btnEliminar.innerHTML = '<i class="bi bi-x"></i>';
        btnEliminar.onclick = function() {
            quitarEspecialidad(this, id);
        };
       
        especialidadDiv.appendChild(inputTexto);
        especialidadDiv.appendChild(inputHidden);
        especialidadDiv.appendChild(btnEliminar);
       
        contenedor.appendChild(especialidadDiv);
        select.selectedIndex = 0;
    }


    function quitarEspecialidad(boton, id) {
        // Verificar si es la última especialidad
        const contenedor = document.getElementById('especialidadesSeleccionadas');
        const especialidadesActuales = contenedor.querySelectorAll('[data-id]');
       
        if (especialidadesActuales.length <= 1) {
            alert('No se puede eliminar la última especialidad. Una sección debe tener al menos una especialidad asignada.');
            return;
        }
       
        // Remover del array
        especialidadesAgregadas = especialidadesAgregadas.filter(espId => espId !== id);
        // Remover del DOM
        boton.parentElement.remove();
    }


    // Función para agregar especialidad cuando se repobla desde old()
    function agregarEspecialidadOld(id, nombre) {
        // Verificar si ya está agregada
        if (especialidadesAgregadas.includes(id)) {
            return;
        }
       
        // Agregar al array de control
        especialidadesAgregadas.push(id);
       
        // Crear elemento visual
        const contenedor = document.getElementById('especialidadesSeleccionadas');
        const especialidadDiv = document.createElement('div');
        especialidadDiv.className = 'input-group mt-2';
        especialidadDiv.setAttribute('data-id', id);
       
        // Crear los elementos por separado para evitar problemas con comillas
        const inputTexto = document.createElement('input');
        inputTexto.type = 'text';
        inputTexto.className = 'form-control';
        inputTexto.value = nombre;
        inputTexto.readOnly = true;
       
        const inputHidden = document.createElement('input');
        inputHidden.type = 'hidden';
        inputHidden.name = 'especialidades[]';
        inputHidden.value = id;
       
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.className = 'btn btn-danger';
        btnEliminar.innerHTML = '<i class="bi bi-x"></i>';
        btnEliminar.onclick = function() {
            quitarEspecialidad(this, id);
        };
       
        especialidadDiv.appendChild(inputTexto);
        especialidadDiv.appendChild(inputHidden);
        especialidadDiv.appendChild(btnEliminar);
       
        contenedor.appendChild(especialidadDiv);
    }


    // ========== ABRIR MODALES CON ERRORES ==========
   
    // Abrir modal de crear si hay errores de validación para crear
    @if (session('modal_crear'))
        document.addEventListener('DOMContentLoaded', function() {
            var modalCrear = new bootstrap.Modal(document.getElementById('modalAgregarSeccion'));
            modalCrear.show();
           
            // Repoblar especialidades seleccionadas
            @if(old('especialidades'))
                const especialidadesOld = @json(old('especialidades'));
                const especialidadesData = @json($especialidades->pluck('nombre', 'id'));
               
                especialidadesOld.forEach(function(id) {
                    if (especialidadesData[id]) {
                        agregarEspecialidadOld(id, especialidadesData[id]);
                    }
                });
            @endif
            // Si hay institución previa, cargar sus especialidades en el select
            const oldInst = '{{ old('id_institucion') }}';
            if (oldInst) {
                cargarEspecialidadesPorInstitucion(oldInst);
            }
        });
    @endif


    // Abrir modal de editar si hay errores de validación para editar
    @if (session('modal_editar_id'))
        document.addEventListener('DOMContentLoaded', function() {
            var modalEditar = new bootstrap.Modal(document.getElementById('modalEditarSeccion-{{ session('modal_editar_id') }}'));
            modalEditar.show();
        });
    @endif


    // ========== FUNCIONES DE BÚSQUEDA ==========


    // Funcionalidad de búsqueda en tiempo real - con verificación de existencia
    document.addEventListener('DOMContentLoaded', function() {
        let timeoutId;
        const inputBusqueda = document.getElementById('inputBusqueda');
        const formBusqueda = document.getElementById('busquedaForm');
        const btnLimpiar = document.getElementById('limpiarBusqueda');
        const selectInstitucion = document.getElementById('selectInstitucion');
        const selectEspecialidad = document.getElementById('selectEspecialidad');
       
        if (inputBusqueda && formBusqueda) {
            inputBusqueda.addEventListener('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    formBusqueda.submit();
                }, 500);
            });
           
            inputBusqueda.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    formBusqueda.submit();
                }
            });
        }
       
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function() {
                if (inputBusqueda) {
                    inputBusqueda.value = '';
                }
                window.location.href = '{{ route("seccion.index") }}';
            });
        }

        // Cambio de institución: cargar opciones de especialidades
        if (selectInstitucion && selectEspecialidad) {
            selectInstitucion.addEventListener('change', function() {
                const instId = this.value;
                especialidadesAgregadas = []; // reset selección previa
                document.getElementById('especialidadesSeleccionadas').innerHTML = '';
                cargarEspecialidadesPorInstitucion(instId);
            });
        }
       
    });

    function cargarEspecialidadesPorInstitucion(instId) {
        const selectEspecialidad = document.getElementById('selectEspecialidad');
        // Limpiar
        selectEspecialidad.innerHTML = '';
        if (!instId) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Seleccione primero una institución';
            selectEspecialidad.appendChild(opt);
            selectEspecialidad.disabled = true;
            return;
        }
        const opciones = ESPECIALIDADES_POR_INST[instId] || [];
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = opciones.length ? 'Seleccione una especialidad' : 'No hay especialidades para la institución';
        selectEspecialidad.appendChild(placeholder);
        opciones.forEach(esp => {
            const option = document.createElement('option');
            option.value = esp.id;
            option.setAttribute('data-nombre', esp.nombre);
            option.textContent = esp.nombre;
            selectEspecialidad.appendChild(option);
        });
        selectEspecialidad.disabled = opciones.length === 0;
    }


    // Función simplificada para agregar especialidad
    function agregarEspecialidadSimple(seccionId) {
        // Ocultar mensaje de validación anterior
        ocultarMensajeValidacion(seccionId);
       
        const select = document.getElementById(`selectEspecialidadEdit-${seccionId}`);
        const especialidadId = select.value;
        const nombreEspecialidad = select.options[select.selectedIndex].getAttribute('data-nombre');
       
        if (!especialidadId) {
            mostrarMensajeValidacion(seccionId, 'Por favor seleccione una especialidad');
            return;
        }
       
        // Verificar que no esté ya visible en la interfaz
        const contenedorVisual = document.getElementById(`especialidadesVisuales-${seccionId}`);
        const existeVisual = contenedorVisual.querySelector(`[data-id="${especialidadId}"]`);
       
        if (existeVisual) {
            mostrarMensajeValidacion(seccionId, 'Esta especialidad ya está asignada a la sección');
            return;
        }
       
        // Marcar el checkbox oculto
        const checkbox = document.getElementById(`esp-${seccionId}-${especialidadId}`);
        if (checkbox) {
            checkbox.checked = true;
        }
       
        // Agregar visual
        const elementoVisual = document.createElement('div');
        elementoVisual.className = 'input-group mt-2 especialidad-visual';
        elementoVisual.setAttribute('data-id', especialidadId);
        elementoVisual.innerHTML = `
            <input type="text" class="form-control" value="${nombreEspecialidad}" readonly>
            <button type="button" class="btn btn-danger" onclick="eliminarEspecialidadSimple('${seccionId}', '${especialidadId}')">
                <i class="bi bi-x"></i>
            </button>
        `;
        contenedorVisual.appendChild(elementoVisual);
       
        // Resetear select
        select.value = '';
       
        // Actualizar opciones del select para ocultar la especialidad agregada
        actualizarOpcionesSelect(seccionId);
    }
   
    // Función simplificada para eliminar especialidad
    function eliminarEspecialidadSimple(seccionId, especialidadId) {
        // Desmarcar checkbox oculto
        const checkbox = document.getElementById(`esp-${seccionId}-${especialidadId}`);
        if (checkbox) {
            checkbox.checked = false;
        }
       
        // Eliminar elemento visual
        const elementoVisual = document.querySelector(`#especialidadesVisuales-${seccionId} .especialidad-visual[data-id="${especialidadId}"]`);
        if (elementoVisual) {
            elementoVisual.remove();
        }
       
        // Actualizar opciones del select para mostrar la especialidad nuevamente
        actualizarOpcionesSelect(seccionId);
    }
   
    // Función para actualizar las opciones del select
    function actualizarOpcionesSelect(seccionId) {
        const select = document.getElementById(`selectEspecialidadEdit-${seccionId}`);
        const contenedorVisual = document.getElementById(`especialidadesVisuales-${seccionId}`);
       
        // Obtener IDs de especialidades visualmente asignadas
        const especialidadesAsignadas = [];
        const elementosVisuales = contenedorVisual.querySelectorAll('.especialidad-visual');
        elementosVisuales.forEach(elemento => {
            especialidadesAsignadas.push(elemento.getAttribute('data-id'));
        });
       
        // Mostrar/ocultar opciones del select
        Array.from(select.options).forEach(option => {
            if (option.value === '') {
                // Mantener la opción por defecto
                option.style.display = '';
                return;
            }
           
            if (especialidadesAsignadas.includes(option.value)) {
                option.style.display = 'none';
            } else {
                option.style.display = '';
            }
        });
       
        // Resetear selección si la opción actual está oculta
        if (select.value && especialidadesAsignadas.includes(select.value)) {
            select.value = '';
        }
    }
   
    // Event listener para formularios de editar
    document.addEventListener('DOMContentLoaded', function() {
        // Event listener para formulario de crear
        const modalCrear = document.getElementById('modalAgregarSeccion');

        if (modalCrear) {
            const formCrear = modalCrear.querySelector('form');
            if (formCrear) {
                formCrear.addEventListener('submit', function(e) {
                    // Ocultar mensajes de validación anteriores
                    ocultarMensajeValidacionCrear();

                    const instSelect = document.getElementById('selectInstitucion');
                    if (!instSelect || !instSelect.value) {
                        e.preventDefault();
                        mostrarMensajeValidacionCrear('Debe seleccionar una institución');
                        return false;
                    }

                    // Validar que haya al menos una especialidad
                    if (especialidadesAgregadas.length === 0) {
                        e.preventDefault();
                        mostrarMensajeValidacionCrear('Debe asignar al menos una especialidad a la sección');
                        return false;
                    }
                });
            }

            // Limpiar mensajes al abrir/cerrar modal de crear
            modalCrear.addEventListener('shown.bs.modal', function() {
                ocultarMensajeValidacionCrear();
            });
            modalCrear.addEventListener('hidden.bs.modal', function() {
                ocultarMensajeValidacionCrear();
            });
        }

        // Event listeners para TODOS los modales de editar
        document.querySelectorAll('[id^="modalEditarSeccion-"]').forEach(function(modalEditarEl) {
            modalEditarEl.addEventListener('shown.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                const seccionId = trigger ? trigger.getAttribute('data-id') : (modalEditarEl.id || '').split('-').pop();
                const form = modalEditarEl.querySelector('form');

                if (!seccionId || !form) return;

                // Ocultar mensajes de validación al abrir el modal
                ocultarMensajeValidacion(seccionId);

                // Actualizar opciones del select al abrir el modal
                actualizarOpcionesSelect(seccionId);

                // Agregar event listener al formulario si no lo tiene
                if (!form.hasAttribute('data-listener-added')) {
                    form.addEventListener('submit', function(e) {
                        // Validar que hay al menos una especialidad seleccionada
                        const especialidadesInputs = form.querySelectorAll('input[name="especialidades[]"]');
                        
                        if (especialidadesInputs.length === 0) {
                            e.preventDefault();
                            alert('Debe agregar al menos una especialidad');
                            return false;
                        }
                    });
                    form.setAttribute('data-listener-added', 'true');
                }
            });

            modalEditarEl.addEventListener('hidden.bs.modal', function() {
                const form = modalEditarEl.querySelector('form');
                const seccionIdInput = form ? form.querySelector('input[name="id"]') : null;
                const seccionId = seccionIdInput ? seccionIdInput.value : (modalEditarEl.id || '').split('-').pop();
                if (seccionId) {
                    ocultarMensajeValidacion(seccionId);
                }
            });
        });
    });

// JavaScript súper simple - solo validación si es necesaria
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema de checkboxes cargado correctamente');
});
</script>
@endsection





