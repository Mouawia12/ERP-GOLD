<?php

namespace Database\Seeders;

use App\Models\GoldCarat;
use App\Models\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CaratSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        GoldCarat::truncate();
        GoldCarat::create([
            'title' => ['ar' => 'عيار 18', 'en' => 'Carat 18'],
            'label' => 'C18',
            'tax_id' => Tax::where('zatca_code', 'S')->first()->id,
            'transform_factor' => 0.8571,
        ]);
        GoldCarat::create([
            'title' => ['ar' => 'عيار 21', 'en' => 'Carat 21'],
            'label' => 'C21',
            'tax_id' => Tax::where('zatca_code', 'S')->first()->id,
            'transform_factor' => 1,
        ]);
        GoldCarat::create([
            'title' => ['ar' => 'عيار 22', 'en' => 'Carat 22'],
            'label' => 'C22',
            'tax_id' => Tax::where('zatca_code', 'S')->first()->id,
            'transform_factor' => 1.047,
        ]);
        GoldCarat::create([
            'title' => ['ar' => 'عيار 24', 'en' => 'Carat 24'],
            'label' => 'C24',
            'tax_id' => Tax::where('zatca_code', 'O')->first()->id,
            'transform_factor' => 1.1428,
            'is_pure' => true,
        ]);
        Schema::enableForeignKeyConstraints();
    }
}
