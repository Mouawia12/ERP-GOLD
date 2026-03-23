<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_vouchers', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'credit_card', 'bank_transfer'])
                ->default('cash')
                ->after('type');
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('to_account_id')
                ->constrained('bank_accounts')
                ->nullOnDelete();
            $table->string('reference_no')
                ->nullable()
                ->after('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('financial_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn(['payment_method', 'reference_no']);
        });
    }
};
