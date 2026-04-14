<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_settings', function (Blueprint $table) {
            // إضافة حساب الخصم المفقود
            if (! Schema::hasColumn('account_settings', 'sales_discount_account')) {
                $table->foreignId('sales_discount_account')->nullable()->constrained('accounts');
            }
            // حسابات المقتنيات
            $table->foreignId('collectible_sales_account')->nullable()->constrained('accounts');
            $table->foreignId('collectible_return_sales_account')->nullable()->constrained('accounts');
            $table->foreignId('collectible_purchase_account')->nullable()->constrained('accounts');
            $table->foreignId('collectible_purchase_return_account')->nullable()->constrained('accounts');
            $table->foreignId('silver_sales_account')->nullable()->constrained('accounts');
            $table->foreignId('silver_return_sales_account')->nullable()->constrained('accounts');
            $table->foreignId('silver_purchase_account')->nullable()->constrained('accounts');
            $table->foreignId('silver_purchase_return_account')->nullable()->constrained('accounts');
        });
    }

    public function down(): void
    {
        Schema::table('account_settings', function (Blueprint $table) {
            if (Schema::hasColumn('account_settings', 'sales_discount_account')) {
                $table->dropForeign(['sales_discount_account']);
                $table->dropColumn('sales_discount_account');
            }
            $table->dropForeign(['collectible_sales_account']);
            $table->dropForeign(['collectible_return_sales_account']);
            $table->dropForeign(['collectible_purchase_account']);
            $table->dropForeign(['collectible_purchase_return_account']);
            $table->dropForeign(['silver_sales_account']);
            $table->dropForeign(['silver_return_sales_account']);
            $table->dropForeign(['silver_purchase_account']);
            $table->dropForeign(['silver_purchase_return_account']);
            $table->dropColumn([
                'collectible_sales_account',
                'collectible_return_sales_account',
                'collectible_purchase_account',
                'collectible_purchase_return_account',
                'silver_sales_account',
                'silver_return_sales_account',
                'silver_purchase_account',
                'silver_purchase_return_account',
            ]);
        });
    }
};
