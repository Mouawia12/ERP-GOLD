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
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safe_account')->nullable()->constrained('accounts');
            $table->foreignId('bank_account')->nullable()->constrained('accounts');
            $table->foreignId('sales_account')->nullable()->constrained('accounts');
            $table->foreignId('return_sales_account')->nullable()->constrained('accounts');
            $table->foreignId('stock_account_crafted')->nullable()->constrained('accounts');
            $table->foreignId('stock_account_scrap')->nullable()->constrained('accounts');
            $table->foreignId('stock_account_pure')->nullable()->constrained('accounts');
            $table->foreignId('made_account')->nullable()->constrained('accounts');
            $table->foreignId('cost_account_crafted')->nullable()->constrained('accounts');
            $table->foreignId('cost_account_scrap')->nullable()->constrained('accounts');
            $table->foreignId('cost_account_pure')->nullable()->constrained('accounts');
            $table->foreignId('reverse_profit_account')->nullable()->constrained('accounts');
            $table->foreignId('profit_account')->nullable()->constrained('accounts');
            $table->foreignId('sales_tax_account')->nullable()->constrained('accounts');
            $table->foreignId('purchase_tax_account')->nullable()->constrained('accounts');
            $table->foreignId('sales_tax_excise_account')->nullable()->constrained('accounts');
            $table->foreignId('supplier_default_account')->nullable()->constrained('accounts');
            $table->foreignId('clients_account')->nullable()->constrained('accounts');
            $table->foreignId('suppliers_account')->nullable()->constrained('accounts');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_settings');
    }
};
