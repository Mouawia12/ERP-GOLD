<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\BranchItem;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    private Branch $branch;
    private User $user;
    private Warehouse $warehouse;
    private FinancialYear $financialYear;
    private Tax $vatTax;
    private Tax $oosTax;
    private GoldCarat $c18;
    private GoldCarat $c21;
    private GoldCarat $c22;
    private GoldCarat $c24;
    private GoldCaratType $crafted;
    private GoldCaratType $scrap;
    private GoldCaratType $pure;

    public function run(): void
    {
        DB::transaction(function () {
            $this->branch = Branch::findOrFail(1);
            $this->user   = User::where('email', 'ymouawia10@gmail.com')->firstOrFail();

            $this->command?->info('--- إنشاء البيانات الأساسية ---');
            $this->seedTaxes();
            $this->seedCaratTypes();
            $this->seedCarats();
            $this->seedFinancialYear();
            $this->seedBranchDetails();
            $this->seedAccounts();
            $this->seedWarehouse();

            $this->command?->info('--- إنشاء الأصناف ---');
            $categories = $this->seedCategories();
            $items      = $this->seedItems($categories);

            $this->command?->info('--- إنشاء العملاء والموردين ---');
            $customers  = $this->seedCustomers();
            $suppliers  = $this->seedSuppliers();

            $this->command?->info('--- إنشاء الفواتير ---');
            $this->seedSalesInvoices($items, $customers);
            $this->seedPurchaseInvoices($items, $suppliers);

            $this->command?->info('تم إنشاء البيانات التجريبية بنجاح ✓');
        });
    }

    // ─── البيانات الأساسية ────────────────────────────────────────────────────

    private function seedTaxes(): void
    {
        $this->vatTax = Tax::firstOrCreate(
            ['zatca_code' => 'S'],
            [
                'title' => ['ar' => 'ضريبة القيمة المضافة 15%', 'en' => 'VAT 15%'],
                'rate'  => 15,
            ]
        );

        $this->oosTax = Tax::firstOrCreate(
            ['zatca_code' => 'O'],
            [
                'title'                   => ['ar' => 'خارج نطاق الضريبة', 'en' => 'Out of Scope'],
                'rate'                    => 0,
                'zatca_exemption_code'    => 'VATEX-SA-OOS',
                'zatca_exemption_reason'  => ['ar' => 'خارج نطاق ضريبة القيمة المضافة', 'en' => 'Out of scope VAT'],
            ]
        );
        $this->command?->line('  ✓ الضرائب');
    }

    private function seedCaratTypes(): void
    {
        $types = [
            ['key' => 'crafted', 'ar' => 'مشغولات', 'en' => 'Crafted'],
            ['key' => 'scrap',   'ar' => 'كسر',      'en' => 'Scrap'],
            ['key' => 'pure',    'ar' => 'صافي',      'en' => 'Pure'],
        ];

        foreach ($types as $t) {
            GoldCaratType::firstOrCreate(
                ['key' => $t['key']],
                ['title' => ['ar' => $t['ar'], 'en' => $t['en']]]
            );
        }

        $this->crafted = GoldCaratType::where('key', 'crafted')->first();
        $this->scrap   = GoldCaratType::where('key', 'scrap')->first();
        $this->pure    = GoldCaratType::where('key', 'pure')->first();
        $this->command?->line('  ✓ أنواع العيار');
    }

    private function seedCarats(): void
    {
        $carats = [
            ['label' => 'C18', 'ar' => 'عيار 18', 'en' => 'Carat 18', 'factor' => 0.8571, 'pure' => false],
            ['label' => 'C21', 'ar' => 'عيار 21', 'en' => 'Carat 21', 'factor' => 1.0,    'pure' => false],
            ['label' => 'C22', 'ar' => 'عيار 22', 'en' => 'Carat 22', 'factor' => 1.047,  'pure' => false],
            ['label' => 'C24', 'ar' => 'عيار 24', 'en' => 'Carat 24', 'factor' => 1.1428, 'pure' => true],
        ];

        foreach ($carats as $c) {
            GoldCarat::firstOrCreate(
                ['label' => $c['label']],
                [
                    'title'            => ['ar' => $c['ar'], 'en' => $c['en']],
                    'tax_id'           => $this->vatTax->id,
                    'transform_factor' => $c['factor'],
                    'is_pure'          => $c['pure'],
                ]
            );
        }

        $this->c18 = GoldCarat::where('label', 'C18')->first();
        $this->c21 = GoldCarat::where('label', 'C21')->first();
        $this->c22 = GoldCarat::where('label', 'C22')->first();
        $this->c24 = GoldCarat::where('label', 'C24')->first();
        $this->command?->line('  ✓ العيارات');
    }

    private function seedFinancialYear(): void
    {
        $year = (int) Carbon::now()->format('Y');

        $this->financialYear = FinancialYear::firstOrCreate(
            [
                'from' => Carbon::createFromDate($year, 1, 1)->toDateString(),
                'to'   => Carbon::createFromDate($year, 12, 31)->toDateString(),
            ],
            [
                'description' => "السنة المالية {$year}",
                'is_active'   => true,
                'is_closed'   => false,
            ]
        );
        $this->command?->line('  ✓ السنة المالية');
    }

    private function seedBranchDetails(): void
    {
        $this->branch->update([
            'commercial_register' => $this->branch->commercial_register ?: '1010479133',
            'tax_number'          => $this->branch->tax_number ?: '311294196200003',
            'phone'               => $this->branch->phone ?: '0112345678',
            'city'                => $this->branch->city ?: 'الرياض',
            'region'              => $this->branch->region ?: 'منطقة الرياض',
            'country'             => $this->branch->country ?: 'SA',
            'street_name'         => $this->branch->street_name ?: 'شارع العليا',
            'postal_code'         => $this->branch->postal_code ?: '11564',
        ]);
        $this->command?->line('  ✓ بيانات الفرع');
    }

    private function seedAccounts(): void
    {
        $accountDefs = [
            'safe'            => ['ar' => 'الصندوق',              'en' => 'Cash',                'type' => 'assets',      'side' => 'budget'],
            'bank'            => ['ar' => 'البنك',                'en' => 'Bank',                'type' => 'assets',      'side' => 'budget'],
            'sales'           => ['ar' => 'إيرادات المبيعات',     'en' => 'Sales Revenue',       'type' => 'revenues',    'side' => 'income_statement'],
            'return_sales'    => ['ar' => 'مردودات المبيعات',     'en' => 'Sales Returns',       'type' => 'revenues',    'side' => 'income_statement'],
            'stock_crafted'   => ['ar' => 'مخزون مشغولات',       'en' => 'Stock Crafted',       'type' => 'assets',      'side' => 'budget'],
            'stock_scrap'     => ['ar' => 'مخزون كسر',           'en' => 'Stock Scrap',         'type' => 'assets',      'side' => 'budget'],
            'stock_pure'      => ['ar' => 'مخزون ذهب صافي',      'en' => 'Stock Pure',          'type' => 'assets',      'side' => 'budget'],
            'made'            => ['ar' => 'المصنعية',             'en' => 'Manufacturing',       'type' => 'expenses',    'side' => 'income_statement'],
            'cost_crafted'    => ['ar' => 'تكلفة مشغولات',       'en' => 'Cost Crafted',        'type' => 'expenses',    'side' => 'income_statement'],
            'cost_scrap'      => ['ar' => 'تكلفة كسر',           'en' => 'Cost Scrap',          'type' => 'expenses',    'side' => 'income_statement'],
            'cost_pure'       => ['ar' => 'تكلفة ذهب صافي',      'en' => 'Cost Pure',           'type' => 'expenses',    'side' => 'income_statement'],
            'profit'          => ['ar' => 'الأرباح',              'en' => 'Profit',              'type' => 'revenues',    'side' => 'income_statement'],
            'reverse_profit'  => ['ar' => 'عكس الربح',           'en' => 'Reverse Profit',      'type' => 'expenses',    'side' => 'income_statement'],
            'purchase_tax'    => ['ar' => 'ضريبة المشتريات',     'en' => 'Purchase Tax',        'type' => 'assets',      'side' => 'budget'],
            'sales_tax'       => ['ar' => 'ضريبة المبيعات',      'en' => 'Sales Tax',           'type' => 'liabilities', 'side' => 'budget'],
            'supplier_def'    => ['ar' => 'ذمم موردين فرعية',    'en' => 'Supplier Sub',        'type' => 'liabilities', 'side' => 'budget'],
            'clients'         => ['ar' => 'ذمم العملاء',         'en' => 'Clients',             'type' => 'assets',      'side' => 'budget'],
            'suppliers'       => ['ar' => 'ذمم الموردين',        'en' => 'Suppliers',           'type' => 'liabilities', 'side' => 'budget'],
        ];

        $accounts = [];
        foreach ($accountDefs as $key => $def) {
            $accounts[$key] = Account::firstOrCreate(
                ['name->ar' => $def['ar']],
                [
                    'name'          => json_encode(['ar' => $def['ar'], 'en' => $def['en']], JSON_UNESCAPED_UNICODE),
                    'account_type'  => $def['type'],
                    'transfer_side' => $def['side'],
                    'subscriber_id' => $this->branch->subscriber_id,
                ]
            );
        }

        AccountSetting::updateOrCreate(
            ['branch_id' => $this->branch->id],
            [
                'subscriber_id'          => $this->branch->subscriber_id,
                'safe_account'           => $accounts['safe']->id,
                'bank_account'           => $accounts['bank']->id,
                'sales_account'          => $accounts['sales']->id,
                'return_sales_account'   => $accounts['return_sales']->id,
                'stock_account_crafted'  => $accounts['stock_crafted']->id,
                'stock_account_scrap'    => $accounts['stock_scrap']->id,
                'stock_account_pure'     => $accounts['stock_pure']->id,
                'made_account'           => $accounts['made']->id,
                'cost_account_crafted'   => $accounts['cost_crafted']->id,
                'cost_account_scrap'     => $accounts['cost_scrap']->id,
                'cost_account_pure'      => $accounts['cost_pure']->id,
                'profit_account'         => $accounts['profit']->id,
                'reverse_profit_account' => $accounts['reverse_profit']->id,
                'purchase_tax_account'   => $accounts['purchase_tax']->id,
                'sales_tax_account'      => $accounts['sales_tax']->id,
                'supplier_default_account' => $accounts['supplier_def']->id,
                'clients_account'        => $accounts['clients']->id,
                'suppliers_account'      => $accounts['suppliers']->id,
            ]
        );

        BankAccount::firstOrCreate(
            ['branch_id' => $this->branch->id, 'account_name' => 'الحساب البنكي الرئيسي'],
            [
                'ledger_account_id'    => $accounts['bank']->id,
                'bank_name'            => 'بنك الرياض',
                'iban'                 => 'SA0380000000608010167519',
                'account_number'       => '608010167519',
                'terminal_name'        => 'POS-001',
                'device_code'          => 'DEV-001',
                'supports_credit_card' => true,
                'supports_bank_transfer' => true,
                'is_default'           => true,
                'is_active'            => true,
            ]
        );

        $this->command?->line('  ✓ الحسابات وإعدادات الحسابات');
    }

    private function seedWarehouse(): void
    {
        $this->warehouse = Warehouse::firstOrCreate(
            ['branch_id' => $this->branch->id, 'code' => 'WH-001'],
            ['name' => 'المخزن الرئيسي']
        );
        $this->command?->line('  ✓ المخزن');
    }

    // ─── الأصناف ──────────────────────────────────────────────────────────────

    private function seedCategories(): array
    {
        $defs = [
            ['code' => 'CAT-RING',   'ar' => 'خواتم',       'en' => 'Rings'],
            ['code' => 'CAT-BRACE',  'ar' => 'أساور',       'en' => 'Bracelets'],
            ['code' => 'CAT-NECK',   'ar' => 'قلائد',       'en' => 'Necklaces'],
            ['code' => 'CAT-EARR',   'ar' => 'حلقان',       'en' => 'Earrings'],
            ['code' => 'CAT-BULK',   'ar' => 'ذهب مفرق',    'en' => 'Bulk Gold'],
        ];

        $categories = [];
        foreach ($defs as $d) {
            $categories[$d['code']] = ItemCategory::firstOrCreate(
                ['code' => $d['code']],
                [
                    'title'       => ['ar' => $d['ar'], 'en' => $d['en']],
                    'description' => ['ar' => '', 'en' => ''],
                ]
            );
        }

        $this->command?->line('  ✓ الفئات (5 فئات)');
        return $categories;
    }

    private function seedItems(array $categories): array
    {
        $itemDefs = [
            // خواتم
            [
                'title' => ['ar' => 'خاتم ذهب عيار 21 مشغول', 'en' => 'Gold Ring 21K Crafted'],
                'cat'   => 'CAT-RING', 'carat' => 'c21', 'type' => 'crafted',
                'labor' => 18, 'margin' => 60, 'weight' => 4.5, 'cost' => 220, 'price' => 300,
            ],
            [
                'title' => ['ar' => 'خاتم ذهب عيار 18 مشغول', 'en' => 'Gold Ring 18K Crafted'],
                'cat'   => 'CAT-RING', 'carat' => 'c18', 'type' => 'crafted',
                'labor' => 15, 'margin' => 50, 'weight' => 3.8, 'cost' => 180, 'price' => 250,
            ],
            [
                'title' => ['ar' => 'دبلة زواج ذهب عيار 21', 'en' => 'Wedding Band 21K'],
                'cat'   => 'CAT-RING', 'carat' => 'c21', 'type' => 'crafted',
                'labor' => 20, 'margin' => 55, 'weight' => 5.2, 'cost' => 220, 'price' => 295,
            ],
            // أساور
            [
                'title' => ['ar' => 'سوار ذهب عيار 21 مشغول', 'en' => 'Gold Bracelet 21K Crafted'],
                'cat'   => 'CAT-BRACE', 'carat' => 'c21', 'type' => 'crafted',
                'labor' => 22, 'margin' => 65, 'weight' => 12.0, 'cost' => 220, 'price' => 308,
            ],
            [
                'title' => ['ar' => 'سوار ذهب عيار 22 مشغول', 'en' => 'Gold Bracelet 22K Crafted'],
                'cat'   => 'CAT-BRACE', 'carat' => 'c22', 'type' => 'crafted',
                'labor' => 25, 'margin' => 70, 'weight' => 10.5, 'cost' => 240, 'price' => 335,
            ],
            // قلائد
            [
                'title' => ['ar' => 'قلادة ذهب عيار 18 مشغول', 'en' => 'Gold Necklace 18K Crafted'],
                'cat'   => 'CAT-NECK', 'carat' => 'c18', 'type' => 'crafted',
                'labor' => 30, 'margin' => 80, 'weight' => 8.3, 'cost' => 180, 'price' => 290,
            ],
            [
                'title' => ['ar' => 'قلادة ذهب عيار 21 مشغول', 'en' => 'Gold Necklace 21K Crafted'],
                'cat'   => 'CAT-NECK', 'carat' => 'c21', 'type' => 'crafted',
                'labor' => 28, 'margin' => 75, 'weight' => 9.5, 'cost' => 220, 'price' => 323,
            ],
            // حلقان
            [
                'title' => ['ar' => 'حلق ذهب عيار 18 مشغول', 'en' => 'Gold Earrings 18K Crafted'],
                'cat'   => 'CAT-EARR', 'carat' => 'c18', 'type' => 'crafted',
                'labor' => 12, 'margin' => 40, 'weight' => 2.6, 'cost' => 180, 'price' => 232,
            ],
            // ذهب مفرق
            [
                'title' => ['ar' => 'كسر ذهب عيار 21', 'en' => 'Gold Scrap 21K'],
                'cat'   => 'CAT-BULK', 'carat' => 'c21', 'type' => 'scrap',
                'labor' => 0, 'margin' => 0, 'weight' => 100.0, 'cost' => 210, 'price' => 215,
            ],
            [
                'title' => ['ar' => 'سبيكة ذهب صافي عيار 24', 'en' => 'Pure Gold Bar 24K'],
                'cat'   => 'CAT-BULK', 'carat' => 'c24', 'type' => 'pure',
                'labor' => 0, 'margin' => 5, 'weight' => 31.1, 'cost' => 260, 'price' => 265,
            ],
        ];

        $items = [];
        foreach ($itemDefs as $def) {
            $carat    = $this->{$def['carat']};
            $caratType = $this->{$def['type']};
            $category  = $categories[$def['cat']];

            $existing = Item::where('branch_id', $this->branch->id)
                ->where('title', 'like', '%' . $def['title']['ar'] . '%')
                ->first();

            if ($existing) {
                $item = $existing;
            } else {
                $item = Item::create([
                    'title'                  => $def['title'],
                    'description'            => ['ar' => '', 'en' => ''],
                    'category_id'            => $category->id,
                    'branch_id'              => $this->branch->id,
                    'inventory_classification' => $def['type'] === 'pure' ? Item::CLASSIFICATION_GOLD : Item::CLASSIFICATION_GOLD,
                    'gold_carat_id'          => $carat->id,
                    'gold_carat_type_id'     => $caratType->id,
                    'no_metal'               => 0,
                    'no_metal_type'          => 'fixed',
                    'labor_cost_per_gram'    => $def['labor'],
                    'profit_margin_per_gram' => $def['margin'],
                    'status'                 => true,
                ]);
            }

            ItemUnit::updateOrCreate(
                ['item_id' => $item->id, 'is_default' => true],
                [
                    'weight'                => $def['weight'],
                    'initial_cost_per_gram' => $def['cost'],
                    'average_cost_per_gram' => $def['cost'],
                    'current_cost_per_gram' => $def['cost'],
                    'is_sold'               => false,
                ]
            );

            BranchItem::updateOrCreate(
                ['branch_id' => $this->branch->id, 'item_id' => $item->id],
                [
                    'is_active'              => true,
                    'is_visible'             => true,
                    'sale_price_per_gram'    => $def['price'],
                    'published_by_user_id'   => $this->user->id,
                ]
            );

            $items[] = ['model' => $item, 'price' => $def['price'], 'weight' => $def['weight'], 'carat' => $carat, 'type' => $caratType];
        }

        $this->command?->line('  ✓ الأصناف (10 أصناف)');
        return $items;
    }

    // ─── العملاء والموردون ────────────────────────────────────────────────────

    private function seedCustomers(): array
    {
        $defs = [
            ['name' => 'محمد علي العمري',      'phone' => '0501234567', 'city' => 'الرياض'],
            ['name' => 'أحمد سعد السالم',      'phone' => '0551234568', 'city' => 'جدة'],
            ['name' => 'فاطمة عبدالله الزهراني', 'phone' => '0561234569', 'city' => 'الدمام'],
            ['name' => 'خالد محمد الغامدي',    'phone' => '0541234570', 'city' => 'مكة المكرمة'],
            ['name' => 'سارة إبراهيم الشهري',  'phone' => '0571234571', 'city' => 'الرياض'],
        ];

        $customers = [];
        foreach ($defs as $d) {
            $customers[] = Customer::firstOrCreate(
                ['name' => $d['name'], 'type' => 'customer'],
                ['phone' => $d['phone'], 'city' => $d['city']]
            );
        }

        $this->command?->line('  ✓ العملاء (5 عملاء)');
        return $customers;
    }

    private function seedSuppliers(): array
    {
        $defs = [
            ['name' => 'شركة الذهب السعودية للتجارة',   'phone' => '0112345678', 'city' => 'الرياض',  'tax' => '300123456700003'],
            ['name' => 'مؤسسة سبائك الجزيرة',           'phone' => '0123456789', 'city' => 'جدة',      'tax' => '300234567800003'],
            ['name' => 'شركة الخليج للمجوهرات',          'phone' => '0133456789', 'city' => 'الدمام',   'tax' => '300345678900003'],
        ];

        $suppliers = [];
        foreach ($defs as $d) {
            $suppliers[] = Customer::firstOrCreate(
                ['name' => $d['name'], 'type' => 'supplier'],
                ['phone' => $d['phone'], 'city' => $d['city'], 'tax_number' => $d['tax']]
            );
        }

        $this->command?->line('  ✓ الموردون (3 موردين)');
        return $suppliers;
    }

    // ─── فواتير المبيعات ──────────────────────────────────────────────────────

    private function seedSalesInvoices(array $items, array $customers): void
    {
        $salesData = [
            // [customer_index, sale_type, payment_type, date_offset_days, lines]
            // كل line: [item_index, quantity, weight, price_per_gram]
            [0, 'simplified', 'cash',          -30, [[0, 1, 4.5,  300], [7, 1, 2.6,  232]]],
            [1, 'simplified', 'cash',          -28, [[1, 1, 3.8,  250], [3, 1, 12.0, 308]]],
            [2, 'simplified', 'credit_card',   -25, [[2, 1, 5.2,  295]]],
            [0, 'simplified', 'cash',          -22, [[6, 1, 9.5,  323], [4, 1, 10.5, 335]]],
            [3, 'simplified', 'bank_transfer', -20, [[5, 1, 8.3,  290], [0, 1, 4.5,  300]]],
            [1, 'simplified', 'cash',          -15, [[3, 1, 12.0, 308]]],
            [4, 'simplified', 'credit_card',   -10, [[2, 1, 5.2,  295], [1, 1, 3.8, 250], [7, 1, 2.6, 232]]],
            [2, 'standard',   'cash',           -8, [[6, 1, 9.5,  323], [5, 1, 8.3, 290]]],
            [3, 'standard',   'bank_transfer',  -5, [[4, 1, 10.5, 335]]],
            [0, 'simplified', 'cash',           -2, [[0, 1, 4.5,  300]]],
            [4, 'simplified', 'cash',           -1, [[3, 1, 12.0, 308], [6, 1, 9.5, 323]]],
        ];

        $count = 0;
        foreach ($salesData as [$custIdx, $saleType, $payment, $dayOffset, $lines]) {
            $this->createSaleInvoice(
                $customers[$custIdx % count($customers)],
                $saleType,
                $payment,
                Carbon::now()->addDays($dayOffset)->toDateString(),
                $items,
                $lines
            );
            $count++;
        }

        $this->command?->line("  ✓ فواتير المبيعات ({$count} فاتورة)");
    }

    private function createSaleInvoice(
        Customer $customer,
        string $saleType,
        string $paymentType,
        string $date,
        array $allItems,
        array $lines
    ): Invoice {
        $linesTotal = 0;
        $taxesTotal = 0;

        $lineData = [];
        foreach ($lines as [$itemIdx, $qty, $weight, $pricePerGram]) {
            $itemRow    = $allItems[$itemIdx % count($allItems)];
            $lineTotal  = round($weight * $pricePerGram * $qty, 2);
            $taxRate    = 15.0;
            $lineTax    = round($lineTotal * ($taxRate / 100), 2);
            $lineNet    = $lineTotal + $lineTax;
            $linesTotal += $lineTotal;
            $taxesTotal += $lineTax;

            $lineData[] = [
                'item'       => $itemRow['model'],
                'carat'      => $itemRow['carat'],
                'caratType'  => $itemRow['type'],
                'qty'        => $qty,
                'weight'     => $weight,
                'price'      => $weight * $pricePerGram,
                'taxRate'    => $taxRate,
                'tax'        => $lineTax,
                'lineTotal'  => $lineTotal,
                'lineNet'    => $lineNet,
            ];
        }

        $netTotal = $linesTotal + $taxesTotal;

        $invoice = Invoice::create([
            'financial_year' => $this->financialYear->id,
            'branch_id'      => $this->branch->id,
            'warehouse_id'   => $this->warehouse->id,
            'customer_id'    => $customer->id,
            'user_id'        => $this->user->id,
            'type'           => 'sale',
            'sale_type'      => $saleType,
            'payment_type'   => $paymentType,
            'date'           => $date,
            'time'           => '10:30:00',
            'lines_total'               => $linesTotal,
            'discount_total'            => 0,
            'lines_total_after_discount' => $linesTotal,
            'taxes_total'               => $taxesTotal,
            'net_total'                 => $netTotal,
        ]);

        foreach ($lineData as $ld) {
            InvoiceDetail::create([
                'invoice_id'      => $invoice->id,
                'warehouse_id'    => $this->warehouse->id,
                'item_id'         => $ld['item']->id,
                'gold_carat_id'   => $ld['carat']->id,
                'gold_carat_type_id' => $ld['caratType']->id,
                'unit_tax_id'     => $this->vatTax->id,
                'date'            => $invoice->date,
                'out_quantity'    => $ld['qty'],
                'in_quantity'     => 0,
                'out_weight'      => $ld['weight'],
                'in_weight'       => 0,
                'unit_price'      => $ld['price'],
                'unit_discount'   => 0,
                'unit_tax'        => $ld['tax'],
                'unit_tax_rate'   => $ld['taxRate'],
                'labor_cost_per_gram' => 0,
                'unit_cost'       => 0,
                'line_total'      => $ld['lineTotal'],
                'line_discount'   => 0,
                'line_tax'        => $ld['tax'],
                'net_total'       => $ld['lineNet'],
                'no_metal'        => 0,
                'no_metal_type'   => 'fixed',
            ]);
        }

        return $invoice;
    }

    // ─── فواتير المشتريات ─────────────────────────────────────────────────────

    private function seedPurchaseInvoices(array $items, array $suppliers): void
    {
        $purchaseData = [
            // [supplier_index, date_offset, lines]
            // كل line: [item_index, qty, weight, cost_per_gram]
            [0, -35, [[8, 1, 150.0, 210], [9, 1, 31.1, 255]]],
            [1, -25, [[8, 1, 200.0, 208]]],
            [2, -12, [[9, 1, 62.2, 258], [8, 1, 100.0, 212]]],
        ];

        $count = 0;
        foreach ($purchaseData as [$supIdx, $dayOffset, $lines]) {
            $this->createPurchaseInvoice(
                $suppliers[$supIdx % count($suppliers)],
                Carbon::now()->addDays($dayOffset)->toDateString(),
                $items,
                $lines
            );
            $count++;
        }

        $this->command?->line("  ✓ فواتير المشتريات ({$count} فواتير)");
    }

    private function createPurchaseInvoice(
        Customer $supplier,
        string $date,
        array $allItems,
        array $lines
    ): Invoice {
        $linesTotal = 0;
        $taxesTotal = 0;

        $lineData = [];
        foreach ($lines as [$itemIdx, $qty, $weight, $costPerGram]) {
            $itemRow   = $allItems[$itemIdx % count($allItems)];
            $lineTotal = round($weight * $costPerGram * $qty, 2);
            $taxRate   = 0.0;
            $lineTax   = 0.0;
            $linesTotal += $lineTotal;
            $taxesTotal += $lineTax;

            $lineData[] = [
                'item'      => $itemRow['model'],
                'carat'     => $itemRow['carat'],
                'caratType' => $itemRow['type'],
                'qty'       => $qty,
                'weight'    => $weight,
                'cost'      => $weight * $costPerGram,
                'taxRate'   => $taxRate,
                'tax'       => $lineTax,
                'lineTotal' => $lineTotal,
                'lineNet'   => $lineTotal,
            ];
        }

        $netTotal = $linesTotal + $taxesTotal;

        $invoice = Invoice::create([
            'financial_year' => $this->financialYear->id,
            'branch_id'      => $this->branch->id,
            'warehouse_id'   => $this->warehouse->id,
            'customer_id'    => $supplier->id,
            'user_id'        => $this->user->id,
            'type'           => 'purchase',
            'sale_type'      => 'simplified',
            'payment_type'   => 'cash',
            'date'           => $date,
            'time'           => '09:00:00',
            'lines_total'               => $linesTotal,
            'discount_total'            => 0,
            'lines_total_after_discount' => $linesTotal,
            'taxes_total'               => $taxesTotal,
            'net_total'                 => $netTotal,
        ]);

        foreach ($lineData as $ld) {
            InvoiceDetail::create([
                'invoice_id'      => $invoice->id,
                'warehouse_id'    => $this->warehouse->id,
                'item_id'         => $ld['item']->id,
                'gold_carat_id'   => $ld['carat']->id,
                'gold_carat_type_id' => $ld['caratType']->id,
                'unit_tax_id'     => $this->oosTax->id,
                'date'            => $invoice->date,
                'out_quantity'    => 0,
                'in_quantity'     => $ld['qty'],
                'out_weight'      => 0,
                'in_weight'       => $ld['weight'],
                'unit_price'      => $ld['cost'],
                'unit_cost'       => $ld['cost'],
                'unit_discount'   => 0,
                'unit_tax'        => $ld['tax'],
                'unit_tax_rate'   => $ld['taxRate'],
                'labor_cost_per_gram' => 0,
                'line_total'      => $ld['lineTotal'],
                'line_discount'   => 0,
                'line_tax'        => $ld['tax'],
                'net_total'       => $ld['lineNet'],
                'no_metal'        => 0,
                'no_metal_type'   => 'fixed',
            ]);
        }

        return $invoice;
    }
}
