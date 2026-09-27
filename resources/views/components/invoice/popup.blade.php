<div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/75 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div @click.away="closeModal()" class="bg-white rounded-lg w-[85vw] h-[88vh] p-5 flex flex-col justify-between relative shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="font-bold text-gray-900 text-sm flex items-center space-x-2">
                <i data-lucide="scan-search" class="w-5 h-5 text-orange-500"></i>
                <span x-text="modalTitle"></span>
            </h3>
            <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-md cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Contenedor Viewer.js -->
        <div class="bg-gray-900 rounded-lg overflow-hidden flex-1 my-3 relative">
            <div id="image-viewer-container" class="w-full h-full">
                <img id="image-viewer-target" :src="modalImageUrl" :alt="modalTitle" style="display:none;">
            </div>
        </div>

        <div class="flex justify-between items-center pt-2 text-xs text-gray-500">
            <div>
                <i data-lucide="triangle-alert" class="w-4 h-4 text-red-500 inline mr-1"></i>
                <span>Usa la rueda del ratón y doble click para hacer zoom y arrastra para mover la imagen.</span>
            </div>                
            <button @click="closeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-1.5 px-4 rounded-lg transition-all cursor-pointer">Cerrar</button>
        </div>
    </div>
</div>