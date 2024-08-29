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
        Schema::create('b_o_s', function (Blueprint $table) {
            $table->id();

            // $table->string('affiliate_username');
            // $table->string('currency');
            // $table->integer('nsu')->default(0);
            // $table->integer('ftd')->default(0);
            // $table->integer('active_player')->default(0);
            // $table->decimal('total_deposit',15,2)->default(0.00);
            // $table->decimal('total_withdrawal', 15,2)->default(0.00);
            // $table->decimal('total_turnover', 15,2)->default(0.00);
            // $table->decimal('profit_and_loss', 15,2)->default(0.00);
            // $table->decimal('total_bonus', 15,2)->default(0.00);

            $table->string('affiliate_username')->nullable();
            $table->string('currency')->nullable();
            $table->string('nsu')->nullable();
            $table->string('ftd')->nullable();
            $table->string('active_player')->nullable();
            $table->string('total_deposit')->nullable();
            $table->string('total_withdrawal')->nullable();
            $table->string('total_turnover')->nullable();
            $table->string('profit_and_loss')->nullable();
            $table->string('total_bonus')->nullable();
            $table->date('target_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_o_s');
    }
};
