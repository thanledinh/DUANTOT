<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); 
            $table->string('name'); 
            $table->text('description')->nullable(); 
            $table->string('type'); 
            $table->string('brand'); 
            $table->unsignedBigInteger('category_id'); 
            $table->string('image')->nullable(); 
            $table->timestamps(); 

           
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}
