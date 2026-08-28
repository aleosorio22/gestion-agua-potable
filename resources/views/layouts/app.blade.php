<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard')</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #ffffff;
            --body-bg: #f8f9fa;
            --border-color: #e9ecef;
            --primary-color: #6366f1;
            --text-color: #212529;
            --muted-color: #6c757d;
        }

        body {
            background-color: var(--body-bg);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                         Roboto, Helvetica, Arial, sans-serif;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;

            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);

            display: flex;
            flex-direction: column;

            z-index: 1050;

            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            height: 70px;

            display: flex;
            align-items: center;

            padding: 0 24px;

            border-bottom: 1px solid var(--border-color);

            font-size: 20px;
            font-weight: 700;

            color: var(--text-color);
            text-decoration: none;
        }

        .sidebar-brand span {
            color: var(--primary-color);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;

            padding: 20px 12px;
        }

        .sidebar-section {
            margin-bottom: 24px;
        }

        .sidebar-section-title {
            padding: 0 12px;
            margin-bottom: 8px;

            font-size: 11px;
            font-weight: 600;

            text-transform: uppercase;
            letter-spacing: 0.06em;

            color: #9ca3af;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 10px 12px;
            margin-bottom: 3px;

            border-radius: 7px;

            color: #4b5563;
            text-decoration: none;

            font-size: 14px;
            font-weight: 500;

            transition: all 0.2s ease;
        }

        .sidebar-link i {
            font-size: 17px;
            width: 20px;
            text-align: center;
        }

        .sidebar-link:hover {
            background-color: #f3f4f6;
            color: var(--text-color);
        }

        .sidebar-link.active {
            background-color: #eef2ff;
            color: var(--primary-color);
        }

        /* =========================
           SIDEBAR FOOTER
        ========================= */

        .sidebar-footer {
            padding: 15px;

            border-top: 1px solid var(--border-color);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 8px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background-color: #eef2ff;
            color: var(--primary-color);

            font-weight: 600;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 11px;
            color: var(--muted-color);
        }

        /* =========================
           MAIN
        ========================= */

        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar-custom {
            height: 70px;

            background-color: #ffffff;

            border-bottom: 1px solid var(--border-color);

            display: flex;
            align-items: center;

            padding: 0 28px;
        }

        .navbar-title {
            font-size: 18px;
            font-weight: 600;
        }

        .navbar-actions {
            margin-left: auto;

            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 7px;

            color: #6b7280;
            text-decoration: none;

            transition: background 0.2s;
        }

        .navbar-icon:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        /* =========================
           CONTENT
        ========================= */

        .main-content {
            padding: 30px;
        }

        /* =========================
           MOBILE
        ========================= */

        .mobile-menu-button {
            display: none;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 991.98px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-menu-button {
                display: flex;

                width: 38px;
                height: 38px;

                align-items: center;
                justify-content: center;

                border: none;
                background: transparent;

                font-size: 22px;
            }

            .navbar-custom {
                padding: 0 18px;
            }

            .main-content {
                padding: 20px;
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;

                background: rgba(0, 0, 0, 0.35);

                z-index: 1040;
            }

            .sidebar-overlay.show {
                display: block;
            }
        }

        @media (max-width: 575.98px) {

            .main-content {
                padding: 15px;
            }

            .navbar-title {
                font-size: 16px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside id="sidebar" class="sidebar">

        <!-- Logo -->

        <a href="{{ url('/') }}" class="sidebar-brand">
            Mi<span>App</span>
        </a>


        <!-- Navigation -->

        <div class="sidebar-content">

            <!-- Principal -->

            <div class="sidebar-section">

                <div class="sidebar-section-title">
                    Principal
                </div>

                <a href="{{ url('/dashboard') }}"
                   class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}">

                    <i class="bi bi-grid"></i>

                    <span>Dashboard</span>

                </a>

            </div>


            <!-- Administración -->

            <div class="sidebar-section">

                <div class="sidebar-section-title">
                    Administración
                </div>

                <a href="#"
                   class="sidebar-link">

                    <i class="bi bi-people"></i>

                    <span>Usuarios</span>

                </a>

                <a href="{{ url('/tarifas') }}"
                   class="sidebar-link">

                    <i class="bi bi-box"></i>

                    <span>Tarifas</span>

                </a>

                <a href="#"
                   class="sidebar-link">

                    <i class="bi bi-tags"></i>

                    <span>Contadores</span>

                </a>

            </div>


            <!-- Sistema -->

            <div class="sidebar-section">

                <div class="sidebar-section-title">
                    Sistema
                </div>

                <a href="#"
                   class="sidebar-link">

                    <i class="bi bi-gear"></i>

                    <span>Configuración</span>

                </a>

            </div>

        </div>


        <!-- User -->

        <div class="sidebar-footer">

            <div class="user-card">

                <div class="user-avatar">
                    R
                </div>

                <div class="user-info">

                    <div class="user-name">
                        Usuario
                    </div>

                    <div class="user-role">
                        Administrador
                    </div>

                </div>

                <button class="btn btn-sm border-0">
                    <i class="bi bi-three-dots"></i>
                </button>

            </div>

        </div>

    </aside>


    <!-- Overlay móvil -->

    <div id="sidebarOverlay"
         class="sidebar-overlay">
    </div>


    <!-- =========================
         MAIN
    ========================== -->

    <div class="main-wrapper">


        <!-- NAVBAR -->

        <nav class="navbar-custom">

            <!-- Mobile menu -->

            <button id="menuButton"
                    class="mobile-menu-button">

                <i class="bi bi-list"></i>

            </button>


            <!-- Page title -->

            <div class="navbar-title">

                @yield('page-title', 'Dashboard')

            </div>


            <!-- Actions -->

            <div class="navbar-actions">

                <a href="#"
                   class="navbar-icon">

                    <i class="bi bi-bell"></i>

                </a>

                <a href="#"
                   class="navbar-icon">

                    <i class="bi bi-person-circle"></i>

                </a>

            </div>

        </nav>


        <!-- CONTENT -->

        <main class="main-content">

            @yield('content')

        </main>

    </div>


    <!-- Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>


    <!-- Sidebar JS -->

    <script>

        const sidebar = document.getElementById('sidebar');
        const menuButton = document.getElementById('menuButton');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        menuButton.addEventListener('click', function () {

            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');

        });

        sidebarOverlay.addEventListener('click', function () {

            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');

        });

    </script>

    @stack('scripts')

</body>
</html>