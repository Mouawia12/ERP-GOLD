<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique('branches_commercial_register_unique');
            $table->dropUnique('branches_tax_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unique('commercial_register');
            $table->unique('tax_number');
        });
    }
};
