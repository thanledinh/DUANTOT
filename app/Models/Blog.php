<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Blog extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',  
        'title',
        'content',
        'image_url'
    ];

    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class);  // Mỗi blog thuộc về 1 người dùng
    }
    public function canUpdate()
    {
        return $this->user_id === Auth::id() || Auth::user()->hasRole('admin');
    }
    public function canDelete()
    {
        return $this->user_id === Auth::id() || Auth::user()->hasRole('admin');
    }
}
