<script>
    function invoiceShow(config) {
        return {
            invoiceId: config.invoiceId,
            totalInvoice: config.totalInvoice,
            totalPaid: config.initialPaid,
            paymentStatus: config.paymentStatus,
            isReviewed: config.isReviewed,
            originalImageUrl: config.originalImageUrl,
            payments: config.payments || [],
            routes: config.routes,

            // Estado de Formulario de Pago
            newPayment: {
                amount: Math.max(0, config.totalInvoice - config.initialPaid),
                payment_date: new Date().toISOString().split('T')[0],
                payment_method: 'cheque',
                reference_number: '',
                notes: ''
            },
            paymentFile: null,
            paymentPreview: null,
            fileName: '',
            isConvertingPdf: false,
            pdfProgress: '',
            isSubmittingPayment: false,
            paymentError: '',
            paymentSuccess: '',
            isUpdatingStatus: false,

            // Estado de Modal Viewer.js
            showModal: false,
            modalImageUrl: '',
            modalTitle: '',
            viewerInstance: null,

            init() {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            formatCLP(val) {
                const num = Math.round(Number(val) || 0);
                return num.toLocaleString('es-CL');
            },

            remainingAmount() {
                return Math.max(0, this.totalInvoice - this.totalPaid);
            },

            percentPaid() {
                if (!this.totalInvoice || this.totalInvoice <= 0) return 0;
                return Math.min(100, Math.round((this.totalPaid / this.totalInvoice) * 100));
            },

            getStatusButtonText() {
                if (this.paymentStatus === 'pagado') {
                    return 'CAMBIAR A ADEUDADO';
                }
                if (this.remainingAmount() <= 0.001) {
                    return '¿MARCAR COMO PAGADO?';
                }
                return '¿MARCAR COMO PAGADO?';
            },

            async markAsReviewed() {
                if (this.isReviewed) return;
                try {
                    const res = await fetch(this.routes.review, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.isReviewed = true;
                        this.$nextTick(() => lucide.createIcons());
                    }
                } catch (e) {
                    console.error('Error al marcar como revisada:', e);
                }
            },

            async togglePaymentStatus() {
                if (this.isUpdatingStatus) return;
                this.isUpdatingStatus = true;

                try {
                    const res = await fetch(this.routes.togglePaymentStatus, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.paymentStatus = data.payment_status;
                        this.$nextTick(() => lucide.createIcons());
                    }
                } catch (e) {
                    console.error('Error al actualizar estado:', e);
                } finally {
                    this.isUpdatingStatus = false;
                }
            },

            async onFileSelected(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.paymentError = '';
                this.fileName = file.name;

                // Detección de PDF para conversión en el navegador con PDF.js
                if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    this.isConvertingPdf = true;
                    this.pdfProgress = 'Procesando PDF a imagen JPG...';

                    try {
                        const arrayBuffer = await file.arrayBuffer();
                        const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
                        const pdf = await loadingTask.promise;
                        const page = await pdf.getPage(1);

                        // Escala 2.0 para garantizar máxima legibilidad de números de cheque
                        const scale = 2.0;
                        const viewport = page.getViewport({ scale });

                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        const ctx = canvas.getContext('2d');

                        await page.render({ canvasContext: ctx, viewport }).promise;

                        canvas.toBlob((blob) => {
                            if (blob) {
                                this.paymentFile = new File([blob], file.name.replace(/\.pdf$/i, '.jpg'), { type: 'image/jpeg' });
                                this.paymentPreview = canvas.toDataURL('image/jpeg', 0.85);
                            } else {
                                this.paymentError = 'Error al convertir el PDF a formato de imagen.';
                            }
                            this.isConvertingPdf = false;
                            this.pdfProgress = '';
                            this.$nextTick(() => lucide.createIcons());
                        }, 'image/jpeg', 0.85);

                    } catch (err) {
                        console.error('Error procesando PDF:', err);
                        this.paymentError = 'No se pudo leer el documento PDF. Por favor selecciona una imagen JPG o PNG.';
                        this.isConvertingPdf = false;
                        this.paymentFile = null;
                        this.paymentPreview = null;
                    }

                } else {
                    // Imagen estándar (JPG, PNG, WebP)
                    this.paymentFile = file;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.paymentPreview = e.target.result;
                        this.$nextTick(() => lucide.createIcons());
                    };
                    reader.readAsDataURL(file);
                }
            },

            clearSelectedFile() {
                this.paymentFile = null;
                this.paymentPreview = null;
                this.fileName = '';
                const input = document.getElementById('payment-file-input');
                if (input) input.value = '';
                this.$nextTick(() => lucide.createIcons());
            },

            async submitPayment() {
                if (!this.paymentFile) {
                    this.paymentError = 'Debes adjuntar una imagen o PDF del comprobante o cheque.';
                    return;
                }
                if (this.newPayment.amount <= 0) {
                    this.paymentError = 'El monto debe ser superior a 0.';
                    return;
                }

                this.isSubmittingPayment = true;
                this.paymentError = '';
                this.paymentSuccess = '';

                const formData = new FormData();
                formData.append('amount', this.newPayment.amount);
                formData.append('payment_date', this.newPayment.payment_date);
                formData.append('payment_method', this.newPayment.payment_method);
                if (this.newPayment.reference_number) {
                    formData.append('reference_number', this.newPayment.reference_number);
                }
                if (this.newPayment.notes) {
                    formData.append('notes', this.newPayment.notes);
                }
                formData.append('image', this.paymentFile);

                try {
                    const res = await fetch(this.routes.storePayment, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        this.paymentError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error al guardar el pago.');
                        return;
                    }

                    // Éxito: agregar pago a la lista reactiva y actualizar métricas
                    this.payments.push(data.payment);
                    this.totalPaid = data.total_paid;
                    this.paymentStatus = data.payment_status;

                    this.paymentSuccess = 'Pago registrado exitosamente.';
                    setTimeout(() => { this.paymentSuccess = ''; }, 4000);

                    // Resetear formulario
                    this.clearSelectedFile();
                    this.newPayment.amount = this.remainingAmount();
                    this.newPayment.reference_number = '';
                    this.newPayment.notes = '';

                    this.$nextTick(() => lucide.createIcons());

                } catch (e) {
                    console.error('Error al registrar pago:', e);
                    this.paymentError = 'Ocurrió un error inesperado al enviar el pago.';
                } finally {
                    this.isSubmittingPayment = false;
                }
            },

            async deletePayment(paymentId) {
                if (!confirm('¿Estás seguro de anular este pago? Se eliminará permanentemente el registro y el comprobante del servidor.')) {
                    return;
                }

                try {
                    const res = await fetch(`${this.routes.deletePaymentBase}/${paymentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.routes.csrf,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();

                    if (data.success) {
                        this.payments = this.payments.filter(p => p.id !== paymentId);
                        this.totalPaid = data.total_paid;
                        this.paymentStatus = data.payment_status;
                        this.newPayment.amount = this.remainingAmount();
                        this.$nextTick(() => lucide.createIcons());
                    } else {
                        alert(data.message || 'No se pudo anular el pago.');
                    }
                } catch (e) {
                    console.error('Error al anular pago:', e);
                    alert('Error de conexión al intentar anular el pago.');
                }
            },

            openModal(imageUrl, title) {
                if (!imageUrl) return;
                this.modalImageUrl = imageUrl;
                this.modalTitle = title || 'Documento';
                this.showModal = true;

                this.$nextTick(() => {
                    setTimeout(() => {
                        const target = document.getElementById('image-viewer-target');
                        if (target) {
                            if (this.viewerInstance) {
                                this.viewerInstance.destroy();
                            }
                            this.viewerInstance = new Viewer(target, {
                                inline: true,
                                button: false,
                                toolbar: false,
                                navbar: false,
                                title: false,
                                tooltip: false,
                                movable: true,
                                zoomable: true,
                                scalable: false,
                                transition: false,
                                ready: function () {
                                    this.viewer.zoomTo(2.2);
                                }
                            });
                        }
                    }, 60);
                });
            },

            closeModal() {
                this.showModal = false;
                if (this.viewerInstance) {
                    this.viewerInstance.destroy();
                    this.viewerInstance = null;
                }
            }
        };
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<style>
    .viewer-canvas, .viewer-move {
        cursor: move !important;
    }
    .viewer-canvas:active, .viewer-move:active {
        cursor: grabbing !important;
    }
</style>
