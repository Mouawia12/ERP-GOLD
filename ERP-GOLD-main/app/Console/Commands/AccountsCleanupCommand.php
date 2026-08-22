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
            $orphans = $this->reportOrphans();               // report only
            $sameName = $this->reportSameNameDifferentParent(); // report only
            $deleted = $this->deleteSameSpotEmptyDuplicates($apply); // the only auto-fix

            return [$orphans, $sameName, $deleted];
        };

        [$orphans, $sameName, $deleted] = $apply ? DB::transaction($run) : $run();

        $this->newLine();
        $this->table(['Result', 'Count'], [
            ['Orphans (report only — fix via UI)', count($orphans)],
            ['Same name / different parent (review — NOT touched)', count($sameName)],
            ['True same-spot empty duplicates ' . ($apply ? 'deleted' : 'to delete'), count($deleted)],
        ]);

        if (! $apply) {
            $this->warn('Dry run — nothing changed. Only same-spot EMPTY duplicates would be deleted on --apply.');
            $this->warn('Orphans and same-name/different-parent cases are reported for manual review, never auto-changed.');
        } else {
            Log::info('[accounts:cleanup] applied', [
                'orphans_reported' => count($orphans),
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
     * A root (parent NULL) whose CODE is a strict prefix of another account's code
     * is fine; a real orphan is a NULL-parent account whose OWN code has an
     * existing prefix account (its natural parent). Roots have no such prefix, so
     * they are never listed. Report only — never modifies.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reportOrphans(): array
    {
        $found = [];

        $codeMaps = $this->codeMapsBySubscriber();

        $roots = DB::table('accounts')->whereNull('parent_account_id')->get();

        foreach ($roots as $root) {
            $suggested = $this->suggestParentByCode($root, $codeMaps);
            if ($suggested === null) {
                continue; // genuine root (no prefix parent) — leave alone
            }

            $found[] = [
                'id' => $root->id,
                'name' => $this->arName($root->name),
                'code' => $root->code,
                'suggested_parent_id' => $suggested->id,
                'suggested_parent' => $this->arName($suggested->name) . ' (' . $suggested->code . ')',
            ];

            $this->line(sprintf(
                '  ORPHAN #%d "%s" (code %s) — suggested parent: #%d "%s" (code %s) [review via UI]',
                $root->id,
                $this->arName($root->name),
                $root->code,
                $suggested->id,
                $this->arName($suggested->name),
                $suggested->code
            ));
        }

        return $found;
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

    /**
     * @return array<string, array<string, object>>  subscriberKey => (code => account)
     */
    private function codeMapsBySubscriber(): array
    {
        $maps = [];
        foreach (DB::table('accounts')->whereNotNull('code')->get() as $account) {
            $key = $account->subscriber_id ?? 'null';
            $maps[$key][(string) $account->code] = $account;
        }

        return $maps;
    }

    private function suggestParentByCode(object $account, array $codeMaps): ?object
    {
        $code = (string) $account->code;
        if ($code === '') {
            return null;
        }

        $key = $account->subscriber_id ?? 'null';
        $map = $codeMaps[$key] ?? [];

        for ($len = strlen($code) - 1; $len >= 1; $len--) {
            $prefix = substr($code, 0, $len);
            if (isset($map[$prefix]) && (int) $map[$prefix]->id !== (int) $account->id) {
                return $map[$prefix];
            }
        }

        return null;
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
