<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('subscriber_id')
                ->nullable()
                ->after('id')
                ->constrained('subscribers')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscriber_id')
                ->nullable()
                ->after('id')
                ->constrained('subscribers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscriber_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscriber_id');
        });
    }
};
