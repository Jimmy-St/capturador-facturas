# 🛡️ Talos - Observador de Facturas (Documento de Requerimientos y Arquitectura)

Sistema de captura, procesamiento inteligente con IA (OCR), auditoría tributaria y control de pagos de facturas físicas en terreno.

---

## 📌 1. Stack Tecnológico

- **Backend:** Laravel 11+ (PHP 8.3)
- **Base de Datos:** SQLITE con soporte de colas (`jobs`) y sesiones en base de datos.
- **Frontend / UI:** Blade Templates, Tailwind CSS (vía CDN), Lucide Icons.
- **Interactividad Reactiva (SPA-like):** Alpine.js para formularios AJAX, estados en vivo y modales.
- **Visor de Documentos:** Viewer.js (zoom con rueda de ratón y paneo fluido sin toolbar distractora).
- **Procesamiento PDF en Cliente:** PDF.js (renderizado y conversión cliente de PDF a imagen JPG en `<canvas>` a escala 2.0).
- **Inteligencia Artificial:** Google Gemini API con esquema estructurado estricto (`responseSchema`).
- **Autenticación:** Laravel Auth + Laravel Sanctum (preparado para API y futura PWA móvil).

---

## 👥 2. Roles y Matriz de Acceso

La autenticación utiliza nombre de usuario (`username`) en lugar de email:

| Rol | Escáner (`/scan`) | Dashboard (`/dashboard`) | Detalle & Edición (`/invoices/{id}`) | Gestión de Pagos |
| :--- | :---: | :---: | :---: | :---: |
| **`operator` (Capturador)** |  (Redirección automática post-login) | ❌ Bloqueado | ❌ Bloqueado | ❌ Bloqueado |
| **`supervisor` (Auditor)** |  |  |  |  |
| **`admin` (Administrador)** |  |  |  |  |

---

## 🗄️ 3. Modelo de Datos

### 3.1. `users`
- `id` (PK)
- `username` (STRING, unique)
- `password` (STRING, hashed)
- `role` (STRING, default: `'supervisor'`; opciones: `'operator'`, `'supervisor'`, `'admin'`)
- `remember_token`, `timestamps`

### 3.2. `invoices`
- `id` (PK)
- `document_type` (STRING, default: `'FACTURA'`)
- `folio` (STRING)
- `rut` (STRING - RUT Proveedor)
- `supplier` (STRING - Razón Social)
- `document_date` (DATE - Fecha de emisión tributaria)
- `reception_date` (DATETIME - Marca temporal de ingreso al sistema)
- `amount` (DECIMAL 12,2 - Monto total del documento)
- `fidelity` (STRING: `'alta'`, `'media'`, `'baja'`)
- `tokens_cost` (UNSIGNED INT - Tokens consumidos de Gemini)
- `is_reviewed` (BOOLEAN, default: `false` - Estado de validación humana)
- `payment_status` (STRING, default: `'adeudado'`, indexado; valores: `'adeudado'`, `'pagado'`)
- `image_path` (STRING, nullable - Almacenada en disco público con UUID único: `invoices/{uuid}.jpg`)
- `raw_response_json` (LONGTEXT, nullable - Respuesta JSON original devuelta por la IA)
- `user_id` (FOREIGN KEY a `users`, `nullOnDelete`)
- `timestamps`, `softDeletes`

### 3.3. `invoice_payments` (Registro y Control de Abonos)
- `id` (PK)
- `invoice_id` (FOREIGN KEY a `invoices`, `cascadeOnDelete`)
- `user_id` (FOREIGN KEY a `users`, `nullOnDelete` - Usuario que registró el abono)
- `amount` (DECIMAL 12,2 - Monto del pago)
- `payment_method` (STRING, default: `'cheque'`; ej: cheque, transferencia, efectivo)
- `payment_date` (DATE - Fecha del pago o emisión del cheque)
- `reference_number` (STRING, nullable - N° de cheque, operación bancaria o comprobante)
- `image_path` (STRING - Comprobante optimizado en disco: `payments/{uuid}.jpg`)
- `notes` (TEXT, nullable - Observaciones adicionales)
- `timestamps`

---

## 📱 4. Módulo de Captura en Terreno (`/scan`)

Diseñado como una experiencia de **App Móvil Nativa de Alto Rendimiento**:

- **Interfaz Inmersiva:** Modo oscuro a pantalla completa (`bg-slate-950`), sin barras de navegación de escritorio ni rebotes táctiles (`touch-action: manipulation`).
- **Control de Iluminación:** Botón flotante para encender/apagar la linterna del teléfono (`torch`) si la API de hardware del navegador lo soporta.
- **Sensor de Alta Resolución:** Solicita cámara trasera ambiental con resolución ideal hasta 4K (`3840x2160`).
- **Encuadre Formato Oficio:** Guía visual punteada con proporción exacta 216x330 mm (~0.654) y esquinas destacadas.
- **Recorte Inteligente en Cliente (Canvas):**
  - Mapea la posición visual de la guía al sensor de video real, compensando `object-cover`.
  - Recorta el marco exacto sin enviar el fondo innecesario.
  - Re-escala si supera la dimensión máxima (`max_dimension: 1800px`) y comprime a JPG al 85% de calidad en el navegador, reduciendo el payload de subida a fracciones de MB.
