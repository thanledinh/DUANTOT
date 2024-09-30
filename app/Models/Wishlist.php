<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'variant_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id'); // Thêm tham số 'variant_id' nếu cần
    }

    public function product()
    {
        // Sử dụng quan hệ 'variant' để truy xuất 'product'
        return $this->hasOneThrough(Product::class, ProductVariant::class, 'id', 'id', 'variant_id', 'product_id');
    }
}
