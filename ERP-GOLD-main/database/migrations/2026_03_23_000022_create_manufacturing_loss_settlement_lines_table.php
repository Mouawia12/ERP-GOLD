<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manufacturing_loss_settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('parent_detail_id')->nullable()->constrained('invoice_details')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('cascade');
            $table->foreignId('gold_carat_id')->nullable()->constrained('gold_carats')->onDelete('cascade');
            $table->foreignId('gold_carat_type_id')->nullable()->constrained('gold_carat_types')->onDelete('cascade');
            $table->string('settlement_type')->default('natural_loss');
            $table->date('date');
            $table->double('settled_quantity')->default(0);
            $table->double('settled_weight')->default(0);
            $table->double('unit_cost')->default(0);
            $table->double('line_total')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_loss_settlement_lines');
    }
};
