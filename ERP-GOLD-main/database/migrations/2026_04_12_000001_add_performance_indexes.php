<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('type');
            $table->index('sale_type');
            $table->index('date');
            $table->index(['type', 'sale_type', 'branch_id']);
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->index('item_id');
            $table->index('gold_carat_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['sale_type']);
            $table->dropIndex(['date']);
            $table->dropIndex(['type', 'sale_type', 'branch_id']);
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropIndex(['item_id']);
            $table->dropIndex(['gold_carat_id']);
        });
    }
};
