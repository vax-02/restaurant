<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Buy;
use App\Models\BuyDetail;
use App\Models\Delivery;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardRemove;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

#[Signature('app:bot-polling')]
#[Description('Iniciar bot de Telegram en modo polling')]
class BotPolling extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Iniciar bot en modo polling para desarrollo local';

    protected array $userStates = [];

    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        if (!$token) {
            $this->error('❌ TELEGRAM_BOT_TOKEN no configurado en .env');
            return 1;
        }

        $telegram = new BotApi($token);
        $this->info('🤖 Bot iniciado en modo polling');
        $this->info('📨 Esperando mensajes...');
        $this->info('Presiona Ctrl+C para detener');
        $this->newLine();

        $lastUpdateId = 0;

        while (true) {
            try {
                $updates = $telegram->getUpdates([
                    'offset' => $lastUpdateId + 1,
                    'timeout' => 30
                ]);

                foreach ($updates as $update) {
                    $lastUpdateId = $update->getUpdateId();

                    if ($update->getCallbackQuery()) {
                        $this->handleCallback($telegram, $update);
                        continue;
                    }

                    $message = $update->getMessage();
                    if (!$message) continue;

                    $chatId = $message->getChat()->getId();
                    $text = $message->getText() ?? '';
                    $location = $message->getLocation();
                    $photos = $message->getPhoto();
                    $firstName = $message->getChat()->getFirstName() ?? 'Usuario';

                    $this->info("📩 Mensaje de {$firstName}: {$text}");

                    $this->handleMessage($telegram, $chatId, $text, $location, $photos, $firstName);
                }

            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                sleep(5);
            }
        }
    }

    /**
     * Manejar todos los mensajes entrantes
     */
    protected function handleMessage($telegram, $chatId, $text, $location, $photos, $firstName)
    {
        $state = $this->userStates[$chatId]['state'] ?? null;

        // ==========================================
        // MANEJO DE UBICACIÓN EN VIVO DEL DELIVERY
        // ==========================================
        if ($state === 'delivery_tracking') {
            $this->handleDeliveryLocation($telegram, $chatId, $location);
            return;
        }

        // ==========================================
        // COMANDOS DEL DELIVERY
        // ==========================================
        $trimmedText = trim($text);

        if ($trimmedText === '/entregado') {
            $this->confirmDelivery($telegram, $chatId);
            return;
        }

        if ($trimmedText === '/cancelar-entrega') {
            $this->cancelDelivery($telegram, $chatId);
            return;
        }

        // ==========================================
        // FLUJO DE COMPRA (Cliente)
        // ==========================================
        if ($state === 'awaiting_quantity') {
            $this->handleQuantity($telegram, $chatId, $text);
            return;
        }

        if ($state === 'awaiting_voucher') {
            $this->receiveVoucher($telegram, $chatId, $text, $photos);
            return;
        }

        if ($state === 'awaiting_location') {
            $this->handleLocation($telegram, $chatId, $location, $text);
            return;
        }

        // ==========================================
        // VINCULACIÓN DE REPARTIDOR / DELIVERY
        // ==========================================
        if (str_starts_with($trimmedText, '/repartidor') || str_starts_with($trimmedText, '/delivery')) {
            $this->registerDelivery($telegram, $chatId, $trimmedText);
            return;
        }

        // ==========================================
        // COMANDOS PRINCIPALES
        // ==========================================
        switch ($trimmedText) {
            case '/start':
                $telegram->sendMessage(
                    $chatId,
                    "*¡Bienvenido al Restaurante, {$firstName}!*\n\nUsa /menu para ver nuestros platos y bebidas disponibles hoy.",
                    'Markdown'
                );
                break;

            case '/menu':
                $this->showMenu($telegram, $chatId);
                break;

            case '/carrito':
                $this->showCart($telegram, $chatId);
                break;

            case '/ayuda':
            case '/help':
                $telegram->sendMessage(
                    $chatId,
                    "❓ *Ayuda*\n\n"
                    . "/menu - Ver menú de hoy\n"
                    . "/carrito - Ver tu carrito actual\n"
                    . "/repartidor <codigo> - Activar cuenta de repartidor\n\n"
                    . "📌 *Comandos de repartidor:*\n"
                    . "/entregado - Confirmar entrega\n"
                    . "/cancelar-entrega - Cancelar entrega",
                    'Markdown'
                );
                break;

            default:
                $this->searchProductByName($telegram, $chatId, $text);
                break;
        }
    }

    // ==========================================
    // MANEJO DE DELIVERY (UBICACIÓN EN VIVO)
    // ==========================================

    /**
     * Manejar la ubicación en vivo del delivery
     */
    protected function handleDeliveryLocation($telegram, $chatId, $location)
    {
        $buyId = $this->userStates[$chatId]['buy_id'] ?? null;

        if (!$buyId) {
            $telegram->sendMessage($chatId, "❌ No hay pedido activo.");
            return;
        }

        if (!$location) {
            return;
        }

        $buy = Buy::with('delivery')->find($buyId);

        if (!$buy) {
            $telegram->sendMessage($chatId, "❌ Pedido no encontrado.");
            unset($this->userStates[$chatId]);
            return;
        }

        // Actualizar ubicación del delivery en el pedido
        $buy->update([
            'delivery_latitude' => $location->getLatitude(),
            'delivery_longitude' => $location->getLongitude(),
            'delivery_last_location_at' => now(),
        ]);

        // Enviar ubicación al cliente
        $clientChatId = $buy->client;
        if ($clientChatId) {
            try {
                $telegram->sendLocation(
                    $clientChatId,
                    $location->getLatitude(),
                    $location->getLongitude(),
                    null,
                    null,
                    null,
                    null,
                    "🚴‍♂️ *Tu repartidor está en camino!*\n📍 Ubicación actualizada.",
                    'Markdown'
                );
            } catch (\Exception $e) {
                Log::error("Error enviando ubicación al cliente: " . $e->getMessage());
            }
        }

        $this->info("📍 Ubicación del delivery actualizada para pedido #{$buyId}");
    }

    /**
     * Iniciar entrega desde el botón del delivery
     */
    protected function handleDeliveryStart($telegram, $chatId, $buyId)
    {
        $delivery = Delivery::where('user_telegram', $chatId)
            ->where('status', 1)
            ->first();

        if (!$delivery) {
            $telegram->sendMessage(
                $chatId,
                "❌ No tienes permiso para iniciar esta entrega.",
                'Markdown'
            );
            return;
        }

        $buy = Buy::where('id', $buyId)
            ->where('delivery_id', $delivery->id)
            ->first();

        if (!$buy) {
            $telegram->sendMessage(
                $chatId,
                "❌ Pedido no encontrado o no asignado a ti.",
                'Markdown'
            );
            return;
        }

        $buy->update([
            'status' => '1',
        ]);

        $this->userStates[$chatId]['state'] = 'delivery_tracking';
        $this->userStates[$chatId]['buy_id'] = $buy->id;

        $telegram->sendMessage(
            $chatId,
            "🚀 *¡Entrega iniciada!*\n\n"
            . "📦 Pedido #{$buy->id}\n"
            . "📍 *Comparte tu ubicación en vivo para que el cliente te siga.*\n\n"
            . "📌 *Comandos disponibles:*\n"
            . "/entregado - Confirmar entrega\n"
            . "/cancelar-entrega - Cancelar entrega\n\n"
            . "⚠️ *Importante:* La ubicación se actualizará automáticamente mientras la compartas.",
            'Markdown'
        );

        $clientChatId = $buy->client;
        if ($clientChatId) {
            $telegram->sendMessage(
                $clientChatId,
                "🚴‍♂️ *¡Tu pedido #{$buy->id} está en camino!*\n\n"
                . "El repartidor {$delivery->name} ha iniciado la entrega.\n"
                . "Recibirás su ubicación en tiempo real.",
                'Markdown'
            );
        }

        $replyMarkup = new ReplyKeyboardMarkup(
            [
                [['text' => '📍 Compartir ubicación en vivo', 'request_location' => true]]
            ],
            true,
            true
        );

        $telegram->sendMessage(
            $chatId,
            "📍 *Comparte tu ubicación en vivo*\n\n"
            . "Presiona el botón de abajo para que el cliente pueda seguir tu recorrido.",
            'Markdown',
            false,
            null,
            $replyMarkup
        );
    }

    /**
     * Confirmar entrega del pedido (comando /entregado)
     */
    protected function confirmDelivery($telegram, $chatId)
    {
        $buyId = $this->userStates[$chatId]['buy_id'] ?? null;

        if (!$buyId) {
            $telegram->sendMessage(
                $chatId,
                "❌ No hay pedido activo para confirmar entrega.",
                'Markdown'
            );
            return;
        }

        $buy = Buy::with('delivery')->find($buyId);

        if (!$buy) {
            $telegram->sendMessage($chatId, "❌ Pedido no encontrado.");
            unset($this->userStates[$chatId]);
            return;
        }

        $delivery = Delivery::where('user_telegram', $chatId)
            ->where('status', 1)
            ->first();

        if (!$delivery || $buy->delivery_id != $delivery->id) {
            $telegram->sendMessage(
                $chatId,
                "❌ No tienes permiso para confirmar esta entrega.",
                'Markdown'
            );
            return;
        }

        $buy->update([
            'status' => '2',
            'delivered_at' => now(),
        ]);

        unset($this->userStates[$chatId]);

        $telegram->sendMessage(
            $chatId,
            "✅ *¡Pedido #{$buy->id} entregado!*\n\n"
            . "👍 ¡Excelente trabajo!",
            'Markdown',
            false,
            null,
            new ReplyKeyboardRemove()
        );

        $clientChatId = $buy->client;
        if ($clientChatId) {
            $telegram->sendMessage(
                $clientChatId,
                "✅ *¡Tu pedido #{$buy->id} ha sido entregado!*\n\n"
                . "✨ *¡Gracias por tu compra!* 🙏\n"
                . "Esperamos verte pronto. ¡Disfruta tu pedido! 🍽️",
                'Markdown'
            );
        }

        $this->info("✅ Pedido #{$buyId} entregado por delivery ID {$delivery->id}");
    }

    /**
     * Cancelar entrega en curso
     */
    protected function cancelDelivery($telegram, $chatId)
    {
        $buyId = $this->userStates[$chatId]['buy_id'] ?? null;

        if (!$buyId) {
            $telegram->sendMessage($chatId, "❌ No hay pedido activo.");
            return;
        }

        $buy = Buy::find($buyId);

        if ($buy) {
            $buy->update([
                'status' => '-1',
                'cancel_reason' => 'Cancelado por el repartidor',
            ]);
        }

        unset($this->userStates[$chatId]);

        $telegram->sendMessage(
            $chatId,
            "⛔ *Entrega cancelada*",
            'Markdown',
            false,
            null,
            new ReplyKeyboardRemove()
        );

        if ($buy && $buy->client) {
            $telegram->sendMessage(
                $buy->client,
                "⛔ *Tu pedido #{$buy->id} ha sido cancelado*\n\n"
                . "El repartidor no pudo completar la entrega.\n"
                . "Contacta al restaurante para más información.",
                'Markdown'
            );
        }
    }

    // ==========================================
    // CALLBACKS
    // ==========================================

    protected function handleCallback($telegram, $update)
    {
        $callback = $update->getCallbackQuery();
        $chatId = $callback->getMessage()->getChat()->getId();
        $data = $callback->getData();

        $this->info("Callback: {$data}");

        if (str_starts_with($data, 'iniciar_entrega_')) {
            $buyId = str_replace('iniciar_entrega_', '', $data);
            $this->handleDeliveryStart($telegram, $chatId, $buyId);
            $telegram->answerCallbackQuery($callback->getId());
            return;
        }

        switch ($data) {
            case 'ver_menu':
                $this->showMenu($telegram, $chatId);
                break;

            case 'platos':
                $this->showProducts($telegram, $chatId, Product::CATEGORY_PLATE, '🍽️ Platos del Día');
                break;

            case 'Bebidas':
            case 'liquidos':
                $this->showProducts($telegram, $chatId, Product::CATEGORY_LIQUID, '🥤 Bebidas del Día');
                break;

            case 'ver_carrito':
                $this->showCart($telegram, $chatId);
                break;

            case 'vaciar_carrito':
                unset($this->userStates[$chatId]['cart']);
                $telegram->sendMessage($chatId, "🗑️ Carrito vaciado.");
                $this->showMenu($telegram, $chatId);
                break;

            case 'proceder_pago':
                $this->processCheckout($telegram, $chatId);
                break;

            case 'delivery_si':
                $this->askLocation($telegram, $chatId);
                break;

            case 'delivery_no':
                $this->finishOrder($telegram, $chatId, false);
                break;

            default:
                if (str_starts_with($data, 'comprar_')) {
                    $productId = str_replace('comprar_', '', $data);
                    $this->askQuantity($telegram, $chatId, $productId);
                }
                break;
        }

        $telegram->answerCallbackQuery($callback->getId());
    }

    // ==========================================
    // REGISTRO DE REPARTIDOR
    // ==========================================

    protected function registerDelivery($telegram, $chatId, string $text): void
    {
        $parts = explode(' ', $text);

        if (count($parts) < 2 || empty(trim($parts[1]))) {
            $telegram->sendMessage(
                $chatId,
                "⚠️ *Por favor proporciona tu código de repartidor.*\nEjemplo: `/repartidor CODIGO123`",
                'Markdown'
            );
            return;
        }

        $code = trim($parts[1]);

        $delivery = Delivery::where('code', $code)->first();

        if (!$delivery) {
            $telegram->sendMessage(
                $chatId,
                "❌ *Código inválido.* Por favor verifica el código asignado por administración.",
                'Markdown'
            );
            return;
        }

        if (!empty($delivery->user_telegram) && $delivery->user_telegram != $chatId) {
            $telegram->sendMessage(
                $chatId,
                "⚠️ Este código ya se encuentra vinculado a otra cuenta de Telegram.",
                'Markdown'
            );
            return;
        }

        $delivery->update([
            'user_telegram' => (string) $chatId,
            'status'        => 1,
        ]);

        $mensajeExito = "✅ *¡Bienvenido/a, {$this->escapeMarkdown($delivery->name)} {$this->escapeMarkdown($delivery->lastname)}!*\n\n"
                      . "Tu cuenta de repartidor ha sido activada correctamente.\n"
                      . "Recibirás avisos por este chat cuando se te asigne un nuevo pedido.\n\n"
                      . "📌 *Comandos de repartidor:*\n"
                      . "/entregado - Confirmar entrega\n"
                      . "/cancelar-entrega - Cancelar entrega";

        $telegram->sendMessage($chatId, $mensajeExito, 'Markdown');
        $this->info("🚴 Repartidor registrado: {$delivery->name} {$delivery->lastname} (ChatID: {$chatId})");
    }

    // ==========================================
    // MENÚ Y PRODUCTOS
    // ==========================================

    protected function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
    }

    protected function getTodayProductsQuery()
    {
        $today = Carbon::today()->toDateString();

        return Product::whereHas('dailyAvailabilities', function ($query) use ($today) {
            $query->whereDate('date', $today)
                  ->where('stock', '>', 0);
        })->with(['dailyAvailabilities' => function ($query) use ($today) {
            $query->whereDate('date', $today);
        }]);
    }

    protected function showMenu($telegram, $chatId)
    {
        $products = $this->getTodayProductsQuery()->get();

        if ($products->isEmpty()) {
            $telegram->sendMessage(
                $chatId,
                "😔 *Lo sentimos*\n\nNo hay productos disponibles hoy. Por favor, vuelve más tarde.",
                'Markdown'
            );
            return;
        }

        $plates = $products->where('category', Product::CATEGORY_PLATE);
        $liquids = $products->where('category', Product::CATEGORY_LIQUID);

        $message = "🍽️ *Menú del Día*\n\n";

        if ($plates->isNotEmpty()) {
            $message .= "*Platos:*\n";
            foreach ($plates as $product) {
                $name = $this->escapeMarkdown($product->name);
                $message .= "• {$name} - Bs. {$product->price}\n";
            }
            $message .= "\n";
        }

        if ($liquids->isNotEmpty()) {
            $message .= "*Bebidas:*\n";
            foreach ($liquids as $product) {
                $name = $this->escapeMarkdown($product->name);
                $message .= "• {$name} - Bs. {$product->price}\n";
            }
        }

        $message .= "\nLos pagos son por QR";

        $keyboard = [];
        if ($plates->isNotEmpty()) {
            $keyboard[] = [['text' => '🍽️ Ver Platos', 'callback_data' => 'platos']];
        }
        if ($liquids->isNotEmpty()) {
            $keyboard[] = [['text' => '🥤 Ver Bebidas', 'callback_data' => 'liquidos']];
        }

        if (!empty($this->userStates[$chatId]['cart'])) {
            $keyboard[] = [['text' => '🛒 Ver mi Carrito', 'callback_data' => 'ver_carrito']];
        }

        $replyMarkup = !empty($keyboard) ? new InlineKeyboardMarkup($keyboard) : null;

        $telegram->sendMessage($chatId, $message, 'Markdown', false, null, $replyMarkup);
    }

    protected function showProducts($telegram, $chatId, $category, $title)
    {
        $products = $this->getTodayProductsQuery()
            ->where('category', $category)
            ->get();

        if ($products->isEmpty()) {
            $telegram->sendMessage(
                $chatId,
                "😔 No hay productos disponibles en esta categoría para hoy."
            );
            return;
        }

        $telegram->sendMessage($chatId, $title);

        foreach ($products as $product) {
            $name = $this->escapeMarkdown($product->name);
            $desc = $this->escapeMarkdown($product->description ?? '');

            $text = "*{$name}*\n"
                  . "💰 *Precio:* Bs. {$product->price}\n";

            if (!empty($desc)) {
                $text .= "📝 *Descripción:* {$desc}\n";
            }

            $replyMarkup = new InlineKeyboardMarkup([
                [['text' => '🛒 Añadir al Carrito', 'callback_data' => 'comprar_' . $product->id]]
            ]);

            if ($product->image && file_exists(storage_path('app/public/' . $product->image))) {
                $telegram->sendPhoto(
                    $chatId,
                    new \CURLFile(storage_path('app/public/' . $product->image)),
                    $text,
                    null,
                    $replyMarkup,
                    false,
                    'Markdown'
                );
            } else {
                $telegram->sendMessage($chatId, $text, 'Markdown', false, null, $replyMarkup);
            }
        }

        $telegram->sendMessage(
            $chatId,
            "🔄 *Opciones del Menú*",
            'Markdown',
            false,
            null,
            new InlineKeyboardMarkup([
                [['text' => '📋 Ver Menú Completo', 'callback_data' => 'ver_menu']],
                [['text' => '🛒 Ver Carrito', 'callback_data' => 'ver_carrito']]
            ])
        );
    }

    protected function showProduct($telegram, $chatId, $productId)
    {
        $product = $this->getTodayProductsQuery()
            ->where('id', $productId)
            ->first();

        if (!$product) {
            $telegram->sendMessage(
                $chatId,
                "❌ Producto no encontrado o no disponible para hoy."
            );
            return;
        }

        $todayAvailability = $product->dailyAvailabilities->first();
        $stock = $todayAvailability ? $todayAvailability->stock : 0;

        $name = $this->escapeMarkdown($product->name);
        $desc = $this->escapeMarkdown($product->description ?? '');

        $text = "*{$name}*\n\n"
              . "💰 *Precio:* Bs. {$product->price}\n";

        if ($desc) {
            $text .= "📝 *Descripción:* {$desc}\n";
        }

        $text .= "\n🍽️ *Categoría:* " . ($product->category === Product::CATEGORY_PLATE ? 'Plato' : 'Bebida');
        $text .= "\n📦 *Stock hoy:* {$stock}";

        $replyMarkup = new InlineKeyboardMarkup([
            [['text' => '🛒 Añadir al Carrito', 'callback_data' => 'comprar_' . $product->id]]
        ]);

        if ($product->image && file_exists(storage_path('app/public/' . $product->image))) {
            $telegram->sendPhoto(
                $chatId,
                new \CURLFile(storage_path('app/public/' . $product->image)),
                $text,
                null,
                $replyMarkup,
                false,
                'Markdown'
            );
        } else {
            $telegram->sendMessage($chatId, $text, 'Markdown', false, null, $replyMarkup);
        }
    }

    protected function searchProductByName($telegram, $chatId, $text)
    {
        $products = $this->getTodayProductsQuery()
            ->where('name', 'like', '%' . $text . '%')
            ->get();

        if ($products->isEmpty()) {
            $telegram->sendMessage(
                $chatId,
                "❓ No encontré ningún producto disponible hoy con ese nombre. Usa /menu para ver los productos del día."
            );
            return;
        }

        if ($products->count() === 1) {
            $product = $products->first();
            $this->showProduct($telegram, $chatId, $product->id);
            return;
        }

        $message = "🔍 *Encontré varios productos disponibles hoy:*\n\n";
        $keyboard = [];

        foreach ($products as $product) {
            $nameEscaped = $this->escapeMarkdown($product->name);
            $message .= "• {$nameEscaped} - Bs. {$product->price}\n";
            $keyboard[] = [['text' => $product->name, 'callback_data' => 'comprar_' . $product->id]];
        }

        $message .= "\nSelecciona cuál quieres añadir:";
        $replyMarkup = new InlineKeyboardMarkup($keyboard);

        $telegram->sendMessage(
            $chatId,
            $message,
            'Markdown',
            false,
            null,
            $replyMarkup
        );
    }

    // ==========================================
    // CARRITO DE COMPRAS
    // ==========================================

    protected function askQuantity($telegram, $chatId, $productId)
    {
        $product = $this->getTodayProductsQuery()
            ->where('id', $productId)
            ->first();

        if (!$product) {
            $telegram->sendMessage(
                $chatId,
                "❌ Producto no encontrado o no disponible para hoy."
            );
            return;
        }

        $todayAvailability = $product->dailyAvailabilities->first();
        $stock = $todayAvailability ? $todayAvailability->stock : 0;

        if ($stock <= 0) {
            $telegram->sendMessage(
                $chatId,
                "😔 *Lo siento, no hay stock disponible para {$this->escapeMarkdown($product->name)} hoy.*",
                'Markdown'
            );
            return;
        }

        $this->userStates[$chatId]['state'] = 'awaiting_quantity';
        $this->userStates[$chatId]['pending_product'] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $stock
        ];

        $nameEscaped = $this->escapeMarkdown($product->name);

        $telegram->sendMessage(
            $chatId,
            "*{$nameEscaped}*\n\n"
            . "💰 *Precio:* Bs. {$product->price}\n"
            . "✍️ *¿Cuántas unidades quieres agregar al carrito?*\n"
            . "Escribe un número (ej: 2) o /cancelar.",
            'Markdown'
        );
    }

    protected function handleQuantity($telegram, $chatId, $text)
    {
        $pending = $this->userStates[$chatId]['pending_product'] ?? null;

        if (!$pending) {
            return;
        }

        if (trim($text) === '/cancelar') {
            unset($this->userStates[$chatId]['state'], $this->userStates[$chatId]['pending_product']);
            $telegram->sendMessage($chatId, "✅ Operación cancelada. Usa /menu para continuar.");
            return;
        }

        if (!is_numeric(trim($text)) || (int)$text <= 0) {
            $telegram->sendMessage(
                $chatId,
                "❌ Por favor, escribe un número entero válido (ej: 1, 2) o /cancelar."
            );
            return;
        }

        $quantity = (int)$text;

        if ($pending['stock'] < $quantity) {
            $telegram->sendMessage(
                $chatId,
                "😔 *Lo siento, solo hay {$pending['stock']} unidades disponibles.*\n\n"
                . "Por favor escribe una cantidad menor o /cancelar.",
                'Markdown'
            );
            return;
        }

        if (!isset($this->userStates[$chatId]['cart'])) {
            $this->userStates[$chatId]['cart'] = [];
        }

        $productId = $pending['id'];

        if (isset($this->userStates[$chatId]['cart'][$productId])) {
            $this->userStates[$chatId]['cart'][$productId]['quantity'] += $quantity;
        } else {
            $this->userStates[$chatId]['cart'][$productId] = [
                'id' => $pending['id'],
                'name' => $pending['name'],
                'price' => $pending['price'],
                'quantity' => $quantity
            ];
        }

        unset($this->userStates[$chatId]['state'], $this->userStates[$chatId]['pending_product']);

        $telegram->sendMessage(
            $chatId,
            "✅ *¡Añadido al carrito!* ({$quantity}x {$this->escapeMarkdown($pending['name'])})",
            'Markdown'
        );

        $this->showCart($telegram, $chatId);
    }

    protected function showCart($telegram, $chatId)
    {
        $cart = $this->userStates[$chatId]['cart'] ?? [];

        if (empty($cart)) {
            $telegram->sendMessage(
                $chatId,
                "🛒 *Tu carrito está vacío.*\n\nUsa /menu para ver las opciones disponibles.",
                'Markdown'
            );
            return;
        }

        $message = "🛒 *Tu Carrito de Compras:*\n\n";
        $total = 0;

        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            $name = $this->escapeMarkdown($item['name']);
            $message .= "• {$name} x {$item['quantity']} = Bs. {$subtotal}\n";
        }

        $message .= "\n💰 *Total a pagar:* Bs. {$total}";

        $keyboard = new InlineKeyboardMarkup([
            [['text' => '➕ Agregar más productos', 'callback_data' => 'ver_menu']],
            [['text' => '💳 Proceder al Pago', 'callback_data' => 'proceder_pago']],
            [['text' => '🗑️ Vaciar Carrito', 'callback_data' => 'vaciar_carrito']]
        ]);

        $telegram->sendMessage($chatId, $message, 'Markdown', false, null, $keyboard);
    }

    // ==========================================
    // PROCESO DE PAGO Y COMPROBANTE
    // ==========================================

    protected function processCheckout($telegram, $chatId)
    {
        $cart = $this->userStates[$chatId]['cart'] ?? [];

        if (empty($cart)) {
            $telegram->sendMessage($chatId, "⚠️ Tu carrito está vacío.");
            return;
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += ($item['price'] * $item['quantity']);
        }

        $this->userStates[$chatId]['state'] = 'awaiting_voucher';
        $this->userStates[$chatId]['total'] = $total;

        $this->sendQr($telegram, $chatId, $total);
    }

    protected function sendQr($telegram, $chatId, $total)
    {
        $cart = $this->userStates[$chatId]['cart'] ?? [];

        $summary = "📋 *Resumen del Pedido:*\n";
        foreach ($cart as $item) {
            $name = $this->escapeMarkdown($item['name']);
            $summary .= "• {$name} x {$item['quantity']}\n";
        }

        $telegram->sendMessage(
            $chatId,
            "✅ *Pedido en proceso*\n\n"
            . $summary
            . "• *Total:* Bs. {$total}\n\n"
            . "💳 *Escanea el QR para pagar:*",
            'Markdown'
        );

        $qrPath = storage_path('app/public/qr-pago.webp');
        if (!file_exists($qrPath)) {
            $qrPath = storage_path('app/public/qr-pago.png');
        }

        if (file_exists($qrPath)) {
            $telegram->sendPhoto(
                $chatId,
                new \CURLFile($qrPath),
                "QR de pago - Total: Bs. {$total}"
            );
        }

        $telegram->sendMessage(
            $chatId,
            "📸 *Por favor, envía una FOTO del comprobante de pago.*\n"
            . "Escribe /cancelar si deseas cancelar.",
            'Markdown'
        );
    }

    protected function receiveVoucher($telegram, $chatId, $text, $photos)
    {
        if (trim($text) === '/cancelar') {
            unset($this->userStates[$chatId]);
            $telegram->sendMessage(
                $chatId,
                "✅ Pedido cancelado. Usa /menu para ver más opciones."
            );
            return;
        }

        if (!empty($photos)) {
            try {
                $photo = end($photos);
                $fileId = $photo->getFileId();

                $file = $telegram->getFile($fileId);
                $filePath = $file->getFilePath();

                $token = env('TELEGRAM_BOT_TOKEN');
                $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";

                $fileContents = file_get_contents($fileUrl);
                $fileName = 'vouchers/' . uniqid('voucher_') . '.jpg';

                Storage::disk('public')->put($fileName, $fileContents);

                $this->userStates[$chatId]['comprobante'] = $fileName;

                $telegram->sendMessage($chatId, "✅ Comprobante recibido correctamente.");

                $this->askDelivery($telegram, $chatId);
                return;

            } catch (\Exception $e) {
                $this->error("Error guardando comprobante: " . $e->getMessage());
                $telegram->sendMessage(
                    $chatId,
                    "❌ Ocurrió un problema al procesar la foto. Por favor, reenvíala."
                );
                return;
            }
        }

        $telegram->sendMessage(
            $chatId,
            "📸 Necesitamos la *foto del comprobante de pago* para continuar.\nPor favor, adjunta una imagen o escribe /cancelar.",
            'Markdown'
        );
    }

    // ==========================================
    // UBICACIÓN Y ENTREGA
    // ==========================================

    protected function askDelivery($telegram, $chatId)
    {
        $this->userStates[$chatId]['state'] = 'awaiting_delivery_type';

        $replyMarkup = new InlineKeyboardMarkup([
            [
                ['text' => '🚴‍♂️ Delivery', 'callback_data' => 'delivery_si'],
                ['text' => '🏪 Recoger en local', 'callback_data' => 'delivery_no']
            ]
        ]);

        $telegram->sendMessage(
            $chatId,
            "🛵 *¿Cómo deseas recibir tu pedido?*",
            'Markdown',
            false,
            null,
            $replyMarkup
        );
    }

    protected function askLocation($telegram, $chatId)
    {
        $this->userStates[$chatId]['state'] = 'awaiting_location';

        $replyMarkup = new ReplyKeyboardMarkup(
            [
                [['text' => '📍 Compartir mi ubicación', 'request_location' => true]]
            ],
            true,
            true
        );

        $telegram->sendMessage(
            $chatId,
            "📍 *Envíanos tu ubicación*\n\nPresiona el botón de abajo para enviar tu ubicación actual o envía un mapa desde Telegram.",
            'Markdown',
            false,
            null,
            $replyMarkup
        );
    }

    protected function handleLocation($telegram, $chatId, $location, $text)
    {
        if (trim($text) === '/cancelar') {
            unset($this->userStates[$chatId]);
            $telegram->sendMessage(
                $chatId,
                "✅ Pedido cancelado.",
                'Markdown',
                false,
                null,
                new ReplyKeyboardRemove()
            );
            return;
        }

        if (!$location) {
            $telegram->sendMessage(
                $chatId,
                "⚠️ Por favor, usa el botón *📍 Compartir mi ubicación*.",
                'Markdown'
            );
            return;
        }

        $this->userStates[$chatId]['latitude'] = $location->getLatitude();
        $this->userStates[$chatId]['longitude'] = $location->getLongitude();

        $this->finishOrder($telegram, $chatId, true);
    }

    protected function finishOrder($telegram, $chatId, bool $isDelivery)
    {
        $state = $this->userStates[$chatId] ?? null;

        if (!$state) {
            return;
        }

        $cart = $state['cart'] ?? [];

        if (empty($cart)) {
            $telegram->sendMessage($chatId, "❌ Ocurrió un error con el carrito.");
            unset($this->userStates[$chatId]);
            return;
        }

        try {
            $assignedDelivery = null;

            DB::transaction(function () use ($chatId, $state, $cart, $isDelivery, &$assignedDelivery) {
                if ($isDelivery) {
                    $assignedDelivery = Delivery::where('status', 1)
                        ->whereNotNull('user_telegram')
                        ->inRandomOrder()
                        ->first();
                }

                $buy = Buy::create([
                    'comprobante' => $state['comprobante'] ?? 'sin_comprobante.jpg',
                    'client'      => (string) $chatId,
                    'type'        => $isDelivery ? 'delivery' : 'restaurant',
                    'status'      => '0',
                    'latitude'    => $isDelivery ? ($state['latitude'] ?? null) : null,
                    'longitude'   => $isDelivery ? ($state['longitude'] ?? null) : null,
                ]);

                foreach ($cart as $item) {
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        BuyDetail::create([
                            'buy_id'     => $buy->id,
                            'product_id' => $item['id'],
                            'price'      => $item['price'],
                            'quantity'   => $item['quantity'],
                        ]);
                    }

                    $product = $this->getTodayProductsQuery()->where('id', $item['id'])->first();
                    if ($product) {
                        $todayAvailability = $product->dailyAvailabilities->first();
                        if ($todayAvailability) {
                            $todayAvailability->decrement('stock', $item['quantity']);
                        }
                    }
                }
            });

            $summary = "📋 *Detalle del Pedido:*\n";
            foreach ($cart as $item) {
                $name = $this->escapeMarkdown($item['name']);
                $summary .= "• {$name} x {$item['quantity']} (Bs. " . ($item['price'] * $item['quantity']) . ")\n";
            }

            if ($isDelivery && $assignedDelivery) {
                $mapsUrl = "https://www.google.com/maps?q={$state['latitude']},{$state['longitude']}";

                $msgDelivery = "🚴‍♂️ *¡NUEVO PEDIDO ASIGNADO!*\n\n"
                             . $summary . "\n"
                             . "💰 *Total a Cobrar:* Bs. {$state['total']}\n"
                             . "📍 *Ubicación de Entrega:* [Ver en Google Maps]({$mapsUrl})\n\n"
                             . "Por favor ponte en marcha lo antes posible.";

                try {
                    $telegram->sendMessage($assignedDelivery->user_telegram, $msgDelivery, 'Markdown');
                } catch (\Exception $e) {
                    $this->error("Error al notificar al repartidor ID {$assignedDelivery->id}: " . $e->getMessage());
                }
            }

            $deliveryMessage = $isDelivery
                ? "🚴‍♂️ *Modalidad:* Delivery\n📍 *Ubicación:* Lat {$state['latitude']}, Long {$state['longitude']}"
                : "🏪 *Modalidad:* Recoger en local";

            $telegram->sendMessage(
                $chatId,
                "🎉 *¡Pedido Confirmado y Registrado!*\n\n"
                . $summary . "\n"
                . "• *Total:* Bs. {$state['total']}\n"
                . "{$deliveryMessage}\n\n"
                . "Tu pedido ya está registrado en nuestro sistema. ¡Muchas gracias por tu compra!",
                'Markdown',
                false,
                null,
                new ReplyKeyboardRemove()
            );

        } catch (\Exception $e) {
            $this->error("Error al registrar el pedido en DB: " . $e->getMessage());
            $telegram->sendMessage(
                $chatId,
                "❌ Ocurrió un error al guardar tu pedido en el sistema. Por favor, ponte en contacto con soporte."
            );
        }

        unset($this->userStates[$chatId]);
    }
}