<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_karat_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->date('bill_date');
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('gold_carat_type_id')->constrained('gold_carat_types');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('out_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('in_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedInteger('lines_count')->default(0);
            $table->double('total_from_weight')->default(0);
            $table->double('total_to_weight')->default(0);
            $table->double('total_value')->default(0);
            $table->timestamps();

            $table->index(['from_branch_id', 'to_branch_id']);
            $table->index('bill_date');
        });

        Schema::create('branch_karat_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('branch_karat_transfers')->cascadeOnDelete();
            $table->foreignId('from_carat_id')->constrained('gold_carats')->cascadeOnDelete();
            $table->double('from_weight');
            $table->foreignId('to_carat_id')->constrained('gold_carats')->cascadeOnDelete();
            $table->double('to_weight');
            $table->double('unit_cost')->default(0);
            $table->double('line_value')->default(0);
            $table->string('line_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_karat_transfer_lines');
        Schema::dropIfExists('branch_karat_transfers');
    }
};
