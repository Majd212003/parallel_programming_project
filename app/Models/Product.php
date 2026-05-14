<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function inventories()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
