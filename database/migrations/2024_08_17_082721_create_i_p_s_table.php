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
        Schema::create('i_p_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('ip_address');
            $table->boolean('status')->default(0);//0 - means authorized, 1 - means not
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i_p_s');
    }
};
