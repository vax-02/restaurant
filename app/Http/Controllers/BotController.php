<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class BotController extends Controller
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * Manejar las actualizaciones del webhook
     */
    public function webhook(Request $request)
    {
        try {
            $update = $this->telegram->getWebhookUpdate();

            if (!$update) {
                return response()->json(['status' => 'no-update']);
            }

            // Si es callback query (botón presionado)
            if ($update->callbackQuery) {
                $this->handleCallback($update);
                return response()->json(['status' => 'ok']);
            }

            $message = $update->getMessage();

            if (!$message) {
                return response()->json(['status' => 'no-message']);
            }

            $chatId = $message->getChat()->getId();
            $text = $message->getText() ?? '';
            $firstName = $message->getChat()->getFirstName() ?? 'Usuario';

            Log::info('Mensaje recibido', [
                'chat_id' => $chatId,
                'text' => $text,
                'user' => $firstName
            ]);

            // Procesar comandos
            $this->handleCommand($chatId, $text, $firstName);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Error en webhook: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Manejar comandos del bot
     */
    protected function handleCommand($chatId, $text, $firstName)
    {
        $text = trim($text);

        switch ($text) {
            case '/start':
                $this->sendWelcome($chatId, $firstName);
                break;

            case '/menu':
                $this->sendMenu($chatId);
                break;

            case '/ayuda':
            case '/help':
                $this->sendHelp($chatId);
                break;

            case '/pedido':
                $this->sendOrderInfo($chatId);
                break;

            default:
                $this->sendUnknownCommand($chatId);
                break;
        }
    }

    /**
     * Manejar callbacks (botones inline)
     */
    protected function handleCallback($update)
    {
        $callback = $update->callbackQuery;
        $chatId = $callback->getMessage()->getChat()->getId();
        $data = $callback->getData();

        Log::info('Callback recibido', [
            'chat_id' => $chatId,
            'data' => $data
        ]);

        switch ($data) {
            case 'ver_menu':
                $this->sendMenu($chatId);
                break;

            case 'nuevo_pedido':
                $this->sendOrderInfo($chatId);
                break;

            case 'platos':
                $this->sendProductsByCategory($chatId, 'plate', '🍽️ Platos del Día');
                break;

            case 'liquidos':
                $this->sendProductsByCategory($chatId, 'liquid', '🥤 Bebidas del Día');
                break;

            default:
                // Si es selección de producto (producto_X)
                if (str_starts_with($data, 'producto_')) {
                    $productId = str_replace('producto_', '', $data);
                    $this->sendProductDetail($chatId, $productId);
                }
                break;
        }

        // Responder al callback
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callback->getId(),
        ]);
    }

    /**
     * Enviar mensaje de bienvenida
     */
    protected function sendWelcome($chatId, $firstName)
    {
        $message = "🍽️ *¡Bienvenido al Restaurante, {$firstName}!*\n\n"
                 . "Somos un restaurante con los mejores platos y bebidas.\n\n"
                 . "📋 *Comandos disponibles:*\n"
                 . "/menu - Ver el menú del día\n"
                 . "/pedido - Hacer un pedido\n"
                 . "/ayuda - Ayuda\n\n"
                 . "¿Qué deseas hacer hoy?";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Enviar el menú completo con botones
     */
    protected function sendMenu($chatId)
    {
        // Obtener productos disponibles
        $products = Product::where('available', true)->get();

        if ($products->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "😔 *Lo sentimos*\n\nNo hay productos disponibles hoy. Por favor, vuelve más tarde.",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        $plates = $products->where('category', Product::CATEGORY_PLATE);
        $liquids = $products->where('category', Product::CATEGORY_LIQUID);

        $message = "🍽️ *Menú del Día*\n\n";

        if ($plates->isNotEmpty()) {
            $message .= "*Platos:*\n";
            foreach ($plates as $product) {
                $message .= "• {$product->name} - S/ {$product->price}\n";
            }
            $message .= "\n";
        }

        if ($liquids->isNotEmpty()) {
            $message .= "*Bebidas:*\n";
            foreach ($liquids as $product) {
                $message .= "• {$product->name} - S/ {$product->price}\n";
            }
        }

        $message .= "\n📌 *Selecciona una categoría para más detalles:*";

        // Crear teclado inline con categorías
        $keyboard = [];

        if ($plates->isNotEmpty()) {
            $keyboard[] = [['text' => '🍽️ Ver Platos', 'callback_data' => 'platos']];
        }

        if ($liquids->isNotEmpty()) {
            $keyboard[] = [['text' => '🥤 Ver Bebidas', 'callback_data' => 'liquidos']];
        }

        $keyboard[] = [['text' => '🛒 Hacer Pedido', 'callback_data' => 'nuevo_pedido']];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

    /**
     * Enviar productos por categoría
     */
    protected function sendProductsByCategory($chatId, $category, $title)
    {
        $products = Product::where('available', true)
            ->where('category', $category)
            ->get();

        if ($products->isEmpty()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "😔 No hay productos disponibles en esta categoría.",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        // Enviar título
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $title,
            'parse_mode' => 'Markdown'
        ]);

        // Enviar cada producto
        foreach ($products as $product) {
            $text = "*{$product->name}*\n"
                  . "💰 *Precio:* S/ {$product->price}\n";

            if ($product->description) {
                $text .= "📝 *Descripción:* {$product->description}\n";
            }

            $text .= "\n🔢 *ID:* {$product->id}";

            // Enviar con imagen si tiene
            if ($product->image) {
                $this->telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => asset('storage/' . $product->image),
                    'caption' => $text,
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown'
                ]);
            }
        }

        // Botón para volver al menú
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "🔄 *Volver al menú principal*",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '📋 Ver Menú Completo', 'callback_data' => 'ver_menu']]
                ]
            ])
        ]);
    }

    /**
     * Enviar detalle de un producto específico
     */
    protected function sendProductDetail($chatId, $productId)
    {
        $product = Product::where('available', true)
            ->where('id', $productId)
            ->first();

        if (!$product) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Producto no encontrado o no disponible.",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        $text = "*{$product->name}*\n\n"
              . "💰 *Precio:* S/ {$product->price}\n";

        if ($product->description) {
            $text .= "📝 *Descripción:* {$product->description}\n";
        }

        $text .= "\n🍽️ *Categoría:* " . ($product->category === 'plate' ? 'Plato' : 'Bebida');

        if ($product->image) {
            $this->telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => asset('storage/' . $product->image),
                'caption' => $text,
                'parse_mode' => 'Markdown'
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ]);
        }
    }

    /**
     * Enviar ayuda
     */
    protected function sendHelp($chatId)
    {
        $message = "❓ *Ayuda del Bot*\n\n"
                 . "📋 *Comandos disponibles:*\n"
                 . "/start - Iniciar conversación\n"
                 . "/menu - Ver el menú del día\n"
                 . "/pedido - Hacer un pedido\n"
                 . "/ayuda - Ver esta ayuda\n\n"
                 . "📌 *¿Cómo pedir?*\n"
                 . "1. Usa /menu para ver los productos\n"
                 . "2. Selecciona la categoría que te interesa\n"
                 . "3. Elige el producto\n"
                 . "4. Usa /pedido para comenzar tu pedido\n\n"
                 . "¿Tienes alguna duda? ¡Pregúntanos!";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Información sobre pedidos
     */
    protected function sendOrderInfo($chatId)
    {
        $message = "🛒 *Hacer un Pedido*\n\n"
                 . "Para hacer un pedido, sigue estos pasos:\n\n"
                 . "1️⃣ Usa /menu para ver los productos disponibles\n"
                 . "2️⃣ Elige los productos que deseas\n"
                 . "3️⃣ Envía el ID del producto y la cantidad\n"
                 . "4️⃣ Confirma tu pedido\n\n"
                 . "📌 *Ejemplo:*\n"
                 . "`/agregar 3 2` - Agrega 2 unidades del producto ID 3\n\n"
                 . "⚠️ *Próximamente*: Carrito de compras interactivo";

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '📋 Ver Menú', 'callback_data' => 'ver_menu']]
                ]
            ])
        ]);
    }

    /**
     * Comando desconocido
     */
    protected function sendUnknownCommand($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "❓ *Comando no reconocido*\n\n"
                    . "Usa estos comandos:\n"
                    . "/start - Iniciar conversación\n"
                    . "/menu - Ver el menú\n"
                    . "/pedido - Hacer un pedido\n"
                    . "/ayuda - Ayuda",
            'parse_mode' => 'Markdown'
        ]);
    }

    // ============================================
    // MÉTODOS PARA CONFIGURAR EL WEBHOOK
    // ============================================

    /**
     * Configurar el webhook
     */
    public function setWebhook()
    {
        try {
            $webhookUrl = env('TELEGRAM_BOT_WEBHOOK_URL');

            if (!$webhookUrl) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'TELEGRAM_BOT_WEBHOOK_URL no configurado en .env'
                ]);
            }

            $response = $this->telegram->setWebhook([
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query']
            ]);

            return response()->json([
                'status' => 'success',
                'webhook' => $webhookUrl,
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar el webhook
     */
    public function deleteWebhook()
    {
        try {
            $response = $this->telegram->removeWebhook();

            return response()->json([
                'status' => 'success',
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del webhook
     */
    public function getWebhookInfo()
    {
        try {
            $response = $this->telegram->getWebhookInfo();

            return response()->json([
                'status' => 'success',
                'webhook_info' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
