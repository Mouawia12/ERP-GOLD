<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('sale_mode')->default('repeatable')->after('inventory_classification');
        });

        DB::table('items')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('item_units')
                    ->whereColumn('item_units.item_id', 'items.id')
                    ->where('item_units.is_default', false);
            })
            ->update([
                'sale_mode' => 'single',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('sale_mode');
        });
    }
};
