<?php

namespace App\Models;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];



    public function store()
    {
        return $this->belongsTo(Store::class);
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
