<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasFactory;
    protected $fillable = [
        'is_hidden',
        'product_id',
        'user_id',
        'rating',
        'comment',
    ];

        // Thiết lập mối quan hệ với bảng users
        public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }
}
