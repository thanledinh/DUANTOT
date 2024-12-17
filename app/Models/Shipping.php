<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $table = 'shipping'; 
    
    protected $fillable = [
        'order_id',
        'full_name',
        'email',
        'shipping_address',
        'city',
        'district',
        'ward',
        'phone',
        'shipping_method',
        'shipping_cost',
        'shipping_status'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
