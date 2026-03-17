<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'meli_id',
        'title',
        'seller',
        'status',
        'last_sync', // Usado para identificar a última vez que o produto foi atualizado
    ];

    protected $casts = [
        'last_sync' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($product) {
            $product->last_sync = now();
        });
    }


    public function scopeSeller($query, int $seller)
    {
        return $query->where('seller', $seller);
    }

    public function scopeMeliId($query, string $meliId)
    {
        return $query->where('meli_id', $meliId);
    }
}
