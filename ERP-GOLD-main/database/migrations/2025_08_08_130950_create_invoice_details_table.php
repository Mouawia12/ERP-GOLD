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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('invoice_details')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('cascade');
            $table->double('no_metal')->default(0);
            $table->enum('no_metal_type', ['fixed', 'percent'])->default('fixed');
            $table->foreignId('unit_id')->nullable()->constrained('item_units')->onDelete('cascade');
            $table->foreignId('gold_carat_id')->nullable()->constrained('gold_carats')->onDelete('cascade');
            $table->foreignId('gold_carat_type_id')->nullable()->constrained('gold_carat_types')->onDelete('cascade');
            $table->date('date');
            $table->double('in_quantity')->default(0);
            $table->double('out_quantity')->default(0);
            $table->double('in_weight')->default(0);
            $table->double('out_weight')->default(0);
            $table->double('unit_cost')->default(0);
            $table->double('labor_cost_per_gram')->default(0);
            $table->double('unit_price')->default(0);
            $table->double('unit_discount')->default(0);
            $table->double('unit_tax')->default(0);
            $table->double('unit_tax_rate')->default(0);
            $table->foreignId('unit_tax_id')->nullable()->constrained('taxes')->onDelete('cascade');
            $table->double('line_total')->default(0);
            $table->double('line_discount')->default(0);
            $table->double('line_tax')->default(0);
            $table->double('net_total')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
