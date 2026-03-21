<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Tax::truncate();
        Tax::create([
            'title' => ['ar' => 'ضريبة القيمة المضافة', 'en' => 'Value Added Tax (15%)'],
            'rate' => 15,
            'zatca_code' => 'S',
        ]);
        Tax::create([
            'title' => ['en' => 'Out of Scope Services', 'ar' => 'التوريدات خارج نطاق الضريبة'],
            'rate' => 0,
            'zatca_code' => 'O',
            'zatca_exemption_code' => 'VATEX-SA-OOS',
            'zatca_exemption_reason' => [
                'en' => 'Reason provided by the taxpayer on a case-by-case basis',
                'ar' => 'السبب يتم تزويده من قبل المكلف على أساس كل حالة على حدة'
            ]
        ]);
        Schema::enableForeignKeyConstraints();
    }
}
