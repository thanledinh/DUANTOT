<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable(); // Cho phép giá trị null
            $table->string('type')->nullable(); // Cho phép giá trị null
            $table->string('size')->nullable(); // Cho phép giá trị null
            $table->string('flavor')->nullable(); // Cho phép giá trị null
            $table->integer('stock_quantity');
            $table->boolean('sale')->default(false);
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
}