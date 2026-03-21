<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Services\MigrateOld\Run;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DefaultRoleSeeder::class,
            CaratTypeSeeder::class,
            TaxSeeder::class,
            CaratSeeder::class,
            FinancialYearSeeder::class,
        ]);

        Run::run();
        AccountSetting::create([
            'safe_account' => Account::where('old_id', 5)->first()->id,
            'bank_account' => Account::where('old_id', 6)->first()->id,
            'sales_account' => Account::where('old_id', 58)->first()->id,
            'return_sales_account' => Account::where('old_id', 60)->first()->id,
            'stock_account_crafted' => Account::where('old_id', 113)->first()->id,
            'stock_account_scrap' => Account::where('old_id', 114)->first()->id,
            'stock_account_pure' => Account::where('old_id', 115)->first()->id,
            'made_account' => Account::where('old_id', 117)->first()->id,
            'cost_account_crafted' => Account::where('old_id', 192)->first()->id,
            'cost_account_scrap' => Account::where('old_id', 193)->first()->id,
            'cost_account_pure' => Account::where('old_id', 194)->first()->id,
            'reverse_profit_account' => Account::where('old_id', 79)->first()->id,
            'supplier_default_account' => Account::where('old_id', 104)->first()->id,
            'profit_account' => Account::where('old_id', 51)->first()->id,
            'purchase_tax_account' => Account::where('old_id', 43)->first()->id,
            'sales_tax_account' => Account::where('old_id', 23)->first()->id,
            'suppliers_account' => Account::where('old_id', 28)->first()->id,
            'clients_account' => Account::where('old_id', 13)->first()->id,
            'branch_id' => 1,
            'created_at' => '2023-05-17 07:22:03',
            'updated_at' => '2023-05-18 08:56:11',
        ]);
    }
}
