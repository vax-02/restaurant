<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buy extends Model
{
    protected $fillable = [
        'comprobante',
        'delivery_id',
        'client',
        'type',
        'status',
        'latitude',
        'longitude',
        'cancel_reason',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class,'delivery_id');
    }

    public function details()
    {
        return $this->hasMany(BuyDetail::class);
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            -1 => 'Anulado',
            0 => 'Pendiente',
            1 => 'En camino',
            2 => 'Entregado',
            default => 'Desconocido',
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            -1 => 'danger',
            0 => 'warning',
            1 => 'info',
            2 => 'success',
            default => 'secondary',
        };
    }

    public function getTotalAttribute()
    {
        return $this->details->sum(function ($detail) {
            return $detail->price;
        });
    }

    /**
     * Cancel the buy, restoring stock and setting status to -1.
     */
    public function cancel(string $reason = 'Comprobante inválido'): void
    {
        // Restore stock for each detail
        foreach ($this->details as $detail) {
            $product = $detail->product;
            if ($product) {
                $availability = $product->todayAvailability()->first();
                if ($availability) {
                    $availability->increment('stock');
                }
            }
        }

        $this->update([
            'status' => '-1',
            'cancel_reason' => $reason,
        ]);
    }
}