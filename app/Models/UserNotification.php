<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    // Thêm các cột có thể gán giá trị hàng loạt
    protected $fillable = [
        'notification_id',
        'user_id',
        'status',
        'read_status',
        'important', 
    ];
}