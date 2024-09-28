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
        'minimum_order_value', // giá trị tối thiểu của đơn hàng
        'applicable_products', // sản phẩm/danh mục áp dụng
        'min_quantity', // số lượng sản phẩm tối thiểu
        'free_shipping', // miễn phí vận chuyển
        'is_member_only', // chỉ dành cho khách hàng thành viên
    ];

    // kiểm tra nếu mã khuyến mãi hợp lệ cho đơn hàng
    public function isValidForOrder($order)
    {
        if ($this->is_member_only && !$order->user->isMember()) {
            return false;
        }

        if ($this->minimum_order_value && $order->total < $this->minimum_order_value) {
            return false;
        }

        if ($this->applicable_products && !$order->hasApplicableProduct($this->applicable_products)) {
            return false;
        }

        if ($this->min_quantity && !$order->hasMinQuantity($this->min_quantity)) {
            return false;
        }

        return true;
    }
}
