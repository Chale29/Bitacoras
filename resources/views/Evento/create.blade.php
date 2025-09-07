@extends('Template-profesor')

@section('title', 'Registrar Evento')

@section('content')
  <div class="wrapper">
    <div class="main-content">
      <div class="container my-4">
        <div class="card shadow">
          <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Registrar Reporte</h4>
          </div>

          <div class="card-body p-4">
            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="bi bi-exclamation-triangle me-2"></i>¡Error!</strong>
                <ul class="mb-0 mt-2">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            <form method="POST" action="{{ route('evento.store') }}" id="eventoForm" class="needs-validation" novalidate>
              @csrf

              {{-- Campos ocultos --}}
              <input type="hidden" name="id_bitacora" id="id_bitacora">
              <input type="hidden" name="id_seccion" id="id_seccion">
              <input type="hidden" name="id_subarea" id="id_subarea">
              <input type="hidden" name="id_leccion" id="id_leccion">
              <input type="hidden" name="id_horario" id="id_horario">

              <div class="row g-4">
                {{-- Docente --}}
                <div class="col-md-6">
                  <div class="form-floating">
                    <input class="form-control bg-light" disabled value="{{ Auth::user()->name }}" id="docenteInput">
                    <label for="docenteInput"><i class="bi bi-person me-2"></i>Docente</label>
                  </div>
                </div>

                {{-- Selección de Lección --}}
                <div class="col-md-6">
                  <div class="form-floating">
                    <select name="leccion" id="leccionSelect" class="form-select" required>
                      <option value="">Seleccione una lección</option>
                      @foreach ($lecciones as $leccion)
                        @php
                          $horarioLeccion = \DB::table('horario_leccion')
                              ->where('idHorario', $leccion->horario_data->id ?? 0)
                              ->where('idLeccion', $leccion->id)
                              ->first();

                          // Intentar diferentes nombres de columnas para las horas
                          $horaInicio =
                              $horarioLeccion->horaInicio ??
                              ($horarioLeccion->hora_inicio ?? ($leccion->hora_inicio ?? 'N/A'));

                          $horaFin =
                              $horarioLeccion->horaFin ??
                              ($horarioLeccion->hora_fin ??
                                  ($horarioLeccion->hora_final ?? ($leccion->hora_final ?? 'N/A')));
                        @endphp
                        <option value="{{ $leccion->id }}" data-horario-id="{{ $leccion->horario_data->id ?? '' }}"
                          data-recinto="{{ optional($leccion->horario_data->recinto)->nombre ?? '' }}"
                          data-recinto-id="{{ optional($leccion->horario_data->recinto)->id ?? '' }}"
                          data-seccion="{{ optional($leccion->horario_data->seccion)->nombre ?? '' }}"
                          data-seccion-id="{{ optional($leccion->horario_data->seccion)->id ?? '' }}"
                          data-subarea="{{ optional($leccion->horario_data->subarea)->nombre ?? '' }}"
                          data-subarea-id="{{ optional($leccion->horario_data->subarea)->id ?? '' }}">
                          {{ $leccion->leccion }} - {{ $horaInicio }} a {{ $horaFin }}
                        </option>
                      @endforeach
                    </select>

                    <label for="leccionSelect"><i class="bi bi-clock me-2"></i>Lección</label>
                  </div>
                </div>
              </div>

              {{-- Campos dinámicos --}}
              <div class="row g-4 mt-2 d-none" id="camposDinamicos">
                <div class="col-md-4">
                  <div class="form-floating">
                    <input type="text" class="form-control bg-light" id="recintoInput" readonly>
                    <label for="recintoInput"><i class="bi bi-building me-2"></i>Recinto</label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-floating">
                    <input type="text" class="form-control bg-light" id="seccionInput" readonly>
                    <label for="seccionInput"><i class="bi bi-door-open me-2"></i>Sección</label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-floating">
                    <input type="text" class="form-control bg-light" id="subareaInput" readonly>
                    <label for="subareaInput"><i class="bi bi-geo-alt me-2"></i>Subárea</label>
                  </div>
                </div>
              </div>

              {{-- Observaciones y Prioridad --}}
              <div class="row g-4 mt-2">
                <div class="col-md-8">
                  <div class="form-floating">
                    <textarea class="form-control" name="observacion" id="observacionInput" style="height: 100px" required></textarea>
                    <label for="observacionInput"><i class="bi bi-chat-text me-2"></i>Observaciones</label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-floating">
                    <select name="prioridad" class="form-select" id="prioridadSelect" required>
                      <option value="">Seleccione una prioridad</option>
                      <option value="alta" class="text-danger">Alta</option>
                      <option value="media" class="text-warning">Media</option>
                      <option value="regular" class="text-info">Regular</option>
                      <option value="baja" class="text-success">Baja</option>
                    </select>
                    <label for="prioridadSelect"><i class="bi bi-exclamation-circle me-2"></i>Prioridad</label>
                  </div>
                  <div class="col-md-4">
                    <div class="form-floating">
                      <select name="id_institucion" class="form-select" id="prioridadSelect" required>
                        <option value="">Seleccione una institución</option>
                        @foreach ($instituciones as $institucion)
                          <option value="{{ $institucion->id }}">{{ $institucion->nombre }}</option>
                        @endforeach
                      </select>
                      <label for="prioridadSelect"><i class="bi bi-exclamation-circle me-2"></i>Institución</label>
                    </div>
                  </div>
                </div>

                {{-- Botones --}}
                <div class="d-flex justify-content-end gap-2 mt-4">
                  <button type="button" class="btn btn-secondary px-4" onclick="confirmarCancelar()">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                  </button>
                  <button type="submit" class="btn btn-primary px-4" onclick="return confirmarEnvio()">
                    <i class="bi bi-send me-2"></i>Enviar Reporte
                  </button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmación de Cancelación -->
  <div class="modal fade" id="modalConfirmacionCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content custom-modal">
        <div class="modal-body text-center">
          <div class="icon-container">
            <div class="circle-icon">
              <i class="bi bi-x-circle"></i>
            </div>
          </div>
          <p class="modal-text">¿Está seguro de cancelar el registro del evento?</p>
          <div class="btn-group-custom">
            <button type="button" class="btn btn-custom" onclick="cancelarFormulario()">Sí</button>
            <button type="button" class="btn btn-custom" data-bs-dismiss="modal">No</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmación de Envío -->
  <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content custom-modal">
        <div class="modal-body text-center">
          <div class="icon-container">
            <div class="circle-icon">
              <i class="bi bi-exclamation-circle"></i>
            </div>
          </div>
          <p class="modal-text">¿Está seguro de enviar este Reporte?</p>
          <div class="btn-group-custom">
            <button type="button" class="btn btn-custom" onclick="enviarFormulario()">Sí</button>
            <button type="button" class="btn btn-custom" data-bs-dismiss="modal">No</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Éxito -->
  <div class="modal fade" id="modalExito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-center">
        <div class="modal-body d-flex flex-column align-items-center gap-3 p-4">
          <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
          <p class="mb-0">Evento registrado con éxito</p>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      const bitacoras = @json($bitacoras);

      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('leccionSelect').addEventListener('change', mostrarCampos);
      });

      function mostrarCampos() {
        const select = document.getElementById('leccionSelect').selectedOptions[0];
        if (!select || !select.value) return limpiarCampos();

        document.getElementById('camposDinamicos').classList.remove('d-none');
        document.getElementById('recintoInput').value = select.getAttribute('data-recinto');
        document.getElementById('seccionInput').value = select.getAttribute('data-seccion');
        document.getElementById('subareaInput').value = select.getAttribute('data-subarea');

        // Actualizar campos ocultos
        document.getElementById('id_leccion').value = select.value;
        document.getElementById('id_horario').value = select.getAttribute('data-horario-id') || '';
        document.getElementById('id_bitacora').value = bitacoras.find(b => b.recinto_id == select.getAttribute(
          'data-recinto-id'))?.id || '';
        document.getElementById('id_seccion').value = select.getAttribute('data-seccion-id') || '';
        document.getElementById('id_subarea').value = select.getAttribute('data-subarea-id') || '';
      }

      function limpiarCampos() {
        document.getElementById('camposDinamicos').classList.add('d-none');
        document.getElementById('id_bitacora').value = '';
        document.getElementById('id_seccion').value = '';
        document.getElementById('id_subarea').value = '';
        document.getElementById('id_leccion').value = '';
        document.getElementById('id_horario').value = '';
      }

      function limpiarFormulario() {
        document.getElementById('eventoForm').reset();
        limpiarCampos();
      }

      function confirmarEnvio(e) {
        if (e) e.preventDefault();
        if (!document.getElementById('eventoForm').checkValidity()) {
          return true; // Permite que el formulario muestre las validaciones nativas
        }
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
        modal.show();
        return false;
      }

      function enviarFormulario() {
        document.getElementById('eventoForm').submit();
      }

      function confirmarCancelar() {
        // Mostrar modal de confirmación para cancelar
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmacionCancelar'));
        modal.show();
      }

      function cancelarFormulario() {
        // Limpiar el formulario
        limpiarFormulario();

        // Redirigir a la página anterior
        window.location.href = "{{ url()->previous() }}";
      }

      // Mostrar modal de éxito si hay mensaje de success
      @if (session('success'))
        document.addEventListener('DOMContentLoaded', function() {
          const modal = new bootstrap.Modal(document.getElementById('modalExito'));
          modal.show();
          setTimeout(() => {
            modal.hide();
          }, 2000);
        });
      @endif
    </script>
  @endpush

  @push('styles')
  @endpush
@endsection
