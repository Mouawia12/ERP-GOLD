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
        Schema::create('gold_carats', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('label');
            $table->foreignId('tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->string('transform_factor');
            $table->boolean('is_pure')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_carats');
    }
};
