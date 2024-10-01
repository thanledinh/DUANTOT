<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'product_id']; // Chỉ định 'product_id' thay vì 'variant_id'

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        // Sử dụng quan hệ 'product' để truy xuất 'product'
        return $this->belongsTo(Product::class, 'product_id'); // Chỉ định rõ ràng quan hệ với bảng products
    }
}