<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'code',
        'status',
        'name',
        'lastname',
        'cellphone',
        'user_telegram',
    ];

    public function buys()
    {
        return $this->hasMany(Buy::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->lastname}";
    }

    public function getStatusTextAttribute()
    {
        return $this->status ? 'Activo' : 'Inactivo';
    }

    public function getCompletedBuysCountAttribute()
    {
        return $this->buys()->where('status', 2)->count();
    }

    public function getPendingBuysCountAttribute()
    {
        return $this->buys()->where('status', 0)->count();
    }

    public function getInProgressBuysCountAttribute()
    {
        return $this->buys()->where('status', 1)->count();
    }

    public function getTotalBuysCountAttribute()
    {
        return $this->buys()->count();
    }
}