<?php

namespace App\Console\Commands;

use App\Models\AccountSetting;
use App\Services\Accounts\BranchAccountEligibility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يكشف الروابط المحاسبية التي تشير إلى حساب يخص فرعًا آخر.
 *
 * ربط كهذا يجعل مبيعات فرع تُرحَّل إلى حساب فرع آخر، فتظهر في قائمة دخل الفرع
 * الأول كأن فرعًا يتسرّب داخل فرع. الأمر يقرأ ولا يكتب: التصحيح قرار محاسبي
 * (تعديل الربط للمستقبل، وقيد إعادة تصنيف للماضي) لا يصح أن يُتخذ آليًا.
 */
class AccountsBranchLinkAuditCommand extends Command
{
    protected $signature = 'accounts:audit-branch-links';

    protected $description = 'يعرض الروابط المحاسبية التي تشير إلى حساب مخصّص لفرع آخر';

    public function handle(BranchAccountEligibility $eligibility): int
    {
        if (! Schema::hasTable('account_settings') || ! Schema::hasTable('account_branch')) {
            $this->warn('الجداول المطلوبة غير موجودة.');

            return self::SUCCESS;
        }

        $accountFields = array_values(array_filter(
            Schema::getColumnListing('account_settings'),
            fn (string $column) => str_contains($column, 'account')
        ));

        $rows = [];

        foreach (AccountSetting::query()->withoutGlobalScopes()->cursor() as $setting) {
            if (blank($setting->branch_id)) {
                continue;
            }

            $branchName = $this->nameOf('branches', $setting->branch_id);

            foreach ($accountFields as $field) {
                $accountId = $setting->{$field} ?? null;

                if (blank($accountId) || $eligibility->isLinkableTo((int) $accountId, (int) $setting->branch_id)) {
                    continue;
                }

                $rows[] = [
                    $branchName,
                    $field,
                    $this->nameOf('accounts', (int) $accountId),
                    $eligibility->branchNamesFor((int) $accountId),
                ];
            }
        }

        if ($rows === []) {
            $this->info('لا توجد روابط محاسبية تشير إلى حساب يخص فرعًا آخر.');

            return self::SUCCESS;
        }

        $this->warn('روابط محاسبية تشير إلى حسابات تخص فروعًا أخرى:');
        $this->table(['الفرع', 'الحقل', 'الحساب المربوط', 'الحساب يخص'], $rows);
        $this->line('');
        $this->line('التصحيح: عدّل الربط في «الروابط المحاسبية» ليشير إلى حساب هذا الفرع،');
        $this->line('ثم سجّل قيد إعادة تصنيف ينقل الأرصدة المرحّلة سابقًا إلى حسابها الصحيح.');

        return self::SUCCESS;
    }

    private function nameOf(string $table, int $id): string
    {
        $name = DB::table($table)->where('id', $id)->value('name');
        $decoded = json_decode((string) $name, true);

        return is_array($decoded)
            ? ($decoded['ar'] ?? $decoded['en'] ?? (string) $name)
            : (string) $name;
    }
}
