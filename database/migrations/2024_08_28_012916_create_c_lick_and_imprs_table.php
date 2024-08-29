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
        Schema::create('c_lick_and_imprs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b_o_s_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('creative_id')->default(0);
            $table->string('imprs')->default(0);
            $table->string('clicks')->default(0);
            $table->string('spending')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_lick_and_imprs');
    }
};
