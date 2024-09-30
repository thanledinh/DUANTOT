<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description', 'type', 'brand_id', 'category_id', 'image', 'barcode'];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}
