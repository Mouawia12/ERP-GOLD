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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code');
            $table->longText('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('gold_carat_id')->nullable()->constrained('gold_carats')->onDelete('cascade');
            $table->foreignId('gold_carat_type_id')->nullable()->constrained('gold_carat_types')->onDelete('cascade');
            $table->double('no_metal')->default(0);
            $table->enum('no_metal_type', ['fixed', 'percent'])->default('fixed');
            $table->double('labor_cost_per_gram')->default(0);
            $table->double('profit_margin_per_gram')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
