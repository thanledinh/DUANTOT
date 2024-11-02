<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price',
        'stock_quantity',
        'size',
        'flavor',
        'type',
        'image',
        'sale'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
