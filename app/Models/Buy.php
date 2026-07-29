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
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function details()
    {
        return $this->hasMany(BuyDetail::class);
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            0 => 'Pendiente',
            1 => 'En camino',
            2 => 'Entregado',
            default => 'Desconocido',
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
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
}