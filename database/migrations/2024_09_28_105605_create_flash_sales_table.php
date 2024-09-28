<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlashSalesTable extends Migration
{
    public function up()
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->timestamp('start_time')->useCurrent(); 
            $table->timestamp('end_time')->nullable();   
            $table->integer('discount_percentage');
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->boolean('status')->default(true); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flash_sales');
    }
}
