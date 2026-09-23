<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Oficina de Agua Potable')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

    <header class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <span class="font-semibold text-lg">💧 {{ $entidad['nombre'] ?? 'Oficina de Agua Potable' }}</span>
            <a href="{{ route('filament.portal.auth.login') }}" class="text-sm text-blue-600 hover:underline">Iniciar sesión</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-10">
        @yield('content')
    </main>

    <footer class="text-center text-sm text-slate-400 py-8">
        &copy; {{ date('Y') }} {{ $entidad['nombre'] ?? '' }}
    </footer>

</body>
</html>