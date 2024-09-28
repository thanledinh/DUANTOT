<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpsTable extends Migration
{
    public function up()
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id(); // Khóa chính
            $table->string('email'); // Email nhận OTP
            $table->string('otp'); // Mã OTP
            $table->timestamp('created_at')->useCurrent(); // Thời gian tạo
            $table->timestamp('expires_at')->nullable(); // Thay đổi để cho phép NULL
        });
    }

    public function down()
    {
        Schema::dropIfExists('otps');
    }
}