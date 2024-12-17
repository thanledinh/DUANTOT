<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingTable extends Migration
{
    public function up()
    {
        Schema::create('shipping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('shipping_address');
            $table->string('city');
            $table->string('district');
            $table->string('ward');
            $table->string('shipping_method');
            $table->decimal('shipping_cost', 10, 2);
            $table->string('shipping_status');
            $table->string('full_name'); 
            $table->string('email'); 
            $table->string('phone'); 
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping');
    }
}
