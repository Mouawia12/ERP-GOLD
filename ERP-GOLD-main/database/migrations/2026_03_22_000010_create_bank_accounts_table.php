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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('iban')->nullable();
            $table->string('account_number')->nullable();
            $table->string('terminal_name')->nullable();
            $table->string('device_code')->nullable();
            $table->boolean('supports_credit_card')->default(true);
            $table->boolean('supports_bank_transfer')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
