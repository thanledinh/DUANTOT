<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsTable extends Migration
{
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->text('description');
            $table->integer('discount_percentage')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('minimum_order_value', 10, 2)->nullable();  // giá trị tối thiểu của đơn hàng
            $table->string('applicable_products')->nullable();  // danh sách các sản phẩm/danh mục áp dụng
            $table->integer('min_quantity')->nullable();  // số lượng tối thiểu của sản phẩm
            $table->boolean('free_shipping')->default(false);  // miễn phí vận chuyển
            $table->boolean('is_member_only')->default(false);  // chỉ dành cho khách hàng thành viên
            $table->date('start_date');
            $table->date('end_date');
            $table->string('promotion_type');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotions');
    }
}
