<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill NULL branch_id on historical rows so branch-scoped reports
 * (which use WHERE branch_id IN (...)) stop silently dropping them.
 *
 * WHY: invoices.branch_id and journal_entries.branch_id are both nullable
 * and were never backfilled. Any row created before branches were enforced
 * carries branch_id = NULL, and `IN (...)` never matches NULL — so users
 * scoped to a branch see completely empty reports.
 *
 * Sources (safe, same-subscriber by construction):
 *  - invoices        -> creator's home branch (users.branch_id via invoices.user_id)
 *  - journal_entries -> the linked invoice's branch_id (journalable = Invoice)
 *
 * Idempotent: only touches rows where branch_id IS NULL. Re-running is a no-op.
 */
return new class extends Migration {
    public function up(): void
    {
        $invoicesBefore = DB::table('invoices')->whereNull('branch_id')->count();

        // Invoices: adopt the creating user's home branch.
        $invoicesFixed = DB::table('invoices')
            ->whereNull('branch_id')
            ->whereNotNull('user_id')
            ->update([
                'branch_id' => DB::raw(
                    '(SELECT u.branch_id FROM users u WHERE u.id = invoices.user_id)'
                ),
            ]);

        $invoicesRemaining = DB::table('invoices')->whereNull('branch_id')->count();

        $journalFixed = 0;
        $journalRemaining = 0;

        if (DB::getSchemaBuilder()->hasTable('journal_entries')) {
            // Journal entries: adopt the branch of the invoice they belong to.
            $journalFixed = DB::table('journal_entries')
                ->whereNull('branch_id')
                ->where('journalable_type', \App\Models\Invoice::class)
                ->whereNotNull('journalable_id')
                ->update([
                    'branch_id' => DB::raw(
                        '(SELECT i.branch_id FROM invoices i WHERE i.id = journal_entries.journalable_id)'
                    ),
                ]);

            $journalRemaining = DB::table('journal_entries')->whereNull('branch_id')->count();
        }

        Log::info('[backfill_invoices_branch_id] completed', [
            'invoices_null_before' => $invoicesBefore,
            'invoices_fixed' => $invoicesFixed,
            'invoices_still_null' => $invoicesRemaining,
            'journal_entries_fixed' => $journalFixed,
            'journal_entries_still_null' => $journalRemaining,
        ]);
    }

    /**
     * No-op: we must NOT re-NULL rows on rollback — the original NULLs were a
     * data defect, not a state worth restoring, and we cannot distinguish
     * rows this migration touched from rows that were already correct.
     */
    public function down(): void
    {
    }
};
