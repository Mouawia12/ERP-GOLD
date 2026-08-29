<?php

namespace App\Console\Commands;

use App\Services\Accounts\AccountCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * إعادة ترقيم شجرة الحسابات إلى النمط المعياري لكل مشترك على حدة:
 * الأصول 1، الخصوم 2، حقوق الملكية 3، الإيرادات 4، المصروفات 5، ثم تتابع الفروع.
 *
 * لا تلمس العملية parent_account_id ولا أي رصيد — أكواد ومستويات فقط. ومع ذلك
 * تُشغَّل داخل معاملة وتُفحص قبل الاعتماد: بصمة الأرصدة، عدد الحسابات، الآباء،
 * عدد الجذور لكل مشترك، وتصادم الأكواد. أي فحص يسقط ⇦ تراجع كامل.
 *
 * تشغيل جاف افتراضيًا (يعرض الفروق ثم يتراجع). --apply للاعتماد. قابلة للتكرار.
 */
class AccountsRenumberCommand extends Command
{
    protected $signature = 'accounts:renumber
        {--subscriber=* : حصر العملية بمشترك/مشتركين (استخدم null لدلو بلا مشترك)}
        {--apply : اعتماد التغيير فعليًا (الافتراضي تشغيل جاف)}
        {--limit=40 : عدد صفوف الفروق المعروضة}';

    protected $description = 'Renumber the chart of accounts to the standard scheme (assets 1, liabilities 2, equity 3, revenues 4, expenses 5).';

