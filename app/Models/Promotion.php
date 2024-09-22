<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'description',
        'discount_percentage',
        'discount_amount',
        'start_date',
        'end_date',
        'promotion_type',
    ];
}
