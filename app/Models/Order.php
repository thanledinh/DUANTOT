<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'id_promotion',
        'order_date',
        'quantity',
        'total_price',
        'status',
        'payment_method',
        'sale',
    ];

    protected $dates = ['order_date'];

    /**
     * Relationship with the User table (an order belongs to a user)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with the Product table (if you need to get products related to the order)
     */
    public function product()
    {
        return $this->belongsToMany(Product::class, 'order_products', 'order_id', 'product_id')
                    ->withPivot('quantity', 'price');
    }

    /**
     * Relationship with the Promotion table (if there is a promotion code)
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'id_promotion');
    }
}