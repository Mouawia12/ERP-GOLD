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
        Schema::table('gold_prices', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('currency');
            $table->string('source_currency')->nullable()->after('source');
            $table->json('meta')->nullable()->after('source_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gold_prices', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_currency', 'meta']);
        });
    }
};
