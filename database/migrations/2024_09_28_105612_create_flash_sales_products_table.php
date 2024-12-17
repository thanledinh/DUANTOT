<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlashSalesProductsTable extends Migration
{
    public function up()
    {
        Schema::create('flash_sales_products', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('flash_sale_id'); 
            $table->unsignedBigInteger('product_id'); 
            $table->integer('discount_percentage')->nullable(); 
            $table->integer('quantity_limit_per_customer')->nullable(); 
            $table->integer('stock_quantity'); 
            $table->timestamps(); 
            $table->foreign('flash_sale_id')->references('id')->on('flash_sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('flash_sales_products');
    }
}
