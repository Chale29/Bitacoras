@extends('Template-profesor')

@section('title', 'Bitácoras')

@section('content')
<style>
/* Estilos responsivos para botones */
.btn-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
}

.btn-responsive {
    flex: 1;
    min-height: 40px;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Tablet y pantallas medianas */
@media (min-width: 576px) {
    .btn-actions {
        flex-direction: row;
        width: auto;
    }
    
    .btn-responsive {
        flex: none;
        white-space: nowrap;
    }
}

/* Pantallas grandes */
@media (min-width: 768px) {
    .btn-responsive {
        font-size: 0.9rem;
    }
}

/* Ajustes para el header de las cards */
.card-header-responsive {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
}

@media (min-width: 768px) {
    .card-header-responsive {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
}
</style>

<div class="container mt-4">
    <h1 class="text-center mb-4">Bitácoras</h1>
    
    {{-- Búsqueda + botones de filtro --}}
    <div class="search-bar-wrapper mb-4">
        
        
        {{-- Botones de filtro por estado --}}
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('bitacora.index', ['inactivas' => '1']) }}" 
               class="btn {{ request('inactivas') ? 'btn-warning' : 'btn-outline-warning' }}">
                Mostrar inactivos
            </a>
            <a href="{{ route('bitacora.index') }}" 
               class="btn {{ !request('inactivas') ? 'btn-primary' : 'btn-outline-primary' }}">
                Mostrar activos
            </a>
        </div>
    </div>
    
    {{-- Estadísticas de bitácoras --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-journal-text text-primary" style="font-size: 2rem;"></i>
                    <h3 class="mt-2">{{ $bitacoras->count() }}</h3>
                    <p class="text-muted">Total Bitácoras {{ request('inactivas') ? 'Inactivas' : 'Activas' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    <h3 class="mt-2">{{ isset($todasLasBitacoras) ? $todasLasBitacoras->where('estado', 1)->count() : 0 }}</h3>
                    <p class="text-muted">Activas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                    <h3 class="mt-2">{{ isset($todasLasBitacoras) ? $todasLasBitacoras->where('estado', 0)->count() : 0 }}</h3>
                    <p class="text-muted">Inactivas</p>
                </div>
            </div>
        </div>
    </div>
    
    @if(isset($bitacoras) && count($bitacoras) > 0)
        @foreach ($bitacoras as $bitacora)
            <div class="card mb-4">
                <div class="card-header card-header-responsive">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h5 mb-0">Bitácora - {{ $bitacora->recinto->nombre ?? 'Sin Recinto Asociado' }}</h2>
                        <span class="badge {{ $bitacora->estado == 1 ? 'bg-success' : 'bg-secondary' }}">
                            {{ $bitacora->estado == 1 ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                    <div class="btn-actions">
                        @if($bitacora->estado == 1)
                            <a href="{{ route('evento.create', ['id_bitacora' => $bitacora->id]) }}" class="btn btn-success btn-sm btn-responsive">
                                <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Agregar evento</span><span class="d-sm-none">Agregar</span>
                            </a>
                        @else
                            <button class="btn btn-secondary btn-sm btn-responsive" disabled title="No se pueden agregar eventos a bitácoras inactivas">
                                <i class="bi bi-x-circle"></i> <span class="d-none d-sm-inline">Agregar evento</span><span class="d-sm-none">Agregar</span>
                            </button>
                        @endif
                        @if ($bitacora->evento && $bitacora->evento->isNotEmpty())
                            <button class="btn btn-outline-primary btn-sm btn-responsive" type="button" data-bs-toggle="collapse" data-bs-target="#eventos-{{ $bitacora->id }}" aria-expanded="false" aria-controls="eventos-{{ $bitacora->id }}">
                                <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">Ver eventos ({{ $bitacora->evento->count() }})</span><span class="d-sm-none">Ver ({{ $bitacora->evento->count() }})</span>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($bitacora->estado == 0)
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>Bitácora Inactiva:</strong> No se pueden agregar nuevos eventos a esta bitácora.
                        </div>
                    @endif
                    @if ($bitacora->evento && $bitacora->evento->isEmpty())
                        <p class="text-muted">No hay eventos registrados para esta bitácora.</p>
                    @elseif ($bitacora->evento && $bitacora->evento->isNotEmpty())
                        <div class="collapse" id="eventos-{{ $bitacora->id }}">
                            <h6 class="mb-3"><i class="bi bi-calendar-event"></i> Eventos registrados:</h6>
                            <div class="row">
                                @foreach ($bitacora->evento as $evento)
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3">
                                            <div class="mb-2">
                                                <strong><i class="bi bi-calendar3"></i> Fecha:</strong> 
                                                <span class="text-primary">{{ $evento->fecha }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong><i class="bi bi-person"></i> Realizado por:</strong> 
                                                <span>{{ $evento->usuario->name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong><i class="bi bi-chat-text"></i> Observación:</strong> 
                                                <span>{{ $evento->observacion }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong><i class="bi bi-exclamation-triangle"></i> Prioridad:</strong> 
                                                <span class="badge bg-{{ $evento->prioridad == 'alta' ? 'danger' : ($evento->prioridad == 'media' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($evento->prioridad) }}
                                                </span>
                                            </div>
                                            <div>
                                                <strong><i class="bi bi-info-circle"></i> Estado:</strong> 
                                                <span class="badge bg-info">{{ $evento->estado }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-muted">No hay eventos registrados para esta bitácora.</p>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info">
            <h4>No hay bitácoras disponibles</h4>
            <p>No se han encontrado bitácoras de los recintos asignados a sus horarios.</p>
            <p><small class="text-muted">Solo puede ver bitácoras de los recintos donde tiene clases programadas.</small></p>
        </div>
    @endif
</div>

<script>
const inputBusqueda = document.getElementById('inputBusqueda');
const btnLimpiar = document.getElementById('limpiarBusqueda');

if (btnLimpiar && inputBusqueda) {
    btnLimpiar.addEventListener('click', function() {
        // Limpiar búsqueda y mantener el filtro de estado actual
        const url = new URL(window.location);
        url.searchParams.delete('busquedaBitacora');
        window.location.href = url.toString();
    });
}
</script>
@endsection
