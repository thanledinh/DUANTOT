<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->integer('old_quantity');
            $table->integer('new_quantity');
            $table->timestamp('changed_at')->useCurrent();
            $table->string('changed_by')->nullable(); // Có thể lưu tên người thay đổi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_history');
    }
}; 