<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTypesTable extends Migration
{
    public function up()
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id(); // Mã loại sản phẩm (Primary Key)
            $table->string('type_name'); // Tên loại sản phẩm
            $table->timestamps(); // Thời gian tạo và cập nhật
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_types');
    }
}