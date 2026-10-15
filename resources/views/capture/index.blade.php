<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Escanear Documento - {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
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
<body class="bg-slate-950 text-white h-screen overflow-hidden flex flex-col" 
      x-data="documentScanner({
          targetWidth: {{ config('invoices.target_width', 760) }},
          maxHeight: {{ config('invoices.max_height', 1530) }},
          clientQuality: {{ config('invoices.client_quality', 0.85) }}
      })">

    <!-- HEADER FLOTANTE TIPO APP -->
    <header class="absolute top-0 inset-x-0 z-40 bg-gradient-to-b from-black/80 via-black/30 to-transparent pt-2.5 pb-2 px-3.5 flex items-center justify-between pointer-events-auto">
        <div class="flex items-center space-x-2">
            <!-- Logo Flotante sin marco con degradé naranja del login -->
            <svg class="w-9 h-9 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 2px 8px rgba(249, 115, 22, 0.45));">
                <defs>
                    <linearGradient id="talos-scan-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f97316" />
                        <stop offset="100%" stop-color="#f59e0b" />
                    </linearGradient>
                </defs>
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" 
                      stroke="url(#talos-scan-grad)" 
                      stroke-width="1.5" 
                      stroke-linecap="round" 
                      stroke-linejoin="round" />
                <path d="m9 12 2 2 4-4" 
                      fill="none" 
                      stroke="url(#talos-scan-grad)" 
                      stroke-width="1.8" 
                      stroke-linecap="round" 
                      stroke-linejoin="round" />
            </svg>
            <div class="flex flex-col">
                <span class="font-black text-sm tracking-wider text-white leading-none">TALOS</span>
                <span class="text-[9px] text-orange-400 font-extrabold uppercase tracking-widest mt-0.5 leading-none">
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

            <!-- Acceso a Dashboard para Supervisor/Admin (Oculto para Operator) -->
            @auth
                @if(auth()->user()->role !== 'operator')
                    <a href="{{ route('dashboard') }}" class="bg-white/10 hover:bg-white/20 active:scale-95 backdrop-blur-md px-3 py-1.5 rounded-full text-xs font-semibold transition flex items-center space-x-1.5 border border-white/10 text-white">
                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 text-orange-400"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
            @endauth

            <!-- Salir / Logout -->
            <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                @csrf
                <button type="submit" class="bg-white/10 hover:bg-rose-500/20 hover:text-rose-400 active:scale-95 backdrop-blur-md p-2 rounded-full text-xs transition text-gray-300" title="Cerrar sesión">
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

        <!-- LÍNEAS GUÍA MINIMALISTAS A ANCHO COMPLETO (Carta y Oficio) -->
        <div class="absolute inset-0 pointer-events-none z-20 overflow-hidden">
            <!-- 1. Línea Superior: INICIO -->
            <div class="absolute inset-x-0 flex items-center transition-all duration-300" 
                 :style="'top: ' + topY + 'px;'">
                <div class="w-full border-t border-dashed border-orange-500/80 drop-shadow-[0_1px_3px_rgba(0,0,0,0.85)]"></div>
                <span class="absolute right-3 -top-2.5 px-2 py-0.5 rounded bg-black/75 backdrop-blur-md border border-orange-500/40 text-[9px] font-extrabold text-orange-400 tracking-wider uppercase shadow-lg flex items-center space-x-0.5">
                    <span>INICIO</span>
                    <i data-lucide="chevron-down" class="w-2.5 h-2.5 inline"></i>
                </span>
            </div>

            <!-- 2. Línea Intermedia: FORMATO CARTA (Proporción 1 : 1.294) -->
            <div class="absolute inset-x-0 flex items-center transition-all duration-300" 
                 :style="'top: ' + cartaY + 'px;'">
                <div class="w-full border-t border-dashed border-orange-500/70 drop-shadow-[0_1px_3px_rgba(0,0,0,0.85)]"></div>
                <span class="absolute right-3 -top-2.5 px-2 py-0.5 rounded bg-black/75 backdrop-blur-md border border-orange-500/40 text-[9px] font-extrabold text-orange-400 tracking-wider uppercase shadow-lg">
                    CARTA
                </span>
            </div>

            <!-- 3. Línea Inferior: FORMATO OFICIO (Proporción 1 : 1.529) -->
            <div class="absolute inset-x-0 flex items-center transition-all duration-300" 
                 :style="'top: ' + oficioY + 'px;'">
                <div class="w-full border-t border-dashed border-orange-500/90 drop-shadow-[0_1px_3px_rgba(0,0,0,0.85)]"></div>
                <span class="absolute right-3 -top-2.5 px-2 py-0.5 rounded bg-black/75 backdrop-blur-md border border-orange-500/40 text-[9px] font-extrabold text-orange-400 tracking-wider uppercase shadow-lg">
                    OFICIO
                </span>
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

    <!-- BARRA INFERIOR CON CONTROLES (ESTILO CÁMARA NATIVA) -->
    <footer class="absolute bottom-0 inset-x-0 z-40 bg-gradient-to-t from-black/90 via-black/40 to-transparent pt-3 pb-5 px-4 flex flex-col items-center pointer-events-auto">
        
        <!-- Notificación Flotante de Éxito -->
        <div x-show="successMessage" 
             x-transition
             class="mb-3 w-full max-w-sm p-3 bg-emerald-500/20 border border-emerald-500/40 rounded-2xl text-emerald-300 text-xs flex items-center space-x-2.5 backdrop-blur-md shadow-xl"
             style="display: none;">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-400"></i>
            <span x-text="successMessage" class="leading-tight font-medium"></span>
        </div>

        <!-- Notificación Flotante de Error -->
        <div x-show="errorMessage" 
             x-transition
             class="mb-3 w-full max-w-sm p-3 bg-rose-500/20 border border-rose-500/40 rounded-2xl text-rose-300 text-xs flex items-center space-x-2.5 backdrop-blur-md shadow-xl"
             style="display: none;">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-400"></i>
            <span x-text="errorMessage" class="leading-tight font-medium"></span>
        </div>

        <!-- Grupo de Botones -->
        <div class="w-full max-w-sm flex items-center space-x-3">
            <!-- Botón Alternativo: Subir imagen desde galería/archivo -->
            <label class="w-12 h-12 bg-white/10 hover:bg-white/20 active:scale-95 border border-white/10 text-white rounded-2xl cursor-pointer transition flex items-center justify-center shrink-0 shadow-lg backdrop-blur-md"
                   title="Subir imagen desde galería">
                <input type="file" 
                       accept="image/*" 
                       class="hidden" 
                       @change="handleFileUpload($event)">
                <i data-lucide="image-plus" class="w-5 h-5 text-orange-400"></i>
            </label>

            <!-- Botón Principal de Captura Estilizado Ergonómico -->
            <button @click="captureAndSend()" 
                    :disabled="!isCameraReady || isProcessing"
                    type="button"
                    class="flex-1 py-3 px-5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 active:scale-95 text-white font-black rounded-2xl shadow-xl shadow-orange-500/25 flex items-center justify-center space-x-2.5 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <div class="w-5 h-5 rounded-full border-2 border-white flex items-center justify-center">
                    <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                </div>
                <span class="tracking-wider text-xs sm:text-sm uppercase font-extrabold">Capturar Documento</span>
            </button>
        </div>
    </footer>

    <!-- CÁMARA, CANVAS Y AJAX -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('documentScanner', (config = {}) => ({
                targetWidth: (config && config.targetWidth) || 760,
                maxHeight: (config && config.maxHeight) || 1530,
                clientQuality: (config && config.clientQuality) || 0.85,
                stream: null,
                isCameraReady: false,
                cameraError: false,
                isProcessing: false,
                loadingText: 'Capturando imagen...',
                errorMessage: '',
                successMessage: '',
                hasTorch: false,
                torchOn: false,

                // Geometría y posicionamiento de guías en pantalla
                topY: 56,
                screenWidth: window.innerWidth,
                screenHeight: window.innerHeight,

                get guideWidth() {
                    return Math.min(this.screenWidth, 440);
                },
                get guideLeft() {
                    return Math.round((this.screenWidth - this.guideWidth) / 2);
                },
                get cartaHeight() {
                    return Math.round(this.guideWidth * 1.294);
                },
                get oficioHeight() {
                    return Math.round(this.guideWidth * 1.529);
                },
                get cartaY() {
                    return this.topY + this.cartaHeight;
                },
                get oficioY() {
                    return this.topY + this.oficioHeight;
                },

                init() {
                    this.startCamera();
                    this.refreshIcons();
                    window.addEventListener('resize', () => {
                        this.screenWidth = window.innerWidth;
                        this.screenHeight = window.innerHeight;
                    });
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

                    const videoWidth = video.videoWidth;
                    const videoHeight = video.videoHeight;

                    if (!videoWidth || !videoHeight) {
                        this.errorMessage = 'La cámara aún no transmite fotogramas válidos.';
                        return;
                    }

                    this.isProcessing = true;
                    this.loadingText = 'Optimizando encuadre...';
                    this.errorMessage = '';
                    this.successMessage = '';

                    // Obtener geometría de pantalla y video
                    const videoRect = video.getBoundingClientRect();

                    // Calcular escala de 'object-cover' (escala uniforme para llenar el contenedor)
                    const scale = Math.max(videoRect.width / videoWidth, videoRect.height / videoHeight);
                    const renderedWidth = videoWidth * scale;
                    const renderedHeight = videoHeight * scale;

                    // Desplazamiento por centrado (object-position: center center por defecto)
                    const offsetX = (videoRect.width - renderedWidth) / 2;
                    const offsetY = (videoRect.height - renderedHeight) / 2;

                    // Límites exactos del encuadre natural: ancho de pantalla y desde topY hasta oficioY
                    const cropBoxX = this.guideLeft;
                    const cropBoxY = this.topY;
                    const cropBoxW = this.guideWidth;
                    const cropBoxH = this.oficioHeight;

                    // Mapeo a las coordenadas nativas del sensor
                    let cropX = (cropBoxX - offsetX) / scale;
                    let cropY = (cropBoxY - offsetY) / scale;
                    let cropWidth = cropBoxW / scale;
                    let cropHeight = cropBoxH / scale;

                    // Clamp seguro dentro de los límites del fotograma
                    cropX = Math.max(0, Math.min(cropX, videoWidth - 10));
                    cropY = Math.max(0, Math.min(cropY, videoHeight - 10));
                    cropWidth = Math.min(cropWidth, videoWidth - cropX);
                    cropHeight = Math.min(cropHeight, videoHeight - cropY);

                    // Escalado óptimo por targetWidth y maxHeight (.env)
                    const targetWidth = this.targetWidth || 760;
                    const maxHeight = this.maxHeight || 1530;
                    let targetHeight = Math.round((cropHeight * targetWidth) / cropWidth);
                    if (targetHeight > maxHeight) {
                        targetHeight = maxHeight;
                    }

                    // Renderizar en canvas optimizado
                    const croppedCanvas = document.createElement('canvas');
                    croppedCanvas.width = targetWidth;
                    croppedCanvas.height = targetHeight;
                    const croppedCtx = croppedCanvas.getContext('2d');

                    croppedCtx.drawImage(
                        video, 
                        cropX, cropY, cropWidth, cropHeight, 
                        0, 0, targetWidth, targetHeight
                    );

                    this.loadingText = 'Analizando documento...';

                    croppedCanvas.toBlob(async (blob) => {
                        if (!blob) {
                            this.isProcessing = false;
                            this.errorMessage = 'Error al generar la imagen del encuadre.';
                            return;
                        }

                        const formData = new FormData();
                        formData.append('image', blob, 'invoice_scan.jpg');

                        await this.sendFormData(formData);
                    }, 'image/jpeg', this.clientQuality);
                },

                async handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.isProcessing = true;
                    this.loadingText = 'Optimizando foto de galería...';
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        // Pre-escalado local en el navegador antes de subir fotos de galería
                        const optimizedBlob = await this.downscaleImageFile(file, this.targetWidth, this.clientQuality);
                        this.loadingText = 'Analizando documento...';

                        const formData = new FormData();
                        formData.append('image', optimizedBlob, 'gallery_invoice.jpg');

                        await this.sendFormData(formData);
                    } catch (e) {
                        console.error('Error pre-escalando imagen de galería:', e);
                        // Fallback al archivo original
                        const formData = new FormData();
                        formData.append('image', file);
                        await this.sendFormData(formData);
                    } finally {
                        event.target.value = '';
                    }
                },

                downscaleImageFile(file, targetW, quality) {
                    return new Promise((resolve) => {
                        const img = new Image();
                        img.onload = () => {
                            let w = img.width;
                            let h = img.height;
                            let newW = targetW || 760;
                            let newH = Math.round((h * newW) / w);
                            const maxH = this.maxHeight || 1530;
                            if (newH > maxH) {
                                newH = maxH;
                                newW = Math.round((w * maxH) / h);
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = newW;
                            canvas.height = newH;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, newW, newH);

                            canvas.toBlob((blob) => {
                                resolve(blob || file);
                            }, 'image/jpeg', quality);
                        };
                        img.onerror = () => resolve(file);
                        img.src = URL.createObjectURL(file);
                    });
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
                            this.loadingText = '¡Documento recibido!';
                            this.successMessage = '¡Documento enviado a procesamiento! Cámara lista para el siguiente documento.';
                            setTimeout(() => {
                                this.isProcessing = false;
                                this.refreshIcons();
                                setTimeout(() => {
                                    this.successMessage = '';
                                }, 3500);
                            }, 800);
                        } else {
                            this.isProcessing = false;
                            this.errorMessage = result.error || 'Ocurrió un error al procesar el documento con OCR.';
                            this.refreshIcons();
                        }

                    } catch (err) {
                        this.isProcessing = false;
                        this.errorMessage = 'Error de conexión con el servidor. Por favor, reintenta.';
                        console.error('Error al procesar factura:', err);
                        this.refreshIcons();
                    }
                }
            }));
        });
    </script>
</body>
</html>