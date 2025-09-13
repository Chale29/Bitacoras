@foreach ($eventos as $evento)
    <div class="record-row hover-effect">
        <div data-label="Docente">{{ $evento->usuario->name ?? 'N/A' }}</div>
        <div data-label="Recinto">{{ $evento->horario->recinto->nombre ?? '' }}</div>
        <div data-label="Fecha">{{ \Carbon\Carbon::parse($evento->fecha)->format('d/m/Y') }}</div>
        <div data-label="Hora">{{ \Carbon\Carbon::parse($evento->hora_envio)->format('H:i') }}</div>
        <div data-label="Prioridad">
            <span class="badge bg-secondary text-white" style="background-color: #6c757d !important; color: #fff !important;">
                {{ ucfirst($evento->prioridad) }}
            </span>
        </div>
        <div data-label="Estado">
            @switch($evento->estado)
                @case('en_espera')
                    <span class="badge estado-en-espera" style="background-color: #ffc107 !important; color: #000 !important;">
                        <i class="bi bi-clock-fill me-1" style="color: #fff !important; font-size: 0.7rem !important;"></i>En Espera
                    </span>
                    @break
                @case('en_proceso')
                    <span class="badge estado-en-proceso" style="background-color: #17a2b8 !important; color: #fff !important;">
                        <i class="bi bi-gear-fill me-1" style="font-size: 0.7rem !important;"></i>En Proceso
                    </span>
                    @break
                @case('completado')
                    <span class="badge estado-completado" style="background-color: #28a745 !important; color: #fff !important;">
                        <i class="bi bi-check-circle-fill me-1" style="font-size: 0.7rem !important;"></i>Completado
                    </span>
                    @break
                @default
                    <span class="badge bg-secondary text-white" style="background-color: #6c757d !important; color: #fff !important;">
                        <i class="bi bi-question-circle me-1" style="font-size: 0.7rem !important;"></i>{{ $evento->estado ? ucfirst($evento->estado) : 'Sin Estado' }}
                    </span>
            @endswitch
        </div>
        <div data-label="Detalles">
            <button class="btn btn-sm rounded-pill px-3" 
                    style="background-color: #134496; color: white;"
                    onclick="abrirModal({{ $evento->id }})">
                <i class="bi bi-pencil-square me-1"></i> Editar
            </button>
        </div>
    </div>
@endforeach
