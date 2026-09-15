<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name') . ' - ' . config('app.tagline'))</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 flex flex-col">

    <!-- Header Fijo Transversal con Logotipo, Auth y Barra de Búsqueda/Filtros Integrada -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <!-- Fila Superior: Logo y Usuario -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between border-b border-gray-100">
            
            <!-- Logo / Título con redirección al Dashboard -->
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                    <i data-lucide="shield-check" class="w-10 h-10 text-orange-500"></i>
                    <div class="flex flex-col">
                        <h1 class="font-extrabold text-xl tracking-wider text-gray-800 group-hover:text-orange-600 transition-colors leading-tight">{{config('app.name')}}</h1>
                        <span class="text-xs font-bold text-gray-600">{{config('app.tagline')}}</span>
                    </div>
                </a>
            </div>

            <!-- Usuario y Logout (AUTH) -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2 text-sm text-gray-600 font-medium">
                    <i data-lucide="user" class="w-5 h-5 text-orange-500 hidden sm:inline"></i>
                    <span>{{ auth()->user()->username ?? 'Admin' }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                    @csrf
                    <button type="submit" class="flex items-center space-x-1 text-sm bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-700 px-3 py-2 rounded-lg transition-all" title="Cerrar Sesión">
                        <span class="hidden sm:inline">Salir</span>
                        <i data-lucide="square-arrow-right-exit" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Fila Inferior del Header: Barra de Filtros Global y Fija -->
        <div class="bg-white py-3 shadow-inner">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    
                    <!-- Buscador por texto / RUT -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Buscar (RUT / Folio)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ej. 76.543.210-k"
                                   class="w-full pl-9 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-orange-300 focus:bg-white">
                        </div>
                    </div>

                    <!-- Fecha Desde -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Desde</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-orange-300 focus:bg-white">
                    </div>

                    <!-- Fecha Hasta -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Hasta</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-orange-300 focus:bg-white">
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center space-x-2">
                        <button type="submit" class="flex-grow bg-orange-500 hover:bg-orange-600 text-white font-medium py-1.5 px-4 rounded-lg text-xs transition-all shadow-sm shadow-orange-500/20 flex items-center justify-center space-x-1">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            <span>Buscar</span>
                        </button>
                        <a href="{{ route('dashboard') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 py-1.5 px-3 rounded-xl text-xs transition-all flex items-center justify-center" title="Limpiar filtros">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full">
        @yield('content')
    </main>

    <!-- Inicializar Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>