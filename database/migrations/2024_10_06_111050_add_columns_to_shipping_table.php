<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToShippingTable extends Migration
{
    public function up()
    {
        Schema::table('shipping', function (Blueprint $table) {
            $table->string('full_name'); 
            $table->string('email'); 
            $table->string('city'); 
            $table->string('district'); 
            $table->string('phone'); 
        });
    }

    public function down()
    {
        Schema::table('shipping', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'email', 'city', 'district', 'phone']);
        });
    }
}
