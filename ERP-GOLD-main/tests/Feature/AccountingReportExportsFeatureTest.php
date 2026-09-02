<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use ZipArchive;

/**
 * تصدير التقارير المحاسبية PDF و Excel: كل تقرير له صفحة عرض فيها الزرّان،
 * وملف Excel يحمل أرقام التقرير نفسها لا صورةً عنها.
 */
class AccountingReportExportsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_every_accounting_report_print_page_offers_pdf_and_excel(): void
    {
        $admin = $this->createAdminUser();
        $data = $this->seedAccountingData($admin);

        $pages = [
            'trail_balance.print' => [],
            'income_statement.print' => [],
            'balance_sheet.print' => [],
            'cost_centers.print' => [],
            'account_statement.print' => ['account_id' => $data['cash']],
        ];

        foreach ($pages as $route => $parameters) {
            $response = $this->actingAs($admin, 'admin-web')
                ->get(route($route, $parameters + $this->period(), false));

            $response->assertOk();
            $response->assertSee('حفظ PDF');
            $response->assertSee('حفظ Excel');
        }
    }

    public function test_every_accounting_report_exports_a_pdf(): void
    {
        $admin = $this->createAdminUser();
        $data = $this->seedAccountingData($admin);

        $routes = [
            'trail_balance.pdf' => [],
            'income_statement.pdf' => [],
            'balance_sheet.pdf' => [],
            'cost_centers.pdf' => [],
            'account_statement.pdf' => ['account_id' => $data['cash']],
        ];

        foreach ($routes as $route => $parameters) {
            $response = $this->actingAs($admin, 'admin-web')
                ->get(route($route, $parameters + $this->period(), false));

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        }
    }

    /**
     * خطوط dompdf المدمجة بلا حروف عربية، فبدون تضمين خط عربي تخرج كل كلمة
     * في التقرير علامات استفهام. هذا الاختبار يحرس التضمين.
     */
    public function test_report_pdf_embeds_an_arabic_font(): void
    {
        $admin = $this->createAdminUser();
        $this->seedAccountingData($admin);

        $response = $this->actingAs($admin, 'admin-web')
            ->get(route('income_statement.pdf', $this->period(), false));

        $response->assertOk();
        $this->assertStringContainsString('/BaseFont /Tajawal-Regular', (string) $response->getContent());
    }

    public function test_trial_balance_excel_carries_the_report_numbers(): void
    {
        $admin = $this->createAdminUser();
        $this->seedAccountingData($admin);

        $sheet = $this->downloadSheet($admin, 'trail_balance.excel');

        $this->assertStringContainsString('ميزان المراجعة', $sheet);
        $this->assertStringContainsString('الصندوق', $sheet);
        $this->assertStringContainsString('اجمالي الميزان', $sheet);
        // الأرقام خانات رقمية حقيقية تقبل الجمع، لا نصًا منسّقًا
        $this->assertStringContainsString('<v>500</v>', $sheet);
    }

    public function test_income_statement_excel_lists_revenues_expenses_and_profit(): void
    {
        $admin = $this->createAdminUser();
        $this->seedAccountingData($admin);

        $sheet = $this->downloadSheet($admin, 'income_statement.excel');

        $this->assertStringContainsString('الإيرادات', $sheet);
        $this->assertStringContainsString('المصروفات', $sheet);
        $this->assertStringContainsString('صافي الربح', $sheet);
        // إيراد 900 ومصروف 400 ⇐ ربح 500 دائن
        $this->assertStringContainsString('500.00 / دائن', $sheet);
    }

    public function test_balance_sheet_excel_lists_the_three_sections(): void
    {
        $admin = $this->createAdminUser();
        $this->seedAccountingData($admin);

        $sheet = $this->downloadSheet($admin, 'balance_sheet.excel');

        $this->assertStringContainsString('الأصول', $sheet);
        $this->assertStringContainsString('الخصوم', $sheet);
        $this->assertStringContainsString('حقوق الملكية', $sheet);
    }

    public function test_cost_centers_excel_puts_every_branch_in_its_own_column(): void
    {
        $admin = $this->createAdminUser();
        $this->seedAccountingData($admin);

        $sheet = $this->downloadSheet($admin, 'cost_centers.excel');

        $this->assertStringContainsString($admin->branch->name, $sheet);
        $this->assertStringContainsString('الإجمالي', $sheet);
        $this->assertStringContainsString('صافي الربح', $sheet);
    }

    public function test_account_statement_excel_carries_the_movement_rows(): void
    {
        $admin = $this->createAdminUser();
        $data = $this->seedAccountingData($admin);

        $sheet = $this->downloadSheet($admin, 'account_statement.excel', ['account_id' => $data['cash']]);

        $this->assertStringContainsString('تقرير حركة حساب', $sheet);
        $this->assertStringContainsString('إجمالي الرصيد', $sheet);
        $this->assertStringContainsString('<v>500</v>', $sheet);
    }

    public function test_excel_export_respects_the_branch_filter(): void
    {
        $admin = $this->createAdminUser();
        $data = $this->seedAccountingData($admin);

        $otherBranch = $this->createBranch('فرع بعيد');
        $otherJournal = $this->insertJournalEntry([
            'serial' => 'J-OTHER-1',
            'financial_year' => $data['financial_year'],
            'branch_id' => $otherBranch->id,
        ]);
        $this->insertJournalEntryDocument([
            'journal_id' => $otherJournal,
            'account_id' => $data['cash'],
            'debit' => 777,
        ]);

        $sheet = $this->downloadSheet($admin, 'trail_balance.excel', ['branch_id' => $admin->branch_id]);

        $this->assertStringContainsString('<v>500</v>', $sheet);
        $this->assertStringNotContainsString('<v>777</v>', $sheet);
    }

    /**
     * ينزّل ملف Excel ويعيد ورقته الأولى نصًا، للتأكّد من محتواها لا من حجمها.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function downloadSheet(User $admin, string $route, array $parameters = []): string
    {
        $response = $this->actingAs($admin, 'admin-web')
            ->get(route($route, $parameters + $this->period(), false));

        $response->assertOk();
        $this->assertSame(self::XLSX_MIME, $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        return $this->firstSheetOf($response);
    }

    private function firstSheetOf(TestResponse $response): string
    {
        $path = tempnam(sys_get_temp_dir(), 'erp-test-xlsx-');
        file_put_contents($path, $response->getContent());

        $archive = new ZipArchive();
        $this->assertTrue($archive->open($path) === true, 'ملف Excel الناتج ليس أرشيفًا صالحًا.');

        $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
        $archive->close();
        @unlink($path);

        $this->assertIsString($sheet);

        return $sheet;
    }

    /**
     * @return array<string, string>
     */
    private function period(): array
    {
        return [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
            'period_from' => '2026-01-01',
            'period_to' => '2026-12-31',
        ];
    }

    /**
     * شجرة حسابات صغيرة كاملة الأركان مع حركة معلومة المقدار.
     *
     * @return array<string, int>
     */
    private function seedAccountingData(User $admin): array
    {
        $financialYearId = $this->createFinancialYear();

        $assets = $this->createAccount(['name' => 'الأصول', 'code' => '1000', 'level' => 1, 'account_type' => 'assets', 'transfer_side' => 'budget']);
        $cash = $this->createAccount(['name' => 'الصندوق', 'code' => '1100', 'level' => 2, 'parent_account_id' => $assets, 'account_type' => 'assets', 'transfer_side' => 'budget']);
        $this->createAccount(['name' => 'الخصوم', 'code' => '2000', 'level' => 1, 'account_type' => 'liabilities', 'transfer_side' => 'budget']);
        $this->createAccount(['name' => 'حقوق الملكية', 'code' => '3000', 'level' => 1, 'account_type' => 'equity', 'transfer_side' => 'budget']);

        $revenues = $this->createAccount(['name' => 'الإيرادات', 'code' => '4000', 'level' => 1, 'account_type' => 'revenues', 'transfer_side' => 'income_statement']);
        $sales = $this->createAccount(['name' => 'مبيعات', 'code' => '4100', 'level' => 2, 'parent_account_id' => $revenues, 'account_type' => 'revenues', 'transfer_side' => 'income_statement']);
        $expenses = $this->createAccount(['name' => 'المصروفات', 'code' => '5000', 'level' => 1, 'account_type' => 'expenses', 'transfer_side' => 'income_statement']);
        $salaries = $this->createAccount(['name' => 'رواتب', 'code' => '5100', 'level' => 2, 'parent_account_id' => $expenses, 'account_type' => 'expenses', 'transfer_side' => 'income_statement']);

        $journalId = $this->insertJournalEntry([
            'serial' => 'J-1-00001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
        ]);

        $this->insertJournalEntryDocument(['journal_id' => $journalId, 'account_id' => $cash, 'debit' => 500]);
        $this->insertJournalEntryDocument(['journal_id' => $journalId, 'account_id' => $sales, 'credit' => 900]);
        $this->insertJournalEntryDocument(['journal_id' => $journalId, 'account_id' => $salaries, 'debit' => 400]);

        return [
            'financial_year' => $financialYearId,
            'assets' => $assets,
            'cash' => $cash,
            'revenues' => $revenues,
            'expenses' => $expenses,
        ];
    }

    private function createAdminUser(): User
    {
        $branch = $this->createBranch('فرع تصدير التقارير');

        $role = Role::create([
            'name' => ['ar' => 'مدير تصدير', 'en' => 'Exports Admin'],
            'guard_name' => 'admin-web',
        ]);

        $role->givePermissionTo(Permission::create([
            'name' => 'employee.accounting_reports.show',
            'guard_name' => 'admin-web',
        ]));

        $user = User::create([
            'name' => 'Exports Admin',
            'email' => 'exports-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createFinancialYear(): int
    {
        return DB::table('financial_years')->insertGetId([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(array $attributes): int
    {
        $name = $attributes['name'];

        return DB::table('accounts')->insertGetId(array_merge([
            'code' => '1000',
            'old_id' => null,
            'level' => 1,
            'parent_account_id' => null,
            'subscriber_id' => auth('admin-web')->user()?->subscriber_id,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes, [
            'name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertJournalEntry(array $attributes): int
    {
        return DB::table('journal_entries')->insertGetId(array_merge([
            'serial' => null,
            'journal_date' => '2026-03-22',
            'notes' => null,
            'financial_year' => null,
            'branch_id' => null,
            'journalable_type' => null,
            'journalable_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertJournalEntryDocument(array $attributes): int
    {
        return DB::table('journal_entry_documents')->insertGetId(array_merge([
            'journal_id' => null,
            'account_id' => null,
            'document_date' => '2026-03-22',
            'credit' => 0,
            'debit' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }
}
