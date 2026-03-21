<?php

namespace Database\Seeders;

use App\Models\GoldCaratType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CaratTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        GoldCaratType::truncate();
        GoldCaratType::create([
            'title' => ['ar' => 'مشغول', 'en' => 'Crafted'],
            'key' => 'crafted',
        ]);
        GoldCaratType::create([
            'title' => ['ar' => 'كسر', 'en' => 'Scrap'],
            'key' => 'scrap',
        ]);
        GoldCaratType::create([
            'title' => ['ar' => 'صافي', 'en' => 'Pure'],
            'key' => 'pure',
        ]);
        Schema::enableForeignKeyConstraints();
    }
}
