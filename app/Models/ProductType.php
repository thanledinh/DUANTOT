<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    // Đặt tên bảng nếu khác với quy tắc mặc định
    protected $table = 'product_types';

    // Các thuộc tính có thể gán hàng loạt
    protected $fillable = [
        'type_name', // Tên loại sản phẩm
    ];

    // Nếu bạn cần thêm các phương thức, bạn có thể thêm ở đây
    // Ví dụ: phương thức để lấy danh sách sản phẩm thuộc loại này
    public function products()
    {
        return $this->hasMany(Product::class); // Giả sử bạn có model Product
    }
}
