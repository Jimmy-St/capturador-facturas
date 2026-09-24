# 🛡️ Talos - Observador de Facturas (Resumen del Proyecto)

## 📌 1. Stack Tecnológico
- **Backend:** Laravel (PHP)
- **Frontend / UI:** Blade Templates, Tailwind CSS (vía CDN), Lucide Icons
- **Interactividad UI/UX:** Alpine.js (para modales, estados reactivos y peticiones AJAX)
- **Visor de Imágenes Avanzado:** Viewer.js (zoom con rueda del ratón y arrastre fluido sin barra de herramientas)
- **Inteligencia Artificial:** Google Gemini API (`gemini-3.5-flash-lite`) con esquema JSON estricto (`responseSchema`)
- **Autenticación:** Laravel Auth + Laravel Sanctum (preparado para futura PWA)

---

## 👥 2. Roles y Accesos de Usuario
El sistema maneja tres tipos de roles en la tabla `users` (`username`, `password`, `role`):
1. **`operator` (Capturador):** Su única función es la captura en terreno. Tras hacer login, es redirigido automáticamente a `/scan` y **jamás** tiene acceso al dashboard.
2. **`supervisor` (Supervisor):** Acceso al escáner de terreno y al panel de visualización/auditoría (Dashboard).
3. **`admin` (Administrador):** Acceso total (escáner, dashboard y gestión de usuarios).

---

## 🗄️ 3. Modelo de Datos (`invoices`)
La tabla principal almacena la trazabilidad completa del documento tributario:
- `id` (Primary Key)
- `document_type` (STRING, ej: Factura Electrónica)
- `folio` (STRING)
- `rut` (STRING - Proveedor)
- `supplier` (STRING - Razón Social)
- `document_date` (DATE - Fecha de emisión del documento)
- `reception_date` (DATETIME - Fecha de recepción en el sistema)
- `amount` (DECIMAL 12,2 - Monto total)
- `fidelity` (STRING: `alta`, `media`, `baja`)
- `tokens_cost` (UNSIGNED INT - Costo en tokens devuelto por Gemini)
- `is_reviewed` (BOOLEAN - Estado de revisión humana, por defecto `false`)
- `image_path` (STRING - Almacenada en disco `public/invoices/` con nombre **UUID único**)
- `raw_response_json` (LONGTEXT - Respuesta cruda en formato JSON devuelta por Gemini)
- `user_id` (FOREIGN KEY a `users`, `nullOnDelete`)
- `timestamps`, `softDeletes`

---

## 📱 4. Módulo de Captura en Terreno (`/scan`)
Diseñado con una experiencia de usuario (UX) tipo **App Nativa móvil**:
- **Interfaz Inmersiva:** Sin la barra de navegación clásica de PC, pantalla completa oscura con enfoque fotográfico.
- **Guía Visual:** Cuadro punteado con proporción **Formato Oficio** para orientar al operador.
- **Recorte Inteligente en Cliente:** Captura el frame de la cámara trasera en resolución nativa, recorta el área exacta del documento mediante un `<canvas>` sin pérdida de calidad, y lo comprime como JPG al 95% para enviarlo por `FormData`.
- **Cero Basura en Servidor:** El backend (`InvoiceController` + `GeminiService`) procesa la imagen directamente desde la memoria temporal. Si Gemini reporta errores, la foto se descarta. Si es exitosa, se optimiza y comprime al **70% de calidad** en el servidor usando GD library y se guarda con un nombre **UUID único**.
- **Loading Amigable:** Overlay translúcido con spinner animado e indicadores de texto en tiempo real ("Optimizando encuadre...", "Enviando a Gemini IA...").

---

## 🖥️ 5. Panel de Control y Auditoría (`/dashboard` & `/invoices/{id}`)
- **Dashboard:** Listado de documentos con filtros rápidos por fidelidad (`baja`, `media`, `alta`) y por estado de validación humana (`vista` / `is_reviewed`). Búsqueda integrada por Folio, RUT o Proveedor.
- **Vista de Detalle (`show`):** 
  - Ficha principal con **campos editables** (Tipo, Folio, RUT, Proveedor, Monto, Fecha de emisión) y botón de "Guardar Cambios".
  - Fecha de recepción del sistema (no editable).
  - Miniatura interactiva de la factura que abre un **modal al 80% de ancho**.
  - **Viewer.js** integrado para hacer *zoom* con la rueda del ratón y *drag* (arrastrar) libremente.
  - **Botón de Revisión Instantánea:** Petición AJAX ultrarrápida con Alpine.js (`/review`) que cambia el estado a "Documento Revisado" al instante sin recargar la página.
  - Visor de **JSON Raw** con la respuesta original de la IA.