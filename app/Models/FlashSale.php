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
        return $this->hasMany(FlashSaleProduct::class);
    }
}
