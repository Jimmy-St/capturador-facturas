---
name: clean-code-style
description: Reglas de estilo de código, documentación de métodos (entradas y salidas) y manejo de términos de negocio. Actívalo siempre al escribir, editar o refactorizar código.
---

# Reglas de Estilo de Código y Documentación

## 1. Idioma y Negocio
- **Capa técnica en inglés:** Nombres de clases, métodos, variables estructurales, tablas y migraciones se escriben en **inglés**.
- **Data y Dominio de Negocio en español:** Los estados del negocio (ej: `'pendiente'`, `'aprobado'`, `'facturado'`), roles de usuario del negocio y datos propios del dominio pueden ir en **español** si así fue definido.

## 2. Comentarios
- **Ubicación:** Solo en la cabecera de clases y métodos.
- **Contenido obligatorio:**
  1. Propósito breve (1 línea).
  2. **Qué espera:** Parámetros de entrada.
  3. **Qué retorna:** Valor o estructura de salida.
- **Prohibido:** Comentarios internos dentro de la lógica del método o línea por línea. El cuerpo del método debe ser autoexplicativo.

---

## Ejemplo en PHP / Laravel

### ✅ Correcto:
```php
/**
 * Processes an order payment and transitions the business state.
 *
 * @param  Order  $order   The customer order to process.
 * @param  float  $amount  The payment amount received.
 * @return bool            True if payment succeeds and state updates to 'pagado', false otherwise.
 */
public function processPayment(Order $order, float $amount): bool
{
    if ($amount < $order->total) {
        $order->update(['estado' => 'pago_parcial']);
        return false;
    }

    $order->update(['estado' => 'pagado']);
    return true;
}