# Registro de uso de asistentes de IA

**Herramientas utilizadas:** 
- ChatGPT (OpenAI)
- DeepSeek
- Gemini

---

| Fecha | Issue / PR | Para qué se usó | Qué devolvió | Cómo se verificó | Qué se modificó |
|---|---|---|---|---|---|
| 2026-07-28 | #4 | Crear gestión de disponibilidad diaria | Migración `daily_availability`, modelo `DailyAvailability`, relación con `Product` y vista de gestión | Se probó modificando stocks desde el panel y verificando en BD | Se implementó el sistema de stock diario con scopes `available()` y `hasStockToday()` |
| 2026-07-28 | #5 | Configurar bot de Telegram y listar productos | Comandos `/start`, `/menu`, polling, webhook y controlador `BotController` | Se probó en Telegram enviando `/start` y `/menu` | Se configuró el bot con modo polling, webhook y se integró con productos disponibles |
| 2026-07-29 | #6 | Integrar stock diario en el bot | Filtrado de productos con `available()` y `whereHas('todayAvailability')` | Se probó que el bot solo muestre productos con stock > 0 | Se actualizaron `BotController` y `BotPolling` para usar el scope `available()` |
| 2026-07-29 | #7 | Implementar carrito de compras en el bot | Migración `carts`, modelo `Cart`, comandos `/carrito`, `/agregar`, `/quitar`, `/vaciar`, `/confirmar` | Se probó agregando productos, viendo el carrito y confirmando pedido | Se implementó el carrito en memoria con persistencia en BD |
| 2026-07-29 | #7 | Implementar flujo de pagos (QR y comprobantes) | Campos en `orders`: `payment_voucher`, `payment_status`, `delivery_user_id`, `delivery_status` | Se probó subiendo QR desde el panel y enviando comprobante desde el bot | Se implementó la gestión de QR, recepción de comprobantes y asignación de repartidores |
| 2026-07-29 | #8 | Implementar ubicación en tiempo real del delivery | Lógica de `delivery_tracking`, comandos `/entregado`, `/cancelar-entrega`, callback `iniciar_entrega_` | Se probó con un delivery compartiendo ubicación y cliente recibiendo actualizaciones | Se implementó el envío de ubicación en vivo, confirmación de entrega y agradecimiento al cliente |
| 2026-07-29 | #8 | Configurar Observer para notificaciones automáticas | `BuyObserver` con notificaciones al cliente y al repartidor | Se probó cambiando estados del pedido y verificando mensajes en Telegram | Se implementaron notificaciones automáticas para: cancelación, aprobación, asignación de repartidor y entrega |
| 2026-07-29 | #6 | Corrección de errores en el bot | Métodos para manejar `getId()` → `getUpdateId()` | Se probó el bot en modo polling sin errores | Se corrigieron errores de compatibilidad con la versión de `telegram-bot/api` |
| 2026-07-29 | #5 | Configuración de CSRF en Laravel 11 | Configuración en `bootstrap/app.php` para excluir rutas del webhook | Se probó con `curl` al webhook sin error 419 | Se agregó `$middleware->validateCsrfTokens(except: ['webhook'])` |
| 2026-07-29 | - | Creación de comandos Artisan | Comandos `telegram:poll` y `telegram:set-webhook` | Se ejecutaron los comandos y se verificaron en la terminal | Se registraron los comandos en `routes/console.php` |

---

##  Dónde NO se usó IA

- **Lógica de negocio del carrito**: El manejo del carrito en `userStates` fue implementado manualmente según el flujo conversacional que diseñé.
- **Estructura de la base de datos**: Las migraciones `orders`, `order_items`, `settings`, `daily_availability` y `carts` fueron diseñadas por mí, ajustando las relaciones y tipos de datos según las necesidades del sistema.
- **Diseño del flujo conversacional**: La secuencia de comandos (`/start`, `/menu`, `/pedido`, `/carrito`, `/agregar`, `/confirmar`) fue definida manualmente considerando la experiencia de usuario.
- **Observer y notificaciones**: La lógica de `BuyObserver` fue creada por mí para manejar los diferentes estados del pedido y las notificaciones automáticas.
- **Interacción entre Bot y Base de Datos**: La lógica de `finishOrder()` con transacciones `DB::transaction()` y el descuento de stock fue implementada manualmente para garantizar la integridad de los datos.
- **Configuración del entorno**: La configuración de `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_WEBHOOK_URL` y las variables de entorno fueron definidas manualmente.

---

##  Declaración de Uso de IA

Comprendo todo el código de este repositorio y puedo explicarlo, justificarlo y
modificarlo sin asistencia.
---

##  Fecha de actualización

**Última actualización:** 2026-07-29