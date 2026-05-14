<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'is_paid',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'total_price' => 'decimal:2',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
            return $this->hasOne(Payment::class);
    }


}
