<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Danh sách các trường có thể được gán giá trị một cách hàng loạt
    protected $fillable = [
        'user_id', 
        'id_promotion', 
        'order_date', 
        'total_price', 
        'status', 
        'payment_method', 
        'note', 
        'tracking_code',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipping()
    {
        return $this->hasOne(Shipping::class); // Assuming a single shipping info per order
    }

    public function payment()
    {
        return $this->hasOne(Payment::class); // Assuming a single payment info per order
    }
}
