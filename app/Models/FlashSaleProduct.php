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
        return $this->belongsTo(Product::class);
    }
}
