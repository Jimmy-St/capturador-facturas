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

    <style>
        /* Desactivar selección y rebote en móviles */
        body {
            touch-action: manipulation;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body class="bg-slate-950 text-white h-screen overflow-hidden flex flex-col" x-data="documentScanner()">

    <!-- HEADER FLOTANTE TIPO APP -->
    <header class="absolute top-0 inset-x-0 z-40 bg-gradient-to-b from-black/85 via-black/40 to-transparent p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-xl bg-orange-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
                <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-wider text-white">TALOS</span>
                <span class="text-[10px] text-orange-400 font-semibold uppercase block leading-none">
                    {{ auth()->user()->role ?? 'Scanner' }}
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Botón linterna (Torch) si el dispositivo lo soporta -->
            <button x-show="hasTorch"
                    @click="toggleTorch()" 
                    type="button"
                    class="bg-white/10 hover:bg-white/20 active:scale-95 backdrop-blur-md p-2 rounded-full text-xs font-semibold transition flex items-center justify-center text-amber-300"
                    :class="{ 'bg-amber-400/20 text-amber-400': torchOn }"
                    title="Encender linterna">
                <i data-lucide="flashlight" class="w-4 h-4"></i>
            </button>

            <!-- Acceso a Dashboard para Supervisor/Admin -->
            @auth
                @if(auth()->user()->role !== 'operator')
                    <a href="{{ route('dashboard') }}" class="bg-white/10 hover:bg-white/20 active:scale-95 backdrop-blur-md px-3 py-1.5 rounded-full text-xs font-semibold transition flex items-center space-x-1.5 border border-white/10">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 text-orange-400"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
            @endauth

            <!-- Salir / Logout -->
            <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                @csrf
                <button type="submit" class="bg-white/10 hover:bg-rose-500/20 hover:text-rose-400 active:scale-95 backdrop-blur-md p-2 rounded-full text-xs transition" title="Cerrar sesión">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- ÁREA PRINCIPAL DE CÁMARA (FULLSCREEN) -->
    <main class="relative flex-1 w-full h-full bg-black flex items-center justify-center overflow-hidden">
        
        <!-- Video de la Cámara -->
        <video x-ref="video" 
               autoplay 
               playsinline 
               muted 
               class="absolute inset-0 w-full h-full object-cover">
        </video>

        <!-- Spinner mientras la cámara inicializa -->
        <div x-show="!isCameraReady && !cameraError" 
             class="absolute inset-0 z-20 flex flex-col items-center justify-center space-y-3 bg-black/80 backdrop-blur-xs">
            <div class="w-10 h-10 border-3 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-xs text-gray-300 font-medium">Iniciando cámara trasera...</p>
        </div>

        <!-- LÍNEAS GUÍA FORMATO OFICIO (Proporción exacta 216x330 mm) -->
        <div class="relative w-full h-full flex items-center justify-center p-4 pt-16 pb-28 pointer-events-none z-10">
            <div x-ref="guideBox"
                 class="w-full max-w-[360px] aspect-[216/330] max-h-[74vh] border-2 border-dashed border-white/70 rounded-3xl flex flex-col justify-between p-5 shadow-2xl relative backdrop-contrast-105">
                
                <!-- Esquinas Superiores Naranjas -->
                <div class="flex justify-between">
                    <span class="w-7 h-7 border-t-4 border-l-4 border-orange-500 rounded-tl-xl shadow-sm"></span>
                    <span class="w-7 h-7 border-t-4 border-r-4 border-orange-500 rounded-tr-xl shadow-sm"></span>
                </div>

                <!-- Insignia Central Formato Oficio -->
                <div class="text-center">
                    <span class="bg-black/60 backdrop-blur-md text-orange-400 border border-orange-500/30 text-[10px] font-bold tracking-widest px-3.5 py-1.5 rounded-full uppercase shadow-lg inline-flex items-center space-x-1">
                        <i data-lucide="scan" class="w-3 h-3"></i>
                        <span>Encuadre Formato Oficio</span>
                    </span>
                </div>

                <!-- Esquinas Inferiores Naranjas -->
                <div class="flex justify-between">
                    <span class="w-7 h-7 border-b-4 border-l-4 border-orange-500 rounded-bl-xl shadow-sm"></span>
                    <span class="w-7 h-7 border-b-4 border-r-4 border-orange-500 rounded-br-xl shadow-sm"></span>
                </div>
            </div>
        </div>

        <!-- OVERLAY DE CARGA / LOADING AMIGABLE -->
        <div x-show="isProcessing" 
             x-transition.opacity
             class="absolute inset-0 bg-slate-950/90 backdrop-blur-md z-50 flex flex-col items-center justify-center space-y-6 p-6 text-center"
             style="display: none;">
            
            <div class="relative w-20 h-20 flex items-center justify-center">
                <div class="absolute inset-0 border-4 border-orange-500/20 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                <i data-lucide="sparkles" class="w-8 h-8 text-orange-400 animate-pulse"></i>
            </div>
            
            <div class="space-y-2 max-w-xs">
                <h3 class="text-white font-bold text-lg" x-text="loadingText">Procesando documento...</h3>
                <p class="text-xs text-gray-400">OCR está extrayendo los datos tributarios con alta precisión</p>
            </div>
        </div>
    </main>

    <!-- BARRA INFERIOR CON CONTROLES Y FEEDBACK -->
    <footer class="absolute bottom-0 inset-x-0 z-40 bg-gradient-to-t from-slate-950 via-slate-950/90 to-transparent p-5 flex flex-col items-center">
        
        <!-- Notificación Flotante de Éxito -->
        <div x-show="successMessage" 
             x-transition
             class="mb-3 w-full max-w-sm p-3.5 bg-emerald-500/20 border border-emerald-500/40 rounded-2xl text-emerald-300 text-xs flex items-center space-x-2.5 backdrop-blur-md shadow-xl"
             style="display: none;">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
            <span x-text="successMessage" class="leading-tight"></span>
        </div>

        <!-- Notificación Flotante de Error -->
        <div x-show="errorMessage" 
             x-transition
             class="mb-3 w-full max-w-sm p-3.5 bg-rose-500/20 border border-rose-500/40 rounded-2xl text-rose-300 text-xs flex items-center space-x-2.5 backdrop-blur-md shadow-xl"
             style="display: none;">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-400"></i>
            <span x-text="errorMessage" class="leading-tight"></span>
        </div>

        <!-- Grupo de Botones -->
        <div class="w-full max-w-sm flex items-center space-x-3">
            
            <!-- Botón Alternativo: Subir imagen desde galería/archivo -->
            <label class="p-4 bg-white/10 hover:bg-white/20 active:scale-95 border border-white/10 text-white rounded-2xl cursor-pointer transition flex items-center justify-center shrink-0 shadow-lg"
                   title="Subir imagen desde archivo">
                <input type="file" 
                       accept="image/*" 
                       class="hidden" 
                       @change="handleFileUpload($event)">
                <i data-lucide="image-plus" class="w-5 h-5 text-orange-400"></i>
            </label>

            <!-- Botón Principal de Captura Estilo App Nativa -->
            <button @click="captureAndSend()" 
                    :disabled="!isCameraReady || isProcessing"
                    type="button"
                    class="flex-1 py-4 px-6 bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-extrabold rounded-2xl shadow-xl shadow-orange-500/30 flex items-center justify-center space-x-3 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center">
                    <div class="w-3.5 h-3.5 bg-white rounded-full"></div>
                </div>
                <span class="tracking-wider text-xs sm:text-sm">CAPTURAR DOCUMENTO</span>
            </button>
        </div>
    </footer>

    <!-- SCRIPT DE CÁMARA, GEOMETRÍA DEL CANVAS Y AJAX -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('documentScanner', () => ({
                stream: null,
                isCameraReady: false,
                cameraError: false,
                isProcessing: false,
                loadingText: 'Capturando imagen...',
                errorMessage: '',
                successMessage: '',
                hasTorch: false,
                torchOn: false,

                init() {
                    this.startCamera();
                    this.refreshIcons();
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                },

                async startCamera() {
                    this.cameraError = false;
                    this.isCameraReady = false;

                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { 
                                facingMode: { ideal: 'environment' },
                                width: { ideal: 3840 }, 
                                height: { ideal: 2160 }
                            },
                            audio: false
                        });

                        const video = this.$refs.video;
                        video.srcObject = this.stream;

                        // Verificar soporte de linterna (Torch)
                        const track = this.stream.getVideoTracks()[0];
                        if (track && track.getCapabilities) {
                            const capabilities = track.getCapabilities();
                            this.hasTorch = !!capabilities.torch;
                        }

                        video.onloadedmetadata = () => {
                            video.play().then(() => {
                                this.isCameraReady = true;
                                this.refreshIcons();
                            }).catch(() => {
                                this.isCameraReady = true;
                                this.refreshIcons();
                            });
                        };

                    } catch (err) {
                        this.cameraError = true;
                        this.isCameraReady = false;
                        this.errorMessage = 'No se pudo acceder a la cámara. Puedes subir una foto usando el botón de galería.';
                        console.error('Error al inicializar cámara:', err);
                        this.refreshIcons();
                    }
                },

                async toggleTorch() {
                    if (!this.stream) return;
                    const track = this.stream.getVideoTracks()[0];
                    if (!track) return;

                    try {
                        this.torchOn = !this.torchOn;
                        await track.applyConstraints({
                            advanced: [{ torch: this.torchOn }]
                        });
                    } catch (e) {
                        console.warn('Torch no disponible:', e);
                        this.torchOn = false;
                    }
                },

                async captureAndSend() {
                    if (!this.isCameraReady || this.isProcessing) return;

                    const video = this.$refs.video;
                    const guideBox = this.$refs.guideBox;

                    const videoWidth = video.videoWidth;
                    const videoHeight = video.videoHeight;

                    if (!videoWidth || !videoHeight) {
                        this.errorMessage = 'La cámara aún no transmite fotogramas válidos.';
                        return;
                    }

                    this.isProcessing = true;
                    this.loadingText = 'Optimizando encuadre oficio...';
                    this.errorMessage = '';
                    this.successMessage = '';

                    // 1. Obtener geometrías de pantalla
                    const videoRect = video.getBoundingClientRect();
                    const guideRect = guideBox.getBoundingClientRect();

                    const clientWidth = videoRect.width;
                    const clientHeight = videoRect.height;

                    // 2. Calcular escala de 'object-cover' (escala uniforme para llenar el contenedor)
                    const scale = Math.max(clientWidth / videoWidth, clientHeight / videoHeight);
                    const renderedWidth = videoWidth * scale;
                    const renderedHeight = videoHeight * scale;

                    // 3. Desplazamiento por centrado (object-position: center center por defecto)
                    const offsetX = (clientWidth - renderedWidth) / 2;
                    const offsetY = (clientHeight - renderedHeight) / 2;

                    // 4. Posición del marco guía respecto a los píxeles renderizados
                    const boxX = guideRect.left - videoRect.left;
                    const boxY = guideRect.top - videoRect.top;

                    // 5. Mapeo a las coordenadas nativas del sensor
                    let cropX = (boxX - offsetX) / scale;
                    let cropY = (boxY - offsetY) / scale;
                    let cropWidth = guideRect.width / scale;
                    let cropHeight = guideRect.height / scale;

                    // 6. Clamp seguro dentro de los límites del fotograma
                    cropX = Math.max(0, Math.min(cropX, videoWidth - 10));
                    cropY = Math.max(0, Math.min(cropY, videoHeight - 10));
                    cropWidth = Math.min(cropWidth, videoWidth - cropX);
                    cropHeight = Math.min(cropHeight, videoHeight - cropY);

                    // 7. Renderizar en canvas nativo con la resolución real del recorte
                    const croppedCanvas = document.createElement('canvas');
                    croppedCanvas.width = Math.round(cropWidth);
                    croppedCanvas.height = Math.round(cropHeight);
                    const croppedCtx = croppedCanvas.getContext('2d');

                    croppedCtx.drawImage(
                        video, 
                        cropX, cropY, cropWidth, cropHeight, 
                        0, 0, croppedCanvas.width, croppedCanvas.height
                    );

                    this.loadingText = 'Enviando a Lector OCR...';

                    croppedCanvas.toBlob(async (blob) => {
                        if (!blob) {
                            this.isProcessing = false;
                            this.errorMessage = 'Error al generar la imagen del encuadre.';
                            return;
                        }

                        const formData = new FormData();
                        formData.append('image', blob, 'invoice_scan.jpg');

                        await this.sendFormData(formData);
                    }, 'image/jpeg', 0.95);
                },

                async handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.isProcessing = true;
                    this.loadingText = 'Enviando imagen a Lector OCR...';
                    this.errorMessage = '';
                    this.successMessage = '';

                    const formData = new FormData();
                    formData.append('image', file);

                    await this.sendFormData(formData);
                    // Limpiar el input para permitir seleccionar la misma imagen de nuevo
                    event.target.value = '';
                },

                async sendFormData(formData) {
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

                        if (response.ok && result.success) {
                            if (result.is_operator) {
                                this.loadingText = '¡Factura #' + (result.folio || '') + ' registrada!';
                                this.successMessage = 'Factura #' + (result.folio || '') + ' guardada exitosamente. Cámara lista para la siguiente captura.';
                                setTimeout(() => {
                                    this.isProcessing = false;
                                    this.refreshIcons();
                                    setTimeout(() => {
                                        this.successMessage = '';
                                    }, 4500);
                                }, 1200);
                            } else {
                                this.loadingText = '¡Lectura exitosa! Redirigiendo a auditoría...';
                                if (this.stream) {
                                    this.stream.getTracks().forEach(track => track.stop());
                                }
                                window.location.href = `/invoices/${result.invoice_id}`;
                            }
                        } else {
                            this.isProcessing = false;
                            this.errorMessage = result.error || 'Ocurrió un error al procesar el documento con OCR.';
                            this.refreshIcons();
                        }

                    } catch (err) {
                        this.isProcessing = false;
                        this.errorMessage = 'Error de red al comunicarse con el servidor.';
                        console.error('Error al procesar factura:', err);
                        this.refreshIcons();
                    }
                }
            }));
        });
    </script>
</body>
</html>