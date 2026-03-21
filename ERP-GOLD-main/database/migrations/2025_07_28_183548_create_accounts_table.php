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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('old_id')->nullable();
            $table->string('level')->nullable();
            $table->foreignId('parent_account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->enum('account_type', config('settings.accounts_types'))->default(config('settings.accounts_types')[0]);
            $table->enum('transfer_side', config('settings.transfers_sides'))->default(config('settings.transfers_sides')[0]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
