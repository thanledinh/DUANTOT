<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $table = 'stock_history';

    protected $fillable = [
        'variant_id',
        'old_quantity',
        'new_quantity',
        'changed_at',
        'changed_by',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}