<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); 
            $table->unsignedBigInteger('id_promotion')->nullable();
            $table->date('order_date');
            $table->decimal('total_price', 10, 2);
            $table->string('status');
            $table->string('payment_method');
            $table->integer('sale')->default(false);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_promotion')->references('id')->on('promotions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
