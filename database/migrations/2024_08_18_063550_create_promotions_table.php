<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsTable extends Migration
{
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->text('description');
            $table->integer('discount_percentage')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('promotion_type');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotions');
    }
}
