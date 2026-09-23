<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Escanear Documento - {{ config('app.name') }}</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-950 text-white h-screen overflow-hidden flex flex-col" x-data="documentScanner()">

    <!-- HEADER FLOTANTE TIPO APP -->
    <header class="absolute top-0 inset-x-0 z-40 bg-gradient-to-b from-black/80 to-transparent p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-xl bg-orange-500 flex items-center justify-center shadow-md">
                <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
            </div>
            <span class="font-bold text-sm tracking-wide">TALOS</span>
        </div>

        <!-- Si es supervisor o admin, mostramos un acceso rápido para volver al dashboard -->
        @auth
            @if(auth()->user()->role !== 'operator')
                <a href="{{ route('dashboard') }}" class="bg-white/10 hover:bg-white/20 backdrop-blur-md px-3 py-1.5 rounded-full text-xs font-semibold transition flex items-center space-x-1.5">
                    <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 text-orange-400"></i>
                    <span>Dashboard</span>
                </a>
            @endif
        @endauth
    </header>

    <!-- ÁREA PRINCIPAL DE CÁMARA (FULLSCREEN) -->
    <main class="relative flex-1 w-full h-full bg-black flex items-center justify-center overflow-hidden">
        
        <!-- Video de la Cámara -->
        <video x-ref="video" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"></video>

        <!-- Canvas Oculto para el recorte nativo en alta calidad -->
        <canvas x-ref="canvas" class="hidden"></canvas>

        <!-- LÍNEAS GUÍA FORMATO OFICIO -->
        <div class="absolute inset-x-4 top-20 bottom-28 border-2 border-dashed border-white/60 rounded-3xl pointer-events-none flex flex-col justify-between p-6 z-10 shadow-2xl">
            <div class="flex justify-between">
                <span class="w-6 h-6 border-t-4 border-l-4 border-orange-500 rounded-tl-lg"></span>
                <span class="w-6 h-6 border-t-4 border-r-4 border-orange-500 rounded-tr-lg"></span>
            </div>
            <div class="text-center">
                <span class="bg-black/60 backdrop-blur-md text-orange-400 border border-orange-500/30 text-[11px] font-bold tracking-widest px-4 py-1.5 rounded-full uppercase shadow-lg">
                    Encuadre Formato Oficio
                </span>
            </div>
            <div class="flex justify-between">
                <span class="w-6 h-6 border-b-4 border-l-4 border-orange-500 rounded-bl-lg"></span>
                <span class="w-6 h-6 border-b-4 border-r-4 border-orange-500 rounded-br-lg"></span>
            </div>
        </div>

        <!-- OVERLAY DE CARGA / LOADING AMIGABLE -->
        <div x-show="isProcessing" 
             x-transition.opacity
             class="absolute inset-0 bg-slate-950/90 backdrop-blur-md z-50 flex flex-col items-center justify-center space-y-6 p-6 text-center"
             style="display: none;">
            
            <div class="relative w-16 h-16 flex items-center justify-center">
                <div class="absolute inset-0 border-4 border-orange-500/20 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                <i data-lucide="sparkles" class="w-6 h-6 text-orange-400 animate-pulse"></i>
            </div>
            
            <div class="space-y-2 max-w-xs">
                <h3 class="text-white font-bold text-lg" x-text="loadingText">Procesando documento...</h3>
                <p class="text-xs text-gray-400">Gemini IA está extrayendo los datos tributarios con alta precisión</p>
            </div>
        </div>
    </main>

    <!-- BARRA INFERIOR CON BOTÓN DE CAPTURA -->
    <footer class="absolute bottom-0 inset-x-0 z-40 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent p-6 flex flex-col items-center">
        
        <!-- Mensaje de error si la cámara falla -->
        <div x-show="errorMessage" 
             class="mb-4 w-full max-w-sm p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-300 text-xs flex items-center space-x-2 backdrop-blur-md"
             style="display: none;">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-400"></i>
            <span x-text="errorMessage"></span>
        </div>

        <!-- Botón de Captura Estilo App -->
        <button @click="captureAndSend()" 
                :disabled="isProcessing"
                class="w-full max-w-sm py-4 px-6 bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-bold rounded-2xl shadow-xl shadow-orange-500/30 flex items-center justify-center space-x-3 transition-all cursor-pointer disabled:opacity-50">
            <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center">
                <div class="w-3 h-3 bg-white rounded-full"></div>
            </div>
            <span class="tracking-wider text-sm">CAPTURAR DOCUMENTO</span>
        </button>
    </footer>

    <!-- SCRIPT DE CÁMARA Y RECÓRTE NATIVO -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('documentScanner', () => ({
                stream: null,
                isProcessing: false,
                loadingText: 'Capturando imagen...',
                errorMessage: '',

                init() {
                    this.startCamera();
                    // Inicializar iconos de Lucide
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                },

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { 
                                facingMode: { ideal: 'environment' },
                                width: { ideal: 3840 }, 
                                height: { ideal: 2160 }
                            },
                            audio: false
                        });
                        this.$refs.video.srcObject = this.stream;
                    } catch (err) {
                        this.errorMessage = 'No se pudo acceder a la cámara. Revisa los permisos de tu navegador.';
                        console.error(err);
                    }
                },

                async captureAndSend() {
                    this.isProcessing = true;
                    this.loadingText = 'Optimizando encuadre...';
                    this.errorMessage = '';

                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    const context = canvas.getContext('2d');

                    const videoWidth = video.videoWidth;
                    const videoHeight = video.videoHeight;

                    canvas.width = videoWidth;
                    canvas.height = videoHeight;
                    context.drawImage(video, 0, 0, videoWidth, videoHeight);

                    // Recorte exacto adaptado al marco guía
                    const cropX = videoWidth * 0.08;
                    const cropY = videoHeight * 0.12;
                    const cropWidth = videoWidth * 0.84;
                    const cropHeight = videoHeight * 0.76;

                    const croppedCanvas = document.createElement('canvas');
                    croppedCanvas.width = cropWidth;
                    croppedCanvas.height = cropHeight;
                    const croppedCtx = croppedCanvas.getContext('2d');

                    croppedCtx.drawImage(
                        canvas, 
                        cropX, cropY, cropWidth, cropHeight, 
                        0, 0, cropWidth, cropHeight
                    );

                    this.loadingText = 'Enviando a Gemini IA...';

                    croppedCanvas.toBlob(async (blob) => {
                        const formData = new FormData();
                        // Almacenamiento con nombre UUID en el backend (manejado por tu controlador)
                        formData.append('image', blob, 'invoice_scan.jpg');

                        try {
                            const response = await fetch("{{ route('invoices.process') }}", {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            const result = await response.json();

                            if (result.success) {
                                this.loadingText = '¡Lectura exitosa! Redirigiendo...';
                                if (this.stream) {
                                    this.stream.getTracks().forEach(track => track.stop());
                                }
                                // Redirige al detalle de la factura creada con nombre UUID
                                window.location.href = `/invoices/${result.invoice_id}`;
                            } else {
                                this.isProcessing = false;
                                this.errorMessage = result.error || 'Ocurrió un error al procesar el documento con Gemini.';
                            }

                        } catch (err) {
                            this.isProcessing = false;
                            this.errorMessage = 'Error de red al comunicarse con el servidor.';
                            console.error(err);
                        }
                    }, 'image/jpeg', 0.95);
                }
            }));
        });
    </script>
</body>
</html>