<?php

namespace App\Observers;

use App\Models\Buy;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class BuyObserver
{
    public function updated(Buy $buy): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = $buy->client;

        if (!$token) {
            Log::warning("Observer Buy #{$buy->id}: Falta TELEGRAM_BOT_TOKEN.");
            return;
        }

        $telegram = new BotApi($token);

        try {
            // ==========================================
            // 1. NOTIFICACIONES AL CLIENTE
            // ==========================================
            if (($buy->wasChanged('status') || $buy->isDirty('status')) && $chatId) {
                $status = (string) $buy->status;

                // Status -1: Anulado / Cancelado
                if ($status === '-1') {
                    $motivo = $buy->cancel_reason ?? 'Comprobante inválido';
                    $mensaje = "❌ *Pedido #{$buy->id} Anulado*\n\n"
                             . "Lo sentimos, tu pedido ha sido cancelado.\n"
                             . "*Motivo:* {$motivo}\n\n"
                             . "Si tienes dudas, por favor contáctate con el restaurante.";

                    $telegram->sendMessage($chatId, $mensaje, 'Markdown');
                    Log::info("Notificación de cancelación enviada a cliente {$chatId}");
                }

                // Status 1: Aprobado / En camino / Listo
                if ($status === '1') {
                    $deliveryName = $buy->delivery ? $buy->delivery->name : 'un repartidor';
                    $phone = $buy->delivery ? $buy->delivery->cellphone : 'N/A';

                    if ($buy->type === 'delivery') {
                        $mensaje = "🚴‍♂️ *¡Tu pedido #{$buy->id} está en camino!*\n\n"
                                 . "Comprobante verificado. Tu repartidor asignado es: *{$deliveryName}*.\n"
                                 . "Puedes comunicarte con él/ella al: *{$phone}*\n\n"
                                 . "📍 *Sigue a tu repartidor en tiempo real:*\n"
                                 . "Recibirás su ubicación en vivo mientras se acerca.";
                    } else {
                        $mensaje = "👨‍🍳 *¡Tu pedido #{$buy->id} está listo!*\n\n"
                                 . "Comprobante verificado. Puedes pasar a recogerlo por el local.";
                    }

                    $telegram->sendMessage($chatId, $mensaje, 'Markdown');
                    Log::info("Notificación de pedido aprobado enviada a cliente {$chatId}");
                }

                // Status 2: ENTREGADO (Agradecimiento al cliente)
                if ($status === '2') {
                    $mensaje = "✅ *¡Pedido #{$buy->id} Entregado!*\n\n"
                             . "✨ *¡Gracias por tu compra!* 🙏\n"
                             . "Esperamos verte pronto. ¡Disfruta tu pedido! 🍽️";

                    $telegram->sendMessage($chatId, $mensaje, 'Markdown');
                    Log::info("Notificación de entrega y agradecimiento enviada a cliente {$chatId}");
                }
            }

            // ==========================================
            // 2. NOTIFICACIÓN AL REPARTIDOR (Cuando el admin le asigna el pedido)
            // ==========================================
            if (($buy->wasChanged('delivery_id') || $buy->isDirty('delivery_id')) && $buy->delivery_id) {
                $delivery = $buy->delivery;

                if ($delivery && $delivery->user_telegram) {
                    $deliveryChatId = $delivery->user_telegram;
                    $mapsUrl = "https://www.google.com/maps?q={$buy->latitude},{$buy->longitude}";

                    $mensajeDelivery = "🚴‍♂️ *¡Nuevo Pedido Asignado! (#{$buy->id})*\n\n"
                                     . "📍 *Ubicación del cliente:* [Ver en Google Maps]({$mapsUrl})\n"
                                     . "📍 *Coordenadas:* `{$buy->latitude}, {$buy->longitude}`\n\n"
                                     . "📌 *Instrucciones:*\n"
                                     . "1. Presiona el botón para iniciar la entrega\n"
                                     . "2. Comparte tu ubicación en vivo\n"
                                     . "3. Confirma la entrega con /entregado";

                    $keyboard = new InlineKeyboardMarkup([
                        [
                            ['text' => '🚀 Iniciar Entrega', 'callback_data' => "iniciar_entrega_{$buy->id}"]
                        ]
                    ]);

                    $telegram->sendMessage($deliveryChatId, $mensajeDelivery, 'Markdown', false, null, $keyboard);
                    Log::info("Notificación enviada al repartidor {$delivery->name} para el pedido #{$buy->id}");
                }
            }

            // ==========================================
            // 3. ENVIAR UBICACIÓN DEL DELIVERY AL CLIENTE (cuando se actualiza)
            // ==========================================
            if ($buy->wasChanged('delivery_latitude') && $buy->delivery_latitude && $buy->client) {
                $clientChatId = $buy->client;
                $telegram->sendLocation(
                    $clientChatId,
                    $buy->delivery_latitude,
                    $buy->delivery_longitude,
                    null,
                    null,
                    null,
                    null,
                    "🚴‍♂️ *Ubicación actual de tu repartidor*\n📦 Pedido #{$buy->id}",
                    'Markdown'
                );
            }

        } catch (\Exception $e) {
            Log::error("Error al enviar mensaje de Telegram (BuyObserver #{$buy->id}): " . $e->getMessage());
        }
    }
}