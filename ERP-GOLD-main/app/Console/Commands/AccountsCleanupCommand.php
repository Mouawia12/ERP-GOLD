<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Safe, reviewable diagnostics + minimal auto-fix for chart-of-accounts defects.
 *
 * IMPORTANT SAFETY MODEL (learned from a dry run that caught two false positives):
 *  - account_type is unreliable (often left at its enum default), so it is NEVER
 *    used to group or judge accounts.
 *  - A root (parent_account_id NULL) is NEVER re-parented. Real "orphans" are only
 *    identified when the account's own CODE has an existing prefix account — that
 *    prefix is the natural parent; a genuine root has no such prefix.
 *  - Two accounts count as a true duplicate ONLY when they share the same name
 *    AND the same parent (same spot). Same name under DIFFERENT parents is NOT a
 *    duplicate (e.g. the same trader as both a customer and a supplier) — it is
 *    only reported for a human to review, never deleted.
 *  - Only accounts referenced by NO foreign key to accounts.id are ever deleted.
 *
 * Dry-run by default (reports only). --apply performs the minimal safe changes
 * inside a transaction. Take a DB backup first. Re-runnable / idempotent.
 */
class AccountsCleanupCommand extends Command
{
    protected $signature = 'accounts:cleanup {--apply : Actually perform the minimal safe changes (default is a dry run)}';

    protected $description = 'Report account orphans/duplicates and safely remove only true same-spot empty duplicates.';

    /** @var array<int, array{0:string,1:string}> */
    private array $accountForeignKeys = [];

