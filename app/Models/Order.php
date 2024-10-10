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
        'sale',
        'note',
        'order_date' 
    ];    

    // Mối quan hệ với OrderItem
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // Mối quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mối quan hệ với Shipping: Mỗi đơn hàng có một bản ghi Shipping
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'order_id');
    }

    // Mối quan hệ với Payment: Mỗi đơn hàng có một bản ghi Payment
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
}
