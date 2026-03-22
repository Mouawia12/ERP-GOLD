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
        Schema::create('gold_price_histories', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('source_currency')->nullable();
            $table->double('ounce_price');
            $table->double('ounce_14_price');
            $table->double('ounce_18_price');
            $table->double('ounce_21_price');
            $table->double('ounce_22_price');
            $table->double('ounce_24_price');
            $table->string('currency');
            $table->json('payload')->nullable();
            $table->foreignId('synced_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_price_histories');
    }
};
