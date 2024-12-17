<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    use HasFactory;

    // Chỉ định tên bảng trong cơ sở dữ liệu
    protected $table = 'flash_sales_products';

    protected $fillable = [
        'flash_sale_id', 
        'product_id', 
        'discount_percentage', 
        'quantity_limit_per_customer', 
        'stock_quantity',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    

    // Scope to get products in active Flash Sales
    public function scopeInActiveFlashSale($query)
    {
        return $query->whereHas('flashSale', function ($q) {
            $q->active(); // Sử dụng scopeActive từ FlashSale
        });
    }

    // Check if product in Flash Sale is available
    public function getIsAvailableAttribute()
    {
        return $this->stock_quantity > 0;
    }
}
