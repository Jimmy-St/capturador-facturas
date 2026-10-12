<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{config('app.name')}} - {{config('app.tagline')}}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex flex-col items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-xl shadow-xl w-80 max-w-md border border-gray-100">
        
        <!-- Icono superior con degradé vibrante Talos -->
        <div class="flex justify-center mb-1">
            <svg class="w-24 h-24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 10px rgba(249, 115, 22, 0.22));">
                <defs>
                    <linearGradient id="talos-login-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f97316" />
                        <stop offset="100%" stop-color="#f59e0b" />
                    </linearGradient>
                </defs>
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" 
                      stroke="url(#talos-login-grad)" 
                      stroke-width="1.15" 
                      stroke-linecap="round" 
                      stroke-linejoin="round" />
                <path d="m9 12 2 2 4-4" 
                      fill="none" 
                      stroke="url(#talos-login-grad)" 
                      stroke-width="1.25" 
                      stroke-linecap="round" 
                      stroke-linejoin="round" />
            </svg>
        </div>

        <h2 class="text-center text-xl font-extrabold text-gray-800 tracking-wide uppercase mb-1">{{config('app.name')}}</h2>
        <h3 class="text-center text-sm font-bold text-gray-800 mb-1">{{config('app.tagline')}}</h3>

        <!-- Errores de validación -->
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Usuario</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i data-lucide="user" class="w-6 h-6"></i>
                    </span>
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:outline-none focus:ring-1 focus:ring-orange-300 focus:bg-white transition-all text-sm"
                           placeholder="usuario">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Contraseña</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i data-lucide="lock-keyhole" class="w-6 h-6"></i>
                    </span>
                    <input type="password" name="password" required
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:outline-none focus:ring-1 focus:ring-orange-300 focus:bg-white transition-all text-sm"
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold rounded-lg shadow-lg shadow-orange-500/30 transition-all duration-200 text-sm">
                Ingresar
            </button>
        </form>
    </div>
    <div class="font-semibold text-center mt-4 text-xs text-gray-400">
        {{config('app.tagline')}} {{config('app.client')}} {{ date('Y') }} ©
    </div>

    <!-- Inicializar Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>