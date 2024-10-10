<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('payment_method');
            $table->decimal('amount', 10, 2); // Số tiền thanh toán
            $table->string('bank_account')->nullable(); // Số tài khoản ngân hàng
            $table->string('transaction_id')->nullable(); // Mã giao dịch
            $table->string('payment_status')->default('pending'); // Trạng thái thanh toán
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
