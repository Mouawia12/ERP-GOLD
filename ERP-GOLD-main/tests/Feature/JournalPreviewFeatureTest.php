<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

/**
 * معاينة القيد لا تنهار حين يخصّ أحد سطوره حسابًا يحجبه نطاق المشترك —
 * السطر يظهر بشرطة بدل أن تسقط الشاشة كلها بخطأ 500.
 */
class JournalPreviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_a_line_on_a_hidden_account_renders_as_a_dash(): void
    {
        $admin = $this->createAdminUser(1);
        $mine = $this->createAccount(1, ['code' => '1103001', 'name' => ['ar' => 'عميل افتراضي', 'en' => 'Default Customer']]);
        $theirs = $this->createAccount(2, ['code' => '1101001', 'name' => ['ar' => 'صندوق مشترك آخر', 'en' => 'Other Safe']]);

        $journalId = $this->createJournal($admin->branch_id, [
            [$theirs, 100, 0],
            [$mine, 0, 100],
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.journals.preview', $journalId, false))
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('عميل افتراضي', $html);
        $this->assertStringNotContainsString('صندوق مشترك آخر', $html);
        $this->assertStringContainsString('<td>-</td>', str_replace(' ', '', $html));
        // الإجماليات تبقى صحيحة رغم حجب أحد الحسابات
        $this->assertStringContainsString('100', $html);
    }

    public function test_a_missing_journal_is_a_404_not_a_crash(): void
    {
        $admin = $this->createAdminUser(1);

        $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.journals.preview', 999999, false))
            ->assertNotFound();
    }

    /**
     * @param  array<int, array{0: int, 1: float, 2: float}>  $lines
     */
    private function createJournal(int $branchId, array $lines): int
    {
        $financialYear = DB::table('financial_years')->insertGetId([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalId = DB::table('journal_entries')->insertGetId([
            'serial' => '1',
            'journal_date' => '2026-09-02',
            'notes' => 'قيد اختبار',
            'financial_year' => $financialYear,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($lines as [$accountId, $debit, $credit]) {
            DB::table('journal_entry_documents')->insert([
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'document_date' => '2026-09-02',
                'debit' => $debit,
                'credit' => $credit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $journalId;
    }

    private function createAdminUser(int $subscriberId): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع المعاينة', 'en' => 'Preview Branch'],
            'phone' => '05' . random_int(10000000, 99999999),
            'subscriber_id' => $subscriberId,
        ]);

        return User::create([
            'name' => 'Preview Admin',
            'email' => 'preview-admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'subscriber_id' => $subscriberId,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(int $subscriberId, array $attributes = []): int
    {
        $name = $attributes['name'] ?? ['ar' => 'حساب اختبار', 'en' => 'Test Account'];
        unset($attributes['name']);

        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
            'code' => '4100',
            'old_id' => null,
            'level' => '2',
            'parent_account_id' => null,
            'account_type' => 'revenues',
            'transfer_side' => 'income_statement',
            'subscriber_id' => $subscriberId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
