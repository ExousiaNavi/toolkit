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
        Schema::create('f_t_d_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b_o_s_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('keywords')->default(0);
            $table->string('currency')->default(0);
            $table->string('registration_time')->default(0);
            $table->string('first_deposit_time')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f_t_d_s');
    }
};
