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
        Schema::create('branch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->double('sale_price_per_gram')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'item_id']);
        });

        $now = now();
        $rows = DB::table('items')
            ->select('id as item_id', 'branch_id')
            ->whereNotNull('branch_id')
            ->get()
            ->map(fn ($item) => [
                'branch_id' => $item->branch_id,
                'item_id' => $item->item_id,
                'is_active' => true,
                'is_visible' => true,
                'sale_price_per_gram' => null,
                'published_by_user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if (! empty($rows)) {
            DB::table('branch_items')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_items');
    }
};
