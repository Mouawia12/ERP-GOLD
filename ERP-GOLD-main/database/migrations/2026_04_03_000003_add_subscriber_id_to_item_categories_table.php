<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            $table->foreignId('subscriber_id')
                ->nullable()
                ->after('id')
                ->constrained('subscribers')
                ->nullOnDelete();
            $table->index(['subscriber_id', 'code'], 'item_categories_subscriber_code_index');
        });

        $categorySubscribers = DB::table('items')
            ->join('branches', 'branches.id', '=', 'items.branch_id')
            ->whereNotNull('items.category_id')
            ->whereNotNull('branches.subscriber_id')
            ->select('items.category_id', 'branches.subscriber_id')
            ->orderBy('items.category_id')
            ->orderBy('branches.subscriber_id')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => collect($rows)
                ->pluck('subscriber_id')
                ->map(fn ($subscriberId) => (int) $subscriberId)
                ->unique()
                ->values()
                ->all());

        foreach ($categorySubscribers as $categoryId => $subscriberIds) {
            $category = DB::table('item_categories')->where('id', $categoryId)->first();

            if (! $category || $subscriberIds === []) {
                continue;
            }

            $primarySubscriberId = array_shift($subscriberIds);

            DB::table('item_categories')
                ->where('id', $categoryId)
                ->update([
                    'subscriber_id' => $primarySubscriberId,
                    'updated_at' => now(),
                ]);

            foreach ($subscriberIds as $subscriberId) {
                $duplicateCategoryId = DB::table('item_categories')->insertGetId([
                    'subscriber_id' => $subscriberId,
                    'title' => $category->title,
                    'code' => $category->code,
                    'description' => $category->description,
                    'image_url' => $category->image_url,
                    'created_at' => $category->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $itemIds = DB::table('items')
                    ->join('branches', 'branches.id', '=', 'items.branch_id')
                    ->where('items.category_id', $categoryId)
                    ->where('branches.subscriber_id', $subscriberId)
                    ->pluck('items.id');

                DB::table('items')
                    ->whereIn('id', $itemIds)
                    ->update(['category_id' => $duplicateCategoryId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropIndex('item_categories_subscriber_code_index');
            $table->dropConstrainedForeignId('subscriber_id');
        });
    }
};
