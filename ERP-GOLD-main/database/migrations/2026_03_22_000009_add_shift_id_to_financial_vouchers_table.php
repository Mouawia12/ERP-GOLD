<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_vouchers', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('description')->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });
    }
};
