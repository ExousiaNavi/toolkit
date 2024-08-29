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
        Schema::table('b_o_s', function (Blueprint $table) {
            $table->boolean('is_merged')->default(0); //0 means - no
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b_o_s', function (Blueprint $table) {
            $table->dropColumn('is_merged');
        });
    }
};
