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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->nullable();
            $table->string('serial')->nullable();
            $table->foreignId('financial_year')->nullable()->constrained('financial_years')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->string('bill_client_phone')->nullable();
            $table->string('bill_client_name')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->enum('type', ['sale', 'sale_return', 'purchase', 'purchase_return', 'initial_quantities', 'stock_settlements'])->default('sale');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->enum('sale_type', ['simplified', 'standard'])->default('simplified');
            $table->enum('purchase_type', config('settings.purchase_types'))->nullable();
            $table->foreignId('purchase_carat_type_id')->nullable()->constrained('gold_carat_types')->onDelete('cascade');
            $table->string('supplier_bill_number')->nullable();
            $table->longText('notes')->nullable();
            $table->enum('payment_type', ['cash', 'credit_card', 'bank_transfer'])->default('cash');
            $table->date('date');
            $table->time('time');
            $table->double('lines_total')->default(0);
            $table->double('discount_total')->default(0);
            $table->double('lines_total_after_discount')->default(0);
            $table->double('taxes_total')->default(0);
            $table->double('net_total')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
