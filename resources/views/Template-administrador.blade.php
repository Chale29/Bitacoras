<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistema de Bitácoras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('Css/Sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('Css/tabla.css') }}">
    <link rel="stylesheet" href="{{ asset('Css/Modals.css') }}">
    <link rel="stylesheet" href="{{ asset('Css/delete-alerts.css') }}">
    <link rel="stylesheet" href="{{ asset('Css/Inicio.css') }}">

    <link rel="icon" href="https://covao.ed.cr/wp-content/uploads/2025/02/cropped-favicon-32x32.png" sizes="32x32">
</head>


<body>

    <div id="sidebar-navbar">
        <div class="topbar">
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
            <div class="sidebar-content">
                <div class="sidebar-logo">
                    <img src="https://academiashhc.com/wp-content/uploads/2022/09/AcademiasB.png" alt="Logo Academias" class="right-logo d-none d-md-block" />
                </div>

                @if(isset($sidebarItems))
                    {{-- Inicio --}}
                    @if($sidebarItems['inicio']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['inicio']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['inicio']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['inicio']['label'] }}">{{ $sidebarItems['inicio']['label'] }}</div>
                            </a>
                        </div>
                    @endif

                    {{-- Roles y permisos --}}
                    @if($sidebarItems['roles']['show'])
                        <div class="sidebar-section">
                            <div class="sidebar-item has-subitems" onclick="toggleSidebarSubmenu('roles-subitems')" style="cursor:pointer;">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['roles']['icon'] }}"></i></div>
                                <div class="label">{{ $sidebarItems['roles']['label'] }}</div>
                            </div>
                            <div class="sidebar-subitems" id="roles-subitems" style="display:none;">
                                @if($sidebarItems['roles']['subitems']['roles']['show'])
                                    <a href="{{ $sidebarItems['roles']['subitems']['roles']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['roles']['subitems']['roles']['icon'] }}"></i> {{ $sidebarItems['roles']['subitems']['roles']['label'] }}
                                    </a>
                                @endif
                                @if($sidebarItems['roles']['subitems']['permisos']['show'])
                                    <a href="{{ $sidebarItems['roles']['subitems']['permisos']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['roles']['subitems']['permisos']['icon'] }}"></i> {{ $sidebarItems['roles']['subitems']['permisos']['label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Usuarios --}}
                    @if($sidebarItems['usuarios']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['usuarios']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['usuarios']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['usuarios']['label'] }}">{{ $sidebarItems['usuarios']['label'] }}</div>
                            </a>
                        </div>
                    @endif

                    {{-- Otras opciones --}}
                    @if($sidebarItems['otras_opciones']['show'])
                        <div class="sidebar-section">
                            <div class="sidebar-item has-subitems" onclick="toggleSidebarSubmenu('otras-subitems')" style="cursor:pointer;">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['otras_opciones']['icon'] }}"></i></div>
                                <div class="label">{{ $sidebarItems['otras_opciones']['label'] }}</div>
                            </div>
                            <div class="sidebar-subitems" id="otras-subitems" style="display:none;">
                                @if($sidebarItems['otras_opciones']['subitems']['instituciones']['show'])
                                    <a href="{{ $sidebarItems['otras_opciones']['subitems']['instituciones']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['otras_opciones']['subitems']['instituciones']['icon'] }}"></i> {{ $sidebarItems['otras_opciones']['subitems']['instituciones']['label'] }}
                                    </a>
                                @endif
                                @if($sidebarItems['otras_opciones']['subitems']['especialidades']['show'])
                                    <a href="{{ $sidebarItems['otras_opciones']['subitems']['especialidades']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['otras_opciones']['subitems']['especialidades']['icon'] }}"></i> {{ $sidebarItems['otras_opciones']['subitems']['especialidades']['label'] }}
                                    </a>
                                @endif
                                @if($sidebarItems['otras_opciones']['subitems']['secciones']['show'])
                                    <a href="{{ $sidebarItems['otras_opciones']['subitems']['secciones']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['otras_opciones']['subitems']['secciones']['icon'] }}"></i> {{ $sidebarItems['otras_opciones']['subitems']['secciones']['label'] }}
                                    </a>
                                @endif
                                @if($sidebarItems['otras_opciones']['subitems']['subareas']['show'])
                                    <a href="{{ $sidebarItems['otras_opciones']['subitems']['subareas']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['otras_opciones']['subitems']['subareas']['icon'] }}"></i> {{ $sidebarItems['otras_opciones']['subitems']['subareas']['label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Llaves --}}
                    @if($sidebarItems['llaves']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['llaves']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['llaves']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['llaves']['label'] }}">{{ $sidebarItems['llaves']['label'] }}</div>
                            </a>
                        </div>
                    @endif

                    {{-- Recintos --}}
                    @if($sidebarItems['recintos']['show'])
                        <div class="sidebar-section">
                            <div class="sidebar-item has-subitems" onclick="toggleSidebarSubmenu('recinto-subitems')" style="cursor:pointer;">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['recintos']['icon'] }}"></i></div>
                                <div class="label">{{ $sidebarItems['recintos']['label'] }}</div>
                            </div>
                            <div class="sidebar-subitems" id="recinto-subitems" style="display:none;">
                                @if($sidebarItems['recintos']['subitems']['tipo_recintos']['show'])
                                    <a href="{{ $sidebarItems['recintos']['subitems']['tipo_recintos']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['recintos']['subitems']['tipo_recintos']['icon'] }}"></i> {{ $sidebarItems['recintos']['subitems']['tipo_recintos']['label'] }}
                                    </a>
                                @endif
                                @if($sidebarItems['recintos']['subitems']['estado_recintos']['show'])
                                    <a href="{{ $sidebarItems['recintos']['subitems']['estado_recintos']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['recintos']['subitems']['estado_recintos']['icon'] }}"></i> {{ $sidebarItems['recintos']['subitems']['estado_recintos']['label'] }}
                                    </a>
                                @endif
                                @if($sidebarItems['recintos']['subitems']['recintos']['show'])
                                    <a href="{{ $sidebarItems['recintos']['subitems']['recintos']['route'] }}" class="sidebar-item subitem">
                                        <i class="bi {{ $sidebarItems['recintos']['subitems']['recintos']['icon'] }}"></i> {{ $sidebarItems['recintos']['subitems']['recintos']['label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Horarios --}}
                    @if($sidebarItems['horarios']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['horarios']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['horarios']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['horarios']['label'] }}">{{ $sidebarItems['horarios']['label'] }}</div>
                            </a>
                        </div>
                    @endif

                    {{-- QR Temporales --}}
                    @if($sidebarItems['qr_temporales']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['qr_temporales']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['qr_temporales']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['qr_temporales']['label'] }}">{{ $sidebarItems['qr_temporales']['label'] }}</div>
                            </a>
                        </div>
                    @endif

                    {{-- Bitácora --}}
                    @if($sidebarItems['bitacora']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['bitacora']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['bitacora']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['bitacora']['label'] }}">{{ $sidebarItems['bitacora']['label'] }}</div>
                            </a>
                        </div>
                    @endif

                    {{-- Reportes --}}
                    @if($sidebarItems['reportes']['show'])
                        <div class="sidebar-section">
                            <a href="{{ $sidebarItems['reportes']['route'] }}" class="sidebar-item">
                                <div class="icon-circle"><i class="bi {{ $sidebarItems['reportes']['icon'] }}"></i></div>
                                <div class="label" data-bs-toggle="tooltip" title="{{ $sidebarItems['reportes']['label'] }}">{{ $sidebarItems['reportes']['label'] }}</div>
                            </a>
                        </div>
                    @endif
                @endif

                {{-- Salir - siempre visible --}}
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
        </div>
    </div>
    
    <div class="wrapper">
        <div class="main-content p-4">
            @yield('content')
        </div>
    </div>

    <script>
    function toggleSidebarSubmenu(id) {
        var el = document.getElementById(id);
        var parent = el.previousElementSibling;
        if (el.style.display === 'none' || el.style.display === '') {
            el.style.display = 'block';
            parent.classList.add('active');
        } else {
            el.style.display = 'none';
            parent.classList.remove('active');
        }
    }
    </script>
    <script src="{{ asset('JS/Sidebar.js') }}"></script>
    {{-- <script src="{{ asset('JS/modals-create-especialidad.js') }}"></script> --}}
    {{-- <script src="{{ asset('JS/modals-edit-especialidad.js') }}"></script> --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
    <!-- Scripts específicos de páginas -->
    @stack('scripts')
</body>

</html>
