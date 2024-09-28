<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $fillable = ['start_time', 'end_time', 'discount_percentage', 'max_discount', 'status'];
    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class, 'flash_sale_id');
    }
}
