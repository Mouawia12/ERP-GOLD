<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Renumber the whole chart of accounts to the standard progressive scheme so
 * every subscriber's codes are consistent and reflect the tree position:
 *   L1 = 1 digit (1)  |  L2 = 2 (11)  |  L3 = 4 (1101)  |  L4 = 7 (1101001)
 * Each level adds (level-1) digits. This matches Account::codePrefix() and the
 * SubscriberChartProvisioner template.
 *
 * SAFE: account codes are display/sort only — every ledger link (journal
 * documents, settings, opening balances) is by accounts.id, and nothing in the
 * app infers hierarchy from the code string (structure comes from
 * parent_account_id / level). So rewriting codes changes nothing functionally.
 *
 * Codes are rebuilt from each account's position in the tree, per subscriber,
 * ordering siblings by their current code (the order users already see) then id.
 * Deterministic and idempotent: re-running reproduces the same codes.
 */
return new class extends Migration {
    private function codePrefix(int $number, int $level): string
    {
        // Each level adds (level-1) digits — mirrors Account::codePrefix so a
        // renumbered tree matches freshly auto-generated / provisioned codes.
        return str_pad((string) $number, max($level - 1, 0), '0', STR_PAD_LEFT);
    }

    private function renumberChildren($parentId, string $parentCode, int $level): void
    {
        $children = DB::table('accounts')
            ->where('parent_account_id', $parentId)
            ->orderBy('code')
            ->orderBy('id')
            ->get(['id']);

        $seq = 1;
        foreach ($children as $child) {
            $newCode = $parentCode . $this->codePrefix($seq, $level);

            DB::table('accounts')
                ->where('id', $child->id)
                ->update(['code' => $newCode, 'level' => (string) $level]);

            $this->renumberChildren($child->id, $newCode, $level + 1);
            $seq++;
        }
    }

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('accounts')) {
            return;
        }

        $hasSubscriber = DB::getSchemaBuilder()->hasColumn('accounts', 'subscriber_id');

        // Each subscriber (and the NULL-subscriber bucket) gets its own 1..N roots.
        $subscriberIds = $hasSubscriber
            ? DB::table('accounts')->whereNull('parent_account_id')
                ->select('subscriber_id')->distinct()->pluck('subscriber_id')
            : collect([null]);

        DB::transaction(function () use ($subscriberIds, $hasSubscriber) {
            $totalRoots = 0;

            foreach ($subscriberIds as $subscriberId) {
                $roots = DB::table('accounts')
                    ->whereNull('parent_account_id')
                    ->when($hasSubscriber, function ($query) use ($subscriberId) {
                        return is_null($subscriberId)
                            ? $query->whereNull('subscriber_id')
                            : $query->where('subscriber_id', $subscriberId);
                    })
                    ->orderBy('code')
                    ->orderBy('id')
                    ->get(['id']);

                $seq = 1;
                foreach ($roots as $root) {
                    $newCode = $this->codePrefix($seq, 1);

                    DB::table('accounts')
                        ->where('id', $root->id)
                        ->update(['code' => $newCode, 'level' => '1']);

                    $this->renumberChildren($root->id, $newCode, 2);
                    $seq++;
                    $totalRoots++;
                }
            }

            Log::info('[renumber_accounts_to_standard_scheme] completed', [
                'subscriber_buckets' => $subscriberIds->count(),
                'roots_renumbered' => $totalRoots,
                'accounts_total' => DB::table('accounts')->count(),
            ]);
        });
    }

    /**
     * No-op: the previous codes were inconsistent legacy values, not a state
     * worth restoring, and links are by id so nothing depends on the old codes.
     */
    public function down(): void
    {
    }
};
