<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Sistema de Bitácoras')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('Css/Sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('Css/inicio.css') }}">
    
    <link rel="stylesheet" href="{{ asset('Css/delete-alerts.css') }}">
    @stack('styles')
</head>

<body>
    <div id="sidebar-navbar">
        <div class="topbar mt-0">
            <button class="hamburger d-md-none" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="left-section d-none d-md-flex">
                <img src="https://academiashhc.com/wp-content/uploads/2022/09/AcademiasB.png" alt="Logo Academias" class="right-logo d-none d-md-block" />

                <div class="separator"></div>
                <img src="https://covao.ed.cr/wp-content/uploads/2025/01/Especialidades-logos-05-e1736905518920.webp" alt="Logo COVAO Nocturno" class="logo" />
               
            </div>
            <div class="title">SIREBI</div>
            <img src="https://covao.ed.cr/wp-content/uploads/2024/12/image-removebg-preview-3.png" alt="Logo COVAO" class="logo" />
           
        </div>

        <div class="yellow-line"></div>
        <div class="sidebar-separator d-none d-md-block"></div>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="https://covao.ed.cr/wp-content/uploads/2024/12/image-removebg-preview-3.png" />
            </div>
            <a href="{{ route('Dashboard.indexDocente') }}" class="sidebar-item">
                <div class="icon-circle"><i class="bi bi-house-door-fill"></i></div>
                <div class="label" data-bs-toggle="tooltip" title="Inicio">Inicio</div>
            </a>

            <a href="{{ route('bitacora.index') }}" class="sidebar-item">
                <div class="icon-circle"><i class="bi bi-calendar-week-fill"></i></div>
                <div class="label" data-bs-toggle="tooltip" title="Bitácora">Crear reportes</div>
            </a>

            <a href="{{ route('profesor-llave.index') }}" class="sidebar-item" title="Ir a Gestión de Llaves">
                <div class="icon-circle"><i class="bi bi-key-fill"></i></div>
                <div class="label" data-bs-toggle="tooltip" title="Gestión de Llaves">Llaves</div>
            </a>

            <a href="{{route('evento.index_profesor')}}" class="sidebar-item">
                <div class="icon-circle"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
                <div class="label" data-bs-toggle="tooltip" title="Reportes">Mis reportes</div>
            </a>


            <div class="mt-auto mb-3" style="width:100%;">
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <a href="#" onclick="this.closest('form').submit();return false;" class="sidebar-item" style="width:100%;">
                        <div class="icon-circle"><i class="bi bi-box-arrow-right"></i></div>
                        <div class="label" data-bs-toggle="tooltip" title="Salir">Salir</div>
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="wrapper">
        <div class="main-content p-4">
            @yield('content')
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('JS/Sidebar.js') }}"></script>
    <script src="{{ asset('JS/indexBitacoras.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    @stack('scripts')
</body>

</html>
