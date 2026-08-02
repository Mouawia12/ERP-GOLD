<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Re-stamp MANUAL journal entries that were saved with the wrong branch.
 *
 * WHY: JournalEntryController@store used to set
 *      'branch_id' => Branch::first()->id
 * which returns the globally-first branch (lowest id) system-wide, ignoring
 * the current subscriber. In a multi-tenant setup every manual journal entry
 * therefore got stamped with a FOREIGN subscriber's branch, and the
 * branch-scoped account statement (WHERE branch_id IN (subscriber branches))
 * silently dropped them — "the posted entry exists but never shows in the
 * statement". The controller is fixed to use the current branch going forward;
 * this migration corrects the historical rows.
 *
 * The correct subscriber for a manual entry is derived from the subscriber of
 * the accounts on its own lines (accounts.subscriber_id) — safe and
 * unambiguous because a balanced manual entry lives entirely inside one tenant.
 * The entry is re-pointed to that subscriber's lowest-id branch (any of the
 * subscriber's branches makes it visible under "all branches").
 *
 * Idempotent: only rows whose current branch belongs to a DIFFERENT subscriber
 * than their accounts are touched. Re-running is a no-op. Invoice/voucher
 * generated entries (journalable_type NOT NULL) are never touched — their
 * branch already comes from the source document.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('journal_entries')
            || ! DB::getSchemaBuilder()->hasColumn('accounts', 'subscriber_id')
            || ! DB::getSchemaBuilder()->hasColumn('branches', 'subscriber_id')) {
            return;
        }

        // branch_id => subscriber_id
        $branchSubscriber = DB::table('branches')
            ->pluck('subscriber_id', 'id');

        // subscriber_id => lowest branch_id owned by that subscriber
        $subscriberTargetBranch = DB::table('branches')
            ->whereNotNull('subscriber_id')
            ->orderBy('id')
            ->get(['id', 'subscriber_id'])
            ->groupBy('subscriber_id')
            ->map(fn ($rows) => (int) $rows->first()->id);

        // For each manual, non-deleted journal entry: the single distinct
        // subscriber of the accounts referenced on its (non-deleted) lines.
        $entries = DB::table('journal_entries as j')
            ->join('journal_entry_documents as d', 'd.journal_id', '=', 'j.id')
            ->join('accounts as a', 'a.id', '=', 'd.account_id')
            ->whereNull('j.journalable_type')
            ->whereNull('j.deleted_at')
            ->whereNull('d.deleted_at')
            ->whereNotNull('a.subscriber_id')
            ->groupBy('j.id', 'j.branch_id')
            ->select(
                'j.id as journal_id',
                'j.branch_id as branch_id',
                DB::raw('MIN(a.subscriber_id) as min_sub'),
                DB::raw('MAX(a.subscriber_id) as max_sub')
            )
            ->get();

        $fixed = 0;
        $skippedAmbiguous = 0;
        $skippedNoTarget = 0;

        foreach ($entries as $entry) {
            // Ambiguous: lines span more than one subscriber — leave untouched.
            if ((int) $entry->min_sub !== (int) $entry->max_sub) {
                $skippedAmbiguous++;
                continue;
            }

            $accountsSubscriber = (int) $entry->min_sub;
            $currentBranchSubscriber = $entry->branch_id !== null
                ? (int) ($branchSubscriber[$entry->branch_id] ?? 0)
                : 0;

            // Already stamped with a branch of the right subscriber — nothing to do.
            if ($currentBranchSubscriber === $accountsSubscriber) {
                continue;
            }

            $targetBranchId = $subscriberTargetBranch[$accountsSubscriber] ?? null;
            if (! $targetBranchId) {
                $skippedNoTarget++;
                continue;
            }

            DB::table('journal_entries')
                ->where('id', $entry->journal_id)
                ->update(['branch_id' => $targetBranchId]);
            $fixed++;
        }

        Log::info('[backfill_manual_journal_entries_branch_id] completed', [
            'manual_entries_scanned' => $entries->count(),
            'entries_rebranched' => $fixed,
            'skipped_multi_subscriber' => $skippedAmbiguous,
            'skipped_no_target_branch' => $skippedNoTarget,
        ]);
    }

    /**
     * No-op: the original branch stamps were a data defect, not a state worth
     * restoring, and we cannot distinguish rows this migration touched.
     */
    public function down(): void
    {
    }
};
