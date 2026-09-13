<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso - Sistema de Facturas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex flex-col items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-xl shadow-xl w-80 max-w-md border border-gray-100">
        
        <!-- Icono superior con tono anaranjado -->
        <div class="flex justify-center mb-1">
            <i data-lucide="shield-check" class="w-24 h-24 text-orange-500 stroke-1"></i>
        </div>

        <h2 class="text-center text-xl font-extrabold text-gray-800 tracking-wide uppercase mb-1">
            Talos
        </h2>
        <h3 class="text-center text-sm font-bold text-gray-800 mb-1">Observador de Facturas</h3>

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
        © Gestión de Facturas Pfau {{ date('Y') }}
    </div>

    <!-- Inicializar Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>