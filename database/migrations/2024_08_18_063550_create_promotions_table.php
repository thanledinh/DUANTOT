<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id(); 
            $table->string('code')->unique(); 
            $table->string('description')->nullable(); 
            $table->decimal('discount_percentage', 5, 2)->nullable(); 
            $table->decimal('discount_amount', 10, 2)->nullable(); 
            $table->string('promotion_type'); 
            $table->date('start_date');
            $table->date('end_date')->nullable(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
}
