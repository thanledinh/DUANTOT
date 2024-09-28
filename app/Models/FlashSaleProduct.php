<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    protected $fillable = ['flash_sale_id', 'product_id', 'discount_price', 'original_price', 'quantity_limit_per_customer', 'stock_quantity'];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
