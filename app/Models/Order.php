<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'payment_method',
        'sale'
    ];
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
