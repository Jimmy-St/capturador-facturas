<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laboratorio de Captura & Gemini OCR - {{ config('app.name') }}</title>
    
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
          clientQuality: 0.72,
          uploadUrl: '{{ route('capture.demo.upload') }}',
          csrfToken: '{{ csrf_token() }}'
      })">

    <!-- HEADER FLOTANTE TIPO APP -->
    <header class="absolute top-0 inset-x-0 z-40 bg-gradient-to-b from-black/85 via-black/40 to-transparent p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-xl bg-purple-500 flex items-center justify-center shadow-lg shadow-purple-500/20">
                <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-wider text-white">TALOS LAB</span>
                <span class="text-[10px] text-purple-400 font-semibold uppercase block leading-none">
                    Test Binario &bull; Gemini OCR
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
            <div class="w-10 h-10 border-3 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-xs text-gray-300 font-medium">Iniciando cámara en 1080p Full HD...</p>
        </div>

        <!-- LÍNEAS GUÍA FORMATO OFICIO -->
        <div class="relative w-full h-full flex items-center justify-center p-4 pt-16 pb-28 pointer-events-none z-10">
            <div x-ref="guideBox"
                 class="w-full max-w-[360px] aspect-[216/330] max-h-[74vh] border-2 border-dashed border-purple-400/70 rounded-3xl flex flex-col justify-between p-5 shadow-2xl relative backdrop-contrast-105">
                
                <!-- Esquinas Superiores -->
                <div class="flex justify-between">
                    <span class="w-7 h-7 border-t-4 border-l-4 border-purple-400 rounded-tl-xl shadow-sm"></span>
                    <span class="w-7 h-7 border-t-4 border-r-4 border-purple-400 rounded-tr-xl shadow-sm"></span>
                </div>

                <!-- Insignia Central -->
                <div class="text-center">
                    <span class="bg-black/70 backdrop-blur-md text-purple-300 border border-purple-400/40 text-[10px] font-bold tracking-widest px-3.5 py-1.5 rounded-full uppercase shadow-lg inline-flex items-center space-x-1.5">
                        <i data-lucide="sparkles" class="w-3 h-3 text-purple-400"></i>
                        <span>Encuadre Oficio + IA</span>
                    </span>
                </div>

                <!-- Esquinas Inferiores -->
                <div class="flex justify-between">
                    <span class="w-7 h-7 border-b-4 border-l-4 border-purple-400 rounded-bl-xl shadow-sm"></span>
                    <span class="w-7 h-7 border-b-4 border-r-4 border-purple-400 rounded-br-xl shadow-sm"></span>
                </div>
            </div>
        </div>
    </main>

    <!-- BARRA INFERIOR CON DISPARADOR Y ESTADOS DINÁMICOS -->
    <footer class="absolute bottom-0 inset-x-0 z-40 bg-gradient-to-t from-slate-950 via-slate-950/90 to-transparent p-5 flex flex-col items-center">
        
        <!-- Estado de progreso dinámico -->
        <div x-show="isProcessing" 
             x-transition 
             class="mb-3 w-full max-w-sm p-3.5 bg-slate-900/90 border border-purple-500/40 rounded-2xl text-purple-200 text-xs flex items-center space-x-3 backdrop-blur-md shadow-2xl"
             style="display: none;">
            <div class="w-4 h-4 border-2 border-purple-400 border-t-transparent rounded-full animate-spin shrink-0"></div>
            <span x-text="processingStageText" class="font-medium tracking-wide"></span>
        </div>

        <!-- Error si ocurre algo -->
        <div x-show="errorMessage" 
             x-transition
             class="mb-3 w-full max-w-sm p-3.5 bg-rose-500/20 border border-rose-500/40 rounded-2xl text-rose-300 text-xs flex items-center space-x-2.5 backdrop-blur-md shadow-xl"
             style="display: none;">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-400"></i>
            <span x-text="errorMessage" class="leading-tight"></span>
        </div>

        <div class="w-full max-w-sm flex items-center justify-center">
            <!-- Botón Principal de Captura y Subida con Gemini -->
            <button @click="captureAndProcessWithGemini()" 
                    :disabled="!isCameraReady || isProcessing"
                    type="button"
                    class="w-full py-4 px-6 bg-gradient-to-r from-purple-600 via-indigo-600 to-sky-600 hover:from-purple-500 hover:to-sky-500 active:scale-95 text-white font-extrabold rounded-2xl shadow-xl shadow-purple-500/25 flex items-center justify-center space-x-3 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="sparkles" class="w-5 h-5 text-amber-300"></i>
                <span class="tracking-wider text-xs sm:text-sm">CAPTURAR, SUBIR & GEMINI OCR</span>
            </button>
        </div>
    </footer>

    <!-- MODAL DE RESULTADOS Y TELEMETRÍA COMPLETA -->
    <div x-show="showResultModal" 
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 overflow-y-auto"
         style="display: none;">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md p-5 flex flex-col space-y-4 shadow-2xl my-auto">
            
            <!-- Cabecera de Telemetría -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-white">Captura, Subida & Gemini OCR</h3>
                        <p class="text-[10px] text-purple-400 font-medium">Telemetría completa de tiempos</p>
                    </div>
                </div>
                <button @click="closeResultModal()" class="text-gray-400 hover:text-white p-1 rounded-md cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Fila 1 de Métricas: Desglose por Etapas -->
            <div class="grid grid-cols-4 gap-1.5 text-center">
                <div class="bg-slate-950 p-2 rounded-xl border border-slate-800/80">
                    <span class="text-[8px] font-bold text-gray-400 block uppercase">1. Móvil</span>
                    <span class="text-xs font-black text-emerald-400 block mt-0.5" x-text="metrics.captureTimeMs + ' ms'"></span>
                    <span class="text-[7px] text-gray-500 block">Render+JPG</span>
                </div>
                <div class="bg-slate-950 p-2 rounded-xl border border-slate-800/80">
                    <span class="text-[8px] font-bold text-gray-400 block uppercase">2. Red 4G</span>
                    <span class="text-xs font-black text-sky-400 block mt-0.5" x-text="metrics.uploadNetTimeMs + ' ms'"></span>
                    <span class="text-[7px] text-gray-500 block">HTTP Subida</span>
                </div>
                <div class="bg-slate-950 p-2 rounded-xl border border-slate-800/80">
                    <span class="text-[8px] font-bold text-gray-400 block uppercase">3. Disco</span>
                    <span class="text-xs font-black text-teal-400 block mt-0.5" x-text="metrics.serverDiskTimeMs + ' ms'"></span>
                    <span class="text-[7px] text-gray-500 block">SSD Servidor</span>
                </div>
                <div class="bg-slate-950 p-2 rounded-xl border border-purple-500/40 bg-purple-950/20">
                    <span class="text-[8px] font-bold text-purple-300 block uppercase">4. Gemini IA</span>
                    <span class="text-xs font-black text-purple-400 block mt-0.5" x-text="metrics.geminiTimeMs + ' ms'"></span>
                    <span class="text-[7px] text-purple-400/70 block">OCR API</span>
                </div>
            </div>

            <!-- Fila 2: Resumen Global y Peso -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-gradient-to-br from-slate-950 to-purple-950/30 p-2.5 rounded-xl border border-purple-500/30">
                    <span class="text-[9px] font-bold text-purple-300 block uppercase">Tiempo Total E2E</span>
                    <span class="text-sm font-black text-white" x-text="metrics.totalTimeMs + ' ms'"></span>
                    <span class="text-[8px] text-purple-300/70 block">Extremo a extremo</span>
                </div>
                <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800/80">
                    <span class="text-[9px] font-bold text-gray-400 block uppercase">Peso Imagen</span>
                    <span class="text-sm font-black text-amber-400" x-text="metrics.fileSizeKb + ' KB'"></span>
                    <span class="text-[8px] text-gray-500 block" x-text="metrics.dimensions"></span>
                </div>
                <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800/80">
                    <span class="text-[9px] font-bold text-gray-400 block uppercase">Tokens IA</span>
                    <span class="text-sm font-black text-pink-400" x-text="metrics.tokensCost"></span>
                    <span class="text-[8px] text-gray-500 block">Consumo Google</span>
                </div>
            </div>

            <!-- RESULTADOS DEL OCR EXTRAÍDOS POR GEMINI -->
            <div x-show="metrics.ocrSuccess && metrics.ocrData" class="bg-slate-950 border border-slate-800 rounded-2xl p-3 space-y-2">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-1.5">
                    <span class="text-[10px] font-bold text-purple-300 uppercase tracking-wider flex items-center space-x-1">
                        <i data-lucide="file-check" class="w-3.5 h-3.5 text-purple-400 inline"></i>
                        <span>Datos Extraídos por Gemini</span>
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                          :class="metrics.ocrData?.fidelidad_estimada >= 80 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400'"
                          x-text="'Fidelidad: ' + (metrics.ocrData?.fidelidad_estimada || 0) + '%'">
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-[9px] text-gray-500 uppercase block font-semibold">Proveedor</span>
                        <span class="font-bold text-white truncate block text-[11px]" x-text="metrics.ocrData?.nombre_proveedor || 'No identificado'"></span>
                    </div>
                    <div>
                        <span class="text-[9px] text-gray-500 uppercase block font-semibold">RUT Proveedor</span>
                        <span class="font-mono text-gray-300 block text-[11px]" x-text="metrics.ocrData?.rut_proveedor || 'N/A'"></span>
                    </div>
                    <div>
                        <span class="text-[9px] text-gray-500 uppercase block font-semibold">Folio / Doc</span>
                        <span class="font-mono text-amber-400 font-bold block text-[11px]" x-text="(metrics.ocrData?.tipo_documento || 'DOC') + ' #' + (metrics.ocrData?.numero_documento || 'S/N')"></span>
                    </div>
                    <div>
                        <span class="text-[9px] text-gray-500 uppercase block font-semibold">Total</span>
                        <span class="font-extrabold text-emerald-400 block text-[11px]" x-text="metrics.ocrData?.total || '$0'"></span>
                    </div>
                </div>
            </div>

            <!-- Error de OCR si falla -->
            <div x-show="!metrics.ocrSuccess && metrics.ocrError" class="p-3 bg-rose-500/20 border border-rose-500/40 rounded-xl text-rose-300 text-xs">
                <span class="font-bold block">Aviso de OCR:</span>
                <span x-text="metrics.ocrError"></span>
            </div>

            <!-- Identificador UUID en Servidor -->
            <div class="bg-slate-950/80 border border-slate-800 p-2 rounded-xl space-y-0.5 text-[9px]">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 font-semibold uppercase">UUID:</span>
                    <span class="text-purple-400 font-mono font-bold truncate max-w-[230px]" x-text="metrics.uuid"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 font-semibold uppercase">Ruta:</span>
                    <span class="text-gray-300 font-mono truncate max-w-[230px]" x-text="metrics.filePath"></span>
                </div>
            </div>

            <!-- Previsualización de la Foto -->
            <div class="bg-black rounded-2xl overflow-hidden border border-slate-800 flex items-center justify-center max-h-[20vh] relative group">
                <img :src="capturedImageUrl" alt="Captura demo" class="max-h-[20vh] w-auto object-contain">
                <div class="absolute bottom-1 inset-x-1 bg-black/60 backdrop-blur-xs text-[9px] text-center text-gray-300 py-0.5 rounded-lg">
                    Foto enviada a Gemini OCR
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="space-y-2 pt-1">
                <button @click="closeResultModal()" 
                        type="button"
                        class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 active:scale-95 text-white font-bold rounded-xl text-xs flex items-center justify-center space-x-2 transition shadow-lg shadow-purple-600/20 cursor-pointer">
                    <i data-lucide="camera" class="w-4 h-4"></i>
                    <span>Disparar otra captura</span>
                </button>

                <div class="grid grid-cols-2 gap-2">
                    <a :href="metrics.fileUrl" 
                       target="_blank"
                       class="py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-sky-400 font-semibold rounded-xl text-[11px] flex items-center justify-center space-x-1.5 transition text-center">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Ver en Servidor</span>
                    </a>

                    <a :href="capturedImageUrl" 
                       :download="capturedFileName"
                       class="py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-gray-300 font-semibold rounded-xl text-[11px] flex items-center justify-center space-x-1.5 transition text-center">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Copia Móvil</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPT ALPINE.JS CON MEDIDAS DE CAPTURA, RED Y GEMINI -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('demoScanner', (config = {}) => ({
                maxDimension: config.maxDimension || 1280,
                clientQuality: config.clientQuality || 0.72,
                uploadUrl: config.uploadUrl || '/scan-demo/upload',
                csrfToken: config.csrfToken || '',
                
                stream: null,
                isCameraReady: false,
                cameraError: false,
                isProcessing: false,
                processingStageText: '',
                errorMessage: '',
                hasTorch: false,
                torchOn: false,

                // Telemetría
                showResultModal: false,
                capturedImageUrl: '',
                capturedFileName: '',
                metrics: {
                    captureTimeMs: 0,
                    uploadNetTimeMs: 0,
                    serverDiskTimeMs: 0,
                    geminiTimeMs: 0,
                    totalTimeMs: 0,
                    fileSizeKb: 0,
                    dimensions: '',
                    tokensCost: 0,
                    uuid: '',
                    filePath: '',
                    fileUrl: '',
                    ocrSuccess: false,
                    ocrError: null,
                    ocrData: null
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

                captureAndProcessWithGemini() {
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
                    this.errorMessage = '';
                    this.processingStageText = '1/3 Recortando y procesando en el móvil...';

                    // Etapa 1: Captura local en Canvas
                    const captureStartTime = performance.now();

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

                    const croppedCanvas = document.createElement('canvas');
                    croppedCanvas.width = targetWidth;
                    croppedCanvas.height = targetHeight;
                    const croppedCtx = croppedCanvas.getContext('2d');

                    croppedCtx.drawImage(
                        video, 
                        cropX, cropY, cropWidth, cropHeight, 
                        0, 0, targetWidth, targetHeight
                    );

                    // Generar Blob JPEG binario
                    croppedCanvas.toBlob(async (blob) => {
                        const captureEndTime = performance.now();
                        const captureTimeMs = Math.round(captureEndTime - captureStartTime);

                        if (!blob) {
                            this.isProcessing = false;
                            this.errorMessage = 'Error al generar la imagen del encuadre.';
                            return;
                        }

                        // URL local de previsualización
                        if (this.capturedImageUrl) {
                            URL.revokeObjectURL(this.capturedImageUrl);
                        }
                        this.capturedImageUrl = URL.createObjectURL(blob);
                        this.capturedFileName = 'captura_' + Date.now() + '.jpg';

                        // Guardar automáticamente copia física en el almacenamiento del móvil
                        const autoSaveLink = document.createElement('a');
                        autoSaveLink.href = this.capturedImageUrl;
                        autoSaveLink.download = this.capturedFileName;
                        document.body.appendChild(autoSaveLink);
                        autoSaveLink.click();
                        document.body.removeChild(autoSaveLink);

                        // Etapa 2 y 3: Subida en binario y análisis en backend
                        this.processingStageText = '2/3 Subiendo binario (4G) & ejecutando Gemini OCR...';
                        const requestStartTime = performance.now();

                        try {
                            const formData = new FormData();
                            formData.append('image', blob, 'scan_mobile.jpg');
                            if (this.csrfToken) {
                                formData.append('_token', this.csrfToken);
                            }

                            const response = await fetch(this.uploadUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            const requestEndTime = performance.now();
                            const totalRequestMs = Math.round(requestEndTime - requestStartTime);

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Error en el servidor al procesar la imagen.');
                            }

                            // Desglose de red restando el tiempo interno del servidor
                            const serverTotalMs = result.server_total_time_ms || (result.server_disk_time_ms + result.gemini_time_ms);
                            const uploadNetTimeMs = Math.max(10, Math.round(totalRequestMs - serverTotalMs));

                            // Registrar métricas completas
                            this.metrics = {
                                captureTimeMs: captureTimeMs,
                                uploadNetTimeMs: uploadNetTimeMs,
                                serverDiskTimeMs: result.server_disk_time_ms || 0,
                                geminiTimeMs: result.gemini_time_ms || 0,
                                totalTimeMs: captureTimeMs + totalRequestMs,
                                fileSizeKb: (blob.size / 1024).toFixed(1),
                                dimensions: targetWidth + ' x ' + targetHeight,
                                tokensCost: result.ocr_data?.tokens_cost || 0,
                                uuid: result.uuid || '',
                                filePath: result.file_path || '',
                                fileUrl: result.file_url || '',
                                ocrSuccess: result.ocr_success,
                                ocrError: result.ocr_error,
                                ocrData: result.ocr_data
                            };

                            this.isProcessing = false;
                            this.showResultModal = true;
                            this.refreshIcons();

                        } catch (err) {
                            this.isProcessing = false;
                            this.errorMessage = 'Fallo en el procesamiento: ' + err.message;
                            console.error('Error en captura/OCR:', err);
                            this.refreshIcons();
                        }

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
