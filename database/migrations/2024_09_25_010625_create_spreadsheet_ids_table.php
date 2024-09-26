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
        Schema::create('spreadsheet_ids', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->unique(); // This is your identifier key
            $table->string('spread_id');     // Store the spread ID
            $table->string('platform');      // Store the platform name
            $table->string('brand');      // Store the platform name
            $table->string('currencyType');      // Store the platform name
            $table->string('index');      // Store the platform name
            $table->boolean('is_active');      // Store the platform name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_ids');
    }
};
