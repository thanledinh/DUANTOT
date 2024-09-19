<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeImageColumnInCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->longText('image')->change(); // Thay đổi kiểu dữ liệu của cột image thành LONGTEXT
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image', 255)->change(); // Quay lại kiểu dữ liệu cũ nếu cần
        });
    }
}