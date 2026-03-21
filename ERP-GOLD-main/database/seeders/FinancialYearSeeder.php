<?php

namespace Database\Seeders;

use App\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FinancialYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        FinancialYear::truncate();
        $en = 'Financial year from ' . Carbon::now()->startOfYear()->format('Y-m-d') . ' to ' . Carbon::now()->endOfYear()->format('Y-m-d');
        $ar = 'السنة المالية من ' . Carbon::now()->startOfYear()->format('Y-m-d') . ' الي ' . Carbon::now()->endOfYear()->format('Y-m-d');
        $financial_year = FinancialYear::create([
            'description' => ['en' => $en, 'ar' => $ar],
            'from' => Carbon::now()->startOfYear()->format('Y-m-d'),
            'to' => Carbon::now()->endOfYear()->format('Y-m-d'),
            'is_closed' => false,
            'is_active' => true,
        ]);
        Schema::enableForeignKeyConstraints();
    }
}
