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
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->double('initial_cost_per_gram')->default(0);
            $table->double('average_cost_per_gram')->default(0);
            $table->double('current_cost_per_gram')->default(0);
            $table->string('barcode')->nullable();
            $table->double('weight')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_sold')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
