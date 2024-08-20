<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('product_id'); 
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity'); 
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('size')->nullable(); 
            $table->string('flavor')->nullable(); 
            $table->string('type')->nullable(); 
            $table->string('image')->nullable(); 
            $table->timestamps(); 

           
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
}
