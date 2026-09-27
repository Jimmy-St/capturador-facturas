<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laboratorio de Captura (Demo 1080p) - {{ config('app.name') }}</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            touch-action: manipulation;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body class="bg-slate-950 text-white h-screen overflow-hidden flex flex-col" 
      x-data="demoScanner({
          maxDimension: 1280,
          clientQuality: 0.72
      })">

    <!-- HEADER FLOTANTE TIPO APP -->
    <header class="absolute top-0 inset-x-0 z-40 bg-gradient-to-b from-black/85 via-black/40 to-transparent p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                <i data-lucide="flask-conical" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-wider text-white">TALOS LAB</span>
                <span class="text-[10px] text-emerald-400 font-semibold uppercase block leading-none">
                    Test 1080p &bull; Local
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Botón linterna (Torch) -->
            <button x-show="hasTorch"
                    @click="toggleTorch()" 
                    type="button"
                    class="bg-white/10 hover:bg-white/20 active:scale-95 backdrop-blur-md p-2 rounded-full text-xs font-semibold transition flex items-center justify-center text-amber-300"
                    :class="{ 'bg-amber-400/20 text-amber-400': torchOn }"
                    title="Encender linterna">
                <i data-lucide="flashlight" class="w-4 h-4"></i>
            </button>

            <!-- Acceso a Scan Oficial -->
            <a href="{{ route('capture.index') }}" class="bg-white/10 hover:bg-white/20 active:scale-95 backdrop-blur-md px-3 py-1.5 rounded-full text-xs font-semibold transition flex items-center space-x-1.5 border border-white/10">
                <i data-lucide="scan" class="w-3.5 h-3.5 text-orange-400"></i>
                <span>Scan Oficial</span>
            </a>

            <!-- Salir -->
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
            <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-xs text-gray-300 font-medium">Iniciando cámara en 1080p Full HD...</p>
        </div>

        <!-- LÍNEAS GUÍA FORMATO OFICIO -->
        <div class="relative w-full h-full flex items-center justify-center p-4 pt-16 pb-28 pointer-events-none z-10">
            <div x-ref="guideBox"
                 class="w-full max-w-[360px] aspect-[216/330] max-h-[74vh] border-2 border-dashed border-emerald-400/70 rounded-3xl flex flex-col justify-between p-5 shadow-2xl relative backdrop-contrast-105">
                
                <!-- Esquinas Superiores Esmeralda -->
                <div class="flex justify-between">
                    <span class="w-7 h-7 border-t-4 border-l-4 border-emerald-400 rounded-tl-xl shadow-sm"></span>
                    <span class="w-7 h-7 border-t-4 border-r-4 border-emerald-400 rounded-tr-xl shadow-sm"></span>
                </div>

                <!-- Insignia Central -->
                <div class="text-center">
                    <span class="bg-black/70 backdrop-blur-md text-emerald-300 border border-emerald-400/40 text-[10px] font-bold tracking-widest px-3.5 py-1.5 rounded-full uppercase shadow-lg inline-flex items-center space-x-1.5">
                        <i data-lucide="zap" class="w-3 h-3 text-emerald-400"></i>
                        <span>Encuadre Oficio (1080p Test)</span>
                    </span>
                </div>

                <!-- Esquinas Inferiores Esmeralda -->
                <div class="flex justify-between">
                    <span class="w-7 h-7 border-b-4 border-l-4 border-emerald-400 rounded-bl-xl shadow-sm"></span>
                    <span class="w-7 h-7 border-b-4 border-r-4 border-emerald-400 rounded-br-xl shadow-sm"></span>
                </div>
            </div>
        </div>
    </main>

    <!-- BARRA INFERIOR CON DISPARADOR -->
    <footer class="absolute bottom-0 inset-x-0 z-40 bg-gradient-to-t from-slate-950 via-slate-950/90 to-transparent p-5 flex flex-col items-center">
        
        <!-- Error si falla la cámara -->
        <div x-show="errorMessage" 
             x-transition
             class="mb-3 w-full max-w-sm p-3.5 bg-rose-500/20 border border-rose-500/40 rounded-2xl text-rose-300 text-xs flex items-center space-x-2.5 backdrop-blur-md shadow-xl"
             style="display: none;">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-400"></i>
            <span x-text="errorMessage" class="leading-tight"></span>
        </div>

        <div class="w-full max-w-sm flex items-center justify-center">
            <!-- Botón Principal de Captura Demo -->
            <button @click="captureAndSaveLocal()" 
                    :disabled="!isCameraReady || isProcessing"
                    type="button"
                    class="w-full py-4 px-6 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-extrabold rounded-2xl shadow-xl shadow-emerald-500/30 flex items-center justify-center space-x-3 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center">
                    <div class="w-3.5 h-3.5 bg-white rounded-full"></div>
                </div>
                <span class="tracking-wider text-xs sm:text-sm">DISPARAR Y GUARDAR EN MÓVIL</span>
            </button>
        </div>
    </footer>

    <!-- MODAL DE RESULTADOS Y TELEMETRÍA LOCAL -->
    <div x-show="showResultModal" 
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4"
         style="display: none;">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md p-5 flex flex-col space-y-4 shadow-2xl">
            
            <!-- Cabecera de Telemetría -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-white">Captura Local Exitosa</h3>
                        <p class="text-[10px] text-gray-400">Guardada en la memoria del dispositivo</p>
                    </div>
                </div>
                <button @click="closeResultModal()" class="text-gray-400 hover:text-white p-1 rounded-md cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Métricas en Tarjetas -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] font-bold text-gray-400 block uppercase">Tiempo</span>
                    <span class="text-base font-black text-emerald-400" x-text="metrics.processingTimeMs + ' ms'"></span>
                </div>
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] font-bold text-gray-400 block uppercase">Peso JPG</span>
                    <span class="text-base font-black text-sky-400" x-text="metrics.fileSizeKb + ' KB'"></span>
                </div>
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] font-bold text-gray-400 block uppercase">Resolución</span>
                    <span class="text-[11px] font-black text-amber-400 block mt-1" x-text="metrics.dimensions"></span>
                </div>
            </div>

            <!-- Previsualización de la Foto Resultante -->
            <div class="bg-black rounded-2xl overflow-hidden border border-slate-800 flex items-center justify-center max-h-[38vh] relative group">
                <img :src="capturedImageUrl" alt="Captura local" class="max-h-[38vh] w-auto object-contain">
                <div class="absolute bottom-2 inset-x-2 bg-black/60 backdrop-blur-xs text-[10px] text-center text-gray-300 py-1 rounded-lg">
                    Revisa con tus dedos la nitidez de letras y números
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="space-y-2 pt-1">
                <a :href="capturedImageUrl" 
                   :download="capturedFileName"
                   class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold rounded-xl text-xs flex items-center justify-center space-x-2 transition shadow-lg shadow-emerald-600/20">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Volver a descargar imagen</span>
                </a>

                <button @click="closeResultModal()" 
                        type="button"
                        class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-gray-200 font-bold rounded-xl text-xs flex items-center justify-center space-x-2 transition">
                    <i data-lucide="camera" class="w-4 h-4"></i>
                    <span>Disparar otra captura</span>
                </button>
            </div>

        </div>
    </div>

    <!-- SCRIPT ALPINE.JS 100% LOCAL -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('demoScanner', (config = {}) => ({
                maxDimension: config.maxDimension || 1280,
                clientQuality: config.clientQuality || 0.72,
                stream: null,
                isCameraReady: false,
                cameraError: false,
                isProcessing: false,
                errorMessage: '',
                hasTorch: false,
                torchOn: false,

                // Telemetría
                showResultModal: false,
                capturedImageUrl: '',
                capturedFileName: '',
                metrics: {
                    processingTimeMs: 0,
                    fileSizeKb: 0,
                    dimensions: ''
                },

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
                        // 1080p Full HD en lugar de 4K
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { 
                                facingMode: { ideal: 'environment' },
                                width: { ideal: 1920 }, 
                                height: { ideal: 1080 }
                            },
                            audio: false
                        });

                        const video = this.$refs.video;
                        video.srcObject = this.stream;

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
                        this.errorMessage = 'No se pudo acceder a la cámara en 1080p. Asegúrate de dar permisos.';
                        console.error('Error al inicializar cámara demo:', err);
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

                captureAndSaveLocal() {
                    if (!this.isCameraReady || this.isProcessing) return;

                    const startTime = performance.now();
                    const video = this.$refs.video;
                    const guideBox = this.$refs.guideBox;

                    const videoWidth = video.videoWidth;
                    const videoHeight = video.videoHeight;

                    if (!videoWidth || !videoHeight) {
                        this.errorMessage = 'La cámara aún no transmite fotogramas válidos.';
                        return;
                    }

                    this.isProcessing = true;

                    // Geometría para recorte exacto de la guía
                    const videoRect = video.getBoundingClientRect();
                    const guideRect = guideBox.getBoundingClientRect();

                    const clientWidth = videoRect.width;
                    const clientHeight = videoRect.height;

                    const scale = Math.max(clientWidth / videoWidth, clientHeight / videoHeight);
                    const renderedWidth = videoWidth * scale;
                    const renderedHeight = videoHeight * scale;

                    const offsetX = (clientWidth - renderedWidth) / 2;
                    const offsetY = (clientHeight - renderedHeight) / 2;

                    const boxX = guideRect.left - videoRect.left;
                    const boxY = guideRect.top - videoRect.top;

                    let cropX = (boxX - offsetX) / scale;
                    let cropY = (boxY - offsetY) / scale;
                    let cropWidth = guideRect.width / scale;
                    let cropHeight = guideRect.height / scale;

                    cropX = Math.max(0, Math.min(cropX, videoWidth - 10));
                    cropY = Math.max(0, Math.min(cropY, videoHeight - 10));
                    cropWidth = Math.min(cropWidth, videoWidth - cropX);
                    cropHeight = Math.min(cropHeight, videoHeight - cropY);

                    // Escalado con tope a maxDimension (1280px)
                    let targetWidth = Math.round(cropWidth);
                    let targetHeight = Math.round(cropHeight);
                    const maxDim = this.maxDimension;

                    if (targetWidth > maxDim || targetHeight > maxDim) {
                        if (targetWidth >= targetHeight) {
                            targetHeight = Math.round((targetHeight * maxDim) / targetWidth);
                            targetWidth = maxDim;
                        } else {
                            targetWidth = Math.round((targetWidth * maxDim) / targetHeight);
                            targetHeight = maxDim;
                        }
                    }

                    // Renderizado ultra liviano en Canvas
                    const croppedCanvas = document.createElement('canvas');
                    croppedCanvas.width = targetWidth;
                    croppedCanvas.height = targetHeight;
                    const croppedCtx = croppedCanvas.getContext('2d');

                    croppedCtx.drawImage(
                        video, 
                        cropX, cropY, cropWidth, cropHeight, 
                        0, 0, targetWidth, targetHeight
                    );

                    // Compresión JPEG local
                    croppedCanvas.toBlob((blob) => {
                        const endTime = performance.now();
                        this.isProcessing = false;

                        if (!blob) {
                            this.errorMessage = 'Error al generar la imagen.';
                            return;
                        }

                        // Calcular métricas
                        this.metrics.processingTimeMs = Math.round(endTime - startTime);
                        this.metrics.fileSizeKb = (blob.size / 1024).toFixed(1);
                        this.metrics.dimensions = targetWidth + ' x ' + targetHeight;

                        // Crear URL local del objeto
                        if (this.capturedImageUrl) {
                            URL.revokeObjectURL(this.capturedImageUrl);
                        }
                        this.capturedImageUrl = URL.createObjectURL(blob);
                        this.capturedFileName = 'captura_demo_' + Date.now() + '.jpg';

                        // Disparar descarga automática al almacenamiento del móvil
                        const downloadLink = document.createElement('a');
                        downloadLink.href = this.capturedImageUrl;
                        downloadLink.download = this.capturedFileName;
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        // Mostrar HUD de resultados
                        this.showResultModal = true;
                        this.refreshIcons();

                    }, 'image/jpeg', this.clientQuality);
                },

                closeResultModal() {
                    this.showResultModal = false;
                    this.refreshIcons();
                }
            }));
        });
    </script>
</body>
</html>
