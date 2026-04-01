<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('subscriber_id')->nullable()->after('id')->constrained('subscribers')->nullOnDelete();
            $table->index(['subscriber_id', 'code']);
        });

        Schema::table('account_settings', function (Blueprint $table) {
            $table->foreignId('subscriber_id')->nullable()->after('id')->constrained('subscribers')->nullOnDelete();
            $table->index(['subscriber_id', 'branch_id']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('subscriber_id')->nullable()->after('id')->constrained('subscribers')->nullOnDelete();
            $table->index(['subscriber_id', 'branch_id']);
        });

        $branchSubscriberMap = Schema::getConnection()
            ->table('branches')
            ->whereNotNull('subscriber_id')
            ->pluck('subscriber_id', 'id');

        Schema::getConnection()
            ->table('account_settings')
            ->select(['id', 'branch_id'])
            ->whereNotNull('branch_id')
            ->get()
            ->each(function ($setting) use ($branchSubscriberMap) {
                $subscriberId = $branchSubscriberMap[$setting->branch_id] ?? null;

                if ($subscriberId) {
                    Schema::getConnection()
                        ->table('account_settings')
                        ->where('id', $setting->id)
                        ->update(['subscriber_id' => $subscriberId]);
                }
            });

        Schema::getConnection()
            ->table('bank_accounts')
            ->select(['id', 'branch_id'])
            ->whereNotNull('branch_id')
            ->get()
            ->each(function ($bankAccount) use ($branchSubscriberMap) {
                $subscriberId = $branchSubscriberMap[$bankAccount->branch_id] ?? null;

                if ($subscriberId) {
                    Schema::getConnection()
                        ->table('bank_accounts')
                        ->where('id', $bankAccount->id)
                        ->update(['subscriber_id' => $subscriberId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscriber_id');
        });

        Schema::table('account_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscriber_id');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscriber_id');
        });
    }
};
