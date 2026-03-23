<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->double('stock_actual_weight')->nullable()->after('out_weight');
            $table->double('stock_counted_weight')->nullable()->after('stock_actual_weight');
            $table->double('stock_diff_weight')->nullable()->after('stock_counted_weight');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn([
                'stock_actual_weight',
                'stock_counted_weight',
                'stock_diff_weight',
            ]);
        });
    }
};