    public function handle(AccountCodeService $codes): int
    {
        if (! Schema::hasTable('accounts')) {
            $this->error('accounts table not found.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $subscriberIds = $this->targetSubscribers();

        $this->info($apply ? '== accounts:renumber — APPLYING ==' : '== accounts:renumber — DRY RUN (no changes) ==');
        $this->line('Subscriber buckets: ' . collect($subscriberIds)->map(fn ($id) => $id ?? 'NULL')->implode(', '));

        $before = $this->snapshot();
        $fingerprintBefore = $this->fingerprint();

        DB::beginTransaction();

        try {
            $touched = 0;
            foreach ($subscriberIds as $subscriberId) {
                $touched += $codes->renumberSubscriber($subscriberId);
            }

            $after = $this->snapshot();
            $fingerprintAfter = $this->fingerprint();

            $changes = $this->changes($before, $after);
            $this->renderChanges($changes);
            $this->renderRoots($after, $subscriberIds);

            $problems = $this->verify($before, $after, $fingerprintBefore, $fingerprintAfter);

            if ($problems !== []) {
                DB::rollBack();
                $this->error('فحوصات السلامة سقطت — تراجع كامل، لم يتغيّر شيء:');
                foreach ($problems as $problem) {
                    $this->error(' - ' . $problem);
                }

                return self::FAILURE;
            }

            $this->newLine();
            $this->table(['Check', 'Result'], [
                ['Accounts visited', $touched],
                ['Codes changed', count($changes)],
                ['Accounts total (before/after)', count($before) . ' / ' . count($after)],
                ['Debit/Credit fingerprint', $fingerprintBefore === $fingerprintAfter ? 'unchanged ✔' : 'CHANGED ✘'],
                ['Parents untouched', 'yes ✔'],
                ['Duplicate codes per subscriber', '0 ✔'],
            ]);

            if ($apply) {
                DB::commit();
                Log::info('[accounts:renumber] applied', [
                    'subscribers' => $subscriberIds,
                    'codes_changed' => count($changes),
                    'accounts_total' => count($after),
                ]);
                $this->info('تم الاعتماد.');
            } else {
                DB::rollBack();
                $this->warn('تشغيل جاف — لم يتغيّر شيء. راجع الفروق أعلاه ثم أعد التشغيل بـ --apply.');
            }

            return self::SUCCESS;
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    /** @return array<int, int|null> */
    private function targetSubscribers(): array
    {
        $requested = $this->option('subscriber');

        if ($requested) {
            return collect($requested)
                ->map(fn ($value) => strtolower((string) $value) === 'null' ? null : (int) $value)
                ->unique(fn ($value) => $value === null ? 'null' : $value)
                ->values()
                ->all();
        }

        return DB::table('accounts')
            ->select('subscriber_id')
            ->distinct()
            ->pluck('subscriber_id')
            ->map(fn ($value) => is_null($value) ? null : (int) $value)
            ->sortBy(fn ($value) => $value ?? -1)
            ->values()
            ->all();
    }

    /** @return array<int, object> id => {code, level, parent_account_id, subscriber_id, name} */
    private function snapshot(): array
    {
        return DB::table('accounts')
            ->get(['id', 'code', 'level', 'parent_account_id', 'subscriber_id', 'name'])
            ->keyBy('id')
            ->all();
    }

    /** بصمة الأرصدة — يجب ألّا تتغيّر إطلاقًا. */
    private function fingerprint(): string
    {
        $documents = DB::table('journal_entry_documents')
            ->selectRaw('COUNT(*) c, COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) k')
            ->first();
        $opening = DB::table('opening_balances')
            ->selectRaw('COUNT(*) c, COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) k')
            ->first();

        return sprintf(
            'jed:%d/%s/%s|ob:%d/%s/%s',
            $documents->c, $documents->d, $documents->k,
            $opening->c, $opening->d, $opening->k
        );
    }

    /** @return array<int, array{0:int,1:string,2:string,3:string}> */
    private function changes(array $before, array $after): array
    {
        $rows = [];

        foreach ($after as $id => $account) {
            $old = $before[$id] ?? null;

            if ($old && (string) $old->code === (string) $account->code && (string) $old->level === (string) $account->level) {
                continue;
            }

            $rows[] = [
                (int) $id,
                $this->arabicName($account->name),
                $old ? $old->code . ' (L' . $old->level . ')' : '—',
                $account->code . ' (L' . $account->level . ')',
            ];
        }

        return $rows;
    }

    private function renderChanges(array $changes): void
    {
        $limit = (int) $this->option('limit');

        $this->newLine();
        $this->line('الأكواد المتغيّرة: ' . count($changes));

        if ($changes === []) {
            return;
        }

        $this->table(['ID', 'الاسم', 'قبل', 'بعد'], array_slice($changes, 0, $limit));

        if (count($changes) > $limit) {
            $this->line('... و' . (count($changes) - $limit) . ' صفًا آخر (زد --limit لعرضها).');
        }
    }

    private function renderRoots(array $after, array $subscriberIds): void
    {
        $rows = [];

        foreach ($after as $account) {
            if (! is_null($account->parent_account_id)) {
                continue;
            }

            $subscriberId = is_null($account->subscriber_id) ? null : (int) $account->subscriber_id;
            if (! in_array($subscriberId, $subscriberIds, true)) {
                continue;
            }

            $rows[] = [$subscriberId ?? 'NULL', $account->code, (int) $account->id, $this->arabicName($account->name)];
        }

        usort($rows, fn ($a, $b) => [(string) $a[0], $a[1]] <=> [(string) $b[0], $b[1]]);

        $this->newLine();
        $this->line('الجذور بعد الترقيم:');
        $this->table(['المشترك', 'الكود', 'ID', 'الاسم'], $rows);
    }

    /** @return array<int, string> */
    private function verify(array $before, array $after, string $fingerprintBefore, string $fingerprintAfter): array
    {
        $problems = [];

        if ($fingerprintBefore !== $fingerprintAfter) {
            $problems[] = 'بصمة المدين/الدائن تغيّرت: ' . $fingerprintBefore . ' ← ' . $fingerprintAfter;
        }

        if (count($before) !== count($after)) {
            $problems[] = 'عدد الحسابات تغيّر: ' . count($before) . ' ← ' . count($after);
        }

        foreach ($after as $id => $account) {
            $old = $before[$id] ?? null;

            if (! $old) {
                $problems[] = 'حساب جديد ظهر أثناء العملية: #' . $id;
                continue;
            }

            if ((string) $old->parent_account_id !== (string) $account->parent_account_id) {
                $problems[] = 'تغيّر الأب للحساب #' . $id . ' — العملية لا يجوز أن تمسّ الشجرة.';
            }

            if ((string) $old->subscriber_id !== (string) $account->subscriber_id) {
                $problems[] = 'تغيّر المشترك للحساب #' . $id . '.';
            }

            if (blank($account->code)) {
                $problems[] = 'الحساب #' . $id . ' بلا كود بعد العملية.';
            }
        }

        $duplicates = DB::table('accounts')
            ->selectRaw('subscriber_id, code, COUNT(*) c')
            ->groupBy('subscriber_id', 'code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $problems[] = 'كود مكرّر داخل المشترك ' . ($duplicate->subscriber_id ?? 'NULL') . ': ' . $duplicate->code
                . ' (' . $duplicate->c . ' حسابات)';
        }

        return $problems;
    }

    private function arabicName($name): string
    {
        if (is_string($name) && str_starts_with($name, '{')) {
            $decoded = json_decode($name, true);

            if (is_array($decoded)) {
                return (string) ($decoded['ar'] ?? $decoded['en'] ?? $name);
            }
        }

        return (string) $name;
    }
}
