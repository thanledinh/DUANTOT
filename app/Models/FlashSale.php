<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time', 
        'end_time', 
        'max_discount', 
        'status',
    ];

    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class, 'flash_sale_id');
    }
    public function flashSaleProducts()
    {
        return $this->hasMany(FlashSaleProduct::class, 'flash_sale_id');
    }
    

    // Scopes for filtering Flash Sales
    public function scopeActive($query)
    {
        return $query->where('start_time', '<=', now())
                     ->where('end_time', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_time', '<', now());
    }

    // Check if Flash Sale is active
    public function getIsActiveAttribute()
    {
        $now = now();
        return $this->start_time <= $now && $this->end_time >= $now;
    }
}
