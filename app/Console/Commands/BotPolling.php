<?php

namespace App\Console\Commands;

use Telegram\Bot\Api;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use \App\Models\Product;
use Illuminate\Foundation\Console\ApiInstallCommand;
use TelegramBot\Api\BotApi;

#[Signature('app:bot-polling')]
#[Description('Command description')]
class BotPolling extends Command
{

protected $signature = 'telegram:poll';
    protected $description = 'Iniciar bot en modo polling para desarrollo local';

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
                    // ✅ CORREGIDO: usar getUpdateId() en lugar de getId()
                    $lastUpdateId = $update->getUpdateId();

                    // Manejar callback query (botones)
                    if ($update->getCallbackQuery()) {
                        $this->handleCallbackPolling($telegram, $update);
                        continue;
                    }

                    $message = $update->getMessage();

                    if (!$message) continue;

                    $chatId = $message->getChat()->getId();
                    $text = $message->getText() ?? '';
                    $firstName = $message->getChat()->getFirstName() ?? 'Usuario';

                    $this->info("📩 Mensaje de {$firstName}: {$text}");

                    $this->handleCommandPolling($telegram, $chatId, $text, $firstName);
                }

            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                sleep(5);
            }
        }
    }

    protected function handleCommandPolling($telegram, $chatId, $text, $firstName)
    {
        switch (trim($text)) {
            case '/start':
                $telegram->sendMessage(
                     $chatId,
                    "*¡Bienvenido al Restaurante, {$firstName}!*\n\nUsa /menu para ver los nuestros platos y bebidas disponibles.",
                );
                break;

            case '/menu':
                $this->sendMenuPolling($telegram, $chatId);
                break;

            case '/ayuda':
            case '/help':
                $telegram->sendMessage(
                    $chatId,
                    "❓ *Ayuda*\n\n/menu - Ver menú\n/pedido - Hacer pedido (próximamente)",
                );
                break;

            default:
                $telegram->sendMessage(
                    $chatId,
                    "❓ Comando no reconocido. Usa /menu para ver los productos disponibles.",
                );
                break;
        }
    }

    protected function handleCallbackPolling($telegram, $update)
    {
        $callback = $update->callbackQuery;
        $chatId = $callback->getMessage()->getChat()->getId();
        $data = $callback->getData();

        $this->info("🔄 Callback: {$data}");

        switch ($data) {
            case 'ver_menu':
                $this->sendMenuPolling($telegram, $chatId);
                break;

            case 'platos':
                $this->sendProductsByCategoryPolling($telegram, $chatId, 'plate', '🍽️ Platos del Día');
                break;

            case 'liquidos':
                $this->sendProductsByCategoryPolling($telegram, $chatId, 'liquid', '🥤 Bebidas del Día');
                break;

            default:
                if (str_starts_with($data, 'producto_')) {
                    $productId = str_replace('producto_', '', $data);
                    $this->sendProductDetailPolling($telegram, $chatId, $productId);
                }
                break;
        }

        // Responder al callback para quitar el "loading"
        $telegram->answerCallbackQuery([
            'callback_query_id' => $callback->getId(),
        ]);
    }

    protected function sendMenuPolling($telegram, $chatId)
    {
        // Obtener productos disponibles
        $products =  Product::where('available', true)->get();

        if ($products->isEmpty()) {
            $telegram->sendMessage([
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
                $message .= "• {$product->name} - Bs/ {$product->price}\n";
            }
            $message .= "\n";
        }

        if ($liquids->isNotEmpty()) {
            $message .= "*Bebidas:*\n";
            foreach ($liquids as $product) {
                $message .= "• {$product->name} - Bs/ {$product->price}\n";
            }
        }
        
        $message .= "\n Los pagos son por qr";
        
        // Crear teclado inline
        $keyboard = [];

        if ($plates->isNotEmpty()) {
            $keyboard[] = [['text' => '🍽️ Ver Platos', 'callback_data' => 'platos']];
        }

        if ($liquids->isNotEmpty()) {
            $keyboard[] = [['text' => '🥤 Ver Bebidas', 'callback_data' => 'liquidos']];
        }

        $telegram->sendMessage(
            $chatId,
            $message,
        );
    }

    protected function sendProductsByCategoryPolling($telegram, $chatId, $category, $title)
    {
        $products = Product::where('available', true)
            ->where('category', $category)
            ->get();

        if ($products->isEmpty()) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "😔 No hay productos disponibles en esta categoría.",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        // Título
        $telegram->sendMessage([
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

            if ($product->image) {
                $telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => asset('storage/' . $product->image),
                    'caption' => $text,
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown'
                ]);
            }
        }

        // Botón para volver
        $telegram->sendMessage([
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

    protected function sendProductDetailPolling($telegram, $chatId, $productId)
    {
        $product = Product::where('available', true)
            ->where('id', $productId)
            ->first();

        if (!$product) {
            $telegram->sendMessage([
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
            $telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => asset('storage/' . $product->image),
                'caption' => $text,
                'parse_mode' => 'Markdown'
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown'
            ]);
        }
    }

}
