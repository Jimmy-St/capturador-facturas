<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Scan</title>
    
    <!-- Enlace al Manifest de la PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f97316">

    <!-- Tailwind CSS (Asegúrate de incluir tu mix o Vite) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js (Requerido para la lógica del botón de instalación) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 h-screen flex flex-col justify-between" x-data="pwaCapture()">

    <!-- Header simple -->
    <header class="bg-white shadow-sm p-4 flex justify-between items-center">
        <div class="flex items-center space-x-2">
            <i data-lucide="shield-check" class="w-6 h-6 text-orange-500"></i>
            <span class="font-bold text-gray-800">{{ config('app.name') }}</span>
        </div>
        <span class="text-xs bg-orange-100 text-orange-700 font-semibold px-2 py-1 rounded">Capturador</span>
    </header>

    <!-- Contenido Principal -->
    <main class="p-4 flex-1 flex flex-col justify-center items-center text-center max-w-md mx-auto w-full">
        
        <!-- Botón de Instalar PWA (Se muestra solo si el navegador permite instalar) -->
        <div x-show="installPrompt" class="mb-6 w-full" x-cloak>
            <button @click="installApp" class="w-full bg-slate-900 text-white font-bold py-3 px-4 rounded-xl shadow-lg flex items-center justify-center space-x-2 hover:bg-slate-800 transition">
                <i data-lucide="download" class="w-5 h-5"></i>
                <span>Instalar Aplicación</span>
            </button>
            <p class="text-xs text-gray-500 mt-2">Instala TALOS en tu celular para un acceso rápido.</p>
        </div>

        <!-- Sección de Cámara / Captura -->
        <div class="bg-white p-6 rounded-2xl shadow-md w-full">
            <h2 class="text-lg font-bold text-gray-800 mb-2">Escanear Documento</h2>
            <p class="text-sm text-gray-500 mb-6">Toma una foto clara de la factura o selecciona un archivo.</p>

            <!-- Botón simulado de cámara que luego conectaremos -->
            <button @click="requestCamera" class="w-full bg-orange-500 text-white font-bold py-4 px-6 rounded-xl shadow-lg flex items-center justify-center space-x-2 hover:bg-orange-600 transition">
                <i data-lucide="camera" class="w-6 h-6"></i>
                <span>Abrir Cámara</span>
            </button>
        </div>
    </main>

    <!-- Footer -->
    <footer class="p-4 text-center text-xs text-gray-400">
        {{ config('app.client') }} &copy; {{ date('Y') }}
    </footer>

    <!-- Script para registrar PWA y capturar evento de instalación -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pwaCapture', () => ({
                installPrompt: null,
                init() {
                    // Registrar Service Worker
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('/sw.js')
                            .then(() => console.log('Service Worker registrado con éxito'))
                            .catch((err) => console.log('Error en Service Worker', err));
                    }

                    // Escuchar evento de instalación PWA
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.installPrompt = e;
                    });
                },
                installApp() {
                    if (!this.installPrompt) return;
                    this.installPrompt.prompt();
                    this.installPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('Usuario aceptó instalar la PWA');
                        }
                        this.installPrompt = null;
                    });
                },
                requestCamera() {
                    // Aquí manejaremos el acceso a la cámara o input file nativo
                    alert('Próximamente: Activando cámara del dispositivo...');
                }
            }))
        });
    </script>
    
    <!-- Lucide Icons (si usas script CDN o tu compilación) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>