- **Carga Alternativa:** Opción de subir imágenes existentes desde la galería o explorador, con escalado local previo al envío.
- **Arquitectura Asíncrona (Cola de Procesamiento):**
  - El endpoint `/invoices/process` almacena la imagen temporal en `temp_invoices/` y despacha el Job [`ProcessInvoiceOcr`] a la cola de Laravel.
  - La respuesta al operador es instantánea (<200ms); la cámara queda lista de inmediato para el siguiente disparo sin esperar el tiempo de respuesta de la IA.

---

## 💳 5. Módulo de Gestión de Pagos y Abonos

Ubicado en el panel lateral de la vista de detalle de cada factura:

- **Balance Financiero en Tiempo Real:**
  - Métricas reactivas con Alpine.js: **Total Factura**, **Total Abonado**, **Saldo Pendiente** (`remainingAmount()`) y barra de progreso porcentual.
  - Al hacer clic en el saldo pendiente, autocompleta el monto sugerido en el formulario de pago.
- **Soporte Híbrido de Comprobantes (Imagen y PDF):**
  - Admite subida de fotos (JPG, PNG) y documentos PDF.
  - **Conversión PDF a JPG en el cliente con PDF.js:** Convierte la primera página del PDF en un `<canvas>` a escala 2.0 y genera un JPG antes de enviarlo, evitando dependencias pesadas en el servidor (ImageMagick/Ghostscript).
- **Procesamiento de Comprobante en Backend:**
  - Valida montos y archivos (máx. 15MB).
  - Optimiza la imagen con GD library (escalado a 1800px y compresión al 75%) y almacena en `payments/{uuid}.jpg`.
  - Al anular un pago (`destroy`), realiza borrado físico del archivo en disco.
- **Estado de Pago:** Alternador rápido (`togglePaymentStatus`) entre `'adeudado'` y `'pagado'`.
- **Historial de Abonos:** Lista cronológica con usuario auditor, fecha, método, referencia y miniatura con apertura directa en Viewer.js para inspeccionar cheques.

---

## 🖥️ 6. Panel de Control y Auditoría (`/dashboard` & `/invoices/{id}`)

### 6.1. Dashboard Principal
- **Tarjetas de Estadísticas Globales:**
  - Total de facturas registradas.
  - Conteo y monto total acumulado de facturas **Adeudadas**.
  - Conteo y monto total acumulado de facturas **Pagadas**.
- **Filtros Rápidos:**
  - Por búsqueda textual (Folio, RUT, Proveedor).
  - Por rango de fechas de emisión (`date_from` - `date_to`).
  - Por fidelidad (`alta`, `media`, `baja`) o validadas (`vista` / `is_reviewed = true`).
  - Por estado de pago (`adeudado` / `pagado`).
- **Listado Paginado:** Muestra badges de estado de pago, total abonado en caso de abonos parciales, fidelidad y botón de acceso a la ficha de auditoría.

### 6.2. Vista de Detalle (`/invoices/{id}`)
- **Ficha de Datos Editables:** Permite corregir campos fiscales extraídos por la IA (Tipo, Folio, RUT, Proveedor, Fecha, Monto) con persistencia en BD.
- **Validación Humana Instantánea:** Botón AJAX con Alpine.js (`PATCH /invoices/{id}/review`) que marca la factura como revisada sin recargar la página.
- **Visor Principal con Viewer.js:** Modal al 85% de la pantalla para inspeccionar la factura original con zoom mediante la rueda del ratón y arrastre fluido.
- **Auditoría Técnica:** Visor de JSON crudo (`raw_response_json`) formateado con la respuesta íntegra devuelta por Gemini.

---

## 🧠 7. Integración con Inteligencia Artificial (Gemini OCR)

- **Servicio:** [`GeminiService.php`] llamando al endpoint `models/{model}:generateContent`.
- **Esquema Estricto (`responseSchema`):**
  - Campos obligatorios: `tipo_documento`, `numero_documento`, `rut_proveedor`, `nombre_proveedor`, `fecha_emision`, `total`, `articulos` (`codigo`, `descripcion`), `fidelidad_estimada` (0-100), `error`.
- **Resiliencia & Costos:**
  - Reintentos automáticos con `Http::retry` ante respuestas 429 (límite de cuota) o 5xx.
  - Registro de tokens consumidos en `tokens_cost` leyendo directamente `usageMetadata.totalTokenCount`.
  - Normalización de montos (`parseAmount`) para evitar inconsistencias de formato regional chileno/anglosajón sin multiplicar decimales por 100.
  - Normalización de fechas a formato estándar `YYYY-MM-DD` con Carbon.
- **Política de Cero Basura:** Si Gemini indica error o el job falla de forma definitiva, los archivos temporales se descartan inmediatamente sin ensuciar la base de datos ni el almacenamiento público.

---

## ⚙️ 8. Parámetros de Configuración (`config/invoices.php`)

Variables de entorno configurables en `.env`:

| Variable | Descripción | Valor por defecto |
| :--- | :--- | :---: |
| `INVOICE_IMAGE_MAX_DIMENSION` | Límite máximo en píxeles (ancho/alto) para optimización | `1800` |
| `INVOICE_IMAGE_QUALITY` | Calidad de compresión JPEG en backend (GD) | `75` |
| `INVOICE_CLIENT_QUALITY` | Calidad de exportación inicial en canvas del cliente | `0.85` |
| `GEMINI_TIMEOUT` | Timeout en segundos para peticiones a la API de Gemini | `20` |
| `GEMINI_MODEL` | Modelo de Gemini utilizado para OCR | `gemini-flash-latest` |
| `PAGINATION_PER_PAGE` | Cantidad de facturas por página en el Dashboard | `40` |