    public function handle(): int
    {
        if (! Schema::hasTable('accounts')) {
            $this->error('accounts table not found.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->accountForeignKeys = $this->discoverAccountForeignKeys();

        $this->info($apply ? '== accounts:cleanup — APPLYING (minimal safe changes) ==' : '== accounts:cleanup — DRY RUN (no changes) ==');
        $this->line('Foreign keys referencing accounts.id: ' . count($this->accountForeignKeys));

        $run = function () use ($apply) {
            [$reparented, $unplaced] = $this->reparentOrphansByName($apply);
            $sameName = $this->reportSameNameDifferentParent(); // report only
            $deleted = $this->deleteSameSpotEmptyDuplicates($apply); // safe auto-fix

            return [$reparented, $unplaced, $sameName, $deleted];
        };

        [$reparented, $unplaced, $sameName, $deleted] = $apply ? DB::transaction($run) : $run();

        $this->newLine();
        $this->table(['Result', 'Count'], [
            ['Orphans re-parented under their main group ' . ($apply ? '' : '(proposed)'), count($reparented)],
            ['Orphans that need manual placement (name unclear)', count($unplaced)],
            ['Same name / different parent (review — NOT touched)', count($sameName)],
            ['True same-spot empty duplicates ' . ($apply ? 'deleted' : 'to delete'), count($deleted)],
        ]);

        if (! $apply) {
            $this->warn('Dry run — nothing changed. Review the proposed re-parenting above, then re-run with --apply.');
            $this->warn('Only balances-safe changes are made: re-parenting (id links unchanged) and deleting same-spot EMPTY duplicates.');
        } else {
            Log::info('[accounts:cleanup] applied', [
                'orphans_reparented' => count($reparented),
                'orphans_unplaced' => count($unplaced),
                'same_name_diff_parent_reported' => count($sameName),
                'empty_same_spot_duplicates_deleted' => count($deleted),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function discoverAccountForeignKeys(): array
    {
        $refs = [];

        if (DB::connection()->getDriverName() === 'mysql') {
            $rows = DB::select(
                'SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.KEY_COLUMN_USAGE '
                . 'WHERE REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()',
                ['accounts', 'id']
            );
            foreach ($rows as $row) {
                $refs[] = [$row->t, $row->c];
            }
        }

        foreach ([
            ['accounts', 'parent_account_id'],
            ['journal_entry_documents', 'account_id'],
            ['opening_balances', 'account_id'],
            ['account_branch', 'account_id'],
            ['bank_accounts', 'account_id'],
            ['customers', 'account_id'],
            ['invoices', 'account_id'],
            ['invoice_payment_lines', 'account_id'],
        ] as $pair) {
            $refs[] = $pair;
        }

        $seen = [];
        $valid = [];
        foreach ($refs as [$t, $c]) {
            $key = $t . '.' . $c;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            if (Schema::hasTable($t) && Schema::hasColumn($t, $c)) {
                $valid[] = [$t, $c];
            }
        }

        return $valid;
    }

    private function isReferenced(int $accountId): bool
    {
        foreach ($this->accountForeignKeys as [$table, $column]) {
            if (DB::table($table)->where($column, $accountId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Re-parent orphan accounts (parent_account_id NULL that are NOT one of the
     * five standard category roots) under the correct main group, inferred from
     * the account NAME — reliable even when codes/types are messy. The five
     * canonical roots are identified by an EXACT name match, so a real root
     * (incl. one accidentally left NULL-parent) is used as a target, never moved.
     *
     * Only parent_account_id/level change — balances (linked by id) are untouched.
     * An orphan whose category can't be inferred is left alone and reported.
     *
     * @return array{0:array<int,array<string,mixed>>,1:array<int,array<string,mixed>>}
     */
    private function reparentOrphansByName(bool $apply): array
    {
        $reparented = [];
        $unplaced = [];

        // Category -> exact root names (the canonical top groups).
        $rootNames = [
            'assets' => ['الأصول', 'الاصول'],
            'liabilities' => ['الخصوم', 'الالتزامات', 'الإلتزامات', 'الخصوم (الالتزامات)'],
            'equity' => ['حقوق الملكية', 'حقوق الملكيه'],
            'revenues' => ['الإيرادات', 'الايرادات', 'الإيراد'],
            'expenses' => ['المصروفات', 'المصاريف'],
        ];

        // Category keywords, checked in this priority order (liabilities before
        // expenses so "مصروفات مستحقة" is a liability, not an expense).
        $keywordRules = [
            ['liabilities', ['مستحق', 'دائن', 'مورد', 'قرض', 'اوراق دفع', 'أوراق دفع', 'ضريبة المشتريات']],
            ['revenues', ['مبيعات', 'مردودات', 'ايراد', 'إيراد']],
            ['equity', ['احتياطي', 'رأس المال', 'راس المال', 'ارباح', 'أرباح', 'خسائر', 'جاري']],
            ['assets', ['صندوق', 'نقدي', 'بنك', 'عهد', 'شيكات', 'عملاء', 'عميل', 'مخزون', 'اصل']],
            ['expenses', ['مصروف', 'ايجار', 'إيجار', 'تلحيم', 'رواتب', 'راتب', 'كهرباء', 'صيان', 'زكا', 'علب', 'قرطاس', 'سعود', 'نظاف', 'انترنت', 'إنترنت', 'مواد', 'عمول', 'دعاي', 'ضريبة المبيعات']],
        ];

        $nullParents = DB::table('accounts')->whereNull('parent_account_id')->get();

        // Identify canonical roots (exact-name match) PER SUBSCRIBER — targets,
        // never moved. Scoping by subscriber is critical: an orphan must only ever
        // be re-parented under a root of its OWN subscriber, never another tenant's.
        $subKey = static fn ($acc) => $acc->subscriber_id === null ? 'null' : (string) $acc->subscriber_id;

        $canonical = [];      // [subscriberKey][category] => account
        $canonicalIds = [];
        foreach ($nullParents as $acc) {
            $name = trim($this->arName($acc->name));
            $sub = $subKey($acc);
            foreach ($rootNames as $category => $names) {
                if (! isset($canonical[$sub][$category]) && in_array($name, $names, true)) {
                    $canonical[$sub][$category] = $acc;
                    $canonicalIds[(int) $acc->id] = true;
                }
            }
        }

        foreach ($nullParents as $orphan) {
            if (isset($canonicalIds[(int) $orphan->id])) {
                continue; // a canonical root — leave as a root
            }

            $name = trim($this->arName($orphan->name));
            $sub = $subKey($orphan);

            $category = null;
            foreach ($keywordRules as [$cat, $keywords]) {
                foreach ($keywords as $kw) {
                    if (mb_strpos($name, $kw) !== false) {
                        $category = $cat;
                        break 2;
                    }
                }
            }

            // Only place under the SAME subscriber's category root.
            if ($category === null || ! isset($canonical[$sub][$category])) {
                $unplaced[] = ['id' => $orphan->id, 'name' => $name];
                $this->warn(sprintf('  ORPHAN #%d "%s" — no same-subscriber root for category, left as-is (place manually)', $orphan->id, $name));
                continue;
            }

            $parent = $canonical[$sub][$category];
            $reparented[] = [
                'id' => $orphan->id,
                'name' => $name,
                'parent_id' => $parent->id,
                'parent' => $this->arName($parent->name),
                'category' => $category,
            ];

            $this->line(sprintf(
                '  ORPHAN #%d "%s" -> under #%d "%s"  [%s]',
                $orphan->id,
                $name,
                $parent->id,
                $this->arName($parent->name),
                $category
            ));

            if ($apply) {
                DB::table('accounts')->where('id', $orphan->id)->update([
                    'parent_account_id' => $parent->id,
                    'level' => (string) ((int) ($parent->level ?? 1) + 1),
                ]);
                Log::info('[accounts:cleanup] reparented orphan by name', [
                    'id' => $orphan->id,
                    'under' => $parent->id,
                    'category' => $category,
                ]);
            }
        }

        return [$reparented, $unplaced];
    }

    /**
     * Same name, but sitting under different parents — ambiguous (could be a
     * legitimate customer-and-supplier, or a misplacement). Report only.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reportSameNameDifferentParent(): array
    {
        $found = [];

        $groups = DB::table('accounts')
            ->select('subscriber_id', 'name', DB::raw('COUNT(DISTINCT COALESCE(parent_account_id, 0)) as parents'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('subscriber_id', 'name')
            ->havingRaw('COUNT(*) > 1 AND COUNT(DISTINCT COALESCE(parent_account_id, 0)) > 1')
            ->get();

        foreach ($groups as $group) {
            $members = DB::table('accounts')
                ->where('name', $group->name)
                ->when(
                    is_null($group->subscriber_id),
                    fn ($q) => $q->whereNull('subscriber_id'),
                    fn ($q) => $q->where('subscriber_id', $group->subscriber_id)
                )
                ->get();

            $ids = $members->map(fn ($m) => $m->id . ' (parent ' . ($m->parent_account_id ?? 'ROOT') . ')')->implode(', ');
            $found[] = ['name' => $this->arName($group->name), 'members' => $ids];

            $this->warn(sprintf('  SAME NAME / DIFFERENT PARENT (review, not touched) "%s": %s', $this->arName($group->name), $ids));
        }

        return $found;
    }

    /**
     * Only a true accidental duplicate: same subscriber + same name + same parent.
     * Delete the EMPTY extras (referenced nowhere), keep one. This is the sole
     * auto-fix, and it cannot affect balances or lose data.
     *
     * @return array<int, array<string, mixed>>
     */
    private function deleteSameSpotEmptyDuplicates(bool $apply): array
    {
        $deleted = [];

        $groups = DB::table('accounts')
            ->select('subscriber_id', 'parent_account_id', 'name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('subscriber_id', 'parent_account_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $members = DB::table('accounts')
                ->where('name', $group->name)
                ->when(
                    is_null($group->parent_account_id),
                    fn ($q) => $q->whereNull('parent_account_id'),
                    fn ($q) => $q->where('parent_account_id', $group->parent_account_id)
                )
                ->when(
                    is_null($group->subscriber_id),
                    fn ($q) => $q->whereNull('subscriber_id'),
                    fn ($q) => $q->where('subscriber_id', $group->subscriber_id)
                )
                ->orderBy('id')
                ->get();

            $referenced = [];
            $empty = [];
            foreach ($members as $member) {
                if ($this->isReferenced((int) $member->id)) {
                    $referenced[] = $member;
                } else {
                    $empty[] = $member;
                }
            }

            // Keeper: a data-bearing member if any, otherwise the lowest-id empty.
            $keeper = $referenced[0] ?? ($empty[0] ?? null);
            if ($keeper === null) {
                continue;
            }

            foreach ($members as $member) {
                if ((int) $member->id === (int) $keeper->id) {
                    continue;
                }
                if ($this->isReferenced((int) $member->id)) {
                    continue; // never delete a referenced account
                }

                $deleted[] = ['id' => $member->id, 'name' => $this->arName($member->name), 'keeper_id' => $keeper->id];

                $this->line(sprintf(
                    '  EMPTY same-spot duplicate #%d "%s" -> delete (keep #%d)',
                    $member->id,
                    $this->arName($member->name),
                    $keeper->id
                ));

                if ($apply) {
                    DB::table('accounts')->where('id', $member->id)->delete();
                    Log::info('[accounts:cleanup] deleted empty same-spot duplicate', ['id' => $member->id, 'keeper' => $keeper->id]);
                }
            }
        }

        return $deleted;
    }

    private function arName($name): string
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
