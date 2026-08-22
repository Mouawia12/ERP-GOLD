<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Safe, reviewable cleanup for chart-of-accounts data defects:
 *
 *  - ORPHANS: a non-root account left with parent_account_id = NULL is
 *    re-parented under the canonical root of its own account_type. Nothing is
 *    deleted; only parent_account_id/level change, so balances are untouched.
 *
 *  - DUPLICATES: accounts sharing the EXACT same name (per subscriber). A
 *    duplicate member is deleted ONLY when it is referenced NOWHERE — checked
 *    against every foreign key that points at accounts.id (journal documents,
 *    opening balances, children, account_settings, invoices, vouchers, …). If
 *    two or more members carry data, the group is left untouched and flagged
 *    for a human to merge (moving posted entries is an accounting decision).
 *
 * Dry-run by default — prints exactly what WOULD happen and changes nothing.
 * Pass --apply to execute (inside a transaction). Always take a DB backup first.
 * Re-runnable and idempotent.
 */
class AccountsCleanupCommand extends Command
{
    protected $signature = 'accounts:cleanup {--apply : Actually perform the changes (default is a dry run)}';

    protected $description = 'Reparent orphan accounts and remove empty duplicate accounts (safe, dry-run by default).';

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

        $this->info($apply ? '== accounts:cleanup — APPLYING ==' : '== accounts:cleanup — DRY RUN (no changes) ==');
        $this->line('Foreign keys referencing accounts.id: ' . count($this->accountForeignKeys));

        $run = function () use ($apply) {
            $reparented = $this->handleOrphans($apply);
            [$deleted, $flagged] = $this->handleDuplicates($apply);

            return [$reparented, $deleted, $flagged];
        };

        [$reparented, $deleted, $flagged] = $apply
            ? DB::transaction($run)
            : $run();

        $this->newLine();
        $this->table(['Action', 'Count'], [
            ['Orphans re-parented', count($reparented)],
            ['Empty duplicates ' . ($apply ? 'deleted' : 'to delete'), count($deleted)],
            ['Duplicate groups flagged for manual review', $flagged],
        ]);

        if (! $apply) {
            $this->warn('Dry run only — nothing was changed. Re-run with --apply to execute (after a DB backup).');
        } else {
            Log::info('[accounts:cleanup] applied', [
                'orphans_reparented' => $reparented,
                'empty_duplicates_deleted' => $deleted,
                'duplicate_groups_flagged' => $flagged,
            ]);
            $this->info('Applied. See storage/logs for the detailed record.');
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

        // Fallback / belt-and-suspenders: known references from the schema.
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

        // Keep only (table, column) pairs that actually exist, de-duplicated.
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
     * @return array<int, array<string, mixed>>
     */
    private function handleOrphans(bool $apply): array
    {
        $done = [];

        $roots = DB::table('accounts')->whereNull('parent_account_id')->get();

        $groups = [];
        foreach ($roots as $root) {
            $key = ($root->subscriber_id ?? 'null') . '|' . $root->account_type;
            $groups[$key][] = $root;
        }

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue; // single root of this type = canonical, no orphan
            }

            // Canonical = the root with the most children, tie-break lowest id.
            usort($group, function ($a, $b) {
                $ca = DB::table('accounts')->where('parent_account_id', $a->id)->count();
                $cb = DB::table('accounts')->where('parent_account_id', $b->id)->count();

                return $cb <=> $ca ?: $a->id <=> $b->id;
            });

            $canonical = array_shift($group);

            foreach ($group as $orphan) {
                $done[] = [
                    'id' => $orphan->id,
                    'name' => $this->arName($orphan->name),
                    'account_type' => $orphan->account_type,
                    'new_parent_id' => $canonical->id,
                ];

                $this->line(sprintf(
                    '  orphan #%d "%s" -> under root #%d "%s"',
                    $orphan->id,
                    $this->arName($orphan->name),
                    $canonical->id,
                    $this->arName($canonical->name)
                ));

                if ($apply) {
                    DB::table('accounts')->where('id', $orphan->id)->update([
                        'parent_account_id' => $canonical->id,
                        'level' => (string) ((int) ($canonical->level ?? 1) + 1),
                    ]);
                    Log::info('[accounts:cleanup] reparented orphan', [
                        'id' => $orphan->id,
                        'under' => $canonical->id,
                    ]);
                }
            }
        }

        return $done;
    }

    /**
     * @return array{0:array<int,array<string,mixed>>,1:int}
     */
    private function handleDuplicates(bool $apply): array
    {
        $deleted = [];
        $flagged = 0;

        $dupGroups = DB::table('accounts')
            ->select('subscriber_id', 'name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('subscriber_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupGroups as $group) {
            $members = DB::table('accounts')
                ->where('name', $group->name)
                ->when(
                    is_null($group->subscriber_id),
                    fn ($q) => $q->whereNull('subscriber_id'),
                    fn ($q) => $q->where('subscriber_id', $group->subscriber_id)
                )
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

            // Two or more members carry data — never auto-merge; leave for a human.
            if (count($referenced) >= 2) {
                $flagged++;
                $ids = array_map(fn ($m) => $m->id, $members->all());
                $this->warn(sprintf(
                    '  DUPLICATE (needs manual merge) "%s" — members with data: [%s]',
                    $this->arName($group->name),
                    implode(', ', array_map(fn ($m) => $m->id, $referenced))
                ));
                Log::warning('[accounts:cleanup] duplicate group needs manual merge', [
                    'name' => $this->arName($group->name),
                    'ids' => $ids,
                    'referenced_ids' => array_map(fn ($m) => $m->id, $referenced),
                ]);
                continue;
            }

            // Keeper: the data-bearing member, else the deepest / lowest-id empty.
            $keeper = $referenced[0] ?? collect($empty)
                ->sortBy([['level', 'desc'], ['id', 'asc']])
                ->first();

            foreach ($members as $member) {
                if ((int) $member->id === (int) $keeper->id) {
                    continue;
                }
                if ($this->isReferenced((int) $member->id)) {
                    continue; // safety net — never delete a referenced account
                }

                $deleted[] = [
                    'id' => $member->id,
                    'name' => $this->arName($member->name),
                    'keeper_id' => $keeper->id,
                ];

                $this->line(sprintf(
                    '  empty duplicate #%d "%s" -> delete (keep #%d)',
                    $member->id,
                    $this->arName($member->name),
                    $keeper->id
                ));

                if ($apply) {
                    DB::table('accounts')->where('id', $member->id)->delete();
                    Log::info('[accounts:cleanup] deleted empty duplicate', [
                        'id' => $member->id,
                        'keeper' => $keeper->id,
                    ]);
                }
            }
        }

        return [$deleted, $flagged];
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
