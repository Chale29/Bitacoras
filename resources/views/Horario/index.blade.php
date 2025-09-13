@extends('Template-administrador')

@section('title', 'Sistema de Bitácoras')

@section('content')

<style>
/* Responsividad adicional para pantallas pequeñas */
@media (max-width: 767.98px) {
    .search-bar-wrapper {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .btn-agregar {
        width: 100%;
        margin-left: 0 !important;
        margin-top: 10px;
    }
    .main-content {
        padding: 0 5px;
    }
    .modal-dialog {
        margin: 0.5rem auto;
    }
    .table th, .table td {
        font-size: 0.95rem;
        padding: 0.4rem 0.3rem;
    }
}
</style>

<div class="wrapper">
    <div class="main-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center mb-3">
            <div class="search-bar-wrapper d-flex flex-column flex-md-row align-items-stretch align-items-md-center mb-4 w-100">
                <div class="search-bar flex-grow-1 mb-2 mb-md-0">
                    <form id="busquedaForm" method="GET" action="{{ route('horario.index') }}" class="w-100 position-relative">
                        <span class="search-icon">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Buscar horario..."
                            name="busquedaHorario"
                            value="{{ request('busquedaHorario') }}"
                            id="inputBusqueda"
                            autocomplete="off"
                        >
                        @if(request('busquedaHorario'))
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
                @can('create_horario')
                    <button class="btn btn-primary rounded-pill px-4 d-flex align-items-center ms-0 ms-md-3 btn-agregar"
                        data-bs-toggle="modal" data-bs-target="#modalHorario"
                        title="Agregar Horario" style="background-color: #134496; font-size: 1.2rem;">
                        Agregar <i class="bi bi-plus-circle ms-2"></i>
                    </button>
                @endcan
            </div>
        </div>
        <div class="mb-3 d-flex flex-column flex-md-row gap-2">
            <a href="{{ route('horario.index', ['inactivos' => 1]) }}" class="btn btn-warning mb-2 mb-md-0 w-100 w-md-auto">
                Mostrar inactivos
            </a>
            <a href="{{ route('horario.index', ['activos' => 1]) }}" class="btn btn-primary w-100 w-md-auto">
                Mostrar activos
            </a>
        </div>
      {{-- Indicador de resultados de búsqueda --}}
            @if(request('busquedaHorario'))
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>
                        Mostrando {{ $horarios->count() }} resultado(s) para "<strong>{{ request('busquedaHorario') }}</strong>"
                        <a href="{{ route('horario.index') }}" class="btn btn-sm btn-outline-primary ms-2">Ver todas</a>
                    </span>
                </div>
            @endif

        {{-- Tabla Horarios Fijos --}}
        <div id="tabla-horarios-fijos" class="table-responsive">
            <table class="table">
                <thead>
                    <tr class="header-row">
                        <th class="col-dia">Día</th>
                        <th class="col-recinto">Recinto</th>
                        <th class="col-especialidad">Especialidad</th>
                        <th class="col-seccion">Sección</th>
                        <th class="col-entrada">Entrada</th>
                        <th class="col-salida">Salida</th>
                        <th class="col-docente">Docente</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $mostrarActivos = request('activos') == 1 || !request('inactivos');
                        $mostrarInactivos = request('inactivos') == 1;
                        $hayHorarios = false;
                    @endphp
                    @foreach ($horarios as $horario)
                        @if (($mostrarActivos && $horario->condicion == 1) || ($mostrarInactivos && $horario->condicion == 0))
                            @php $hayHorarios = true; @endphp
                            @can('view_horario')
                                <tr class="record-row">
                                    <td class="col-dia">
                                        @if($horario->tipoHorario == false)
                                            {{ $horario->fecha->format('Y/m/d') }}
                                        @endif
                                        {{ $horario->dia }}
                                    </td>
                                    <td class="col-recinto">{{ $horario->recinto->nombre ?? '' }}</td>
                                    <td class="col-especialidad">{{ $horario->subarea->nombre ?? '' }}</td>
                                    <td class="col-seccion">{{ $horario->seccion->nombre ?? '' }}</td>
                                    <td class="col-entrada">{{ $horario->hora_entrada }}</td>
                                    <td class="col-salida">{{ $horario->hora_salida }}</td>
                                    <td class="col-docente">{{ $horario->profesor->name ?? '' }}</td>
                                    <td class="col-acciones">
                                        @if($mostrarActivos && $horario->condicion == 1)
                                            @can('edit_horario')
                                                <button type="button" class="btn p-0" data-bs-toggle="modal" data-bs-target="#modalEditarHorario{{ $horario->id }}">
                                                    <i class="bi bi-pencil icon-editar"></i>
                                                </button>
                                            @endcan
                                            @can('delete_horario')
                                                <button class="btn p-0 text-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarHorario{{ $horario->id }}">
                                                    <i class="bi bi-trash icon-eliminar"></i>
                                                </button>
                                            @endcan
                                        @elseif($mostrarInactivos && $horario->condicion == 0)
                                            @can('delete_horario')
                                                <button class="btn p-0 text-success" data-bs-toggle="modal" data-bs-target="#modalEliminarHorario{{ $horario->id }}">
                                                    <i class="bi bi-arrow-counterclockwise icon-eliminar"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endcan
                        @endif
                    @endforeach
                    @unless($hayHorarios)
                        <tr class="record-row">
                            <td class="col text-center" colspan="8">No hay horarios fijos registrados.</td>
                        </tr>
                    @endunless
                </tbody>
            </table>
        </div>

    {{-- Modal Crear Horario --}}
    <div class="modal fade" id="modalHorario" tabindex="-1" aria-labelledby="modalHorarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header modal-header-custom">
                        <button class="btn-back" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <h5 class="modal-title">Crear Horario</h5>
                    </div>
                <form method="POST" action="{{ route('horario.store') }}">
                    @csrf
                    <div class="modal-header custom-header text-white px-4 py-3 position-relative justify-content-center">
                        <button type="button" class="btn p-0 d-flex align-items-center position-absolute start-0 ms-3" data-bs-dismiss="modal" aria-label="Cerrar">
                            <div class="circle-yellow d-flex justify-content-center align-items-center">
                                <i class="fas fa-arrow-left text-blue-forced"></i>
                            </div>
                            <div class="linea-vertical-amarilla ms-2"></div>
                        </button>
                    </div>
                    <div class="linea-divisoria-horizontal"></div>
                    
                    <div class="modal-body px-2 px-md-4 pt-3">
                        {{-- Tipo de Horario --}}
                        <div class="mb-4 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between fw-bold">
                            <label class="w-100 w-md-50 text-start mb-2 mb-md-0">Tipo de horario:</label>
                            <div class="d-flex align-items-center justify-content-center w-100 w-md-50 bg-info bg-opacity-10 border border-info rounded-3 p-2">
                                <div class="form-check me-3 d-flex align-items-center">
                                    <input class="form-check-input {{ $errors->has('tipoHorario') ? 'is-invalid' : '' }} {{ request('tipoHorario') == '1' ? 'active' : '' }}" type="radio" name="tipoHorario" id="fijoRadio" value="fijo" {{ old('tipoHorario') == 'fijo' ? 'checked' : '' }} >
                                    <label class="form-check-label ms-2" for="fijoRadio">Fijo</label>
                                </div>
                                <div style="width:1px; height:24px; background-color:#0d6efd; opacity:0.7;"></div>
                                <div class="form-check ms-3 d-flex align-items-center">
                                    <input class="form-check-input {{ $errors->has('tipoHorario') ? 'is-invalid' : '' }} {{ request('tipoHorario') == '0' ? 'active' : '' }}" type="radio" name="tipoHorario" id="temporalRadio" value="temporal" {{ old('tipoHorario') == 'temporal' ? 'checked' : '' }} >
                                    <label class="form-check-label ms-2" for="temporalRadio">Temporal</label>
                                </div>
                            </div>
                        </div>
                        @error('tipoHorario')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        {{-- Fecha --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Fecha:</label>
                            <input type="date" name="fecha" class="form-control rounded-4 w-100 w-md-50 {{ $errors->has('fecha') ? 'is-invalid' : '' }}" value="{{ old('fecha') }}" @if(old('tipoHorario')=='fijo') disabled @endif>
                        </div>
                        @error('fecha')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        {{-- Día --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Día:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select name="dia" class="form-select rounded-4 pe-5 {{ $errors->has('dia') ? 'is-invalid' : '' }}" @if(old('tipoHorario')=='temporal') disabled @endif>
                                    <option value="" hidden selected>Seleccione...</option>
                                    <option value="Lunes" {{ old('dia') == 'Lunes' ? 'selected' : '' }}>Lunes</option>
                                    <option value="Martes" {{ old('dia') == 'Martes' ? 'selected' : '' }}>Martes</option>
                                    <option value="Miércoles" {{ old('dia') == 'Miércoles' ? 'selected' : '' }}>Miércoles</option>
                                    <option value="Jueves" {{ old('dia') == 'Jueves' ? 'selected' : '' }}>Jueves</option>
                                    <option value="Viernes" {{ old('dia') == 'Viernes' ? 'selected' : '' }}>Viernes</option>
                                    <option value="Sábado" {{ old('dia') == 'Sábado' ? 'selected' : '' }}>Sabado</option>
                                    <option value="Domingo" {{ old('dia') == 'Domingo' ? 'selected' : '' }}>Domingo</option>
                                </select>
                            </div>
                        </div>
                        @error('dia')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror

                        {{-- Docente --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="idDocente" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Docente:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione un docente" data-live-search="true" name="user_id" id="user_id" class="form-select rounded-4 pe-5 {{ $errors->has('user_id') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione un docente</option>
                                    @foreach ($profesores as $profesor)
                                        <option value="{{$profesor->id}}" {{ old('user_id') == $profesor->id ? 'selected' : '' }}>{{$profesor->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('user_id')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        
                        {{-- Recinto --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="recintoSelect" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Recinto:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione un recinto" data-live-search="true" name="idRecinto" id="idRecinto" class="form-select rounded-4 pe-5 {{ $errors->has('idRecinto') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione un recinto</option>
                                    @foreach ($recintos as $recinto)
                                        <option value="{{$recinto->id}}" {{ old('idRecinto') == $recinto->id ? 'selected' : '' }}>{{$recinto->nombre}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('idRecinto')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        
                        {{-- Subárea --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="idSubarea" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Subárea:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione una subárea" data-live-search="true" name="idSubarea" id="idSubarea" class="form-select rounded-4 pe-5 {{ $errors->has('idSubarea') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione una subárea</option>
                                    @foreach ($subareas as $subarea)
                                        <option value="{{$subarea->id}}" {{ old('idSubarea') == $subarea->id ? 'selected' : '' }}>{{$subarea->nombre}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('idSubarea')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror

                        {{-- Sección --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="idSubareaSeccion" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Sección:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione una sección" data-live-search="true" name="idSeccion" id="idSeccion" class="form-select rounded-4 pe-5 {{ $errors->has('idSeccion') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione una sección</option>
                                    @foreach ($secciones as $seccion)
                                        <option value="{{$seccion->id}}" {{ old('idSeccion') == $seccion->id ? 'selected' : '' }}>{{$seccion->nombre}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('idSeccion')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror

                        {{-- Lecciones --}}
                        <div class="mb-3">
                            <label class="fw-bold mb-3">Lecciones:</label>
                            
                            {{-- Botones de modalidad --}}
                            <div class="mb-3 d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="btnDiurnoCrear">
                                    Diurno (7:00 AM - 4:20 PM)
                                </button>
                                <button type="button" class="btn btn-outline-warning" id="btnNocturnoCrear">
                                    Nocturno (5:50 PM - 9:55 PM)
                                </button>
                            </div>
                            
                            <div class="border rounded-4 p-3 {{ $errors->has('lecciones') ? 'border-danger' : '' }}" style="max-height: 300px; overflow-y: auto;">
                                {{-- Lecciones Académicas --}}
                                <div class="mb-3" id="leccionesAcademicasCrear">
                                    <h6 class="text-primary mb-2">Lecciones Académicas</h6>
                                    <div class="row">
                                        @foreach($lecciones as $leccion)
                                            @if(strtolower($leccion->tipoLeccion) == 'academica')
                                                <div class="col-12 col-md-6 mb-2 leccion-item" data-modalidad="diurno">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="lecciones[]" 
                                                            value="{{ $leccion->id }}" id="leccion_create_{{ $leccion->id }}"
                                                            {{ (old('lecciones') && in_array($leccion->id, old('lecciones'))) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="leccion_create_{{ $leccion->id }}">
                                                            {{ $leccion->leccion }} ({{ $leccion->hora_inicio }} - {{ $leccion->hora_final }})
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Lecciones Técnicas --}}
                                <div>
                                    <h6 class="text-success mb-2">Lecciones Técnicas</h6>
                                    <div class="row">
                                        @foreach($lecciones as $leccion)
                                            @if(strtolower($leccion->tipoLeccion) == 'tecnica')
                                                @php
                                                    // Lógica más simple para determinar modalidad
                                                    $horaInicio = $leccion->hora_inicio;
                                                    $periodo = $leccion->hora_inicio_periodo ?? 'AM';
                                                    
                                                    list($hora, $minuto) = explode(':', $horaInicio);
                                                    $hora = (int)$hora;
                                                    $minuto = (int)$minuto;
                                                    
                                                    if ($periodo == 'AM') {
                                                        $modalidad = 'diurno';
                                                    } else { // PM
                                                        if ($hora == 12) {
                                                            // 12:xx PM es mediodía (diurno)
                                                            $modalidad = 'diurno';
                                                        } elseif ($hora >= 1 && $hora <= 4) {
                                                            // 1:xx PM - 4:xx PM es diurno
                                                            $modalidad = 'diurno';
                                                        } elseif ($hora == 4 && $minuto <= 20) {
                                                            // Hasta 4:20 PM es diurno
                                                            $modalidad = 'diurno';
                                                        } elseif ($hora >= 6 || ($hora == 5 && $minuto >= 50)) {
                                                            // 5:50 PM en adelante es nocturno
                                                            $modalidad = 'nocturno';
                                                        } else {
                                                            // Entre 4:21 PM y 5:49 PM (brecha) - tratar como diurno
                                                            $modalidad = 'diurno';
                                                        }
                                                    }
                                                @endphp
                                                
                                                <div class="col-12 col-md-6 mb-2 leccion-item" data-modalidad="{{ $modalidad }}">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="lecciones[]" 
                                                            value="{{ $leccion->id }}" id="leccion_create_{{ $leccion->id }}"
                                                            {{ (old('lecciones') && in_array($leccion->id, old('lecciones'))) ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="leccion_create_{{ $leccion->id }}">
                                                            {{ $leccion->leccion }} ({{ $leccion->hora_inicio }} {{ $periodo }} - {{ $leccion->hora_final }} {{ $leccion->hora_final_periodo ?? $periodo }})
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Botones para seleccionar/deseleccionar todos --}}
                                <div class="mt-3 d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodasLeccionesCrear()">
                                        Seleccionar todas
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodasLeccionesCrear()">
                                        Deseleccionar todas
                                    </button>
                                </div>
                            </div>
                            @error('lecciones')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer px-2 px-md-4 pb-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-crear w-100 w-md-auto">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($horarios as $horario )
    {{-- Modales de edición y eliminación se incluyen por cada horario en el loop de arriba --}}
    <div class="modal fade" id="modalEditarHorario{{ $horario->id }}" tabindex="-1" aria-labelledby="modalEditarHorarioLabel{{ $horario->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header modal-header-custom">
                    <button class="btn-back" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <h5 class="modal-title">Modificar horario</h5>
                </div>
                <form method="POST" action="{{ route('horario.update', ['horario' => $horario]) }}">
                    @csrf
                    @method('PUT')
                    <div class="linea-divisoria-horizontal"></div>
                    
                    <input type="hidden" name="id" value="{{ $horario->id }}">
                    <div class="modal-body px-2 px-md-4 pt-3">
                        {{-- Tipo de Horario --}}
                        <div class="mb-4 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between fw-bold">
                            <label class="w-100 w-md-50 text-start mb-2 mb-md-0">Tipo de horario:</label>
                            <div class="d-flex align-items-center justify-content-center w-100 w-md-50 bg-info bg-opacity-10 border border-info rounded-3 p-2">
                                <div class="form-check me-3 d-flex align-items-center">
                                    <input class="form-check-input {{ $errors->has('tipoHorario') ? 'is-invalid' : '' }}" type="radio" name="tipoHorario" id="fijoRadio{{ $horario->id }}" value="fijo" 
                                    {{ old('tipoHorario', $horario->tipoHorario == 1 ? 'fijo' : 'temporal') == 'fijo' ? 'checked' : ''}} >
                                    <label class="form-check-label ms-2" for="fijoRadio{{ $horario->id }}">Fijo</label>
                                </div>
                                <div style="width:1px; height:24px; background-color:#0d6efd; opacity:0.7;"></div>
                                <div class="form-check ms-3 d-flex align-items-center">
                                    <input class="form-check-input {{ $errors->has('tipoHorario') ? 'is-invalid' : '' }}" type="radio" name="tipoHorario" id="temporalRadio{{ $horario->id }}" value="temporal"
                                    {{ old('tipoHorario', $horario->tipoHorario == 1 ? 'fijo' : 'temporal') == 'temporal' ? 'checked' : ''}} >
                                    <label class="form-check-label ms-2" for="temporalRadio{{ $horario->id }}">Temporal</label>
                                </div>
                            </div>
                        </div>
                        @error('tipoHorario')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        {{-- Fecha --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Fecha:</label>
                            <input type="date" name="fecha" class="form-control rounded-4 w-100 w-md-50 {{ $errors->has('fecha') ? 'is-invalid' : '' }}" value="{{ old('fecha', $horario->fecha ? $horario->fecha->format('Y-m-d') : '') }}">
                        </div>
                        @error('fecha')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        {{-- Día --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Día:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select name="dia" class="form-select rounded-4 pe-5 {{ $errors->has('dia') ? 'is-invalid' : '' }}">
                                    <option value="" hidden>Seleccione...</option>
                                    <option value="Lunes" {{ old('dia', $horario->dia) == 'Lunes' ? 'selected' : '' }}>Lunes</option>
                                    <option value="Martes" {{ old('dia', $horario->dia) == 'Martes' ? 'selected' : '' }}>Martes</option>
                                    <option value="Miércoles" {{ old('dia', $horario->dia) == 'Miércoles' ? 'selected' : '' }}>Miércoles</option>
                                    <option value="Jueves" {{ old('dia', $horario->dia) == 'Jueves' ? 'selected' : '' }}>Jueves</option>
                                    <option value="Viernes" {{ old('dia', $horario->dia) == 'Viernes' ? 'selected' : '' }}>Viernes</option>
                                    <option value="Sábado" {{ old('dia', $horario->dia) == 'Sábado' ? 'selected' : '' }}>Sábado</option>
                                    <option value="Domingo" {{ old('dia', $horario->dia) == 'Domingo' ? 'selected' : '' }}>Domingo</option>
                                </select>
                            </div>
                        </div>
                        @error('dia')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        
                        {{-- Docente --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="idDocente" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Docente:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione un docente" data-live-search="true" name="user_id" id="user_id" class="form-select rounded-4 pe-5 {{ $errors->has('user_id') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione un docente</option>
                                    @foreach ($profesores as $profesor)
                                        <option value="{{$profesor->id}}"
                                         {{ old('user_id', $horario->user_id) == $profesor->id ? 'selected' : '' }}>
                                            {{ $profesor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('user_id')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        
                        {{-- Recinto --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="recintoSelect" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Recinto:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione un recinto" data-live-search="true" name="idRecinto" id="idRecinto" class="form-select rounded-4 pe-5 {{ $errors->has('idRecinto') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione un recinto</option>
                                    @foreach ($recintos as $recinto)
                                        <option value="{{$recinto->id}}"
                                        {{ old('idRecinto', $horario->idRecinto) == $recinto->id ? 'selected' : '' }}>
                                            {{ $recinto->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('idRecinto')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror
                        
                            {{-- Subárea --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="idSubarea" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Subárea:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione una subárea" data-live-search="true" name="idSubarea" id="idSubarea" class="form-select rounded-4 pe-5 {{ $errors->has('idSubarea') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione una subárea</option>
                                    @foreach ($subareas as $subarea)
                                        <option value="{{$subarea->id}}" 
                                            {{ old('idSubarea', $horario->idSubarea) == $subarea->id ? 'selected' : '' }}>
                                            {{ $subarea->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('idSubarea')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror

                            {{-- Sección --}}
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between">
                            <label for="idSubareaSeccion" class="fw-bold me-3 w-100 w-md-50 text-start mb-2 mb-md-0">Sección:</label>
                            <div class="position-relative w-100 w-md-50">
                                <select data-size="4" title="Seleccione una sección" data-live-search="true" name="idSeccion" id="idSeccion" class="form-select rounded-4 pe-5 {{ $errors->has('idSeccion') ? 'is-invalid' : '' }}" >
                                    <option value="">Seleccione una sección</option>
                                    @foreach ($secciones as $seccion)
                                        <option value="{{$seccion->id}}" 
                                        {{ old('idSeccion', $horario->idSeccion) == $seccion->id ? 'selected' : '' }}>
                                            {{ $seccion->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('idSeccion')
                            <div class="text-danger small mb-3 text-end">{{ $message }}</div>
                        @enderror

                                            {{-- Lecciones --}}
                                            <div class="mb-3">
                                                <label class="fw-bold mb-3">Lecciones:</label>
                                                

                                                {{-- Botones de modalidad --}}
                                                <div class="mb-3 d-flex gap-2">
                                                    <button type="button" class="btn btn-primary" id="btnDiurnoEditar{{ $horario->id }}">
                                                        Diurno (7:00 AM - 4:20 PM)
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning" id="btnNocturnoEditar{{ $horario->id }}">
                                                        Nocturno (5:50 PM - 9:55 PM)
                                                    </button>
                    
                                                </div>
                                                
                                                <div class="border rounded-4 p-3 {{ $errors->has('lecciones') ? 'border-danger' : '' }}" style="max-height: 300px; overflow-y: auto;">
                                                    {{-- Lecciones Acadmicas --}}
                                                    <div class="mb-3" id="leccionesAcademicasEditar{{ $horario->id }}">
                                                        <h6 class="text-primary mb-2">Lecciones Académicas</h6>
                                                        <div class="row">
                                                            @foreach($lecciones as $leccion)
                                                                @if(strtolower($leccion->tipoLeccion) == 'academica')
                                                                    <div class="col-12 col-md-6 mb-2 leccion-item" data-modalidad="diurno">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox" name="lecciones[]" 
                                                                                value="{{ $leccion->id }}" id="leccion_edit_{{ $horario->id }}_{{ $leccion->id }}"
                                                                                {{ (old('lecciones') && in_array($leccion->id, old('lecciones'))) || (!old('lecciones') && $horario->leccion->contains($leccion->id)) ? 'checked' : '' }}>
                                                                            <label class="form-check-label small" for="leccion_edit_{{ $horario->id }}_{{ $leccion->id }}">
                                                                                {{ $leccion->leccion }} ({{ $leccion->hora_inicio }} - {{ $leccion->hora_final }})
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    {{-- Lecciones Tcnicas --}}
                                                    <div>
                                                        <h6 class="text-success mb-2">Lecciones Técnicas</h6>
                                                        <div class="row">
                                                            @foreach($lecciones as $leccion)
                                                                @if(strtolower($leccion->tipoLeccion) == 'tecnica')
                                                                    @php
                                                                        // Lógica más simple para determinar modalidad
                                                                        $horaInicio = $leccion->hora_inicio;
                                                                        $periodo = $leccion->hora_inicio_periodo ?? 'AM';
                                                                        
                                                                        list($hora, $minuto) = explode(':', $horaInicio);
                                                                        $hora = (int)$hora;
                                                                        $minuto = (int)$minuto;
                                                                        
                                                                        if ($periodo == 'AM') {
                                                                            $modalidad = 'diurno';
                                                                        } else { // PM
                                                                            if ($hora == 12) {
                                                                                $modalidad = 'diurno';
                                                                            } elseif ($hora >= 1 && $hora <= 4) {
                                                                                $modalidad = 'diurno';
                                                                            } elseif ($hora == 4 && $minuto <= 20) {
                                                                                $modalidad = 'diurno';
                                                                            } elseif ($hora >= 6 || ($hora == 5 && $minuto >= 50)) {
                                                                                $modalidad = 'nocturno';
                                                                            } else {
                                                                                $modalidad = 'diurno';
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    
                                                                    <div class="col-12 col-md-6 mb-2 leccion-item" data-modalidad="{{ $modalidad }}">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox" name="lecciones[]" 
                                                                                value="{{ $leccion->id }}" id="leccion_edit_{{ $horario->id }}_{{ $leccion->id }}"
                                                                                {{ (old('lecciones') && in_array($leccion->id, old('lecciones'))) || (!old('lecciones') && $horario->leccion->contains($leccion->id)) ? 'checked' : '' }}>
                                                                            <label class="form-check-label small" for="leccion_edit_{{ $horario->id }}_{{ $leccion->id }}">
                                                                                {{ $leccion->leccion }} ({{ $leccion->hora_inicio }} - {{ $leccion->hora_final }})
                                                                            </label>
                                                                        </div>
                                                                    </div>  
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                   </div>

                                                    {{-- Botones para seleccionar/deseleccionar todos --}}
                                                    <div class="mt-3 d-flex gap-2 justify-content-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodasLeccionesEditar('{{ $horario->id }}')">
                                                            Seleccionar todas
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodasLeccionesEditar('{{ $horario->id }}')">
                                                            Deseleccionar todas
                                                        </button>
                                                    </div>
                                                </div>
                                                @error('lecciones')
                                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                    </div>
                                    <div class="modal-footer px-2 px-md-4 pb-3 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary btn-modificar w-100 w-md-auto">Modificar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal eliminar -->
                        <div class="modal fade" id="modalEliminarHorario{{ $horario->id }}" tabindex="-1" aria-labelledby="modalEliminarHorarioLabel{{ $horario->id }}" 
                        aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content custom-modal">
                                    <div class="modal-body text-center">
                                        <div class="icon-container">
                                            <div class="circle-icon">
                                            <i class="bi bi-exclamation-circle"></i>
                                            </div>
                                        </div>
                                        <p class="modal-text">
                                            @if($horario->condicion == 1)
                                                ¿Desea eliminar el horario?
                                            @else
                                                ¿Desea restaurar el horario?
                                            @endif
                                        </p>
                                        <div class="btn-group-custom">
                                            <form action="{{ route('horario.destroy', ['horario' => $horario->id]) }}" method="post">
                                                @method('DELETE')
                                                @csrf
                                                <button type="submit" class="btn btn-custom {{ $horario->condicion == 1 }}">Sí</button>
                                                <button type="button" class="btn btn-custom" data-bs-dismiss="modal">No</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- Modal Éxito Eliminar --}}
                    @if(session('eliminado'))
                    <div class="modal fade show" id="modalExitoEliminar" tabindex="-1" aria-modal="true" style="display:block;">
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
                                    <p class="mb-0">Horario eliminado con xito</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
    @endforeach
    
</div>
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Recargar la página al cerrar cualquier modal de crear o editar
        var modalCrear = document.getElementById('modalHorario');
        if (modalCrear) {
            modalCrear.addEventListener('hidden.bs.modal', function () {
                window.location.reload();
            });
        }

        document.querySelectorAll('[id^="modalEditarHorario"]').forEach(function(modalEditar) {
            modalEditar.addEventListener('hidden.bs.modal', function () {
                window.location.reload();
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('[id^="modalEditarHorario"]').forEach(function(modal) {
        const id = modal.id.replace('modalEditarHorario', '');
        const fijoRadio = modal.querySelector(`#fijoRadio${id}`);
        const temporalRadio = modal.querySelector(`#temporalRadio${id}`);
        const fechaInput = modal.querySelector('input[name="fecha"]');
        const diaSelect = modal.querySelector('select[name="dia"]');

        function toggleFieldsEditar() {
            if (fijoRadio && fijoRadio.checked) {
                if (fechaInput) {
                    fechaInput.disabled = true;
                    fechaInput.style.backgroundColor = '#f8f9fa';
                    fechaInput.style.color = '#6c757d';
                    fechaInput.style.cursor = 'not-allowed';
                }
                if (diaSelect) {
                    diaSelect.disabled = false;
                    diaSelect.style.backgroundColor = '';
                    diaSelect.style.color = '';
                    diaSelect.style.cursor = '';
                }
            } else if (temporalRadio && temporalRadio.checked) {
                if (diaSelect) {
                    diaSelect.disabled = true;
                    diaSelect.style.backgroundColor = '#f8f9fa';
                    diaSelect.style.color = '#6c757d';
                    diaSelect.style.cursor = 'not-allowed';
                }
                if (fechaInput) {
                    fechaInput.disabled = false;
                    fechaInput.style.backgroundColor = '';
                    fechaInput.style.color = '';
                    fechaInput.style.cursor = '';
                }
            }
        }

        if (fijoRadio && temporalRadio && fechaInput && diaSelect) {
            fijoRadio.addEventListener('change', toggleFieldsEditar);
            temporalRadio.addEventListener('change', toggleFieldsEditar);

            modal.addEventListener('shown.bs.modal', function() {
                toggleFieldsEditar();
            });

            // Estado inicial
            toggleFieldsEditar();
        }
    });


    // Funcionalidad de búsqueda en tiempo real
    let timeoutId;
    const inputBusqueda = document.getElementById('inputBusqueda');
    const formBusqueda = document.getElementById('busquedaForm');
    const btnLimpiar = document.getElementById('limpiarBusqueda');
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(function() {
                formBusqueda.submit();
            }, 500); // Espera 500ms después de que el usuario deje de escribir
        });
        
        // También permitir búsqueda al presionar Enter
        inputBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                formBusqueda.submit();
            }
        });
    }
    
    // Funcionalidad del botón limpiar
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            inputBusqueda.value = '';
            window.location.href = '{{ route("horario.index") }}';
        });
    }
    // Abrir modal automticamente si hay errores de validación
    @if ($errors->any() && (old('_method') === null))
        // Si hay errores y no es una actualización (método PUT), abrir modal de creación
        const modalCrear = new bootstrap.Modal(document.getElementById('modalHorario'));
        modalCrear.show();
    @elseif ($errors->any() && old('_method') === 'PUT')
        // Si hay errores y es una actualización, abrir modal de edición correspondiente
        @if(old('id'))
            const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarHorario{{ old('id') }}'));
            modalEditar.show();
        @endif
    @endif

    // Obtener elementos
    const fijoRadio = document.getElementById('fijoRadio');
    const temporalRadio = document.getElementById('temporalRadio');
    const fechaInput = document.querySelector('input[name="fecha"]');
    const diaSelect = document.querySelector('select[name="dia"]');

    console.log('Elementos encontrados:', {
        fijoRadio: fijoRadio,
        temporalRadio: temporalRadio,
        fechaInput: fechaInput,
        diaSelect: diaSelect
    });

    // Función para manejar el estado de los campos
    function toggleFields() {
        if (fijoRadio && fijoRadio.checked) {
            // Si se selecciona "Fijo": deshabilitar fecha, habilitar día
            if (fechaInput) {
                fechaInput.disabled = true;
                fechaInput.style.backgroundColor = '#f8f9fa';
                fechaInput.style.color = '#6c757d';
                fechaInput.style.cursor = 'not-allowed';
            }
            if (diaSelect) {
                diaSelect.disabled = false;
                diaSelect.style.backgroundColor = '';
                diaSelect.style.color = '';
                diaSelect.style.cursor = '';
            }
            console.log('Fijo seleccionado - Fecha deshabilitada, Día habilitado');
        } else if (temporalRadio && temporalRadio.checked) {
            // Si se selecciona "Temporal": deshabilitar día, habilitar fecha
            if (diaSelect) {
                diaSelect.disabled = true;
                diaSelect.style.backgroundColor = '#f8f9fa';
                diaSelect.style.color = '#6c757d';
                diaSelect.style.cursor = 'not-allowed';
            }
            if (fechaInput) {
                fechaInput.disabled = false;
                fechaInput.style.backgroundColor = '';
                fechaInput.style.color = '';
                fechaInput.style.cursor = '';
            }
            console.log('Temporal seleccionado - Día deshabilitado, Fecha habilitada');
        }
    }

    // Verificar que los elementos existen
    if (fijoRadio && temporalRadio && fechaInput && diaSelect) {
        console.log('Todos los elementos existen, configurando eventos...');
        
        // Agregar event listeners a los radio buttons
        fijoRadio.addEventListener('change', function() {
            console.log('Fijo radio cambiado');
            toggleFields();
        });

        temporalRadio.addEventListener('change', function() {
            console.log('Temporal radio cambiado');
            toggleFields();
        });

        // Estado inicial basado en los valores old() o por defecto
        toggleFields();

        console.log('Estado inicial configurado');
        
    } else {
        console.error('Algunos elementos no se encontraron:', {
            fijoRadio: !!fijoRadio,
            temporalRadio: !!temporalRadio,
            fechaInput: !!fechaInput,
            diaSelect: !!diaSelect
        });
    }
});

// Funciones para manejar selección de lecciones en modal crear
function seleccionarTodasLeccionesCrear() {
    const checkboxes = document.querySelectorAll('#modalHorario input[name="lecciones[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function deseleccionarTodasLeccionesCrear() {
    const checkboxes = document.querySelectorAll('#modalHorario input[name="lecciones[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

// Funciones para manejar selección de lecciones en modal editar
function seleccionarTodasLeccionesEditar(horarioId) {
    const checkboxes = document.querySelectorAll(`#modalEditarHorario${horarioId} input[name="lecciones[]"]`);
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function deseleccionarTodasLeccionesEditar(horarioId) {
    const checkboxes = document.querySelectorAll(`#modalEditarHorario${horarioId} input[name="lecciones[]"]`);
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

// Funciones para manejar selección de lecciones (mantener compatibilidad)
function seleccionarTodasLecciones() {
    const checkboxes = document.querySelectorAll('input[name="lecciones[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function deseleccionarTodasLecciones() {
    const checkboxes = document.querySelectorAll('input[name="lecciones[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad de filtrado de lecciones en modal crear
    const btnDiurnoCrear = document.getElementById('btnDiurnoCrear');
    const btnNocturnoCrear = document.getElementById('btnNocturnoCrear');

    function filtrarLeccionesCrear(modalidad) {
        const leccionItems = document.querySelectorAll('#modalHorario .leccion-item');
        const leccionesAcademicas = document.getElementById('leccionesAcademicasCrear');
        
        // Resetear estilos de botones
        btnDiurnoCrear.className = 'btn btn-outline-primary';
        btnNocturnoCrear.className = 'btn btn-outline-warning';
        
        if (modalidad === 'diurno') {
            btnDiurnoCrear.className = 'btn btn-primary';
            
            // Mostrar lecciones académicas (son diurnas)
            leccionesAcademicas.style.display = 'block';
            
            // Filtrar lecciones técnicas
            leccionItems.forEach(item => {
                if (item.dataset.modalidad === 'diurno') {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    // Desmarcar checkboxes ocultos
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
            });
            
        } else if (modalidad === 'nocturno') {
            btnNocturnoCrear.className = 'btn btn-warning';
            
            // Ocultar lecciones académicas (no son nocturnas)
            leccionesAcademicas.style.display = 'none';
            
            // Desmarcar todas las lecciones académicas
            leccionesAcademicas.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Filtrar lecciones técnicas
            leccionItems.forEach(item => {
                if (item.dataset.modalidad === 'nocturno') {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    // Desmarcar checkboxes ocultos
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
            });
        }
    }

    // Event listeners para los botones
    if (btnDiurnoCrear) {
        btnDiurnoCrear.addEventListener('click', () => filtrarLeccionesCrear('diurno'));
    }
    if (btnNocturnoCrear) {
        btnNocturnoCrear.addEventListener('click', () => filtrarLeccionesCrear('nocturno'));
    }

    // Estado inicial: mostrar diurno por defecto
    filtrarLeccionesCrear('diurno');

    // Funcionalidad de filtrado para modales de editar
    @foreach($horarios as $horario)
    (function() {
        const btnDiurnoEditar{{ $horario->id }} = document.getElementById('btnDiurnoEditar{{ $horario->id }}');
        const btnNocturnoEditar{{ $horario->id }} = document.getElementById('btnNocturnoEditar{{ $horario->id }}');

        function filtrarLeccionesEditar{{ $horario->id }}(modalidad) {
            const leccionItems = document.querySelectorAll('#modalEditarHorario{{ $horario->id }} .leccion-item');
            const leccionesAcademicas = document.getElementById('leccionesAcademicasEditar{{ $horario->id }}');
            
            // Resetear estilos de botones
            if (btnDiurnoEditar{{ $horario->id }}) btnDiurnoEditar{{ $horario->id }}.className = 'btn btn-outline-primary';
            if (btnNocturnoEditar{{ $horario->id }}) btnNocturnoEditar{{ $horario->id }}.className = 'btn btn-outline-warning';
            
            if (modalidad === 'diurno') {
                if (btnDiurnoEditar{{ $horario->id }}) btnDiurnoEditar{{ $horario->id }}.className = 'btn btn-primary';
                
                // Mostrar lecciones académicas (son diurnas)
                if (leccionesAcademicas) leccionesAcademicas.style.display = 'block';
                
                // Filtrar lecciones técnicas
                leccionItems.forEach(item => {
                    if (item.dataset.modalidad === 'diurno') {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                        // NO desmarcar checkboxes que ya estaban seleccionados
                    }
                });
                
            } else if (modalidad === 'nocturno') {
                if (btnNocturnoEditar{{ $horario->id }}) btnNocturnoEditar{{ $horario->id }}.className = 'btn btn-warning';
                
                // Ocultar lecciones académicas (no son nocturnas)
                if (leccionesAcademicas) leccionesAcademicas.style.display = 'none';
                
                // Filtrar lecciones técnicas
                leccionItems.forEach(item => {
                    if (item.dataset.modalidad === 'nocturno') {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                        // NO desmarcar checkboxes que ya estaban seleccionados
                    }
                });
            }
        }

        // Event listeners para los botones del modal editar
        if (btnDiurnoEditar{{ $horario->id }}) {
            btnDiurnoEditar{{ $horario->id }}.addEventListener('click', () => filtrarLeccionesEditar{{ $horario->id }}('diurno'));
        }
        if (btnNocturnoEditar{{ $horario->id }}) {
            btnNocturnoEditar{{ $horario->id }}.addEventListener('click', () => filtrarLeccionesEditar{{ $horario->id }}('nocturno'));
        }

        // CAMBIO PRINCIPAL: Estado inicial basado en las lecciones del horario
        document.getElementById('modalEditarHorario{{ $horario->id }}').addEventListener('shown.bs.modal', function() {
            @php
                // Detectar modalidad del horario actual
                $esDiurno = false;
                $esNocturno = false;
                
                foreach($horario->leccion as $leccionHorario) {
                    if (strtolower($leccionHorario->tipoLeccion) == 'academica') {
                        $esDiurno = true;
                    } else {
                        $horaInicio = $leccionHorario->hora_inicio;
                        $periodo = $leccionHorario->hora_inicio_periodo ?? 'AM';
                        
                        list($hora, $minuto) = explode(':', $horaInicio);
                        $hora = (int)$hora;
                        $minuto = (int)$minuto;
                        
                        if ($periodo == 'AM') {
                            $esDiurno = true;
                        } else {
                            if ($hora == 12 || ($hora >= 1 && $hora <= 4) || ($hora == 4 && $minuto <= 20)) {
                                $esDiurno = true;
                            } elseif ($hora >= 6 || ($hora == 5 && $minuto >= 50)) {
                                $esNocturno = true;
                            } else {
                                $esDiurno = true;
                            }
                        }
                    }
                }
                
                $modalidadInicial = $esNocturno && !$esDiurno ? 'nocturno' : 'diurno';
            @endphp
            
            filtrarLeccionesEditar{{ $horario->id }}('{{ $modalidadInicial }}');
        });
    })();
    @endforeach
    
});
</script>