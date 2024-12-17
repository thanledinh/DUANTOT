<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'product_id',
        'order_id',
        'variant_id',
        'quantity',
        'price',
        'sale'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }    
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
    public function flashSaleProduct()
    {
        return $this->belongsTo(FlashSaleProduct::class, 'product_id', 'product_id');
    }
}
