<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gold_carat_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('key', ['crafted', 'scrap', 'pure'])->default('crafted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_carat_types');
    }
};
