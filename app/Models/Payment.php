<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Danh sách các trường có thể được gán giá trị một cách hàng loạt
    protected $fillable = [
        'order_id', 
        'payment_method', 
        'amount',            // Thêm trường để lưu số tiền thanh toán
        'bank_account',      // Thêm trường để lưu số tài khoản ngân hàng (nếu có)
        'transaction_id',    // Thêm trường để lưu mã giao dịch (nếu có)
        'payment_status',     // Thêm trường để lưu trạng thái thanh toán (pending, completed, failed)
    ];

    // Mối quan hệ với bảng Orders
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
