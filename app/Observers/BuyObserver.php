<?php

namespace App\Observers;

use App\Models\Buy;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\BotApi;

class BuyObserver
{

    /**
     * Handle the Buy "created" event.
     */
    public function created(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "updated" event.
     */
    public function updated(Buy $buy): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = $buy->client;

        if (!$token || !$chatId) {
            Log::warning("Observer Buy #{$buy->id}: Falta TELEGRAM_BOT_TOKEN o el cliente no tiene chatId.");
            return;
        }

        $telegram = new BotApi($token);

        try {
            // Verificar si la columna 'status' cambió en esta actualización
            if ($buy->wasChanged('status') || $buy->isDirty('status')) {
                $status = (string) $buy->status;

                // 1. PEDIDO CANCELADO / ANULADO (status = -1)
                if ($status === '-1') {
                    $motivo = $buy->cancel_reason ?? 'Comprobante inválido';
                    
                    $mensaje = "❌ *Pedido #{$buy->id} Anulado*\n\n"
                             . "Lo sentimos, tu pedido ha sido cancelado.\n"
                             . "*Motivo:* {$motivo}\n\n"
                             . "Si tienes dudas, por favor contáctate con el restaurante.";

                    $telegram->sendMessage($chatId, $mensaje, 'Markdown');
                    Log::info("Notificación de cancelación enviada a cliente {$chatId}");
                }

                // 2. PEDIDO APROBADO / EN CAMINO / LISTO (status = 1)
                if ($status === '1') {
                    $deliveryName = $buy->delivery ? $buy->delivery->name : 'un repartidor';
                    if ($buy->type === 'delivery') {
                        $mensaje = "🚴‍♂️ *¡Tu pedido #{$buy->id} está en camino!*\n\n"
                                 . "Comprobante verificado. Tu repartidor asignado es: *{$deliveryName}*. comunicate con el mediante *{$buy->delivery->cellphone}*";
                    } else {
                        $mensaje = "👨‍🍳 *¡Tu pedido #{$buy->id} está listo!*\n\n"
                                 . "Comprobante verificado. Puedes pasar a recogerlo por el local.";
                    }

                    $telegram->sendMessage($chatId, $mensaje, 'Markdown');
                    Log::info("Notificación de pedido aprobado enviada a cliente {$chatId}");
                }

                // 3. PEDIDO ENTREGADO (status = 2)
                if ($status === '2') {
                    $mensaje = "✅ *¡Pedido #{$buy->id} Entregado!*\n\n"
                             . "¡Muchas gracias por tu compra!";

                    $telegram->sendMessage($chatId, $mensaje, 'Markdown');
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al enviar mensaje de Telegram (BuyObserver #{$buy->id}): " . $e->getMessage());
        }
    }
    /**
     * Handle the Buy "deleted" event.
     */
    public function deleted(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "restored" event.
     */
    public function restored(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "force deleted" event.
     */
    public function forceDeleted(Buy $buy): void
    {
        //
    }
}
